<?php
session_start();
include 'php/db.php';

// Get appointment ID from session instead of URL
$appointment_id = isset($_SESSION['appointment_id']) ? intval($_SESSION['appointment_id']) : 0;
// Clear the session variable after retrieving
if ($appointment_id > 0) {
    unset($_SESSION['appointment_id']);
}

if ($appointment_id > 0) {
    $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name
              FROM appointments a
              LEFT JOIN users u ON a.doctor_id = u.user_id
              WHERE a.appointment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();
    } else {
        header("Location: appointment.html");
        exit();
    }
} else {
    header("Location: appointment.html");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmed - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="images/hosp_favicon.png" type="image/png">
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #00adb5;
            min-height: 100vh;
            padding: 60px 20px;
            overflow-y: auto;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
            margin: 0 auto 40px;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-header {
            background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%);
            color: white;
            padding: 35px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .success-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .success-icon {
            font-size: 60px;
            margin-bottom: 15px;
            animation: scaleIn 0.5s ease-out 0.3s both;
            position: relative;
            z-index: 1;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0) rotate(-180deg);
            }
            to {
                transform: scale(1) rotate(0deg);
            }
        }

        .success-header h1 {
            font-size: 30px;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .success-header p {
            font-size: 16px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        .success-body {
            padding: 40px;
            background: white;
        }

        .appointment-id {
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 2px dashed #00adb5;
        }

        .appointment-id h2 {
            color: #0e545f;
            font-size: 16px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .appointment-id .id-number {
            font-size: 42px;
            font-weight: 800;
            color: #00adb5;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .appointment-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .detail-label i {
            color: #00adb5;
            font-size: 18px;
            width: 20px;
        }

        .detail-value {
            font-weight: 700;
            color: #212529;
            text-align: right;
            font-size: 16px;
        }

        .priority-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .priority-badge.high {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
        }

        .priority-badge.medium {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
        }

        .priority-badge.low {
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            color: white;
        }

        .alert-box {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-box.high {
            background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
            border-left-color: #f44336;
            color: #c62828;
        }

        .alert-box.medium {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left-color: #FF9800;
            color: #e65100;
        }

        .alert-box.low {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-left-color: #4CAF50;
            color: #2e7d32;
        }

        .alert-box i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .next-steps {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
        }

        .next-steps h3 {
            margin-bottom: 20px;
            color: #0e545f;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .next-steps h3 i {
            color: #00adb5;
        }

        .next-steps ul {
            list-style: none;
            padding: 0;
        }

        .next-steps li {
            padding: 12px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 15px;
            line-height: 1.6;
        }

        .next-steps li i {
            color: #00adb5;
            margin-top: 3px;
            font-size: 18px;
            flex-shrink: 0;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #0e545f;
            border: 2px solid #00adb5;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,173,181,0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #00adb5 0%, #0e545f 100%);
        }

        .btn-secondary:hover {
            background: #00adb5;
            color: white;
            border-color: #00adb5;
        }

        @media (max-width: 600px) {
            .success-header {
                padding: 30px 20px;
            }

            .success-icon {
                font-size: 60px;
            }

            .success-header h1 {
                font-size: 24px;
            }

            .success-body {
                padding: 25px;
            }

            .detail-row {
                flex-direction: column;
                gap: 5px;
            }

            .detail-value {
                text-align: left;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Appointment Booked Successfully!</h1>
            <p>Your appointment has been confirmed and prioritized by our AI system</p>
        </div>

        <div class="success-body">
            <!-- Priority Alert -->
            <?php
            $priorityMessages = [
                'high' => [
                    'icon' => 'fa-exclamation-triangle',
                    'message' => 'High Priority - Your symptoms require immediate medical attention. A doctor will review your case within 1 hour.'
                ],
                'medium' => [
                    'icon' => 'fa-bolt',
                    'message' => 'Medium Priority - Expected wait time is 3-5 days. You will receive confirmation shortly.'
                ],
                'low' => [
                    'icon' => 'fa-check-circle',
                    'message' => 'Low Priority - You will receive a confirmation call within 1-2 weeks. Suitable for routine care.'
                ]
            ];

            $priorityInfo = $priorityMessages[$appointment['priority_level']];
            ?>

            <div class="alert-box <?php echo $appointment['priority_level']; ?>">
                <i class="fas <?php echo $priorityInfo['icon']; ?>"></i>
                <span><?php echo $priorityInfo['message']; ?></span>
            </div>

            <!-- Appointment Details -->
            <div class="appointment-details">
                <h3 style="margin-bottom: 15px;">Appointment Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-hashtag"></i> Appointment ID
                    </span>
                    <span class="detail-value">#<?php echo $appointment['appointment_id']; ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-user"></i> Patient Name
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['full_name']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-calendar"></i> Date
                    </span>
                    <span class="detail-value"><?php echo date('l, F d, Y', strtotime($appointment['appointment_date'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-clock"></i> Time
                    </span>
                    <span class="detail-value"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-user-md"></i> Doctor
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['doctor_name'] ?: 'Will be assigned'); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-flag"></i> Priority Level
                    </span>
                    <span class="detail-value">
                        <span class="priority-badge <?php echo $appointment['priority_level']; ?>">
                            <?php echo strtoupper($appointment['priority_level']); ?>
                        </span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-chart-line"></i> AI Priority Score
                    </span>
                    <span class="detail-value"><?php echo $appointment['priority_score']; ?>/100</span>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h3>What Happens Next?</h3>
                <ul>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Your appointment details have been saved in our system</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Our AI has analyzed your symptoms and assigned priority</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>A confirmation email with calendar invite has been sent to <?php echo htmlspecialchars($appointment['email']); ?></span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>A doctor will review your case based on urgency</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Track your appointment status in your patient dashboard</span>
                    </li>
                </ul>
            </div>

            <!-- Calendar Notice -->
            <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left: 4px solid #2196F3; padding: 20px; border-radius: 8px; margin: 25px 0;">
                <div style="display: flex; align-items: flex-start; gap: 15px;">
                    <i class="fas fa-calendar-plus" style="font-size: 28px; color: #2196F3; margin-top: 2px;"></i>
                    <div>
                        <h4 style="color: #1976D2; margin: 0 0 10px 0; font-size: 16px;">📅 Add to Your Calendar</h4>
                        <p style="color: #555; margin: 0; line-height: 1.6;">
                            Check your email for a calendar invite (.ics file) that you can add to:
                        </p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px;">
                            <span style="background: white; padding: 6px 12px; border-radius: 15px; font-size: 13px; color: #666;">
                                📅 Google Calendar
                            </span>
                            <span style="background: white; padding: 6px 12px; border-radius: 15px; font-size: 13px; color: #666;">
                                📧 Outlook
                            </span>
                            <span style="background: white; padding: 6px 12px; border-radius: 15px; font-size: 13px; color: #666;">
                                🍎 Apple Calendar
                            </span>
                            <span style="background: white; padding: 6px 12px; border-radius: 15px; font-size: 13px; color: #666;">
                                ⚡ & More
                            </span>
                        </div>
                        <p style="color: #555; margin: 12px 0 0 0; font-size: 13px;">
                            <i class="fas fa-bell" style="color: #FF9800;"></i> 
                            <strong>Automatic reminders</strong> will be set for 24 hours and 2 hours before your appointment.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if(isset($_SESSION['logged_in'])): ?>
                    <a href="dashboards/patient_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> View Dashboard
                    </a>
                <?php endif; ?>
                <a href="index.html" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Details
                </button>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
