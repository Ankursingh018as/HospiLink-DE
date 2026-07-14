<?php
/**
 * HospiLink IV Drip Monitor
 * Calculates remaining volume dynamically based on started_at, flow_rate, and volume_ml.
 * Triggers alerts when volume is <= 10 ml and sends email notifications.
 */

class DripMonitor {
    
    public static function checkAllDrips($conn) {
        // Query running drips
        $sql = "SELECT iv.*, pa.admission_id, 
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       b.bed_number, COALESCE(b.ward_name, b.bed_type, 'Ward') AS ward_name
                FROM patient_ivs iv
                JOIN patient_admissions pa ON iv.admission_id = pa.admission_id
                JOIN users p ON pa.patient_id = p.user_id
                LEFT JOIN beds b ON pa.bed_id = b.bed_id
                WHERE iv.status = 'running'";

        $result = $conn->query($sql);
        if (!$result) return;

        require_once __DIR__ . '/hospi_notify.php';

        while ($drip = $result->fetch_assoc()) {
            $iv_id = $drip['iv_id'];
            $volume = (int)$drip['volume_ml'];
            $flow_rate = $drip['flow_rate'];
            $started_at = strtotime($drip['started_at']);
            $now = time();

            // Extract numeric flow rate (ml/hour)
            preg_match('/\d+/', $flow_rate, $matches);
            $rate = !empty($matches[0]) ? (int)$matches[0] : 0;

            if ($rate <= 0 || $volume <= 0) continue;

            // Calculate elapsed hours
            $hours_elapsed = ($now - $started_at) / 3600;
            if ($hours_elapsed < 0) $hours_elapsed = 0;

            // Infused volume and remaining volume
            $infused = $rate * $hours_elapsed;
            $remaining = $volume - $infused;

            // If remaining volume <= 10 ml
            if ($remaining <= 10) {
                // Check if notification already sent for this specific drip ID
                $checkMsg = "%IV Drip ID: $iv_id%";
                $stmt = $conn->prepare("SELECT id FROM hospi_notifications WHERE type = 'drip_low' AND admission_id = ? AND message LIKE ?");
                $stmt->bind_param("is", $drip['admission_id'], $checkMsg);
                $stmt->execute();
                $notifExists = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$notifExists) {
                    $patientName = $drip['patient_name'];
                    $ward = $drip['ward_name'] . ' — Bed ' . $drip['bed_number'];
                    $fluidType = $drip['fluid_type'];
                    $remMl = max(0, round($remaining, 1));

                    // Build alert email body
                    $emailBody = <<<HTML
<div style="font-family:Arial,sans-serif;padding:10px 0;">
  <p style="color:#2d3748;font-size:16px;line-height:1.6;">
    🚨 <strong>Attention Required:</strong> The IV drip for patient <strong>$patientName</strong> in <strong>$ward</strong> is nearly empty!
  </p>
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#fff5f5;border-left:4px solid #e53e3e;border-radius:8px;padding:20px;margin:20px 0;">
    <tr><td style="padding:6px 0;border-bottom:1px solid #fed7d7;">
      <span style="color:#718096;font-size:11px;font-weight:700;text-transform:uppercase;">Patient</span><br>
      <span style="color:#2d3748;font-weight:600;">$patientName</span>
    </td></tr>
    <tr><td style="padding:6px 0;border-bottom:1px solid #fed7d7;">
      <span style="color:#718096;font-size:11px;font-weight:700;text-transform:uppercase;">Ward / Bed</span><br>
      <span style="color:#2d3748;font-weight:600;">$ward</span>
    </td></tr>
    <tr><td style="padding:6px 0;border-bottom:1px solid #fed7d7;">
      <span style="color:#718096;font-size:11px;font-weight:700;text-transform:uppercase;">Fluid Type</span><br>
      <span style="color:#2d3748;font-weight:600;">$fluidType</span>
    </td></tr>
    <tr><td style="padding:6px 0;">
      <span style="color:#718096;font-size:11px;font-weight:700;text-transform:uppercase;">Calculated Remaining</span><br>
      <span style="color:#e53e3e;font-weight:700;font-size:16px;">$remMl mL left (Critical)</span>
    </td></tr>
  </table>
  <p style="color:#718096;font-size:13px;margin-top:10px;">Please check the patient immediately to replace or discontinue the IV fluid.</p>
</div>
HTML;

                    $html = HospiNotify::baseTemplate('linear-gradient(135deg,#ff0844 0%,#ffb199 100%)', '🚨', 'IV Drip Alert', $emailBody);
                    $subject = "🚨 Alert: IV Drip nearly empty ($remMl ml left) for $patientName — HospiLink";

                    // 1. Log notification in database for the bell icon
                    HospiNotify::logNotification(
                        $conn, 
                        'drip_low', 
                        "🚨 Drip Alert: $fluidType nearly empty", 
                        "IV Drip ID: $iv_id | Patient: $patientName | Remaining: $remMl ml", 
                        'staff', 
                        $drip['admission_id']
                    );

                    // 2. Send email to assigned nurse override + active nurses
                    HospiNotify::notifyAllNurses($conn, $subject, $html);

                    // 3. Send email to assigned doctor override
                    HospiNotify::notifyAdmissionDoctor($conn, $drip['admission_id'], null, $subject, $html);
                }
            }
        }
    }
}
