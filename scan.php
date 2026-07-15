<?php
session_start();
require_once 'php/db.php';
require_once 'php/patient_qr_helper.php';

// Get token from URL or allow manual entry
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$manual_entry = isset($_POST['manual_token']) ? trim($_POST['manual_token']) : '';

if (!empty($manual_entry)) {
    $token = $manual_entry;
}

// If token provided, redirect to patient-status.php
if (!empty($token)) {
    header("Location: patient-status.php?token=" . urlencode($token));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HospiLink Bedside QR Scanner</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="images/hosp_favicon.png" type="image/png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1e293b;
        }
        
        .container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1 {
            font-weight: 700;
            color: #0f172a;
            text-align: center;
            margin-bottom: 6px;
            font-size: 28px;
            letter-spacing: -0.5px;
        }
        
        .sub-logo {
            text-align: center;
            font-size: 13px;
            color: #00adb5;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }
        
        .info {
            background: rgba(0, 173, 181, 0.05);
            border-left: 4px solid #00adb5;
            padding: 16px;
            margin-bottom: 25px;
            border-radius: 12px;
        }
        
        .info p {
            margin: 4px 0;
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
        }
        
        label {
            display: block;
            color: #475569;
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 16px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            color: #0f172a;
            font-size: 16px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #00adb5;
            box-shadow: 0 0 15px rgba(0, 173, 181, 0.15);
            background: #ffffff;
        }
        
        .hint {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 8px;
            text-align: center;
            display: block;
        }
        
        button.btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #00adb5 0%, #0a888e 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        button.btn-submit:hover {
            background: linear-gradient(135deg, #00c2cc 0%, #00adb5 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 173, 181, 0.25);
        }
        
        .scan-divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .scan-divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .scan-divider::after {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e2e8f0;
        }
        
        .scan-divider span {
            font-size: 12px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #ffffff;
            padding: 0 10px;
            display: inline-block;
            vertical-align: middle;
            border-radius: 5px;
        }
        
        button.btn-scan {
            width: 100%;
            padding: 16px;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button.btn-scan:hover {
            background: #e2e8f0;
            border-color: #00adb5;
            color: #00adb5;
            transform: translateY(-2px);
        }
        
        button.btn-stop {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
            display: none;
        }
        
        button.btn-stop:hover {
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }
        
        #reader {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid rgba(0, 173, 181, 0.3);
            margin-top: 20px;
            background: #f8fafc;
            display: none;
        }
        
        .back-link {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link:hover {
            color: #00adb5;
        }
        
        /* html5-qrcode library specific UI tweaks */
        #reader__scan_region {
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>HospiLink Bedside Scanner</h1>
        <div class="sub-logo">QR Patient Lookup</div>
        
        <div class="info">
            <p><strong>Bedside Scanning:</strong></p>
            <p>Scan the QR code at the patient's bedside or enter the secure token manually below.</p>
        </div>
        
        <form id="qr-form" method="POST" action="">
            <label for="manual_token">Patient Secure Code</label>
            <input type="text" 
                   id="manual_token" 
                   name="manual_token" 
                   placeholder="HOSP-1234567890-ABCDEF123456-ABC"
                   required
                   autocomplete="off"
                   style="text-transform: uppercase;">
            <p class="hint">Found printed directly below the bedside QR label</p>
            
            <button type="submit" class="btn-submit">
                <span>View Patient Status</span> <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
            </button>
        </form>
        
        <div class="scan-divider">
            <span>Or Scan via Camera</span>
        </div>
        
        <button type="button" id="start-scan-btn" class="btn-scan">
            <i class="fas fa-camera"></i> <span>Launch Camera Scanner</span>
        </button>
        
        <div id="reader"></div>
        
        <button type="button" id="stop-scan-btn" class="btn-stop">
            <i class="fas fa-video-slash"></i> <span>Stop Camera Scanner</span>
        </button>
        
        <div style="text-align: center; margin-top: 30px;">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'doctor'): ?>
                <a href="dashboards/doctor_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'staff'): ?>
                <a href="dashboards/staff_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <?php else: ?>
                <a href="sign_new.html" class="back-link"><i class="fas fa-arrow-left"></i> Return to Login</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Load Html5Qrcode Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let html5Qrcode = null;

        document.getElementById('start-scan-btn').addEventListener('click', function() {
            const readerDiv = document.getElementById('reader');
            readerDiv.style.display = 'block';
            this.style.display = 'none'; // Hide start button
            document.getElementById('stop-scan-btn').style.display = 'block'; // Show stop button
            
            // Initialize Html5Qrcode on the target container
            html5Qrcode = new Html5Qrcode("reader");
            
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                // Scanned QR code successfully.
                // Could be url: http://.../patient-status.php?token=TOKEN
                // or a raw token string
                let token = decodedText.trim();
                
                try {
                    // Check if it's a URL
                    if (token.startsWith('http://') || token.startsWith('https://')) {
                        const urlObj = new URL(token);
                        const tokenParam = urlObj.searchParams.get("token");
                        if (tokenParam) {
                            token = tokenParam;
                        }
                    }
                } catch (e) {
                    console.log("Scanned text is not a URL, using as raw token");
                }
                
                // Fill the text input
                document.getElementById('manual_token').value = token;
                
                // Stop the scanner and submit the form
                html5Qrcode.stop().then(() => {
                    document.getElementById('qr-form').submit();
                }).catch((err) => {
                    console.error("Failed to stop scanner smoothly", err);
                    document.getElementById('qr-form').submit();
                });
            };
            
            const config = { 
                fps: 15, 
                qrbox: function(width, height) {
                    const minSize = Math.min(width, height);
                    const boxSize = Math.floor(minSize * 0.7);
                    return { width: boxSize, height: boxSize };
                }
            };
            
            // Start scanning with environment/back camera
            html5Qrcode.start(
                { facingMode: "environment" }, 
                config, 
                qrCodeSuccessCallback
            ).catch(err => {
                console.error("Unable to start scanning", err);
                alert("Camera access denied or device has no camera. Please verify permissions.");
                
                // Reset UI buttons
                document.getElementById('start-scan-btn').style.display = 'flex';
                readerDiv.style.display = 'none';
                document.getElementById('stop-scan-btn').style.display = 'none';
            });
        });

        document.getElementById('stop-scan-btn').addEventListener('click', function() {
            if (html5Qrcode) {
                html5Qrcode.stop().then(() => {
                    document.getElementById('reader').style.display = 'none';
                    document.getElementById('start-scan-btn').style.display = 'flex';
                    this.style.display = 'none';
                }).catch(err => {
                    console.error("Failed to stop camera scanner", err);
                });
            }
        });
    </script>
</body>
</html>
