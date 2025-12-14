<?php
session_start();
require_once '../php/db.php';
require_once '../php/patient_qr_helper.php';

// Get token from URL or allow manual entry
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$manual_entry = isset($_POST['manual_token']) ? trim($_POST['manual_token']) : '';

if (!empty($manual_entry)) {
    $token = $manual_entry;
}

// If no token, show entry form
if (empty($token)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Patient Code - HospiLink</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #00adb5;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .info {
            background: #f0f8ff;
            border-left: 4px solid #00adb5;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .info p {
            margin: 8px 0;
            color: #333;
            font-size: 14px;
        }
        label {
            display: block;
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 16px;
        }
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            font-family: monospace;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #00adb5;
        }
        .hint {
            color: #666;
            font-size: 13px;
            margin-top: 8px;
        }
        button {
            width: 100%;
            padding: 15px;
            background: #00adb5;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        button:hover {
            background: #008c94;
        }
        .scan-option {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px dashed #ddd;
        }
        .scan-option p {
            color: #666;
            margin-bottom: 10px;
        }
        .qr-icon {
            font-size: 48px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 HospiLink Patient Status</h1>
        
        <div class="info">
            <p><strong>📱 For Medical Staff:</strong></p>
            <p>Scan the QR code at the patient's bedside OR enter the patient code manually below.</p>
        </div>
        
        <form method="POST" action="">
            <label for="manual_token">Enter Patient Code:</label>
            <input type="text" 
                   id="manual_token" 
                   name="manual_token" 
                   placeholder="HOSP-1234567890-ABCDEF123456-ABC"
                   required
                   autocomplete="off"
                   style="text-transform: uppercase;">
            <p class="hint">💡 The code is printed below the QR code on the patient's label</p>
            
            <button type="submit">View Patient Status →</button>
        </form>
        
        <div class="scan-option">
            <p><strong>Or scan QR code:</strong></p>
            <div class="qr-icon">📷</div>
            <p style="font-size: 13px; color: #999;">Use your phone camera to scan the bedside QR code</p>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// If token provided, redirect to patient-status.php
header("Location: patient-status.php?token=" . urlencode($token));
exit;
?>
