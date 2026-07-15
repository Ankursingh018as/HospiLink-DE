<?php
/**
 * HospiLink Notification Engine — PHP/MySQL Native
 * 
 * Sends real-time email notifications using the existing SMTP infrastructure.
 * This bridges the gap between QR-triggered MySQL events and email delivery.
 * 
 * Works by being called directly from patient-update.php after each INSERT.
 */

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/db.php';

class HospiNotify {

    // ═══════════════════════════════════════════════════════════════
    //  SMTP EMAIL SENDER — uses same config as existing email service
    // ═══════════════════════════════════════════════════════════════
    
    public static function sendEmail($to, $subject, $htmlBody) {
        // Create directory for queue files if it doesn't exist
        $queueDir = __DIR__ . '/mails';
        if (!is_dir($queueDir)) {
            @mkdir($queueDir, 0777, true);
        }

        // Save email details to JSON file
        $fileName = $queueDir . '/mail_' . uniqid() . '_' . time() . '.json';
        $data = [
            'to'      => $to,
            'subject' => $subject,
            'html'    => $htmlBody
        ];
        file_put_contents($fileName, json_encode($data));

        // Detect PHP binary location
        $phpBinary = 'php';
        if (file_exists('D:\xampp\php\php.exe')) {
            $phpBinary = 'D:\xampp\php\php.exe';
        } elseif (file_exists('C:\xampp\php\php.exe')) {
            $phpBinary = 'C:\xampp\php\php.exe';
        }

        // Run background command (Windows specific: start /B runs in background silently)
        $scriptPath = __DIR__ . '/background_mailer.php';
        $cmd = 'start /B "" ' . escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($fileName);
        
        // Execute background command without blocking
        $handle = popen($cmd, "r");
        if ($handle) {
            pclose($handle);
        }
        
        return true;
    }

    // ═══════════════════════════════════════════════════════════════
    //  BASE EMAIL TEMPLATE
    // ═══════════════════════════════════════════════════════════════

    public static function baseTemplate($headerGradient, $emoji, $title, $bodyHtml) {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} — HospiLink</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased;color:#334155;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;padding:32px 0;">
    <tr><td align="center">
      <table width="580" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        
        <!-- Header -->
        <tr><td style="padding:32px 40px 20px 40px;border-bottom:1px solid #f1f5f9;">
          <div style="font-size:18px;font-weight:700;color:#0d9488;letter-spacing:-0.5px;">HospiLink</div>
          <h1 style="color:#0f172a;margin:12px 0 0 0;font-size:20px;font-weight:600;letter-spacing:-0.5px;">{$title}</h1>
        </td></tr>
        
        <!-- Body -->
        <tr><td style="padding:32px 40px;">
          {$bodyHtml}
        </td></tr>
        
        <!-- Footer -->
        <tr><td style="background-color:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e2e8f0;">
          <p style="font-size:12px;color:#64748b;margin:0;line-height:1.5;">
            © 2026 HospiLink. Automated notification. Please do not reply.<br>
            Dahod, Gujarat, India | hospilink@gmail.com
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private static function infoRow($label, $value) {
        return "<tr><td style='padding:10px 0;border-bottom:1px solid #f1f5f9;'>
            <span style='color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;'>$label</span><br>
            <span style='color:#0f172a;font-weight:600;font-size:15px;'>$value</span>
        </td></tr>";
    }

    private static function infoCard($color, $rows) {
        $colors = [
            'urgent' => ['#fef2f2', '#ef4444'],
            'high'   => ['#fff7ed', '#f59e0b'],
            'medium' => ['#eff6ff', '#3b82f6'],
            'low'    => ['#f0fdf4', '#10b981'],
        ];
        [$bg, $border] = $colors[$color] ?? $colors['medium'];
        return "<table width='100%' cellpadding='10' cellspacing='0' style='background-color:$bg;border-left:4px solid $border;border-radius:8px;margin:24px 0;'>$rows</table>";
    }

    // ═══════════════════════════════════════════════════════════════
    //  1. MEDICINE ADDED — email the prescribing doctor + nurse
    // ═══════════════════════════════════════════════════════════════

