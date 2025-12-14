<?php
session_start();
require_once '../php/db.php';
require_once '../php/patient_qr_helper.php';

$admission_id = isset($_GET['admission_id']) ? intval($_GET['admission_id']) : 0;

if ($admission_id == 0) {
    die("Error: Invalid admission ID");
}

// Get admission details
$stmt = $conn->prepare("
    SELECT 
        pa.*,
        u.first_name, u.last_name, u.phone,
        b.ward_name, b.bed_number, b.bed_type,
        CONCAT(d.first_name, ' ', d.last_name) as doctor_name
    FROM patient_admissions pa
    JOIN users u ON pa.patient_id = u.user_id
    LEFT JOIN beds b ON pa.bed_id = b.bed_id
    LEFT JOIN users d ON pa.assigned_doctor_id = d.user_id
    WHERE pa.admission_id = ?
");

$stmt->bind_param("i", $admission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: Admission not found");
}

$admission = $result->fetch_assoc();
$stmt->close();
$conn->close();

$qr_token = $admission['qr_code_token'];
$qr_url = PatientQRHelper::getQRScanURL($qr_token);

// Use online API (works best for scanning)
$google_qr_api = PatientQRHelper::generateQRCodeURL($qr_token, 400);

// Also generate using QuickChart as primary (more reliable)
$quickchart_qr = "https://quickchart.io/qr?text=" . urlencode($qr_url) . "&size=400";

// Debug: Echo the QR URL to check
// echo "<!-- DEBUG: QR Token: $qr_token -->";
// echo "<!-- DEBUG: QR URL: $qr_url -->";
// echo "<!-- DEBUG: QuickChart: $quickchart_qr -->";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Code - <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="images/hosp_favicon.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .no-print {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .no-print h1 {
            color: #00adb5;
            margin-bottom: 15px;
        }

        .no-print p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-print {
            background: #00adb5;
            color: white;
        }

        .btn-print:hover {
            background: #0e545f;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .qr-labels {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .qr-label {
            background: white;
            border: 3px solid #00adb5;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            page-break-inside: avoid;
        }

        .qr-label-header {
            background: linear-gradient(135deg, #00adb5 0%, #0e545f 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .qr-label-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .qr-label-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .qr-code-container {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border: 2px dashed #00adb5;
        }

        .qr-code-container img {
            max-width: 100%;
            height: auto;
        }

        .patient-info {
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: bold;
            color: #00adb5;
        }

        .info-value {
            color: #333;
        }

        .instructions {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            text-align: left;
        }

        .instructions h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .instructions ol {
            margin-left: 20px;
            color: #856404;
        }

        .instructions li {
            margin: 5px 0;
            font-size: 14px;
        }

        .token-display {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            margin-top: 10px;
            color: #666;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .qr-labels {
                display: block;
            }

            .qr-label {
                page-break-after: always;
                margin-bottom: 0;
                box-shadow: none;
            }

            .qr-label:last-child {
                page-break-after: auto;
            }
        }

        .large-label {
            grid-column: span 2;
        }

        @media (max-width: 768px) {
            .qr-labels {
                grid-template-columns: 1fr;
            }

            .large-label {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Screen Instructions -->
        <div class="no-print">
            <h1><i class="fas fa-qrcode"></i> QR Code Generated Successfully!</h1>
            <p><strong>Patient:</strong> <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></p>
            <p><strong>Admission ID:</strong> #<?php echo $admission_id; ?></p>
            <p><strong>Bed:</strong> <?php echo htmlspecialchars($admission['ward_name'] . ' - ' . $admission['bed_number']); ?></p>
            <p><strong>Admitted:</strong> <?php echo date('M d, Y h:i A', strtotime($admission['admission_date'])); ?></p>
            
            <div class="button-group">
                <button onclick="window.print()" class="btn btn-print">
                    <i class="fas fa-print"></i> Print QR Labels
                </button>
                <a href="patient-status?token=<?php echo urlencode($qr_token); ?>" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> View Patient Status
                </a>
                <a href="admit.html" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i> Admit Another Patient
                </a>
            </div>
        </div>

        <!-- Printable QR Labels -->
        <div class="qr-labels">
            <!-- Label 1: Bedside Label (Large) -->
            <div class="qr-label large-label">
                <div class="qr-label-header">
                    <h2>HospiLink</h2>
                    <p>Bedside Patient Monitoring</p>
                </div>
                
                <div class="qr-code-container">
                    <img src="<?php echo htmlspecialchars($quickchart_qr); ?>" 
                         onerror="this.src='<?php echo htmlspecialchars($google_qr_api); ?>'" 
                         alt="QR Code">
                </div>
                
                <div class="patient-info">
                    <div class="info-row">
                        <span class="info-label">Patient:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Admission ID:</span>
                        <span class="info-value">#<?php echo $admission_id; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Bed:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admission['ward_name'] . ' - ' . $admission['bed_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Doctor:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admission['doctor_name'] ?: 'Not assigned'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Admitted:</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($admission['admission_date'])); ?></span>
                    </div>
                </div>
                
                <div class="token-display">
                    <strong>Patient Code:</strong><br>
                    <?php echo htmlspecialchars($qr_token); ?>
                </div>
                
                <div class="instructions">
                    <h3><i class="fas fa-info-circle"></i> For Medical Staff:</h3>
                    <ol>
                        <li><strong>Option 1:</strong> Scan QR code with phone camera</li>
                        <li><strong>Option 2:</strong> Visit <strong>hospilink.local/scan</strong> and enter patient code</li>
                        <li>Update medicines, drips, tests, and notes instantly</li>
                        <li>All changes are visible to entire medical team in real-time</li>
                    </ol>
                    <p style="margin-top: 10px; font-size: 13px;"><strong>💡 Tip:</strong> If QR won't scan, manually type the patient code shown above</p>
                </div>
            </div>

            <!-- Label 2: Compact Bedside Tag -->
            <div class="qr-label">
                <div class="qr-label-header">
                    <h2 style="font-size: 20px;">HospiLink</h2>
                    <p style="font-size: 12px;">Scan for Patient Status</p>
                </div>
                
                <div class="qr-code-container">
                    <img src="<?php echo htmlspecialchars($quickchart_qr); ?>" 
                         onerror="this.src='<?php echo htmlspecialchars($google_qr_api); ?>'" 
                         alt="QR Code" style="max-width: 200px;">
                </div>
                
                <div style="margin-top: 15px;">
                    <h3 style="color: #00adb5; font-size: 18px; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>
                    </h3>
                    <p style="color: #666; font-size: 14px; margin: 5px 0;">
                        <strong>Bed:</strong> <?php echo htmlspecialchars($admission['bed_number']); ?>
                    </p>
                    <p style="color: #666; font-size: 14px; margin: 5px 0;">
                        <strong>ID:</strong> #<?php echo $admission_id; ?>
                    </p>
                </div>
            </div>

            <!-- Label 3: Door Tag -->
            <div class="qr-label">
                <div style="background: #0e545f; color: white; padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <h2 style="font-size: 28px; margin-bottom: 5px;"><?php echo htmlspecialchars($admission['bed_number']); ?></h2>
                    <p style="font-size: 14px;"><?php echo htmlspecialchars($admission['ward_name']); ?></p>
                </div>
                
                <h3 style="color: #333; font-size: 20px; margin-bottom: 15px;">
                    <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>
                </h3>
                
                <div class="qr-code-container">
                    <img src="<?php echo htmlspecialchars($quickchart_qr); ?>" 
                         onerror="this.src='<?php echo htmlspecialchars($google_qr_api); ?>'" 
                         alt="QR Code" style="max-width: 180px;">
                </div>
                
                <p style="color: #00adb5; font-weight: bold; margin-top: 15px; font-size: 14px;">
                    <i class="fas fa-mobile-alt"></i> Scan for Patient Info
                </p>
            </div>
        </div>

        <!-- Technical Details (No Print) -->
        <div class="no-print" style="margin-top: 20px;">
            <h3 style="color: #00adb5; margin-bottom: 10px;">Technical Details</h3>
            <p><strong>QR Token:</strong></p>
            <div class="token-display"><?php echo htmlspecialchars($qr_token); ?></div>
            <p style="margin-top: 10px;"><strong>Scan URL:</strong></p>
            <div class="token-display"><?php echo htmlspecialchars($qr_url); ?></div>
            
            <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <p style="color: #004085; font-size: 14px; line-height: 1.6;">
                    <i class="fas fa-shield-alt"></i> <strong>Security:</strong> This QR code is unique and secure. 
                    It only works for authorized medical staff who are logged into the HospiLink system. 
                    The QR code does not contain any medical data—it only links to the patient record.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Auto-print prompt
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (confirm('Print QR code labels now?')) {
                    window.print();
                }
            }, 500);
        });
    </script>
</body>
</html>
