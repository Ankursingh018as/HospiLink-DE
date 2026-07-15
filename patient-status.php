<?php
session_start();
require_once 'php/db.php';
require_once 'php/patient_qr_helper.php';

// Get QR token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    die("Error: No QR code token provided");
}

// Get admission details
$admission = PatientQRHelper::getAdmissionFromToken($conn, $token);

if (!$admission) {
    die("Error: Invalid or expired QR code");
}

// Check if user is logged in and their role
$logged_in_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
$is_staff_or_doctor = in_array($user_role, ['doctor', 'staff', 'nurse', 'admin']);

// Log the scan
PatientQRHelper::logScan($conn, $admission['admission_id'], $logged_in_user_id, 'view');

$admission_id = $admission['admission_id'];

// Get current medicines
$medicines_query = "
    SELECT pm.*, CONCAT(u.first_name, ' ', u.last_name) as prescribed_by_name,
           (SELECT MAX(administered_at) FROM medicine_administration WHERE medicine_id = pm.medicine_id) as last_given
    FROM patient_medicines pm
    JOIN users u ON pm.prescribed_by = u.user_id
    WHERE pm.admission_id = ? AND pm.status = 'active'
    ORDER BY pm.created_at DESC
";
$stmt = $conn->prepare($medicines_query);
$stmt->bind_param("i", $admission_id);
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get active IVs/Drips
$ivs_query = "
    SELECT iv.*, CONCAT(u.first_name, ' ', u.last_name) as started_by_name
    FROM patient_ivs iv
    JOIN users u ON iv.started_by = u.user_id
    WHERE iv.admission_id = ? AND iv.status = 'running'
    ORDER BY iv.started_at DESC
";
$stmt = $conn->prepare($ivs_query);
$stmt->bind_param("i", $admission_id);
$stmt->execute();
$ivs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent test reports
$reports_query = "
    SELECT tr.*, CONCAT(u.first_name, ' ', u.last_name) as ordered_by_name
    FROM patient_test_reports tr
    JOIN users u ON tr.ordered_by = u.user_id
    WHERE tr.admission_id = ?
    ORDER BY tr.ordered_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($reports_query);
$stmt->bind_param("i", $admission_id);
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get doctor notes
$notes_query = "
    SELECT dn.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name, u.specialization
    FROM doctor_notes dn
    JOIN users u ON dn.doctor_id = u.user_id
    WHERE dn.admission_id = ?
    ORDER BY dn.created_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($notes_query);
$stmt->bind_param("i", $admission_id);
$stmt->execute();
$notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get upcoming tasks
$tasks_query = "
    SELECT ts.*, 
           CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
           CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name
    FROM treatment_schedule ts
    JOIN users u1 ON ts.created_by = u1.user_id
    LEFT JOIN users u2 ON ts.assigned_to = u2.user_id
    WHERE ts.admission_id = ? AND ts.status IN ('pending', 'in_progress')
    ORDER BY ts.scheduled_time ASC
    LIMIT 10
