<?php
session_start();
require_once 'php/db.php';
require_once 'php/patient_qr_helper.php';

// Check if user is logged in and authorized (doctor, staff, nurse, or admin)
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['doctor', 'staff', 'nurse', 'admin'])) {
    header("Location: sign_new.html?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : 'medicine';

if (empty($token)) {
    die("Error: No QR code token provided");
}

// Get admission details
$admission = PatientQRHelper::getAdmissionFromToken($conn, $token);

if (!$admission) {
    die("Error: Invalid or expired QR code");
}

$admission_id = $admission['admission_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action_type = $_POST['action_type'];
    
    try {
        if ($action_type == 'medicine') {
            $stmt = $conn->prepare("
                INSERT INTO patient_medicines 
                (admission_id, medicine_name, dosage, frequency, route, start_date, prescribed_by, special_instructions, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param("isssssis", 
                $admission_id,
                $_POST['medicine_name'],
                $_POST['dosage'],
                $_POST['frequency'],
                $_POST['route'],
                $_POST['start_date'],
                $_SESSION['user_id'],
                $_POST['instructions']
            );
            $stmt->execute();
            $stmt->close();
            
            PatientQRHelper::logScan($conn, $admission_id, $_SESSION['user_id'], 'add_medicine');
            $message = "Medicine added successfully!";
            
        } elseif ($action_type == 'iv') {
            $stmt = $conn->prepare("
                INSERT INTO patient_ivs
                (admission_id, fluid_type, volume_ml, flow_rate, started_at, expected_end_at, started_by, site_location, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'running')
            ");
            $stmt->bind_param("isissisis",
                $admission_id,
                $_POST['fluid_type'],
                $_POST['volume_ml'],
                $_POST['flow_rate'],
                $_POST['started_at'],
                $_POST['expected_end_at'],
                $_SESSION['user_id'],
                $_POST['site_location'],
                $_POST['notes']
            );
            $stmt->execute();
            $stmt->close();
            
            PatientQRHelper::logScan($conn, $admission_id, $_SESSION['user_id'], 'add_iv');
            $message = "IV/Drip added successfully!";
            
        } elseif ($action_type == 'test') {
            $stmt = $conn->prepare("
                INSERT INTO patient_test_reports
                (admission_id, test_type, test_name, ordered_by, ordered_at, priority, status)
                VALUES (?, ?, ?, ?, ?, ?, 'ordered')
            ");
            $stmt->bind_param("ississ",
                $admission_id,
                $_POST['test_type'],
                $_POST['test_name'],
                $_SESSION['user_id'],
                $_POST['ordered_at'],
                $_POST['priority']
            );
            $stmt->execute();
            $stmt->close();
            
            PatientQRHelper::logScan($conn, $admission_id, $_SESSION['user_id'], 'order_test');
            $message = "Test ordered successfully!";
            
        } elseif ($action_type == 'note') {
            $stmt = $conn->prepare("
                INSERT INTO doctor_notes
                (admission_id, doctor_id, note_type, chief_complaint, examination_findings, diagnosis, treatment_plan, 
                 vitals_bp, vitals_pulse, vitals_temp, vitals_spo2, vitals_respiratory_rate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iissssssssss",
                $admission_id,
                $_SESSION['user_id'],
                $_POST['note_type'],
                $_POST['chief_complaint'],
                $_POST['examination_findings'],
                $_POST['diagnosis'],
                $_POST['treatment_plan'],
                $_POST['vitals_bp'],
                $_POST['vitals_pulse'],
                $_POST['vitals_temp'],
                $_POST['vitals_spo2'],
                $_POST['vitals_respiratory_rate']
            );
            $stmt->execute();
            $stmt->close();
            
            PatientQRHelper::logScan($conn, $admission_id, $_SESSION['user_id'], 'add_note');
            $message = "Doctor note added successfully!";
            
        } elseif ($action_type == 'task') {
            $stmt = $conn->prepare("
                INSERT INTO treatment_schedule
                (admission_id, task_type, task_description, scheduled_time, assigned_to, created_by, priority, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
            
            $stmt->bind_param("isssiis",
                $admission_id,
                $_POST['task_type'],
                $_POST['task_description'],
                $_POST['scheduled_time'],
                $assigned_to,
                $_SESSION['user_id'],
                $_POST['priority']
            );
            $stmt->execute();
            $stmt->close();
            
            PatientQRHelper::logScan($conn, $admission_id, $_SESSION['user_id'], 'schedule_task');
            $message = "Task scheduled successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get list of doctors and nurses for assignment
$staff_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as name, role FROM users WHERE role IN ('doctor', 'nurse') AND status = 'active' ORDER BY role, name";
$staff_result = $conn->query($staff_query);
$staff_list = $staff_result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Patient - <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="images/hosp_favicon.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 15px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .header h1 {
            color: #333;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab {
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .tab.active {
            background: #00adb5;
            color: white;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00adb5;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            background: #00adb5;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #0e545f;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-injured"></i> <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></h1>
            <p><i class="fas fa-bed"></i> <?php echo htmlspecialchars($admission['ward_name'] . ' - ' . $admission['bed_number']); ?> | 
               <i class="fas fa-calendar"></i> Admitted: <?php echo date('M d, Y', strtotime($admission['admission_date'])); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab <?php echo $action == 'medicine' ? 'active' : ''; ?>" onclick="location.href='?token=<?php echo urlencode($token); ?>&action=medicine'">
                <i class="fas fa-pills"></i> Add Medicine
            </div>
            <div class="tab <?php echo $action == 'iv' ? 'active' : ''; ?>" onclick="location.href='?token=<?php echo urlencode($token); ?>&action=iv'">
                <i class="fas fa-syringe"></i> Add IV/Drip
            </div>
            <div class="tab <?php echo $action == 'test' ? 'active' : ''; ?>" onclick="location.href='?token=<?php echo urlencode($token); ?>&action=test'">
                <i class="fas fa-file-medical"></i> Order Test
            </div>
            <div class="tab <?php echo $action == 'note' ? 'active' : ''; ?>" onclick="location.href='?token=<?php echo urlencode($token); ?>&action=note'">
                <i class="fas fa-notes-medical"></i> Add Note
            </div>
            <div class="tab <?php echo $action == 'task' ? 'active' : ''; ?>" onclick="location.href='?token=<?php echo urlencode($token); ?>&action=task'">
                <i class="fas fa-tasks"></i> Schedule Task
            </div>
        </div>

        <div class="form-container">
            <?php if ($action == 'medicine'): ?>
                <h2 style="margin-bottom: 20px; color: #333;"><i class="fas fa-pills"></i> Add Medicine</h2>
                <form method="POST">
                    <input type="hidden" name="action_type" value="medicine">
                    
                    <div class="form-group">
                        <label>Medicine Name *</label>
                        <input type="text" name="medicine_name" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Dosage *</label>
                            <input type="text" name="dosage" placeholder="e.g., 500mg" required>
                        </div>
                        <div class="form-group">
                            <label>Frequency *</label>
                            <input type="text" name="frequency" placeholder="e.g., Every 6 hours" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Route *</label>
                            <select name="route" required>
                                <option value="Oral">Oral</option>
                                <option value="IV">Intravenous (IV)</option>
                                <option value="IM">Intramuscular (IM)</option>
                                <option value="SC">Subcutaneous (SC)</option>
                                <option value="Topical">Topical</option>
                                <option value="Inhalation">Inhalation</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Start Date & Time *</label>
                            <input type="datetime-local" name="start_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Special Instructions</label>
                        <textarea name="instructions" placeholder="Any special instructions..."></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn"><i class="fas fa-check"></i> Add Medicine</button>
                        <a href="patient-status.php?token=<?php echo urlencode($token); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>

            <?php elseif ($action == 'iv'): ?>
                <h2 style="margin-bottom: 20px; color: #333;"><i class="fas fa-syringe"></i> Add IV/Drip</h2>
                <form method="POST">
                    <input type="hidden" name="action_type" value="iv">
                    
                    <div class="form-group">
                        <label>Fluid Type *</label>
                        <input type="text" name="fluid_type" placeholder="e.g., Normal Saline, Ringer's Lactate" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Volume (ml) *</label>
                            <input type="number" name="volume_ml" placeholder="e.g., 500" required>
                        </div>
                        <div class="form-group">
                            <label>Flow Rate *</label>
                            <input type="text" name="flow_rate" placeholder="e.g., 125 ml/hour" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Started At *</label>
                            <input type="datetime-local" name="started_at" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Expected End At</label>
                            <input type="datetime-local" name="expected_end_at">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Site Location *</label>
                        <input type="text" name="site_location" placeholder="e.g., Left hand, Right arm" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" placeholder="Any additional notes..."></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn"><i class="fas fa-check"></i> Add IV/Drip</button>
                        <a href="patient-status.php?token=<?php echo urlencode($token); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>

            <?php elseif ($action == 'test'): ?>
                <h2 style="margin-bottom: 20px; color: #333;"><i class="fas fa-file-medical"></i> Order Test</h2>
                <form method="POST">
                    <input type="hidden" name="action_type" value="test">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Type *</label>
                            <select name="test_type" required>
                                <option value="Blood Test">Blood Test</option>
                                <option value="Urine Test">Urine Test</option>
                                <option value="X-Ray">X-Ray</option>
                                <option value="CT Scan">CT Scan</option>
                                <option value="MRI">MRI</option>
                                <option value="Ultrasound">Ultrasound</option>
                                <option value="ECG">ECG</option>
                                <option value="Echo">Echocardiogram</option>
                                <option value="Biopsy">Biopsy</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority *</label>
                            <select name="priority" required>
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT (Immediate)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Name *</label>
                        <input type="text" name="test_name" placeholder="e.g., Complete Blood Count (CBC)" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ordered At *</label>
                        <input type="datetime-local" name="ordered_at" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn"><i class="fas fa-check"></i> Order Test</button>
                        <a href="patient-status.php?token=<?php echo urlencode($token); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>

            <?php elseif ($action == 'note'): ?>
                <h2 style="margin-bottom: 20px; color: #333;"><i class="fas fa-notes-medical"></i> Add Doctor Note</h2>
                <form method="POST">
                    <input type="hidden" name="action_type" value="note">
                    
                    <div class="form-group">
                        <label>Note Type *</label>
                        <select name="note_type" required>
                            <option value="checkup">Checkup</option>
                            <option value="progress">Progress Note</option>
                            <option value="diagnosis">Diagnosis</option>
                            <option value="consultation">Consultation</option>
                            <option value="discharge">Discharge Summary</option>
                        </select>
                    </div>
                    
                    <h3 style="margin: 25px 0 15px 0; color: #00adb5; font-size: 16px;">Vital Signs</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Pressure</label>
                            <input type="text" name="vitals_bp" placeholder="e.g., 120/80">
                        </div>
                        <div class="form-group">
                            <label>Pulse (bpm)</label>
                            <input type="text" name="vitals_pulse" placeholder="e.g., 72">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Temperature (°F)</label>
                            <input type="text" name="vitals_temp" placeholder="e.g., 98.6">
                        </div>
                        <div class="form-group">
                            <label>SpO2 (%)</label>
                            <input type="text" name="vitals_spo2" placeholder="e.g., 98">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Respiratory Rate</label>
                        <input type="text" name="vitals_respiratory_rate" placeholder="e.g., 16">
                    </div>
                    
                    <h3 style="margin: 25px 0 15px 0; color: #00adb5; font-size: 16px;">Clinical Notes</h3>
                    
                    <div class="form-group">
                        <label>Chief Complaint</label>
                        <textarea name="chief_complaint" placeholder="Patient's main complaint..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Examination Findings</label>
                        <textarea name="examination_findings" placeholder="Physical examination findings..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Diagnosis</label>
                        <textarea name="diagnosis" placeholder="Clinical diagnosis..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Treatment Plan</label>
                        <textarea name="treatment_plan" placeholder="Recommended treatment plan..."></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn"><i class="fas fa-check"></i> Add Note</button>
                        <a href="patient-status.php?token=<?php echo urlencode($token); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>

            <?php elseif ($action == 'task'): ?>
                <h2 style="margin-bottom: 20px; color: #333;"><i class="fas fa-tasks"></i> Schedule Task</h2>
                <form method="POST">
                    <input type="hidden" name="action_type" value="task">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Task Type *</label>
                            <select name="task_type" required>
                                <option value="medicine">Medicine Administration</option>
                                <option value="procedure">Procedure</option>
                                <option value="test">Test/Investigation</option>
                                <option value="checkup">Checkup</option>
                                <option value="monitoring">Monitoring</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority *</label>
                            <select name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Task Description *</label>
                        <textarea name="task_description" placeholder="Describe the task to be performed..." required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Scheduled Time *</label>
                            <input type="datetime-local" name="scheduled_time" required>
                        </div>
                        <div class="form-group">
                            <label>Assign To</label>
                            <select name="assigned_to">
                                <option value="">-- Not assigned --</option>
                                <?php foreach ($staff_list as $staff): ?>
                                    <option value="<?php echo $staff['user_id']; ?>">
                                        <?php echo htmlspecialchars($staff['name']) . ' (' . ucfirst($staff['role']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn"><i class="fas fa-check"></i> Schedule Task</button>
                        <a href="patient-status.php?token=<?php echo urlencode($token); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