    public static function onMedicineAdded($admission_id, $medicine_data, $prescribed_by_id, $conn) {
        // Get patient + ward + prescriber info
        $sql = "SELECT pa.admission_id,
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       b.bed_number,
                       COALESCE(b.ward_name, b.bed_type, 'Ward') AS ward_name,
                       p.email AS patient_email,
                       CONCAT(u.first_name,' ',u.last_name) AS prescriber_name,
                       u.email AS prescriber_email, u.role AS prescriber_role
                FROM patient_admissions pa
                JOIN users p ON pa.patient_id = p.user_id
                LEFT JOIN beds b ON pa.bed_id = b.bed_id
                JOIN users u ON u.user_id = ?
                WHERE pa.admission_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $prescribed_by_id, $admission_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return;

        $patientName    = $row['patient_name'];
        $ward           = $row['ward_name'] . ' — Bed ' . $row['bed_number'];
        $prescriberName = $row['prescriber_name'];
        $medicineName   = $medicine_data['medicine_name'];
        $dosage         = $medicine_data['dosage'];
        $frequency      = $medicine_data['frequency'];
        $route          = $medicine_data['route'];
        $instructions   = $medicine_data['instructions'] ?: 'None';
        $startDate      = date('d M Y, h:i A', strtotime($medicine_data['start_date']));
        $now            = date('d M Y, h:i A');

        $rows = self::infoRow('Patient', $patientName)
              . self::infoRow('Ward / Bed', $ward)
              . self::infoRow('Medicine', "<strong>$medicineName</strong>")
              . self::infoRow('Dosage', $dosage)
              . self::infoRow('Frequency', $frequency)
              . self::infoRow('Route', $route)
              . self::infoRow('Start Date', $startDate)
              . self::infoRow('Special Instructions', $instructions)
              . self::infoRow('Prescribed By', $prescriberName)
              . self::infoRow('Prescribed At', $now);

        $card = self::infoCard('medium', $rows);
        $body = "<h2 style='color:#0f172a;margin-top:0;font-size:18px;font-weight:600;'>New Medicine Prescribed</h2>
                 <p style='color:#475569;line-height:1.6;'>A new medicine has been added for patient <strong>$patientName</strong>. Please ensure it is administered on schedule.</p>
                 $card
                 <p style='color:#64748b;font-size:13px;margin-top:16px;'>Please ensure the patient receives this medication on schedule and document administration in the patient chart.</p>";

        $html    = self::baseTemplate('', '', 'Medicine Prescribed', $body);
        $subject = "New Medicine: $medicineName for $patientName — HospiLink";

        // Notify prescribing doctor/nurse
        if ($row['prescriber_email']) {
            self::sendEmail($row['prescriber_email'], $subject, $html);
        }

        // Notify all active nurses (staff role)
        self::notifyAllNurses($conn, $subject, $html, $prescribed_by_id);

        // Notify doctor assigned to this admission
        self::notifyAdmissionDoctor($conn, $admission_id, $prescribed_by_id, $subject, $html);
    }

    // ═══════════════════════════════════════════════════════════════
    //  2. IV/DRIP ADDED — email nurse + doctor immediately
    // ═══════════════════════════════════════════════════════════════

