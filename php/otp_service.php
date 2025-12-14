<?php
/**
 * OTP Verification Service for HospiLink
 * Handles OTP generation, verification, and email sending
 */

require_once 'db.php';
require_once 'email_config.php';
require_once 'email_service_smtp.php';

class OTPService {
    
    private $conn;
    private $otpLength = 6;
    private $otpExpiry = 5; // minutes
    private $maxAttempts = 5;
    private $maxResends = 3;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate and send OTP to user's email
     */
    public function generateAndSendOTP($userData) {
        try {
            $email = $userData['email'];
            
            // Check if email already exists in users table
            $checkEmail = $this->conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $checkEmail->bind_param("s", $email);
            $checkEmail->execute();
            $checkEmail->store_result();
            
            if ($checkEmail->num_rows > 0) {
                $checkEmail->close();
                return [
                    'success' => false,
                    'message' => 'Email already registered. Please login or use a different email.'
                ];
            }
            $checkEmail->close();
            
            // Check existing OTP for this email
            $stmt = $this->conn->prepare(
                "SELECT otp_id, resend_count, created_at 
                FROM otp_verification 
                WHERE email = ? AND verified = FALSE AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existingOTP = $result->fetch_assoc();
                
                // Check if resend limit reached
                if ($existingOTP['resend_count'] >= $this->maxResends) {
                    $stmt->close();
                    return [
                        'success' => false,
                        'message' => 'Maximum resend attempts reached. Please try again after 5 minutes.'
                    ];
                }
                
                // Check if minimum time passed (30 seconds)
                $timeSinceCreation = time() - strtotime($existingOTP['created_at']);
                if ($timeSinceCreation < 30) {
                    $stmt->close();
                    return [
                        'success' => false,
                        'message' => 'Please wait ' . (30 - $timeSinceCreation) . ' seconds before requesting a new OTP.'
                    ];
                }
            }
            $stmt->close();
            
            // Generate new OTP
            $otp = $this->generateOTP();
            $otpHash = password_hash($otp, PASSWORD_BCRYPT);
            
            // Debug log (remove in production)
            error_log("[OTP DEBUG] Generated OTP for {$email}: {$otp}");
            $userDataJson = json_encode($userData);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            // Invalidate any existing OTPs for this email
            $invalidate = $this->conn->prepare("UPDATE otp_verification SET verified = TRUE WHERE email = ? AND verified = FALSE");
            $invalidate->bind_param("s", $email);
            $invalidate->execute();
            $invalidate->close();
            
            // Insert new OTP using MySQL's DATE_ADD for timezone consistency
            $insert = $this->conn->prepare(
                "INSERT INTO otp_verification (email, otp_hash, user_data, expires_at, ip_address) 
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)"
            );
            $insert->bind_param("sssis", $email, $otpHash, $userDataJson, $this->otpExpiry, $ipAddress);
            
            if (!$insert->execute()) {
                $insert->close();
                return [
                    'success' => false,
                    'message' => 'Failed to generate OTP. Please try again.'
                ];
            }
            
            $otpId = $insert->insert_id;
            $insert->close();
            
            // Send OTP email
            $emailSent = $this->sendOTPEmail($email, $otp, $userData['firstName']);
            
            if (!$emailSent) {
                return [
                    'success' => false,
                    'message' => 'Failed to send OTP email. Please check your email address.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully to your email.',
                'otp_id' => $otpId,
                'expires_in' => $this->otpExpiry * 60 // seconds
            ];
            
        } catch (Exception $e) {
            error_log("OTP Generation Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Verify OTP entered by user
     */
    public function verifyOTP($email, $otp) {
        try {
            // Get OTP record (only non-expired ones)
            $stmt = $this->conn->prepare(
                "SELECT otp_id, otp_hash, user_data, attempts, expires_at 
                FROM otp_verification 
                WHERE email = ? AND verified = FALSE AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1"
            );
            
            if (!$stmt) {
                error_log("[OTP DEBUG] SQL Error: " . $this->conn->error);
                return [
                    'success' => false,
                    'message' => 'Database error. Please try again.'
                ];
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                error_log("[OTP DEBUG] No valid (non-expired) OTP found for {$email}");
                return [
                    'success' => false,
                    'message' => 'OTP expired or invalid. Please request a new one.'
                ];
            }
            
            $otpRecord = $result->fetch_assoc();
            $stmt->close();
            
            // Check max attempts
            if ($otpRecord['attempts'] >= $this->maxAttempts) {
                return [
                    'success' => false,
                    'message' => 'Maximum verification attempts exceeded. Please request a new OTP.'
                ];
            }
            
            // Increment attempts
            $updateAttempts = $this->conn->prepare(
                "UPDATE otp_verification SET attempts = attempts + 1 WHERE otp_id = ?"
            );
            $updateAttempts->bind_param("i", $otpRecord['otp_id']);
            $updateAttempts->execute();
            $updateAttempts->close();
            
            // Verify OTP
            // Debug log (remove in production)
            error_log("[OTP DEBUG] Verifying OTP for {$email}: Entered='{$otp}', Checking against hash");
            
            if (!password_verify($otp, $otpRecord['otp_hash'])) {
                error_log("[OTP DEBUG] Verification FAILED for {$email}");
                $remainingAttempts = $this->maxAttempts - ($otpRecord['attempts'] + 1);
                return [
                    'success' => false,
                    'message' => "Invalid OTP. You have $remainingAttempts attempts remaining."
                ];
            }
            
            // Mark as verified
            error_log("[OTP DEBUG] Verification SUCCESS for {$email}");
            $markVerified = $this->conn->prepare(
                "UPDATE otp_verification SET verified = TRUE, verified_at = NOW() WHERE otp_id = ?"
            );
            $markVerified->bind_param("i", $otpRecord['otp_id']);
            $markVerified->execute();
            $markVerified->close();
            
            // Return user data for registration
            $userData = json_decode($otpRecord['user_data'], true);
            
            return [
                'success' => true,
                'message' => 'OTP verified successfully.',
                'user_data' => $userData
            ];
            
        } catch (Exception $e) {
            error_log("OTP Verification Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Resend OTP
     */
    public function resendOTP($email) {
        try {
            // Get existing OTP record
            $stmt = $this->conn->prepare(
                "SELECT otp_id, user_data, resend_count, created_at 
                FROM otp_verification 
                WHERE email = ? AND verified = FALSE AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                return [
                    'success' => false,
                    'message' => 'No active OTP found. Please start registration again.'
                ];
            }
            
            $otpRecord = $result->fetch_assoc();
            $stmt->close();
            
            // Check resend limit
            if ($otpRecord['resend_count'] >= $this->maxResends) {
                return [
                    'success' => false,
                    'message' => 'Maximum resend attempts reached. Please try again later.'
                ];
            }
            
            // Check minimum time between resends (30 seconds)
            $timeSinceCreation = time() - strtotime($otpRecord['created_at']);
            if ($timeSinceCreation < 30) {
                return [
                    'success' => false,
                    'message' => 'Please wait ' . (30 - $timeSinceCreation) . ' seconds before resending.'
                ];
            }
            
            // Generate new OTP
            $otp = $this->generateOTP();
            $otpHash = password_hash($otp, PASSWORD_BCRYPT);
            
            // Debug log
            error_log("[OTP DEBUG] Resent OTP for {$email}: {$otp}");
            
            // Update OTP record using MySQL DATE_ADD
            $update = $this->conn->prepare(
                "UPDATE otp_verification 
                SET otp_hash = ?, expires_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), attempts = 0, resend_count = resend_count + 1, created_at = NOW() 
                WHERE otp_id = ?"
            );
            $update->bind_param("sii", $otpHash, $this->otpExpiry, $otpRecord['otp_id']);
            $update->execute();
            $update->close();
            
            // Send OTP email
            $userData = json_decode($otpRecord['user_data'], true);
            $emailSent = $this->sendOTPEmail($email, $otp, $userData['firstName']);
            
            if (!$emailSent) {
                return [
                    'success' => false,
                    'message' => 'Failed to send OTP email. Please try again.'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'New OTP sent successfully.',
                'expires_in' => $this->otpExpiry * 60,
                'resends_remaining' => $this->maxResends - ($otpRecord['resend_count'] + 1)
            ];
            
        } catch (Exception $e) {
            error_log("OTP Resend Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ];
        }
    }
    
    /**
     * Generate random OTP
     */
    private function generateOTP() {
        return str_pad(random_int(0, 999999), $this->otpLength, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send OTP via email using SMTP
     */
    private function sendOTPEmail($to, $otp, $firstName) {
        if (!EMAIL_ENABLED) {
            return true; // Skip if email disabled
        }
        
        $subject = "Email Verification - HospiLink Registration";
        $message = $this->createOTPEmailTemplate($otp, $firstName);
        
        // Use SMTP email service instead of PHP mail()
        return EmailService::sendOTPEmail($to, $subject, $message);
    }
    
    /**
     * Create HTML email template for OTP
     */
    private function createOTPEmailTemplate($otp, $firstName) {
        $otpDigits = str_split($otp);
        $otpBoxes = '';
        foreach ($otpDigits as $digit) {
            $otpBoxes .= '<span style="display: inline-block; background: #f8f9fa; border: 2px solid #00adb5; border-radius: 8px; padding: 12px 16px; margin: 0 3px; font-size: 28px; font-weight: bold; color: #0e545f; min-width: 40px; text-align: center;">' . $digit . '</span>';
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 40px 0;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 28px;">🏥 HospiLink</h1>
                                    <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 16px;">Email Verification</p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #0e545f; margin: 0 0 20px 0;">Hello ' . htmlspecialchars($firstName) . '!</h2>
                                    <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                        Thank you for registering with HospiLink. To complete your registration, please verify your email address using the OTP below:
                                    </p>
                                    
                                    <div style="text-align: center; margin: 40px 0;">
                                        <div style="display: inline-block; white-space: nowrap;">
                                            ' . $otpBoxes . '
                                        </div>
                                    </div>
                                    
                                    <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 30px 0; border-radius: 5px;">
                                        <p style="margin: 0; color: #856404; font-size: 14px;">
                                            <strong>⏱️ This OTP is valid for ' . $this->otpExpiry . ' minutes.</strong>
                                        </p>
                                    </div>
                                    
                                    <p style="color: #666; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                        If you didn\'t request this verification, please ignore this email or contact our support team.
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Security Notice -->
                            <tr>
                                <td style="background-color: #f8f9fa; padding: 20px 30px; border-top: 1px solid #e9ecef;">
                                    <p style="margin: 0; color: #666; font-size: 13px; line-height: 1.5;">
                                        🔒 <strong>Security Tip:</strong> Never share your OTP with anyone. HospiLink staff will never ask for your OTP.
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #0e545f; padding: 20px; text-align: center; border-radius: 0 0 10px 10px;">
                                    <p style="color: #ffffff; margin: 0; font-size: 14px;">
                                        © ' . date('Y') . ' HospiLink. All rights reserved.
                                    </p>
                                    <p style="color: #00adb5; margin: 10px 0 0 0; font-size: 12px;">
                                        Your trusted healthcare partner
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
?>
