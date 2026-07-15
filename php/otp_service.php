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
            $otpBoxes .= '<span style="display: inline-block; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin: 0 4px; font-size: 28px; font-weight: bold; color: #0d9488; min-width: 40px; text-align: center;">' . $digit . '</span>';
        }
        
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background-color: #f8fafc;
                    color: #334155;
                    margin: 0;
                    padding: 0;
                }
                .wrapper {
                    max-width: 580px;
                    margin: 40px auto;
                    background-color: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .header {
                    padding: 32px 40px 20px 40px;
                    border-bottom: 1px solid #f1f5f9;
                }
                .header-logo {
                    font-size: 18px;
                    font-weight: 700;
                    color: #0d9488;
                    letter-spacing: -0.5px;
                }
                .header-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 12px;
                    margin-bottom: 0;
                }
                .body {
                    padding: 32px 40px;
                }
                .body h2 {
                    font-size: 18px;
                    font-weight: 600;
                    color: #0f172a;
                    margin-top: 0;
                    margin-bottom: 12px;
                }
                .body p {
                    font-size: 15px;
                    line-height: 1.6;
                    color: #475569;
                    margin-top: 0;
                    margin-bottom: 16px;
                }
                .expiry-notice {
                    background-color: #fffbef;
                    border-left: 4px solid #f59e0b;
                    padding: 12px;
                    margin: 24px 0;
                    border-radius: 4px;
                    font-size: 14px;
                    color: #b45309;
                }
                .security-notice {
                    background-color: #f8fafc;
                    border: 1px solid #e2e8f0;
                    padding: 16px;
                    border-radius: 8px;
                    margin-top: 24px;
                }
                .security-notice p {
                    margin: 0;
                    font-size: 13px;
                    color: #64748b;
                    line-height: 1.5;
                }
                .footer {
                    background-color: #f8fafc;
                    padding: 24px 40px;
                    border-top: 1px solid #e2e8f0;
                    text-align: center;
                }
                .footer p {
                    font-size: 12px;
                    color: #64748b;
                    line-height: 1.5;
                    margin: 0;
                }
            </style>
        </head>
        <body>
            <div class="wrapper">
                <div class="header">
                    <div class="header-logo">HospiLink</div>
                    <div class="header-title">Email Verification</div>
                </div>
                <div class="body">
                    <h2>Hello ' . htmlspecialchars($firstName) . ',</h2>
                    <p>Thank you for registering with HospiLink. To complete your registration, please verify your email address using the OTP below:</p>
                    
                    <div style="text-align: center; margin: 32px 0;">
                        <div style="display: inline-block; white-space: nowrap;">
                            ' . $otpBoxes . '
                        </div>
                    </div>
                    
                    <div class="expiry-notice">
                        <strong>This OTP is valid for ' . $this->otpExpiry . ' minutes.</strong>
                    </div>
                    
                    <p>If you did not request this verification, please ignore this email or contact our support team.</p>
                    
                    <div class="security-notice">
                        <p><strong>Security Tip:</strong> Never share your OTP with anyone. HospiLink staff will never ask for your verification code.</p>
                    </div>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' HospiLink. All rights reserved.<br>Support: ' . SMTP_FROM_EMAIL . '</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
?>
