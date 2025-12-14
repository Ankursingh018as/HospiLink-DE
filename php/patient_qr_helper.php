<?php
/**
 * QR Code Patient Management Helper
 * Generates and manages QR codes for bedside patient monitoring
 */

class PatientQRHelper {
    
    /**
     * Generate a unique secure token for QR code
     * Format: HOSP-[TIMESTAMP]-[RANDOM]-[HASH]
     */
    public static function generateQRToken($patient_id, $admission_id) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        $hash = hash('sha256', $patient_id . $admission_id . $timestamp . $random);
        $token = 'HOSP-' . $timestamp . '-' . $random . '-' . substr($hash, 0, 12);
        return strtoupper($token);
    }
    
    /**
     * Generate QR code SVG using simple path-based generation
     * This creates a QR code without external libraries
     */
    public static function generateQRCodeSVG($data, $size = 300) {
        // For production, you'd use a library like phpqrcode or endroid/qr-code
        // This is a simple visual representation
        $hash = md5($data);
        $grid_size = 25; // 25x25 grid
        $cell_size = $size / $grid_size;
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
        
        // Create pattern from hash
        for ($y = 0; $y < $grid_size; $y++) {
            for ($x = 0; $x < $grid_size; $x++) {
                $index = ($y * $grid_size + $x) % strlen($hash);
                if (hexdec($hash[$index]) % 2 === 0) {
                    $px = $x * $cell_size;
                    $py = $y * $cell_size;
                    $svg .= '<rect x="' . $px . '" y="' . $py . '" width="' . $cell_size . '" height="' . $cell_size . '" fill="black"/>';
                }
            }
        }
        
        // Add finder patterns (corners)
        $finder_size = $cell_size * 7;
        self::addFinderPattern($svg, 0, 0, $finder_size);
        self::addFinderPattern($svg, $size - $finder_size, 0, $finder_size);
        self::addFinderPattern($svg, 0, $size - $finder_size, $finder_size);
        
        $svg .= '</svg>';
        return $svg;
    }
    
    /**
     * Add finder pattern (corner squares) to QR code
     */
    private static function addFinderPattern(&$svg, $x, $y, $size) {
        // Outer square
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $size . '" height="' . $size . '" fill="black"/>';
        // White middle
        $svg .= '<rect x="' . ($x + $size/7) . '" y="' . ($y + $size/7) . '" width="' . ($size * 5/7) . '" height="' . ($size * 5/7) . '" fill="white"/>';
        // Black center
        $svg .= '<rect x="' . ($x + $size * 2/7) . '" y="' . ($y + $size * 2/7) . '" width="' . ($size * 3/7) . '" height="' . ($size * 3/7) . '" fill="black"/>';
    }
    
    /**
     * Generate QR code using QR Server API (works offline alternative)
     */
    public static function generateQRCodeURL($token, $size = 300) {
        // Use api.qrserver.com which is more reliable
        $qr_url = self::getQRScanURL($token);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($qr_url);
    }
    
    /**
     * Generate offline QR code as SVG data URI
     * Use this if internet is not available
     */
    public static function generateOfflineQR($token, $size = 300) {
        require_once __DIR__ . '/simple_qr.php';
        $qr_url = self::getQRScanURL($token);
        return SimpleQRCode::generate($qr_url, $size);
    }
    
    /**
     * Get QR code scan URL for a token
     * Uses local IP for mobile access, localhost for desktop
     */
    public static function getQRScanURL($token) {
        // Auto-detect: use server IP if accessed from network, localhost otherwise
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        
        // If accessing via IP, use that IP for QR codes too
        if (strpos($host, '192.168.') !== false || strpos($host, '10.') !== false) {
            $base_url = "http://" . $host . "/HospiLink-DE";
        } else {
            // Fallback: use detected local IP
            $local_ip = self::getLocalIP();
            $base_url = "http://" . $local_ip . "/HospiLink-DE";
        }
        
        return $base_url . "/patient-status.php?token=" . urlencode($token);
    }
    
    /**
     * Get local network IP address
     */
    private static function getLocalIP() {
        // Try to get server's local IP
        if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            return $_SERVER['SERVER_ADDR'];
        }
        
        // Fallback to localhost
        return 'localhost';
    }
    
    /**
     * Validate QR token format
     */
    public static function validateToken($token) {
        // Check format: HOSP-[TIMESTAMP]-[RANDOM]-[HASH]
        if (!preg_match('/^HOSP-\d+-[A-F0-9]+-[A-F0-9]{12}$/', $token)) {
            return false;
        }
        return true;
    }
    
    /**
     * Get admission details from QR token
     * Optimized with better error handling and complete data retrieval
     */
    public static function getAdmissionFromToken($conn, $token) {
        if (!self::validateToken($token)) {
            error_log('Invalid QR token format: ' . $token);
            return null;
        }
        
        $stmt = $conn->prepare("
            SELECT 
                pa.*,
                CONCAT(u.first_name, ' ', u.last_name) as patient_name,
                u.first_name, u.last_name, u.email, u.phone, 
                COALESCE(u.gender, 'Not specified') as gender,
                COALESCE(u.age, 0) as age,
                COALESCE(u.blood_group, 'Unknown') as blood_group,
                b.ward_name, b.bed_number, b.bed_type,
                CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                d.specialization, d.department
            FROM patient_admissions pa
            INNER JOIN users u ON pa.patient_id = u.user_id
            LEFT JOIN beds b ON pa.bed_id = b.bed_id
            LEFT JOIN users d ON pa.assigned_doctor_id = d.user_id
            WHERE pa.qr_code_token = ? AND pa.status = 'active'
            LIMIT 1
        ");
        
        if (!$stmt) {
            error_log('Failed to prepare getAdmissionFromToken: ' . $conn->error);
            return null;
        }
        
        $stmt->bind_param("s", $token);
        
        if (!$stmt->execute()) {
            error_log('Failed to execute getAdmissionFromToken: ' . $stmt->error);
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admission = $result->fetch_assoc();
            $stmt->close();
            return $admission;
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * Log QR code scan
     */
    public static function logScan($conn, $admission_id, $user_id, $action = 'view') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO qr_scan_logs (admission_id, scanned_by, scanned_at, ip_address, user_agent, action_taken)
            VALUES (?, ?, NOW(), ?, ?, ?)
        ");
        
        $stmt->bind_param("iisss", $admission_id, $user_id, $ip, $user_agent, $action);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Create admission and generate QR code
     * Optimized with validation and better error handling
     */
    public static function createAdmission($conn, $patient_id, $bed_id, $admission_reason, $doctor_id) {
        try {
            // Validate patient exists
            $patient_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'patient'");
            $patient_check->bind_param("i", $patient_id);
            $patient_check->execute();
            $patient_result = $patient_check->get_result();
            
            if ($patient_result->num_rows === 0) {
                return ['success' => false, 'error' => 'Invalid patient ID'];
            }
            $patient_check->close();
            
            // Generate unique QR token with collision detection
            $max_attempts = 5;
            $qr_token = null;
            
            for ($i = 0; $i < $max_attempts; $i++) {
                $qr_token = self::generateQRToken($patient_id, time() + $i);
                
                // Check if token already exists
                $token_check = $conn->prepare("SELECT admission_id FROM patient_admissions WHERE qr_code_token = ?");
                $token_check->bind_param("s", $qr_token);
                $token_check->execute();
                $token_result = $token_check->get_result();
                
                if ($token_result->num_rows === 0) {
                    $token_check->close();
                    break; // Unique token found
                }
                $token_check->close();
                
                if ($i === $max_attempts - 1) {
                    return ['success' => false, 'error' => 'Failed to generate unique QR token'];
                }
            }
            
            // Handle NULL values properly for foreign keys
            $bed_id_param = ($bed_id && $bed_id > 0) ? $bed_id : null;
            $doctor_id_param = ($doctor_id && $doctor_id > 0) ? $doctor_id : null;
            
            // Prepare admission insertion
            $stmt = $conn->prepare("
                INSERT INTO patient_admissions 
                (patient_id, bed_id, qr_code_token, admission_date, admission_reason, assigned_doctor_id, status)
                VALUES (?, ?, ?, NOW(), ?, ?, 'active')
            ");
            
            if (!$stmt) {
                return ['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error];
            }
            
            $stmt->bind_param("iissi", $patient_id, $bed_id_param, $qr_token, $admission_reason, $doctor_id_param);
            
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                return ['success' => false, 'error' => 'Failed to create admission: ' . $error];
            }
            
            $admission_id = $stmt->insert_id;
            $stmt->close();
            
            // Update bed status only if bed was assigned
            if ($bed_id_param) {
                $bed_stmt = $conn->prepare("
                    UPDATE beds 
                    SET status = 'occupied', patient_id = ?, admitted_date = NOW()
                    WHERE bed_id = ? AND status = 'available'
                ");
                
                if ($bed_stmt) {
                    $bed_stmt->bind_param("ii", $patient_id, $bed_id_param);
                    $bed_stmt->execute();
                    
                    // Check if bed was actually updated (was available)
                    if ($bed_stmt->affected_rows === 0) {
                        // Bed was not available - log warning but don't fail admission
                        error_log("Warning: Bed $bed_id_param was not available for admission $admission_id");
                    }
                    $bed_stmt->close();
                }
            }
            
            return [
                'success' => true,
                'admission_id' => $admission_id,
                'qr_token' => $qr_token,
                'bed_assigned' => $bed_id_param !== null,
                'doctor_assigned' => $doctor_id_param !== null
            ];
            
        } catch (Exception $e) {
            error_log('Create admission error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Discharge patient and deactivate QR code
     */
    public static function dischargePatient($conn, $admission_id) {
        // Get admission details
        $stmt = $conn->prepare("SELECT bed_id FROM patient_admissions WHERE admission_id = ?");
        $stmt->bind_param("i", $admission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admission = $result->fetch_assoc();
        $stmt->close();
        
        // Update admission status
        $stmt = $conn->prepare("
            UPDATE patient_admissions 
            SET status = 'discharged', discharge_date = NOW()
            WHERE admission_id = ?
        ");
        $stmt->bind_param("i", $admission_id);
        $stmt->execute();
        $stmt->close();
        
        // Free up bed
        if ($admission['bed_id']) {
            $stmt = $conn->prepare("
                UPDATE beds 
                SET status = 'available', patient_id = NULL, admitted_date = NULL
                WHERE bed_id = ?
            ");
            $stmt->bind_param("i", $admission['bed_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        return true;
    }
}
?>