    public static function onDripAdded($admission_id, $drip_data, $started_by_id, $conn) {
        $sql = "SELECT pa.admission_id,
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       b.bed_number,
                       COALESCE(b.ward_name, b.bed_type, 'Ward') AS ward_name,
                       CONCAT(u.first_name,' ',u.last_name) AS starter_name,
                       u.email AS starter_email
                FROM patient_admissions pa
                JOIN users p ON pa.patient_id = p.user_id
                LEFT JOIN beds b ON pa.bed_id = b.bed_id
                JOIN users u ON u.user_id = ?
                WHERE pa.admission_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $started_by_id, $admission_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return;

        $patientName  = $row['patient_name'];
        $ward         = $row['ward_name'] . ' — Bed ' . $row['bed_number'];
        $starterName  = $row['starter_name'];
        $fluidType    = $drip_data['fluid_type'];
        $volume       = $drip_data['volume_ml'] . ' mL';
        $flowRate     = $drip_data['flow_rate'];
        $startedAt    = date('d M Y, h:i A', strtotime($drip_data['started_at']));
        $expectedEnd  = $drip_data['expected_end_at'] ? date('d M Y, h:i A', strtotime($drip_data['expected_end_at'])) : 'Not specified';
        $site         = $drip_data['site_location'];
        $notes        = $drip_data['notes'] ?: 'None';

        // Calculate time remaining
        $timeRemaining = '';
        if ($drip_data['expected_end_at']) {
            $endTime    = strtotime($drip_data['expected_end_at']);
            $minRemain  = round(($endTime - time()) / 60);
            $urgency    = $minRemain <= 30 ? 'urgent' : ($minRemain <= 60 ? 'high' : 'medium');
            $timeRemaining = "$minRemain minutes from now";
        } else {
            $urgency = 'medium';
        }

        $rows = self::infoRow('Patient', $patientName)
              . self::infoRow('Ward / Bed', $ward)
              . self::infoRow('Fluid Type', "<strong>$fluidType</strong>")
              . self::infoRow('Volume', $volume)
              . self::infoRow('Flow Rate', $flowRate)
              . self::infoRow('Started At', $startedAt)
              . self::infoRow('Expected End', $expectedEnd)
              . ($timeRemaining ? self::infoRow('Time Remaining', $timeRemaining) : '')
              . self::infoRow('Site', $site)
              . self::infoRow('Notes', $notes)
              . self::infoRow('Started By', $starterName);

        $card = self::infoCard($urgency, $rows);
        $body = "<h2 style='color:#0f172a;margin-top:0;font-size:18px;font-weight:600;'>IV Drip Started</h2>
                 <p style='color:#475569;line-height:1.6;'>An IV drip has been started for patient <strong>$patientName</strong>. Please monitor and be ready for drip completion.</p>
                 $card
                 <p style='color:#64748b;font-size:13px;margin-top:16px;'>Please check on the patient 30 minutes before expected completion time to prepare for the next step.</p>";

        $html    = self::baseTemplate('', '', 'IV Drip Started', $body);
        $subject = "IV Drip Started: $fluidType for $patientName — HospiLink";

        // Notify the nurse who started it
        if ($row['starter_email']) {
            self::sendEmail($row['starter_email'], $subject, $html);
        }

        // Notify ALL nurses (drip monitoring is shared responsibility)
        self::notifyAllNurses($conn, $subject, $html, $started_by_id);

        // Notify doctor
        self::notifyAdmissionDoctor($conn, $admission_id, $started_by_id, $subject, $html);
    }

    // ═══════════════════════════════════════════════════════════════
    //  3. SCHEDULE/TASK ADDED — email assigned nurse + doctor
    // ═══════════════════════════════════════════════════════════════

