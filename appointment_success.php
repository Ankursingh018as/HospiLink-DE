<?php
session_start();
include 'php/db.php';

$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
    <link rel="icon" href="hosp_favicon.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
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
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-out 0.3s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .success-body {
            padding: 40px;
        }

        .appointment-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-value {
            font-weight: 700;
            color: #333;
            text-align: right;
        }

        .priority-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .priority-badge.critical {
            background: #f44336;
            color: white;
        }

        .priority-badge.high {
            background: #FF9800;
            color: white;
        }

        .priority-badge.medium {
            background: #FFC107;
            color: #333;
        }

        .priority-badge.low {
            background: #4CAF50;
            color: white;
        }

        .alert-box {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .alert-box.critical {
            background: #ffebee;
            border-left: 4px solid #f44336;
            color: #c62828;
        }

        .alert-box.high {
            background: #fff3e0;
            border-left: 4px solid #FF9800;
            color: #e65100;
        }

        .alert-box.medium {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            color: #1565c0;
        }

        .alert-box.low {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            color: #2e7d32;
        }

        .alert-box i {
            font-size: 24px;
            margin-top: 3px;
        }

        .next-steps {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .next-steps h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .next-steps ul {
            list-style: none;
            padding: 0;
        }

        .next-steps li {
            padding: 10px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .next-steps li i {
            color: #4CAF50;
            margin-top: 3px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
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
                'critical' => [
                    'icon' => 'fa-exclamation-triangle',
                    'title' => '🚨 CRITICAL PRIORITY',
                    'message' => 'Your symptoms indicate a medical emergency. Please proceed to the emergency department immediately or call emergency services if symptoms worsen. A doctor will contact you very soon.'
                ],
                'high' => [
                    'icon' => 'fa-bolt',
                    'title' => '⚡ HIGH PRIORITY',
                    'message' => 'Your symptoms require urgent medical attention. You have been prioritized in our queue. A doctor will review your case and contact you within 24 hours.'
                ],
                'medium' => [
                    'icon' => 'fa-info-circle',
                    'title' => '📋 MEDIUM PRIORITY',
                    'message' => 'Your appointment has been scheduled. Expected wait time is 3-5 days. A doctor will review your case and confirm the appointment.'
                ],
                'low' => [
                    'icon' => 'fa-check-circle',
                    'title' => '✓ SCHEDULED',
                    'message' => 'Your appointment has been scheduled for routine care. You will receive a confirmation call within 1-2 weeks.'
                ]
            ];

            $priorityInfo = $priorityMessages[$appointment['priority_level']];
            ?>

            <div class="alert-box <?php echo $appointment['priority_level']; ?>">
                <i class="fas <?php echo $priorityInfo['icon']; ?>"></i>
                <div>
                    <strong><?php echo $priorityInfo['title']; ?></strong>
                    <p style="margin-top: 8px;"><?php echo $priorityInfo['message']; ?></p>
                </div>
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
                        <span>A doctor will review your case based on urgency</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>You will receive a confirmation email/call shortly</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Track your appointment status in your patient dashboard</span>
                    </li>
                </ul>
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