";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("i", $admission_id);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Status - <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="images/hosp_favicon.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            padding: 30px 15px;
            color: #334155;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 25px rgba(14, 84, 95, 0.04);
        }

        .patient-info {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .patient-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0e545f 0%, #00adb5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(0, 173, 181, 0.2);
        }

        .patient-details h1 {
            color: #0e545f;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .patient-meta {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .patient-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .patient-meta span i {
            color: #00adb5;
        }

        .refresh-btn {
            margin-left: auto;
            background: linear-gradient(135deg, #00adb5, #0e8389);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 173, 181, 0.2);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 173, 181, 0.3);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 28px;
            margin-bottom: 28px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 25px rgba(14, 84, 95, 0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(14, 84, 95, 0.08);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
        }

        .card-header i {
            font-size: 22px;
            color: #00adb5;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0e545f;
            flex: 1;
        }

        .card-header .badge {
            background: rgba(0, 173, 181, 0.1);
            color: #00adb5;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .medicine-item, .iv-item, .report-item, .note-item, .task-item {
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #00adb5;
            transition: all 0.2s ease;
        }

        .medicine-item:hover, .iv-item:hover, .report-item:hover, .note-item:hover, .task-item:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .medicine-item h3, .iv-item h3, .report-item h3 {
            color: #0e545f;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: capitalize;
        }

        .medicine-item p, .iv-item p, .report-item p, .note-item p, .task-item p {
            color: #475569;
            font-size: 14px;
            margin: 6px 0;
            line-height: 1.5;
        }

        .medicine-item strong, .iv-item strong, .report-item strong, .task-item strong {
            color: #0e545f;
            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .status-active { background: rgba(76, 175, 80, 0.1); color: #388e3c; }
        .status-running { background: rgba(3, 169, 244, 0.1); color: #0288d1; }
        .status-completed { background: rgba(76, 175, 80, 0.1); color: #388e3c; }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #ffa000; }
        .status-ordered { background: rgba(33, 150, 243, 0.1); color: #1976d2; }
        .status-urgent { background: rgba(244, 67, 54, 0.1); color: #d32f2f; margin-left: 6px; }

        .priority-critical { border-left-color: #ef4444; }
        .priority-high { border-left-color: #f97316; }
        .priority-medium { border-left-color: #eab308; }
        .priority-low { border-left-color: #22c55e; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 44px;
            margin-bottom: 12px;
            opacity: 0.4;
            color: #00adb5;
        }

        .vitals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .vital-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .vital-box:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .vital-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vital-value {
            font-size: 22px;
            font-weight: 700;
            color: #00adb5;
            margin-top: 6px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00adb5, #0e8389);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 173, 181, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 173, 181, 0.3);
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .patient-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .refresh-btn {
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }
        }

        .last-updated {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            margin-top: 28px;
            padding: 10px;
        }

        .time-badge {
            background: rgba(0, 173, 181, 0.1);
            color: #00adb5;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Patient Header -->
        <div class="header">
            <div class="patient-info">
                <div class="patient-avatar">
                    <?php echo strtoupper(substr($admission['first_name'], 0, 1) . substr($admission['last_name'], 0, 1)); ?>
                </div>
                <div class="patient-details">
                    <h1><?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></h1>
                    <div class="patient-meta">
                        <span><i class="fas fa-bed"></i> <?php echo htmlspecialchars($admission['ward_name'] . ' - ' . $admission['bed_number']); ?></span>
                        <span><i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($admission['doctor_name']); ?></span>
                        <span><i class="fas fa-calendar"></i> Admitted: <?php echo date('M d, Y', strtotime($admission['admission_date'])); ?></span>
                    </div>
                </div>
                <button class="refresh-btn" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid">
            <!-- Current Medicines -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-pills"></i>
                    <h2>Current Medicines</h2>
                    <span class="badge"><?php echo count($medicines); ?></span>
                </div>
                <?php if (count($medicines) > 0): ?>
                    <?php foreach ($medicines as $med): ?>
                        <div class="medicine-item">
                            <h3><?php echo htmlspecialchars($med['medicine_name']); ?></h3>
                            <p><strong>Dosage:</strong> <?php echo htmlspecialchars($med['dosage']); ?></p>
                            <p><strong>Frequency:</strong> <?php echo htmlspecialchars($med['frequency']); ?></p>
                            <p><strong>Route:</strong> <?php echo htmlspecialchars($med['route']); ?></p>
                            <?php if ($med['last_given']): ?>
                                <p><strong>Last Given:</strong> <?php echo date('M d, h:i A', strtotime($med['last_given'])); ?></p>
                            <?php endif; ?>
                            <?php if ($med['special_instructions']): ?>
                                <p><strong>Instructions:</strong> <?php echo htmlspecialchars($med['special_instructions']); ?></p>
                            <?php endif; ?>
                            <span class="status-badge status-active">Active</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-pills"></i>
                        <p>No active medicines</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- IV/Drips -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-syringe"></i>
                    <h2>IV / Drips</h2>
                    <span class="badge"><?php echo count($ivs); ?></span>
                </div>
                <?php if (count($ivs) > 0): ?>
                    <?php foreach ($ivs as $iv): ?>
                        <div class="iv-item">
                            <h3><?php echo htmlspecialchars($iv['fluid_type']); ?></h3>
                            <p><strong>Volume:</strong> <?php echo htmlspecialchars($iv['volume_ml']); ?> ml</p>
                            <p><strong>Flow Rate:</strong> <?php echo htmlspecialchars($iv['flow_rate']); ?></p>
                            <p><strong>Site:</strong> <?php echo htmlspecialchars($iv['site_location']); ?></p>
                            <p><strong>Started:</strong> <?php echo date('M d, h:i A', strtotime($iv['started_at'])); ?></p>
                            <?php if ($iv['expected_end_at']): ?>
                                <p><strong>Expected End:</strong> <?php echo date('M d, h:i A', strtotime($iv['expected_end_at'])); ?></p>
                            <?php endif; ?>
                            <span class="status-badge status-running">Running</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-syringe"></i>
                        <p>No active IV/Drips</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Test Reports & Next Steps -->
        <div class="grid">
            <!-- Test Reports -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-medical"></i>
                    <h2>Test Reports</h2>
                    <span class="badge"><?php echo count($reports); ?></span>
                </div>
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $report): ?>
                        <div class="report-item">
                            <h3><?php echo htmlspecialchars($report['test_name']); ?></h3>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($report['test_type']); ?></p>
                            <p><strong>Ordered:</strong> <?php echo date('M d, h:i A', strtotime($report['ordered_at'])); ?></p>
                            <?php if ($report['results']): ?>
                                <p><strong>Results:</strong> <?php echo htmlspecialchars($report['results']); ?></p>
                            <?php endif; ?>
                            <?php if ($report['findings']): ?>
                                <p><strong>Findings:</strong> <?php echo htmlspecialchars($report['findings']); ?></p>
                            <?php endif; ?>
                            <span class="status-badge status-<?php echo $report['status']; ?>">
                                <?php echo ucfirst($report['status']); ?>
                            </span>
                            <?php if ($report['priority'] == 'urgent' || $report['priority'] == 'stat'): ?>
                                <span class="status-badge status-urgent"><?php echo strtoupper($report['priority']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-medical"></i>
                        <p>No test reports</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- What's Next -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tasks"></i>
                    <h2>What's Next</h2>
                    <span class="badge"><?php echo count($tasks); ?></span>
                </div>
                <?php if (count($tasks) > 0): ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-item priority-<?php echo $task['priority']; ?>">
                            <p><strong><?php echo htmlspecialchars($task['task_description']); ?></strong></p>
                            <p><i class="fas fa-clock"></i> <?php echo date('M d, h:i A', strtotime($task['scheduled_time'])); ?></p>
                            <?php if ($task['assigned_to_name']): ?>
                                <p><i class="fas fa-user"></i> Assigned to: <?php echo htmlspecialchars($task['assigned_to_name']); ?></p>
                            <?php endif; ?>
                            <span class="status-badge status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <p>No upcoming tasks</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Doctor Notes -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-notes-medical"></i>
                <h2>Doctor Notes & Checkup History</h2>
                <span class="badge"><?php echo count($notes); ?></span>
            </div>
            <?php if (count($notes) > 0): ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-item">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <div>
                                <h3 style="color: #00adb5; font-size: 16px; margin-bottom: 5px;">
                                    Dr. <?php echo htmlspecialchars($note['doctor_name']); ?>
                                </h3>
                                <p style="font-size: 12px; color: #999;">
                                    <?php echo htmlspecialchars($note['specialization']); ?> • 
                                    <?php echo date('M d, Y h:i A', strtotime($note['created_at'])); ?>
                                </p>
                            </div>
                            <span class="status-badge" style="background: #e3f2fd; color: #0d47a1;">
                                <?php echo ucfirst($note['note_type']); ?>
                            </span>
                        </div>
                        
                        <?php if ($note['vitals_bp'] || $note['vitals_pulse'] || $note['vitals_temp'] || $note['vitals_spo2']): ?>
                            <div class="vitals-grid">
                                <?php if ($note['vitals_bp']): ?>
                                    <div class="vital-box">
                                        <div class="vital-label">BP</div>
                                        <div class="vital-value"><?php echo htmlspecialchars($note['vitals_bp']); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($note['vitals_pulse']): ?>
                                    <div class="vital-box">
                                        <div class="vital-label">Pulse</div>
                                        <div class="vital-value"><?php echo htmlspecialchars($note['vitals_pulse']); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($note['vitals_temp']): ?>
                                    <div class="vital-box">
                                        <div class="vital-label">Temp</div>
                                        <div class="vital-value"><?php echo htmlspecialchars($note['vitals_temp']); ?>°</div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($note['vitals_spo2']): ?>
                                    <div class="vital-box">
                                        <div class="vital-label">SpO2</div>
                                        <div class="vital-value"><?php echo htmlspecialchars($note['vitals_spo2']); ?>%</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($note['chief_complaint']): ?>
                            <p style="margin-top: 15px;"><strong style="color: #00adb5;">Chief Complaint:</strong><br><?php echo nl2br(htmlspecialchars($note['chief_complaint'])); ?></p>
                        <?php endif; ?>
                        <?php if ($note['examination_findings']): ?>
                            <p style="margin-top: 10px;"><strong style="color: #00adb5;">Examination:</strong><br><?php echo nl2br(htmlspecialchars($note['examination_findings'])); ?></p>
                        <?php endif; ?>
                        <?php if ($note['diagnosis']): ?>
                            <p style="margin-top: 10px;"><strong style="color: #00adb5;">Diagnosis:</strong><br><?php echo nl2br(htmlspecialchars($note['diagnosis'])); ?></p>
                        <?php endif; ?>
                        <?php if ($note['treatment_plan']): ?>
                            <p style="margin-top: 10px;"><strong style="color: #00adb5;">Treatment Plan:</strong><br><?php echo nl2br(htmlspecialchars($note['treatment_plan'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-notes-medical"></i>
                    <p>No doctor notes yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <?php if ($is_staff_or_doctor): ?>
        <div class="card">
            <h2 style="color: #00adb5; margin-bottom: 20px;">
                <i class="fas fa-user-md"></i> Medical Actions
                <span style="font-size: 14px; color: #666; font-weight: normal;">(Staff/Doctor Only)</span>
            </h2>
            <div class="action-buttons">
                <a href="patient-update.php?token=<?php echo urlencode($token); ?>&action=medicine" class="btn btn-primary">
                    <i class="fas fa-pills"></i> Add Medicine
                </a>
                <a href="patient-update.php?token=<?php echo urlencode($token); ?>&action=iv" class="btn btn-primary">
                    <i class="fas fa-syringe"></i> Add IV/Drip
                </a>
                <a href="patient-update.php?token=<?php echo urlencode($token); ?>&action=test" class="btn btn-primary">
                    <i class="fas fa-file-medical"></i> Order Test
                </a>
                <a href="patient-update.php?token=<?php echo urlencode($token); ?>&action=note" class="btn btn-primary">
                    <i class="fas fa-notes-medical"></i> Add Note
                </a>
                <a href="patient-update.php?token=<?php echo urlencode($token); ?>&action=vitals" class="btn btn-primary">
                    <i class="fas fa-heartbeat"></i> Record Vitals
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <p style="margin: 0; color: #856404;">
                <i class="fas fa-lock"></i> <strong>Medical actions are restricted.</strong> 
                Please login as doctor or staff to add medicines, drips, and other medical details.
            </p>
        </div>
        <?php endif; ?>

        <div class="last-updated">
            <i class="fas fa-info-circle"></i> Last updated: <?php echo date('M d, Y h:i:s A'); ?>
            <span class="time-badge">Auto-refresh recommended every 5 minutes</span>
        </div>
    </div>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