    public static function onTaskScheduled($admission_id, $task_data, $created_by_id, $conn) {
        $sql = "SELECT pa.admission_id,
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       b.bed_number,
                       COALESCE(b.ward_name, b.bed_type, 'Ward') AS ward_name,
                       CONCAT(u.first_name,' ',u.last_name) AS creator_name,
                       u.email AS creator_email
                FROM patient_admissions pa
                JOIN users p ON pa.patient_id = p.user_id
                LEFT JOIN beds b ON pa.bed_id = b.bed_id
                JOIN users u ON u.user_id = ?
                WHERE pa.admission_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $created_by_id, $admission_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return;

        $patientName  = $row['patient_name'];
        $ward         = $row['ward_name'] . ' — Bed ' . $row['bed_number'];
        $creatorName  = $row['creator_name'];
        $taskType     = ucfirst(str_replace('_', ' ', $task_data['task_type']));
        $description  = $task_data['task_description'];
        $scheduledAt  = date('d M Y, h:i A', strtotime($task_data['scheduled_time']));
        $priority     = ucfirst($task_data['priority']);
        $assignedTo   = null;
        $assignedEmail = null;

        // Get assigned user details if set
        if (!empty($task_data['assigned_to'])) {
            $stmtA = $conn->prepare("SELECT CONCAT(first_name,' ',last_name) AS name, email FROM users WHERE user_id = ?");
            $stmtA->bind_param('i', $task_data['assigned_to']);
            $stmtA->execute();
            $assignedRow = $stmtA->get_result()->fetch_assoc();
            $stmtA->close();
            if ($assignedRow) {
                $assignedTo    = $assignedRow['name'];
                $assignedEmail = $assignedRow['email'];
            }
        }

        $priorityColor = ['low' => 'low', 'medium' => 'medium', 'high' => 'high', 'critical' => 'urgent'];
        $urgency = $priorityColor[strtolower($task_data['priority'])] ?? 'medium';

        $rows = self::infoRow('Patient', $patientName)
              . self::infoRow('Ward / Bed', $ward)
              . self::infoRow('Task Type', "<strong>$taskType</strong>")
              . self::infoRow('Description', $description)
              . self::infoRow('Scheduled At', $scheduledAt)
              . self::infoRow('Priority', "<span style='font-weight:700;'>$priority</span>")
              . ($assignedTo ? self::infoRow('Assigned To', $assignedTo) : self::infoRow('Assigned To', 'All available staff'))
              . self::infoRow('Created By', $creatorName);

        $card = self::infoCard($urgency, $rows);
        $body = "<h2 style='color:#0f172a;margin-top:0;font-size:18px;font-weight:600;'>New Task Scheduled</h2>
                 <p style='color:#475569;line-height:1.6;'>A new task has been scheduled for patient <strong>$patientName</strong>. Please ensure it is completed on time.</p>
                 $card
                 <p style='color:#64748b;font-size:13px;margin-top:16px;'>Please complete this task by the scheduled time and update the patient chart accordingly.</p>";

        $html    = self::baseTemplate('', '', 'Task Scheduled', $body);
        $subject = "New Task: $taskType for $patientName at $scheduledAt — HospiLink";

        // Notify creator
        if ($row['creator_email']) {
            self::sendEmail($row['creator_email'], $subject, $html);
        }

        // Notify specifically assigned person
        if ($assignedEmail && $assignedEmail !== $row['creator_email']) {
            self::sendEmail($assignedEmail, $subject, $html);
        }

        // Notify all nurses if not specifically assigned
        if (!$assignedTo) {
            self::notifyAllNurses($conn, $subject, $html, $created_by_id);
        }

        // Notify doctor
        self::notifyAdmissionDoctor($conn, $admission_id, $created_by_id, $subject, $html);
    }

    // ═══════════════════════════════════════════════════════════════
    //  4. NOTE ADDED BY DOCTOR — notify patient's nurse
    // ═══════════════════════════════════════════════════════════════

