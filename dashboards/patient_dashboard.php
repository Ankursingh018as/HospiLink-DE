<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'patient') {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get patient's appointments
$appointmentsQuery = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name, u.specialization
                      FROM appointments a
                      LEFT JOIN users u ON a.doctor_id = u.user_id
                      WHERE a.patient_id = ?
                      ORDER BY a.appointment_date DESC, a.priority_score DESC";
$stmt = $conn->prepare($appointmentsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get medical history
$historyQuery = "SELECT mh.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                 FROM medical_history mh
                 LEFT JOIN users u ON mh.doctor_id = u.user_id
                 WHERE mh.patient_id = ?
                 ORDER BY mh.visit_date DESC
                 LIMIT 5";
$histStmt = $conn->prepare($historyQuery);
$histStmt->bind_param("i", $user_id);
$histStmt->execute();
$history = $histStmt->get_result();

// Get all medical history for paginated records tab
$allHistoryQuery = "SELECT mh.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                 FROM medical_history mh
                 LEFT JOIN users u ON mh.doctor_id = u.user_id
                 WHERE mh.patient_id = ?
                 ORDER BY mh.visit_date DESC LIMIT 100";
$allHistoryStmt = $conn->prepare($allHistoryQuery);
$allHistoryStmt->bind_param("i", $user_id);
$allHistoryStmt->execute();
$allHistory = $allHistoryStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/doctor-dashboard-enhanced.css">
    <link rel="icon" href="../images/hosp_favicon.png" type="image/png">
    
    <style>
        /* SPA Transitions and Active state styling */
        .content-section {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .content-section.active-section {
            display: block;
            opacity: 1;
        }
        
        /* Modal Glassmorphic Overlays */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(14, 84, 95, 0.4);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        .modal-box {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            padding: 30px;
            animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        @keyframes modalSlideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h3 {
            margin: 0;
            color: #0e545f;
            font-size: 22px;
            font-weight: 700;
        }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.2s;
        }
        .modal-close-btn:hover {
            color: #f44336;
        }

        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-input-wrapper {
            position: relative;
            flex: 1;
            max-width: 350px;
        }
        .search-input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .search-input-wrapper input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-input-wrapper input:focus {
            border-color: #00adb5;
            box-shadow: 0 0 0 3px rgba(0, 173, 181, 0.15);
        }
        .filter-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-select {
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .filter-select:focus {
            border-color: #00adb5;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        @media(max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-group-full {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #00adb5;
        }

        .btn-small {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            background: #f3f4f6;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-small:hover {
            background: #e5e7eb;
        }
        .btn-small.danger:hover {
            background: #fee2e2;
            color: #ef4444;
        }
        .btn-small.primary {
            background: #00adb5;
            color: white;
        }
        .btn-small.primary:hover {
            background: #089196;
        }
        .btn-primary {
            background: #00adb5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #089196;
        }

        .pagination-container {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/logo.png" alt="HospiLink">
            </div>
            <nav class="sidebar-nav">
                <a href="#overview" class="nav-item active" id="nav-overview">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#appointments" class="nav-item" id="nav-appointments">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Appointments</span>
                </a>
                <a href="#records" class="nav-item" id="nav-records">
                    <i class="fas fa-file-medical"></i>
                    <span>Medical Records</span>
                </a>
                <a href="../appointment.html" class="nav-item" id="nav-book">
                    <i class="fas fa-plus-circle"></i>
                    <span>Book Appointment</span>
                </a>
                <a href="../beds.html" class="nav-item" id="nav-beds">
                    <i class="fas fa-bed"></i>
                    <span>Bed Availability</span>
                </a>
                <a href="#profile" class="nav-item" id="nav-profile">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="../php/auth.php?logout=true" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div>
                    <h1 id="header-title">Patient Dashboard</h1>
                    <p class="subtitle" id="header-welcome">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</p>
                </div>
                <div class="user-info">
                    <span class="user-role"><i class="fas fa-user"></i> Patient</span>
                </div>
            </header>

            <!-- Overview Section -->
            <section id="overview" class="content-section">
                <div class="section-header">
                    <h2>Dashboard Overview</h2>
                    <div class="live-indicator">
                        <span class="pulse-dot"></span>
                        <span>Live Updates</span>
                    </div>
                </div>
                <p class="section-subtitle"><?php echo date('l, F d, Y'); ?></p>
                
                <div class="stats-grid-enhanced">
                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #4caf50, #388e3c);">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-badge" style="background: rgba(76, 175, 80, 0.1); color: #4caf50;">Confirmed</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php 
                                $countQuery = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = 'confirmed'";
                                $countStmt = $conn->prepare($countQuery);
                                $countStmt->bind_param("i", $user_id);
                                $countStmt->execute();
                                $result = $countStmt->get_result();
                                echo $result->fetch_assoc()['count'];
                            ?></div>
                            <div class="stat-label">Confirmed Appointments</div>
                            <div class="stat-trend" style="color: #4caf50;">
                                <i class="fas fa-check-circle"></i>
                                <span>Active</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced" style="background: linear-gradient(135deg, #ff9800, #f57c00);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-badge warning">Pending</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php 
                                $pendingQuery = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = 'pending'";
                                $pendStmt = $conn->prepare($pendingQuery);
                                $pendStmt->bind_param("i", $user_id);
                                $pendStmt->execute();
                                $result = $pendStmt->get_result();
                                echo $result->fetch_assoc()['count'];
                            ?></div>
                            <div class="stat-label">Pending Appointments</div>
                            <div class="stat-trend orange">
                                <i class="fas fa-hourglass-half"></i>
                                <span>Awaiting</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced blue">
                                <i class="fas fa-file-medical"></i>
                            </div>
                            <div class="stat-badge">Records</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php 
                                $histCount = "SELECT COUNT(*) as count FROM medical_history WHERE patient_id = ?";
                                $histStmt2 = $conn->prepare($histCount);
                                $histStmt2->bind_param("i", $user_id);
                                $histStmt2->execute();
                                $result = $histStmt2->get_result();
                                echo $result->fetch_assoc()['count'];
                            ?></div>
                            <div class="stat-label">Medical Records</div>
                            <div class="stat-trend">
                                <i class="fas fa-notes-medical"></i>
                                <span>Available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Appointments Section -->
            <section id="appointments" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-alt"></i> My Appointments Queue</h2>
                </div>
                <p class="section-subtitle">View and manage your scheduled consultations and medical slots</p>

                <div class="table-controls" style="margin-top: 20px;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="aptSearch" placeholder="Search by doctor name, specialty, symptoms..." onkeyup="filterAppointments()">
                    </div>
                    <div class="filter-controls">
                        <select id="aptStatusFilter" class="filter-select" onchange="filterAppointments()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="appointments-container" id="appointmentsContainer" style="margin-top: 20px;">
                    <?php 
                    if ($appointments->num_rows > 0):
                        $appointments->data_seek(0);
                        while($apt = $appointments->fetch_assoc()): 
                    ?>
                    <div class="appointment-card-enhanced patient-apt-row" 
                         data-status="<?php echo $apt['status']; ?>"
                         data-apt-id="<?php echo $apt['appointment_id']; ?>"
                         data-date="<?php echo date('d M Y', strtotime($apt['appointment_date'])); ?>"
                         data-time="<?php echo date('h:i A', strtotime($apt['appointment_time'])); ?>"
                         data-priority="<?php echo strtoupper($apt['priority_level']); ?>"
                         data-doctor="<?php echo htmlspecialchars(strtolower($apt['doctor_name'] ?: 'Not Assigned')); ?>"
                         data-specialty="<?php echo htmlspecialchars(strtolower($apt['specialization'] ?: 'General')); ?>"
                         data-symptoms="<?php echo htmlspecialchars(strtolower($apt['symptoms'])); ?>"
                         data-notes="<?php echo htmlspecialchars($apt['notes'] ?? 'No additional notes'); ?>">
                        <div class="appointment-header">
                            <div class="appointment-id">#<?php echo $apt['appointment_id']; ?></div>
                            <div class="priority-badge-enhanced <?php echo $apt['priority_level']; ?>">
                                <?php echo strtoupper($apt['priority_level']); ?>
                            </div>
                        </div>
                        
                        <div class="appointment-doctor">
                            <div class="doctor-avatar">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="doctor-info">
                                <div class="doctor-name"><?php echo htmlspecialchars($apt['doctor_name'] ?: 'Not Assigned'); ?></div>
                                <div class="doctor-specialty"><?php echo htmlspecialchars($apt['specialization'] ?: 'General'); ?></div>
                            </div>
                        </div>
                        
                        <div class="appointment-details">
                            <div class="detail-row">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="appointment-symptoms">
                            <strong>Symptoms:</strong>
                            <p><?php echo htmlspecialchars(substr($apt['symptoms'], 0, 80)); ?><?php echo strlen($apt['symptoms']) > 80 ? '...' : ''; ?></p>
                        </div>
                        
                        <div class="appointment-footer">
                            <span class="status-badge-enhanced <?php echo $apt['status']; ?>">
                                <?php echo ucfirst($apt['status']); ?>
                            </span>
                            <div class="appointment-actions">
                                <button class="action-btn primary" onclick="viewDetails(<?php echo $apt['appointment_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if ($apt['status'] !== 'cancelled' && $apt['status'] !== 'completed'): ?>
                                <button class="action-btn warning" onclick="rescheduleAppointment(<?php echo $apt['appointment_id']; ?>, '<?php echo $apt['appointment_date']; ?>', '<?php echo $apt['appointment_time']; ?>')">
                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                </button>
                                <button class="action-btn danger" onclick="cancelAppointment(<?php echo $apt['appointment_id']; ?>)">
                                    <i class="fas fa-times-circle"></i> Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <div class="empty-state">
                        <p>No appointments found. Book your first appointment!</p>
                        <a href="../appointment.html" class="action-btn primary">
                            <i class="fas fa-plus-circle"></i> Book Appointment
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="aptShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMoreAppointments()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Medical Records Section -->
            <section id="records" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-file-medical"></i> My Medical Records</h2>
                </div>
                <p class="section-subtitle">Historical clinical logs, diagnoses, and physician reviews</p>

                <div class="table-controls" style="margin-top: 20px;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="recordSearch" placeholder="Search by diagnosis, treatment, doctor..." onkeyup="filterRecords()">
                    </div>
                </div>

                <div class="records-table-container" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px;">
                    <table style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead style="background:#f9fafb; border-bottom:1px solid #eee;">
                            <tr>
                                <th style="padding:15px; font-weight:600; color:#374151;">Date</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Diagnosis</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Doctor</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Treatment</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Notes / Description</th>
                            </tr>
                        </thead>
                        <tbody id="recordsTableBody">
                            <?php if ($allHistory && $allHistory->num_rows > 0): 
                                $allHistory->data_seek(0);
                                while ($rec = $allHistory->fetch_assoc()): ?>
                                <tr class="record-row" 
                                    data-disease="<?php echo htmlspecialchars(strtolower($rec['diagnosis'] ?? '')); ?>"
                                    data-doctor="<?php echo htmlspecialchars(strtolower($rec['doctor_name'] ?? '')); ?>"
                                    data-treatment="<?php echo htmlspecialchars(strtolower($rec['treatment_plan'] ?? '')); ?>"
                                    data-notes="<?php echo htmlspecialchars(strtolower($rec['notes'] ?? '')); ?>">
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><?php echo date('d M Y', strtotime($rec['visit_date'])); ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><strong><?php echo htmlspecialchars($rec['diagnosis']); ?></strong></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><i class="fas fa-user-md"></i> <?php echo htmlspecialchars($rec['doctor_name'] ?: 'Not specified'); ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><?php echo htmlspecialchars($rec['treatment_plan'] ?: 'None'); ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer;" onclick="alert('Clinical Notes:\n<?php echo htmlspecialchars($rec['notes']); ?>')"><?php echo htmlspecialchars($rec['notes'] ?: 'No notes'); ?></td>
                                </tr>
                                <?php endwhile; 
                            else: ?>
                                <tr>
                                    <td colspan="5" style="padding:30px; text-align:center; color:#9ca3af;">No medical history records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="recordsShowMoreContainer" class="pagination-container" style="display: none; text-align: center; margin-top: 20px;">
                    <button class="btn-small primary" onclick="showMoreRecords()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Edit Profile Section -->
            <section id="profile" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                </div>
                <p class="section-subtitle">Update your personal medical profile, contact, and account credentials</p>
                
                <div class="profile-container" style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px;">
                    <form id="profileEditForm" class="profile-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="prof_first_name"><i class="fas fa-user"></i> First Name</label>
                                <input type="text" id="prof_first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="prof_last_name"><i class="fas fa-user"></i> Last Name</label>
                                <input type="text" id="prof_last_name" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label for="prof_email"><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" id="prof_email" name="email" readonly style="background:#f3f4f6; cursor:not-allowed; opacity:0.8;">
                            </div>
                            <div class="form-group">
                                <label for="prof_phone"><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="tel" id="prof_phone" name="phone">
                            </div>
                            <div class="form-group">
                                <label for="prof_age"><i class="fas fa-calendar-day"></i> Age</label>
                                <input type="number" id="prof_age" name="age" min="1" max="120">
                            </div>
                            <div class="form-group">
                                <label for="prof_gender"><i class="fas fa-venus-mars"></i> Gender</label>
                                <select id="prof_gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group form-group-full">
                                <label for="prof_address"><i class="fas fa-map-marker-alt"></i> Address</label>
                                <textarea id="prof_address" name="address" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="form-divider" style="margin:25px 0; border-top:1px solid #e5e7eb; padding-top:20px;">
                            <span style="font-weight:600; color:#0e545f;">Change Password (Optional)</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="prof_current_password"><i class="fas fa-lock"></i> Current Password</label>
                                <input type="password" id="prof_current_password" name="current_password" placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label for="prof_new_password"><i class="fas fa-key"></i> New Password</label>
                                <input type="password" id="prof_new_password" name="new_password" placeholder="Enter new password">
                            </div>
                        </div>

                        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                        </div>
                    </form>
                    <div id="profileFormFeedback" style="margin-top: 15px; font-weight: 600; text-align: center;"></div>
                </div>
            </section>
        </main>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-alt" style="color: #00adb5;"></i> Reschedule Appointment</h3>
                <button onclick="closeRescheduleModal()" class="modal-close-btn"><i class="ri-close-line"></i></button>
            </div>
            <form id="rescheduleForm">
                <input type="hidden" id="reschedule_appointment_id" name="appointment_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="new_date"><i class="fas fa-calendar"></i> New Date</label>
                    <input type="date" id="new_date" name="new_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="new_time"><i class="fas fa-clock"></i> New Time</label>
                    <input type="time" id="new_time" name="new_time" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="reschedule_reason"><i class="fas fa-comment"></i> Reason for Rescheduling</label>
                    <textarea id="reschedule_reason" name="reason" rows="3" placeholder="Please provide a reason..."></textarea>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-small" onclick="closeRescheduleModal()">Cancel</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Confirm Reschedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Cancel Appointment</h3>
                <button onclick="closeCancelModal()" class="modal-close-btn"><i class="ri-close-line"></i></button>
            </div>
            <p style="margin-bottom: 20px; font-size: 15px; color: #4b5563;">Are you sure you want to cancel this appointment? This action cannot be undone.</p>
            <form id="cancelForm">
                <input type="hidden" id="cancel_appointment_id" name="appointment_id">
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="cancel_reason"><i class="fas fa-comment"></i> Reason for Cancellation</label>
                    <textarea id="cancel_reason" name="reason" rows="3" placeholder="Please provide a reason (optional)..."></textarea>
                </div>
                
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-small" onclick="closeCancelModal()">No, Keep It</button>
                    <button type="submit" class="btn-primary" style="background:#ef4444;"><i class="fas fa-times-circle"></i> Yes, Cancel Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewDetailsModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-file-medical-alt" style="color: #00adb5;"></i> Appointment Details</h3>
                <button onclick="closeViewDetailsModal()" class="modal-close-btn"><i class="ri-close-line"></i></button>
            </div>
            
            <div class="detail-section" style="margin-bottom:20px; padding:15px; background:#f9fafb; border-radius:8px; border-left:4px solid #00adb5;">
                <h4 style="margin:0 0 10px 0; color:#0e545f; font-size:16px;">Basic Information</h4>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:10px; font-size:14px;">
                    <div><strong>Appointment ID:</strong> <span id="detail_apt_id">-</span></div>
                    <div><strong>Date:</strong> <span id="detail_date">-</span></div>
                    <div><strong>Time:</strong> <span id="detail_time">-</span></div>
                    <div><strong>Priority:</strong> <span id="detail_priority">-</span></div>
                    <div><strong>Status:</strong> <span id="detail_status">-</span></div>
                </div>
            </div>

            <div class="detail-section" style="margin-bottom:20px; padding:15px; background:#f9fafb; border-radius:8px; border-left:4px solid #9c27b0;">
                <h4 style="margin:0 0 10px 0; color:#0e545f; font-size:16px;">Doctor Information</h4>
                <div style="font-size:14px;">
                    <div style="margin-bottom:5px;"><strong>Doctor:</strong> <span id="detail_doctor">-</span></div>
                    <div><strong>Specialty:</strong> <span id="detail_specialty">-</span></div>
                </div>
            </div>

            <div class="detail-section" style="margin-bottom:20px; padding:15px; background:#f9fafb; border-radius:8px; border-left:4px solid #ff9800;">
                <h4 style="margin:0 0 10px 0; color:#0e545f; font-size:16px;">Medical Details</h4>
                <div style="font-size:14px;">
                    <div style="margin-bottom:10px;"><strong>Symptoms:</strong><p id="detail_symptoms" style="margin:5px 0 0 0; background:white; padding:10px; border-radius:6px; border:1px solid #eee;"></p></div>
                    <div><strong>Notes:</strong><p id="detail_notes" style="margin:5px 0 0 0; background:white; padding:10px; border-radius:6px; border:1px solid #eee;"></p></div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="button" class="btn-small" onclick="closeViewDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // PAGINATION & VISIBILITY STATE LIMITS
        let appointmentsLimit = 15;
        let recordsLimit = 15;

        let lastAptQuery = '';
        let lastAptStatus = '';

        let lastRecordQuery = '';

        // SPA ROUTING
        function showSection(targetId) {
            if (!targetId || targetId === '#' || targetId === '') targetId = '#overview';
            
            // Check active nav-item
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
                const href = item.getAttribute('href');
                if (href === targetId) {
                    item.classList.add('active');
                } else if (href.startsWith('#')) {
                    item.classList.remove('active');
                }
            });

            // Toggle sections
            document.querySelectorAll('.content-section').forEach(sec => {
                if ('#' + sec.id === targetId) {
                    sec.classList.add('active-section');
                } else {
                    sec.classList.remove('active-section');
                }
            });

            // Set Title Header
            const titleMap = {
                '#overview': 'Patient Dashboard Overview',
                '#appointments': 'My Appointments Queue',
                '#records': 'My Medical Records',
                '#profile': 'Manage Profile'
            };
            document.getElementById('header-title').textContent = titleMap[targetId] || 'Patient Panel';

            // Trigger AJAX data loads
            if (targetId === '#profile') {
                loadProfile();
            }
        }

        window.addEventListener('hashchange', () => {
            showSection(window.location.hash);
        });

        // Initialize SPA and filters on page load
        document.addEventListener('DOMContentLoaded', () => {
            const initialHash = window.location.hash || '#overview';
            showSection(initialHash);
            filterAppointments();
            filterRecords();
        });

        // View appointment details modal
        function viewDetails(appointmentId) {
            const card = document.querySelector(`[data-apt-id="${appointmentId}"]`);
            if (!card) return;

            document.getElementById('detail_apt_id').textContent = '#' + card.dataset.aptId;
            document.getElementById('detail_date').textContent = card.dataset.date;
            document.getElementById('detail_time').textContent = card.dataset.time;
            document.getElementById('detail_priority').textContent = card.dataset.priority;
            document.getElementById('detail_status').textContent = card.dataset.status.charAt(0).toUpperCase() + card.dataset.status.slice(1);
            document.getElementById('detail_doctor').textContent = card.dataset.doctor;
            document.getElementById('detail_specialty').textContent = card.dataset.specialty;
            document.getElementById('detail_symptoms').textContent = card.dataset.symptoms;
            document.getElementById('detail_notes').textContent = card.dataset.notes;

            document.getElementById('viewDetailsModal').style.display = 'flex';
        }

        function closeViewDetailsModal() {
            document.getElementById('viewDetailsModal').style.display = 'none';
        }

        // Reschedule
        function rescheduleAppointment(appointmentId, currentDate, currentTime) {
            document.getElementById('reschedule_appointment_id').value = appointmentId;
            document.getElementById('new_date').value = currentDate;
            document.getElementById('new_time').value = currentTime.substring(0, 5);
            document.getElementById('rescheduleModal').style.display = 'flex';
        }

        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
        }

        // Cancel
        function cancelAppointment(appointmentId) {
            document.getElementById('cancel_appointment_id').value = appointmentId;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }

        // Handle Reschedule Submit
        document.getElementById('rescheduleForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {
                appointment_id: formData.get('appointment_id'),
                new_date: formData.get('new_date'),
                new_time: formData.get('new_time'),
                reason: formData.get('reason')
            };
            
            try {
                const response = await fetch('../php/reschedule_appointment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Appointment rescheduled successfully!');
                    closeRescheduleModal();
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (err) {
                alert('Connection error.');
            }
        });

        // Handle Cancel Submit
        document.getElementById('cancelForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {
                appointment_id: formData.get('appointment_id'),
                reason: formData.get('reason')
            };
            
            try {
                const response = await fetch('../php/cancel_appointment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    alert('Appointment cancelled successfully!');
                    closeCancelModal();
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (err) {
                alert('Connection error.');
            }
        });

        // Outside Click Modal close
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = 'none';
            }
        }

        // Filter appointments
        function filterAppointments() {
            const query = document.getElementById('aptSearch').value.toLowerCase();
            const status = document.getElementById('aptStatusFilter').value;
            const cards = document.querySelectorAll('#appointmentsContainer .patient-apt-row');

            if (query !== lastAptQuery || status !== lastAptStatus) {
                appointmentsLimit = 15;
                lastAptQuery = query;
                lastAptStatus = status;
            }

            let visibleCount = 0;
            cards.forEach(card => {
                const doctor = card.getAttribute('data-doctor') || '';
                const specialty = card.getAttribute('data-specialty') || '';
                const symptoms = card.getAttribute('data-symptoms') || '';
                const rStatus = card.getAttribute('data-status') || '';

                const matchesSearch = doctor.includes(query) || specialty.includes(query) || symptoms.includes(query);
                const matchesStatus = status === '' || rStatus === status;

                if (matchesSearch && matchesStatus) {
                    visibleCount++;
                    if (visibleCount <= appointmentsLimit) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                } else {
                    card.style.display = 'none';
                }
            });

            const container = document.getElementById('aptShowMoreContainer');
            if (visibleCount > appointmentsLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMoreAppointments() {
            appointmentsLimit += 15;
            filterAppointments();
        }

        // Filter Records
        function filterRecords() {
            const query = document.getElementById('recordSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#recordsTableBody .record-row');

            if (query !== lastRecordQuery) {
                recordsLimit = 15;
                lastRecordQuery = query;
            }

            let visibleCount = 0;
            rows.forEach(row => {
                const disease = row.getAttribute('data-disease') || '';
                const doctor = row.getAttribute('data-doctor') || '';
                const treatment = row.getAttribute('data-treatment') || '';
                const notes = row.getAttribute('data-notes') || '';

                const matches = disease.includes(query) || doctor.includes(query) || treatment.includes(query) || notes.includes(query);

                if (matches) {
                    visibleCount++;
                    if (visibleCount <= recordsLimit) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            const container = document.getElementById('recordsShowMoreContainer');
            if (visibleCount > recordsLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMoreRecords() {
            recordsLimit += 15;
            filterRecords();
        }

        // PROFILE SECTION AJAX
        function loadProfile() {
            fetch('../php/get_profile.php')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const d = res.data;
                        document.getElementById('prof_first_name').value = d.first_name || '';
                        document.getElementById('prof_last_name').value = d.last_name || '';
                        document.getElementById('prof_email').value = d.email || '';
                        document.getElementById('prof_phone').value = d.phone || '';
                        document.getElementById('prof_age').value = d.age || '';
                        document.getElementById('prof_gender').value = d.gender || '';
                        document.getElementById('prof_address').value = d.address || '';
                    }
                });
        }

        document.getElementById('profileEditForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const feedback = document.getElementById('profileFormFeedback');
            feedback.style.color = '#4b5563';
            feedback.textContent = 'Saving details...';

            const formData = new FormData(this);

            fetch('../php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    feedback.style.color = '#10b981';
                    feedback.textContent = 'Profile updated successfully!';
                    
                    const newFirstName = document.getElementById('prof_first_name').value;
                    const newLastName = document.getElementById('prof_last_name').value;
                    document.getElementById('header-welcome').textContent = 'Welcome back, ' + newFirstName + ' ' + newLastName + '!';
                    
                    document.getElementById('prof_current_password').value = '';
                    document.getElementById('prof_new_password').value = '';
                } else {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = res.message;
                }
            })
            .catch(() => {
                feedback.style.color = '#ef4444';
                feedback.textContent = 'Connection error updating profile.';
            });
        });
    </script>

    <!-- HospiLink Notification System -->
    <script>window.HOSPILINK_USER_ROLE = 'patient';</script>
    <script src="../js/notifications.js"></script>
    <script src="../js/notificationPanel.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