    public static function onNoteAdded($admission_id, $note_data, $doctor_id, $conn) {
        $sql = "SELECT pa.admission_id,
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       b.bed_number,
                       COALESCE(b.ward_name, b.bed_type, 'Ward') AS ward_name,
                       CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
                       u.email AS doctor_email
                FROM patient_admissions pa
                JOIN users p ON pa.patient_id = p.user_id
                LEFT JOIN beds b ON pa.bed_id = b.bed_id
                JOIN users u ON u.user_id = ?
                WHERE pa.admission_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $doctor_id, $admission_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return;

        $patientName = $row['patient_name'];
        $ward        = $row['ward_name'] . ' — Bed ' . $row['bed_number'];
        $doctorName  = $row['doctor_name'];
        $noteType    = ucfirst(str_replace('_', ' ', $note_data['note_type']));

        $vitalsSection = '';
        $vitals = [];
        if (!empty($note_data['vitals_bp']))         $vitals[] = 'BP: ' . $note_data['vitals_bp'];
        if (!empty($note_data['vitals_pulse']))       $vitals[] = 'Pulse: ' . $note_data['vitals_pulse'] . ' bpm';
        if (!empty($note_data['vitals_temp']))        $vitals[] = 'Temp: ' . $note_data['vitals_temp'] . '°F';
        if (!empty($note_data['vitals_spo2']))        $vitals[] = 'SpO2: ' . $note_data['vitals_spo2'] . '%';
        if (!empty($note_data['vitals_respiratory_rate'])) $vitals[] = 'RR: ' . $note_data['vitals_respiratory_rate'];
        if ($vitals) {
            $vitalsSection = self::infoRow('Vitals', implode(' &nbsp;|&nbsp; ', $vitals));
        }

        $rows = self::infoRow('Patient', $patientName)
              . self::infoRow('Ward / Bed', $ward)
              . self::infoRow('Note Type', "<strong>$noteType</strong>")
              . ($vitalsSection ? $vitalsSection : '')
              . (!empty($note_data['diagnosis']) ? self::infoRow('Diagnosis', $note_data['diagnosis']) : '')
              . (!empty($note_data['treatment_plan']) ? self::infoRow('Treatment Plan', $note_data['treatment_plan']) : '')
              . self::infoRow('Recorded By', "Dr. $doctorName")
              . self::infoRow('Time', date('d M Y, h:i A'));

        $card = self::infoCard('medium', $rows);
        $body = "<h2 style='color:#0f172a;margin-top:0;font-size:18px;font-weight:600;'>Doctor Note Added</h2>
                 <p style='color:#475569;line-height:1.6;'>Dr. <strong>$doctorName</strong> has added a <strong>$noteType</strong> note for patient <strong>$patientName</strong>.</p>
                 $card";

        $html    = self::baseTemplate('', '', 'Doctor Note Added', $body);
        $subject = "Doctor Note: $noteType for $patientName — HospiLink";

        // Notify doctor (confirmation)
        if ($row['doctor_email']) {
            self::sendEmail($row['doctor_email'], $subject, $html);
        }

        // Notify all nurses
        self::notifyAllNurses($conn, $subject, $html, $doctor_id);
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS — fetch and notify nurses/doctors
    // ═══════════════════════════════════════════════════════════════

    public static function notifyAllNurses($conn, $subject, $html, $excludeUserId = null) {
        $sql    = "SELECT email FROM users WHERE role IN ('staff','nurse') AND status = 'active' AND email IS NOT NULL AND email != ''";
        $result = $conn->query($sql);
        if (!$result) return;
        $sent   = [];
        while ($nurse = $result->fetch_assoc()) {
            if ($excludeUserId) {
                $excEmail = self::getEmailById($conn, $excludeUserId);
                if ($excEmail && $nurse['email'] === $excEmail) continue;
            }
            self::sendEmail($nurse['email'], $subject, $html);
            $sent[] = $nurse['email'];
        }
        // Always CC the override nurse email from .env
        $override = env('NOTIFY_NURSE_EMAIL', '');
        if ($override && !in_array($override, $sent)) {
            self::sendEmail($override, $subject, $html);
        }
    }

    public static function notifyAdmissionDoctor($conn, $admission_id, $excludeUserId = null, $subject = '', $html = '') {
        // Find doctor assigned to this admission
        $sql  = "SELECT u.email FROM patient_admissions pa 
                 JOIN users u ON u.user_id = pa.assigned_doctor_id 
                 WHERE pa.admission_id = ? AND u.status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $admission_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $sent = [];
        if ($row && $row['email']) {
            $excludeEmail = $excludeUserId ? self::getEmailById($conn, $excludeUserId) : null;
            if ($row['email'] !== $excludeEmail) {
                self::sendEmail($row['email'], $subject, $html);
                $sent[] = $row['email'];
            }
        }
        // Always CC the override doctor email from .env
        $override = env('NOTIFY_DOCTOR_EMAIL', '');
        if ($override && !in_array($override, $sent)) {
            self::sendEmail($override, $subject, $html);
        }
    }

    private static function getEmailById($conn, $userId) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['email'] ?? null;
    }

    // ═══════════════════════════════════════════════════════════════
    //  LOG to DB for notification bell
    // ═══════════════════════════════════════════════════════════════

    public static function logNotification($conn, $type, $title, $message, $target_role, $admission_id = null) {
        // Check if table exists, create if not
        $conn->query("CREATE TABLE IF NOT EXISTS hospi_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            target_role VARCHAR(20),
            admission_id INT,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_role (target_role),
            INDEX idx_read (is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $conn->prepare("INSERT INTO hospi_notifications (type, title, message, target_role, admission_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $type, $title, $message, $target_role, $admission_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
