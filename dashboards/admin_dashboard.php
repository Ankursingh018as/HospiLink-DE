<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Get system statistics
$totalUsersQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$usersResult = $conn->query($totalUsersQuery);
$userStats = [];
while($row = $usersResult->fetch_assoc()) {
    $userStats[$row['role']] = $row['count'];
}

// Get appointment statistics
$appointmentStatsQuery = "SELECT 
    COUNT(*) as total_appointments,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END) as high,
    SUM(CASE WHEN priority_level = 'medium' THEN 1 ELSE 0 END) as medium
FROM appointments";
$aptStats = $conn->query($appointmentStatsQuery)->fetch_assoc();

// Get all appointments with full details
$allAppointmentsQuery = "SELECT a.*, 
    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
    d.specialization
FROM appointments a
JOIN users p ON a.patient_id = p.user_id
LEFT JOIN users d ON a.doctor_id = d.user_id
ORDER BY a.created_at DESC
LIMIT 100";
$allAppointments = $conn->query($allAppointmentsQuery);

// Get recent activity logs for Dashboard Overview Section
$activityQuery = "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.user_id
ORDER BY al.created_at DESC
LIMIT 10";
$activityLogs = $conn->query($activityQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HospiLink</title>
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
        
        /* Settings Tab inner widgets */
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 100%;
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
        .modal-box.large {
            max-width: 850px;
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
        
        /* Dashboard enhancements */
        .stats-card-enhanced {
            cursor: pointer;
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
        
        /* Modern form styles */
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
        
        /* AI config list in settings */
        .keyword-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: transform 0.2s;
        }
        .keyword-item:hover {
            transform: translateX(3px);
            background: #f3f4f6;
        }
        .keyword-text {
            font-weight: 600;
            color: #1f2937;
        }
        .keyword-meta {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .keyword-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Database management panel */
        .db-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .db-stat-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .db-stat-val {
            font-size: 24px;
            font-weight: 700;
            color: #0e545f;
            margin-bottom: 5px;
        }
        .db-stat-lbl {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Dynamic table actions button alignment */
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
        .btn-action-group {
            display: flex;
            gap: 6px;
        }
        
        /* Custom details modal grid layout */
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media(max-width: 768px) {
            .details-grid { grid-template-columns: 1fr; }
        }

        /* Show More Button alignment */
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
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#appointments" class="nav-item" id="nav-appointments">
                    <i class="fas fa-calendar-alt"></i>
                    <span>All Appointments</span>
                </a>
                <a href="#activity" class="nav-item" id="nav-activity">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="#profile" class="nav-item" id="nav-profile">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="#users" class="nav-item" id="nav-users">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
                <a href="#doctors" class="nav-item" id="nav-doctors">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
                <a href="#patients" class="nav-item" id="nav-patients">
                    <i class="fas fa-user-injured"></i>
                    <span>Patients</span>
                </a>
                <a href="#settings" class="nav-item" id="nav-settings">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
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
                    <h1 id="header-title">Admin Dashboard</h1>
                    <p class="subtitle" id="header-welcome">Welcome back, <?php echo htmlspecialchars($user_name); ?></p>
                </div>
                <div class="user-info">
                    <span class="user-role"><i class="fas fa-user-shield"></i> Administrator</span>
                </div>
            </header>

            <!-- Overview Section -->
            <section id="overview" class="content-section active-section">
                <h2>System Overview</h2>
                
                <div class="stats-grid-enhanced">
                    <div class="stat-card-enhanced" onclick="window.location.hash='#users'">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-badge">Total</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php echo array_sum($userStats); ?></div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-trend">
                                <i class="fas fa-info-circle"></i>
                                <span><?php echo ($userStats['patient'] ?? 0); ?> Patients | <?php echo ($userStats['doctor'] ?? 0); ?> Doctors</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced" onclick="window.location.hash='#appointments'">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced green">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-badge success">Active</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php echo $aptStats['total_appointments'] ?? 0; ?></div>
                            <div class="stat-label">Total Appointments</div>
                            <div class="stat-trend green">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo $aptStats['confirmed'] ?? 0; ?> Confirmed | <?php echo $aptStats['pending'] ?? 0; ?> Pending</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced" onclick="window.location.hash='#appointments'">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced red">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-badge urgent">Urgent</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number critical"><?php echo $aptStats['high'] ?? 0; ?></div>
                            <div class="stat-label">High Priority</div>
                            <div class="stat-trend red">
                                <i class="fas fa-bolt"></i>
                                <span><?php echo $aptStats['medium'] ?? 0; ?> Medium Priority</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced" onclick="window.location.hash='#doctors'">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced orange">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <div class="stat-badge warning">Staff</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php echo $userStats['doctor'] ?? 0; ?></div>
                            <div class="stat-label">Active Doctors</div>
                            <div class="stat-trend">
                                <i class="fas fa-stethoscope"></i>
                                <span>Managing Patients</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Priority Distribution Chart -->
                <div class="chart-container" style="margin-top: 30px;">
                    <h3>Appointment Priority Distribution</h3>
                    <div class="priority-bars">
                        <?php
                        $priorityQuery = "SELECT priority_level, COUNT(*) as count FROM appointments GROUP BY priority_level";
                        $priorityResult = $conn->query($priorityQuery);
                        $priorities = ['high' => 0, 'medium' => 0, 'low' => 0];
                        while($row = $priorityResult->fetch_assoc()) {
                            if (array_key_exists($row['priority_level'], $priorities)) {
                                $priorities[$row['priority_level']] = $row['count'];
                            }
                        }
                        $total = array_sum($priorities);
                        if ($total > 0):
                            foreach($priorities as $level => $count):
                                $percentage = ($count / $total) * 100;
                        ?>
                        <div class="priority-bar-item" style="margin-bottom: 15px;">
                            <div class="bar-label" style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:5px;">
                                <span style="font-weight:600;"><?php echo ucfirst($level); ?></span>
                                <span><?php echo $count; ?> (<?php echo round($percentage, 1); ?>%)</span>
                            </div>
                            <div class="bar-bg" style="background:#e5e7eb; border-radius:10px; height:12px; overflow:hidden;">
                                <div class="bar-fill <?php echo $level; ?>" style="width: <?php echo $percentage; ?>%; height:100%; border-radius:10px;"></div>
                            </div>
                        </div>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </div>
                </div>
            </section>

            <!-- All Appointments Section -->
            <section id="appointments" class="content-section">
                <h2>All Appointments (AI-Prioritized)</h2>
                
                <div class="table-controls">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="aptSearch" placeholder="Search patient, symptoms, or doctor..." onkeyup="filterAppointments()">
                    </div>
                    <div class="filter-controls">
                        <select id="aptPriorityFilter" class="filter-select" onchange="filterAppointments()">
                            <option value="">All Priorities</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <select id="aptStatusFilter" class="filter-select" onchange="filterAppointments()">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Priority</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Symptoms</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($allAppointments && $allAppointments->num_rows > 0):
                                while($apt = $allAppointments->fetch_assoc()): 
                            ?>
                            <tr class="apt-row" 
                                data-patient="<?php echo htmlspecialchars(strtolower($apt['patient_name'])); ?>"
                                data-doctor="<?php echo htmlspecialchars(strtolower($apt['doctor_name'] ?? 'not assigned')); ?>"
                                data-symptoms="<?php echo htmlspecialchars(strtolower($apt['symptoms'])); ?>"
                                data-priority="<?php echo htmlspecialchars($apt['priority_level']); ?>"
                                data-status="<?php echo htmlspecialchars($apt['status']); ?>">
                                <td>#<?php echo $apt['appointment_id']; ?></td>
                                <td>
                                    <span class="priority-badge <?php echo $apt['priority_level']; ?>">
                                        <?php echo strtoupper($apt['priority_level']); ?>
                                    </span>
                                    <br><small>Score: <?php echo $apt['priority_score']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($apt['patient_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($apt['email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($apt['doctor_name'] ?: 'Not Assigned'); ?></strong><br>
                                    <small><?php echo htmlspecialchars($apt['specialization'] ?: ''); ?></small>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(strlen($apt['symptoms']) > 50 ? substr($apt['symptoms'], 0, 47) . '...' : $apt['symptoms']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-action-group">
                                        <button class="btn-small" onclick="viewAppointmentDetails(<?php echo $apt['appointment_id']; ?>)" title="View Full Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-small" onclick="openAssignDoctor(<?php echo $apt['appointment_id']; ?>)" title="Assign Doctor">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                        <button class="btn-small" onclick="viewAIDetails(<?php echo $apt['appointment_id']; ?>)" title="AI Priority Insights">
                                            <i class="fas fa-robot"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No appointments found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="aptShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMoreAppointments()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Activity Logs Section -->
            <section id="activity" class="content-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <div>
                        <h2>System Activity Logs</h2>
                        <p class="section-subtitle">Real-time audit trail of all actions performed inside the system</p>
                    </div>
                    <div>
                        <button class="btn-primary" onclick="loadActivityLogs()"><i class="fas fa-sync-alt"></i> Refresh</button>
                    </div>
                </div>

                <div class="table-controls">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="logSearch" placeholder="Search action, details, user..." onkeyup="loadActivityLogs()">
                    </div>
                    <div class="filter-controls">
                        <select id="logRoleFilter" class="filter-select" onchange="loadActivityLogs()">
                            <option value="">All Roles</option>
                            <option value="admin">Administrator</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="nurse">Nurse</option>
                            <option value="patient">Patient</option>
                        </select>
                    </div>
                </div>

                <div class="activity-logs-container" id="activityLogsContainer">
                    <!-- Loaded dynamically via AJAX -->
                    <div style="text-align: center; padding: 30px;">Loading logs...</div>
                </div>
                <div id="logsShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMoreLogs()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Edit Profile Section -->
            <section id="profile" class="content-section">
                <h2>Edit Profile</h2>
                <p class="section-description">Update your personal account credentials and settings</p>
                
                <div class="profile-container">
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
                                <label for="prof_age"><i class="fas fa-birthday-cake"></i> Age</label>
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

                        <div style="display:flex; gap:15px; margin-top:25px;">
                            <button type="submit" class="btn-primary" style="padding: 12px 24px;"><i class="fas fa-save"></i> Save Changes</button>
                            <button type="button" class="btn-small" onclick="window.location.hash='#overview'" style="padding: 12px 24px;">Cancel</button>
                        </div>
                    </form>
                    <div id="profileFormFeedback" style="margin-top:15px; font-weight:600;"></div>
                </div>
            </section>

            <!-- User Management Section -->
            <section id="users" class="content-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <div>
                        <h2>User Management</h2>
                        <p class="section-subtitle">Manage all system users, credentials, roles, and status</p>
                    </div>
                    <div>
                        <button class="btn-primary" onclick="openAddUserModal()"><i class="fas fa-user-plus"></i> Add New User</button>
                    </div>
                </div>

                <div class="table-controls">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search name, email, phone..." onkeyup="loadUsers()">
                    </div>
                    <div class="filter-controls">
                        <select id="userRoleFilter" class="filter-select" onchange="loadUsers()">
                            <option value="">All Roles</option>
                            <option value="admin">Administrator</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="nurse">Nurse</option>
                            <option value="patient">Patient</option>
                        </select>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Loaded via AJAX -->
                            <tr>
                                <td colspan="8" style="text-align: center;">Loading users...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="usersShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMoreUsers()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Doctors Directory Section -->
            <section id="doctors" class="content-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div>
                        <h2>Doctor Directory</h2>
                        <p class="section-subtitle font-sm">Comprehensive listing of all medical practitioners and specialties</p>
                    </div>
                    <div>
                        <button class="btn-primary" onclick="openAddUserModal('doctor')"><i class="fas fa-plus"></i> Register Doctor</button>
                    </div>
                </div>

                <div class="table-controls">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="doctorSearch" placeholder="Search doctor name or specialty..." onkeyup="loadDoctors()">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Doctor Name</th>
                                <th>Email</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>License Number</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="doctorsTableBody">
                            <!-- Loaded via AJAX -->
                            <tr>
                                <td colspan="8" style="text-align: center;">Loading doctors list...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="doctorsShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMoreDoctors()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Patients Directory Section -->
            <section id="patients" class="content-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div>
                        <h2>Patient Directory</h2>
                        <p class="section-subtitle">Directory of registered patients, contacts, demographics, and status</p>
                    </div>
                    <div>
                        <button class="btn-primary" onclick="openAddUserModal('patient')"><i class="fas fa-plus"></i> Register Patient</button>
                    </div>
                </div>

                <div class="table-controls">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="patientSearch" placeholder="Search patient name, email..." onkeyup="loadPatients()">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient Name</th>
                                <th>Email</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody id="patientsTableBody">
                            <!-- Loaded via AJAX -->
                            <tr>
                                <td colspan="8" style="text-align: center;">Loading patients directory...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="patientsShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMorePatients()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section">
                <h2>System Configuration Settings</h2>
                <p class="section-description">Manage database updates, check tables status, and tweak AI symptom triage prioritizer settings.</p>
                
                <div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                    <!-- AI Symptom Prioritization config card -->
                    <div class="settings-card" style="margin-top:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h3><i class="fas fa-brain"></i> AI Symptom Triage Keywords</h3>
                            <button class="btn-small primary" onclick="openAddKeyword()"><i class="fas fa-plus"></i> Add Keyword</button>
                        </div>
                        <p class="section-subtitle" style="margin-bottom:15px;">Add, edit, or delete keywords used by the AI engine to rank appointment priorities</p>
                        
                        <div style="max-height: 400px; overflow-y: auto; padding-right:5px;" id="keywordsContainer">
                            <!-- Loaded dynamically via AJAX -->
                            <div>Loading keywords...</div>
                        </div>
                    </div>

                    <!-- Database Management Card -->
                    <div class="settings-card" style="margin-top:20px;">
                        <h3><i class="fas fa-database"></i> Database Management</h3>
                        <p class="section-subtitle" style="margin-bottom:15px;">View system statistics, row counts, and optimize database tables</p>
                        
                        <div class="db-stats-grid" id="dbStatsContainer">
                            <!-- Populated dynamically -->
                            <div class="db-stat-item"><div class="db-stat-val">...</div><div class="db-stat-lbl">Loading Stats</div></div>
                        </div>

                        <div style="display:flex; gap:10px; margin-top:20px;">
                            <button class="btn-primary" onclick="optimizeDatabase()"><i class="fas fa-hammer"></i> Optimize Tables</button>
                            <button class="btn-small danger" onclick="clearActivityLogs()"><i class="fas fa-trash-alt"></i> Clear Logs > 30 Days</button>
                        </div>
                        <div id="settingsFeedback" style="margin-top:15px; font-weight:600;"></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL OVERLAYS -->
    
    <!-- 1. Appointment Details Modal -->
    <div id="aptDetailsModal" class="modal-overlay">
        <div class="modal-box large">
            <div class="modal-header">
                <h3>Appointment Profile & AI Diagnostic Insights</h3>
                <button class="modal-close-btn" onclick="closeModal('aptDetailsModal')"><i class="ri-close-line"></i></button>
            </div>
            <div id="aptDetailsContent">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <!-- 2. Doctor Assignment Modal -->
    <div id="assignDoctorModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Assign Doctor to Appointment</h3>
                <button class="modal-close-btn" onclick="closeModal('assignDoctorModal')"><i class="ri-close-line"></i></button>
            </div>
            <input type="hidden" id="assign_apt_id">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="assign_doctor_select">Select Medical Specialist</label>
                <select id="assign_doctor_select" class="filter-select" style="width: 100%;">
                    <option value="">Loading doctors list...</option>
                </select>
            </div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button class="btn-primary" onclick="submitDoctorAssignment()">Assign Doctor</button>
                <button class="btn-small" onclick="closeModal('assignDoctorModal')">Cancel</button>
            </div>
            <div id="assignFeedback" style="margin-top:10px; font-weight:600; color: #f44336;"></div>
        </div>
    </div>

    <!-- 3. Add/Edit User Modal -->
    <div id="userModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="userModalTitle">Add New System User</h3>
                <button class="modal-close-btn" onclick="closeModal('userModal')"><i class="ri-close-line"></i></button>
            </div>
            <form id="userModalForm">
                <input type="hidden" id="modal_user_id" name="user_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="modal_role">User Role</label>
                        <select id="modal_role" name="role" onchange="toggleRoleFields()" required>
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="nurse">Nurse</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_status">Account Status</label>
                        <select id="modal_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modal_first_name">First Name</label>
                        <input type="text" id="modal_first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_last_name">Last Name</label>
                        <input type="text" id="modal_last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_email">Email Address</label>
                        <input type="email" id="modal_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_password" id="label_modal_password">Password</label>
                        <input type="password" id="modal_password" name="password" placeholder="Leave empty to keep existing">
                    </div>
                    <div class="form-group">
                        <label for="modal_phone">Phone Number</label>
                        <input type="tel" id="modal_phone" name="phone">
                    </div>
                    
                    <!-- Patient specific -->
                    <div class="form-group role-specific patient-only">
                        <label for="modal_age">Age</label>
                        <input type="number" id="modal_age" name="age" min="1" max="120">
                    </div>
                    <div class="form-group role-specific patient-only">
                        <label for="modal_gender">Gender</label>
                        <select id="modal_gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    
                    <!-- Doctor specific -->
                    <div class="form-group role-specific doctor-only" style="display:none;">
                        <label for="modal_specialization">Specialization</label>
                        <input type="text" id="modal_specialization" name="specialization" placeholder="e.g. Cardiology">
                    </div>
                    <div class="form-group role-specific doctor-only" style="display:none;">
                        <label for="modal_license_number">License Number</label>
                        <input type="text" id="modal_license_number" name="license_number" placeholder="Medical License ID">
                    </div>
                    
                    <!-- Doctor, Staff, Nurse specific -->
                    <div class="form-group role-specific employee-only" style="display:none;">
                        <label for="modal_department">Department</label>
                        <input type="text" id="modal_department" name="department" placeholder="Assigned Department">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label for="modal_address">Address Details</label>
                        <textarea id="modal_address" name="address" rows="2"></textarea>
                    </div>
                </div>
                
                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:20px;">
                    <button type="submit" class="btn-primary">Save User Details</button>
                    <button type="button" class="btn-small" onclick="closeModal('userModal')">Cancel</button>
                </div>
            </form>
            <div id="userModalFeedback" style="margin-top:10px; font-weight:600; text-align:center;"></div>
        </div>
    </div>

    <!-- 4. AI Keyword Modal -->
    <div id="keywordModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="keywordModalTitle">Add Symptom Priority Keyword</h3>
                <button class="modal-close-btn" onclick="closeModal('keywordModal')"><i class="ri-close-line"></i></button>
            </div>
            <form id="keywordForm">
                <input type="hidden" id="key_id" name="keyword_id">
                <div class="form-group" style="margin-bottom:15px;">
                    <label for="key_word">Symptom Keyword</label>
                    <input type="text" id="key_word" name="keyword" required placeholder="e.g. abdominal pain">
                </div>
                <div class="form-group" style="margin-bottom:15px;">
                    <label for="key_priority">Priority Rank</label>
                    <select id="key_priority" name="priority_level" class="filter-select" style="width:100%;">
                        <option value="low">Low Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label for="key_desc">Medical Context / Priority Reason</label>
                    <textarea id="key_desc" name="description" rows="3" placeholder="Context details regarding this triage assignment"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="submit" class="btn-primary">Save Keyword</button>
                    <button type="button" class="btn-small" onclick="closeModal('keywordModal')">Cancel</button>
                </div>
            </form>
            <div id="keywordFeedback" style="margin-top:10px; font-weight:600; text-align:center;"></div>
        </div>
    </div>

    <!-- AI Insights Modal -->
    <div id="aiModal" style="display:none; position:fixed; inset:0; background:rgba(14, 84, 95, 0.4); align-items:center; justify-content:center; z-index:9999; backdrop-filter: blur(8px); padding:20px;">
        <div class="modal-box">
            <div class="modal-header">
                <h3>AI Triaging Insights</h3>
                <button onclick="closeAIModal()" class="modal-close-btn"><i class="ri-close-line"></i></button>
            </div>
            <div id="aiModalContent">Loading insights...</div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        // PAGINATION & VISIBILITY STATE LIMITS
        let appointmentsLimit = 15;
        let lastQuery = '';
        let lastPriority = '';
        let lastStatus = '';

        let allUsersData = [];
        let usersLimit = 15;

        let allDoctorsData = [];
        let doctorsLimit = 15;

        let allPatientsData = [];
        let patientsLimit = 15;

        let allLogsData = [];
        let logsLimit = 15;

        // SPA ROUTING
        function showSection(targetId) {
            if (!targetId || targetId === '#' || targetId === '') targetId = '#overview';
            
            // Check active nav-item
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
                const href = item.getAttribute('href');
                if (href === targetId) {
                    item.classList.add('active');
                } else {
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
                '#overview': 'Admin Dashboard',
                '#appointments': 'Appointments Management',
                '#activity': 'System Activity Audit Logs',
                '#profile': 'Manage Admin Profile',
                '#users': 'User Account Management',
                '#doctors': 'Doctor Registry & Directories',
                '#patients': 'Patient Demographics Directory',
                '#settings': 'System & AI Configuration'
            };
            document.getElementById('header-title').textContent = titleMap[targetId] || 'Admin Panel';

            // Trigger AJAX data loads
            if (targetId === '#profile') {
                loadProfile();
            } else if (targetId === '#users') {
                loadUsers();
            } else if (targetId === '#doctors') {
                loadDoctors();
            } else if (targetId === '#patients') {
                loadPatients();
            } else if (targetId === '#activity') {
                loadActivityLogs();
            } else if (targetId === '#settings') {
                loadSettings();
            }
        }

        // Listen to hashchange
        window.addEventListener('hashchange', () => {
            showSection(window.location.hash);
        });

        // Load initially
        document.addEventListener('DOMContentLoaded', () => {
            const initialHash = window.location.hash || '#overview';
            showSection(initialHash);
            filterAppointments(); // Initialize appointments filter limits
        });

        // MODALS HANDLERS
        function openModal(id) {
            document.getElementById(id).style.display = 'flex';
        }
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // --- SECTION 1: APPOINTMENTS FILTERING & MODALS ---
        
        function filterAppointments() {
            const query = document.getElementById('aptSearch').value.toLowerCase();
            const priority = document.getElementById('aptPriorityFilter').value;
            const status = document.getElementById('aptStatusFilter').value;
            const rows = document.querySelectorAll('#appointmentsTable tbody .apt-row');
            
            // Reset limit if filter parameters change
            if (query !== lastQuery || priority !== lastPriority || status !== lastStatus) {
                appointmentsLimit = 15;
                lastQuery = query;
                lastPriority = priority;
                lastStatus = status;
            }

            let visibleCount = 0;
            rows.forEach(row => {
                const patient = row.getAttribute('data-patient');
                const doctor = row.getAttribute('data-doctor');
                const symptoms = row.getAttribute('data-symptoms');
                const rPriority = row.getAttribute('data-priority');
                const rStatus = row.getAttribute('data-status');
                
                const matchesSearch = patient.includes(query) || doctor.includes(query) || symptoms.includes(query);
                const matchesPriority = priority === '' || rPriority === priority;
                const matchesStatus = status === '' || rStatus === status;
                
                if (matchesSearch && matchesPriority && matchesStatus) {
                    visibleCount++;
                    if (visibleCount <= appointmentsLimit) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle Show More Button Visibility
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

        // View Appointment Details
        function viewAppointmentDetails(id) {
            const modal = document.getElementById('aptDetailsModal');
            const content = document.getElementById('aptDetailsContent');
            openModal('aptDetailsModal');
            content.innerHTML = '<div style="text-align:center; padding:30px;"><i class="fas fa-spinner fa-spin" style="font-size:32px; color:#00adb5;"></i><br><br>Retrieving appointment file...</div>';

            fetch('../php/admin_api.php?action=get_appointment_details&appointment_id=' + id)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        content.innerHTML = '<div style="color:#ef4444; padding:20px; font-weight:600;">' + res.message + '</div>';
                        return;
                    }
                    const data = res.data;
                    const ai = data.ai || {};
                    
                    let html = `
                        <div class="details-grid">
                            <div>
                                <h4 style="color:#0e545f; border-bottom: 1px solid #eee; padding-bottom:5px; margin-bottom:10px;"><i class="fas fa-id-card"></i> Patient Profile Details</h4>
                                <table style="width:100%; border-collapse:collapse; font-size:14px; margin-bottom:20px;">
                                    <tr><td style="padding:6px 0; font-weight:600; width:120px;">Full Name:</td><td>${data.patient_name}</td></tr>
                                    <tr><td style="padding:6px 0; font-weight:600;">Email:</td><td>${data.email}</td></tr>
                                    <tr><td style="padding:6px 0; font-weight:600;">Phone Number:</td><td>${data.phone || 'Not Provided'}</td></tr>
                                    <tr><td style="padding:6px 0; font-weight:600;">Gender:</td><td>${data.gender}</td></tr>
                                    <tr><td style="padding:6px 0; font-weight:600;">Appointment Date:</td><td>${data.appointment_date} at ${data.appointment_time}</td></tr>
                                </table>

                                <h4 style="color:#0e545f; border-bottom: 1px solid #eee; padding-bottom:5px; margin-bottom:10px;"><i class="fas fa-clipboard-list"></i> Symptoms Reported</h4>
                                <p style="background:#f9fafb; padding:15px; border-radius:8px; border:1px solid #eee; margin-bottom:20px; font-style:italic;">"${data.symptoms}"</p>
                                
                                <h4 style="color:#0e545f; border-bottom: 1px solid #eee; padding-bottom:5px; margin-bottom:10px;"><i class="fas fa-stethoscope"></i> Actionable Status & Notes</h4>
                                <form id="modalNotesForm" onsubmit="saveAppointmentNotes(event, ${data.appointment_id})">
                                    <div class="form-group" style="margin-bottom:12px;">
                                        <label for="modal_apt_status">Appointment Triage Status</label>
                                        <select id="modal_apt_status" class="filter-select" style="width:100%; margin-top:5px;">
                                            <option value="pending" ${data.status === 'pending' ? 'selected' : ''}>Pending</option>
                                            <option value="confirmed" ${data.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                            <option value="completed" ${data.status === 'completed' ? 'selected' : ''}>Completed</option>
                                            <option value="cancelled" ${data.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom:15px;">
                                        <label for="modal_doctor_notes">Physician Observational Notes</label>
                                        <textarea id="modal_doctor_notes" rows="4" style="width:100%; margin-top:5px; padding:8px; border:1px solid #ccc; border-radius:8px;">${data.doctor_notes || ''}</textarea>
                                    </div>
                                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Notes & Status</button>
                                    <span id="modalNotesFeedback" style="margin-left:10px; font-weight:600;"></span>
                                </form>
                            </div>
                            <div style="border-left:1px solid #eee; padding-left:20px;">
                                <h4 style="color:#0e545f; border-bottom: 1px solid #eee; padding-bottom:5px; margin-bottom:10px;"><i class="fas fa-robot"></i> AI Triage Analyzer</h4>
                                <div style="background:#e0f2f1; padding:15px; border-radius:8px; border-left:4px solid #00adb5; margin-bottom:15px;">
                                    <div style="font-weight:700; color:#0e545f; font-size:15px;">Priority: ${data.priority_level.toUpperCase()}</div>
                                    <div style="font-size:12px; color:#00adb5; font-weight:700; margin-top:4px;">Priority Score: ${data.priority_score}/100</div>
                                </div>
                                <div style="font-size:13px; color:#4b5563;">
                                    <p><strong>Suspected Diagnosis:</strong> ${ai.suspected_conditions ? (ai.suspected_conditions.join ? ai.suspected_conditions.join(', ') : ai.suspected_conditions) : 'N/A'}</p><br>
                                    <p><strong>Specialist Triage:</strong> ${ai.recommended_specialist || 'N/A'}</p><br>
                                    <p><strong>Warning Signs:</strong> ${ai.warning_signs ? (ai.warning_signs.join ? ai.warning_signs.join(', ') : ai.warning_signs) : 'None detected'}</p><br>
                                    <p><strong>Urgency Reason:</strong> ${ai.urgency_reason || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    content.innerHTML = html;
                })
                .catch(() => content.innerHTML = '<div style="color:#ef4444; padding:20px;">Connection failed while loading details.</div>');
        }

        // Save notes and status
        function saveAppointmentNotes(e, appointmentId) {
            e.preventDefault();
            const status = document.getElementById('modal_apt_status').value;
            const notes = document.getElementById('modal_doctor_notes').value;
            const feedback = document.getElementById('modalNotesFeedback');
            feedback.style.color = '#4b5563';
            feedback.textContent = 'Saving modifications...';

            const formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('status', status);
            formData.append('doctor_notes', notes);

            fetch('../php/update_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    feedback.style.color = '#10b981';
                    feedback.textContent = 'Changes saved successfully!';
                    setTimeout(() => { window.location.reload(); }, 1000);
                } else {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = 'Save failed: ' + res.message;
                }
            })
            .catch(() => {
                feedback.style.color = '#ef4444';
                feedback.textContent = 'Error saving changes.';
            });
        }

        // Open Doctor Assignment Modal
        function openAssignDoctor(appointmentId) {
            document.getElementById('assign_apt_id').value = appointmentId;
            openModal('assignDoctorModal');
            const select = document.getElementById('assign_doctor_select');
            select.innerHTML = '<option value="">Retrieving specialists...</option>';
            document.getElementById('assignFeedback').textContent = '';

            fetch('../php/get_doctors.php')
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        select.innerHTML = '<option value="">No active doctors registered</option>';
                        return;
                    }
                    let html = '<option value="">-- Choose Doctor --</option>';
                    data.forEach(doc => {
                        html += `<option value="${doc.user_id}">${doc.first_name} ${doc.last_name} (${doc.specialization || 'General'})</option>`;
                    });
                    select.innerHTML = html;
                })
                .catch(() => {
                    select.innerHTML = '<option value="">Error fetching doctors</option>';
                });
        }

        // Submit Doctor Assignment
        function submitDoctorAssignment() {
            const aptId = document.getElementById('assign_apt_id').value;
            const docId = document.getElementById('assign_doctor_select').value;
            const feedback = document.getElementById('assignFeedback');
            
            if (!docId) {
                feedback.textContent = 'Please choose a doctor first.';
                return;
            }

            feedback.style.color = '#00adb5';
            feedback.textContent = 'Assigning doctor...';

            const formData = new FormData();
            formData.append('appointment_id', aptId);
            formData.append('doctor_id', docId);

            fetch('../php/update_appointment.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    feedback.style.color = '#10b981';
                    feedback.textContent = 'Doctor assigned successfully!';
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = res.message;
                }
            })
            .catch(() => {
                feedback.style.color = '#ef4444';
                feedback.textContent = 'Error connecting to server.';
            });
        }

        // View AI insights Modal
        function viewAIDetails(id) {
            const modal = document.getElementById('aiModal');
            const content = document.getElementById('aiModalContent');
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align:center; padding:30px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:#00adb5;"></i><br><br>Retrieving AI summary...</div>';

            fetch('../php/get_ai_analysis.php?appointment_id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = '<div style="color:#ef4444; font-weight:600;">' + data.error + '</div>';
                        return;
                    }

                    const ai = data.ai || {};
                    let html = '';
                    html += '<p><strong>Triage Priority Rank:</strong> <span class="priority-badge ' + data.priority_level + '">' + data.priority_level.toUpperCase() + '</span> (Score: ' + (data.priority_score || '-') + '/100)</p><br>';
                    if (ai.urgency_reason) html += '<p><strong>Reasoning:</strong> ' + ai.urgency_reason + '</p><br>';
                    if (ai.suspected_conditions) html += '<p><strong>Suspected Conditions:</strong> ' + (ai.suspected_conditions.join ? ai.suspected_conditions.join(', ') : ai.suspected_conditions) + '</p><br>';
                    if (ai.recommended_specialist) html += '<p><strong>Recommended Specialist:</strong> ' + ai.recommended_specialist + '</p><br>';
                    if (ai.warning_signs) html += '<p><strong>Critical Warning Signs:</strong> ' + (ai.warning_signs.join ? ai.warning_signs.join(', ') : ai.warning_signs) + '</p><br>';
                    if (ai.next_steps) html += '<p><strong>Recommended Triage Steps:</strong><br>' + (ai.next_steps.join ? '<ul>' + ai.next_steps.map(s=>'<li>'+s+'</li>').join('') + '</ul>' : ai.next_steps) + '</p>';
                    html += '<div style="margin-top:20px; display:flex; gap:10px;"><button class="btn-small primary" onclick="rerunAIAnalysis('+id+')"><i class="fas fa-sync-alt"></i> Re-Run AI Analysis</button><button class="btn-small" onclick="closeAIModal()">Close</button></div>';

                    content.innerHTML = html;
                })
                .catch(() => content.innerHTML = '<div style="color:#ef4444;">Error fetching AI report.</div>');
        }

        function closeAIModal() {
            document.getElementById('aiModal').style.display = 'none';
        }

        function rerunAIAnalysis(id) {
            const content = document.getElementById('aiModalContent');
            content.innerHTML = '<div style="text-align:center; padding:30px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:#00adb5;"></i><br><br>Reprocessing symptom weights...</div>';
            fetch('../php/reanalyze_ai.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'appointment_id=' + id
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    content.innerHTML = '<div style="color:#ef4444;">' + data.error + '</div>';
                    return;
                }
                viewAIDetails(id);
            })
            .catch(() => content.innerHTML = '<div style="color:#ef4444;">Error running prioritizer.</div>');
        }


        // --- SECTION 2: ACTIVITY LOGS LOADER ---

        function loadActivityLogs() {
            const search = document.getElementById('logSearch').value;
            const role = document.getElementById('logRoleFilter').value;
            
            logsLimit = 15;
            
            fetch(`../php/admin_api.php?action=get_logs&q=${encodeURIComponent(search)}&role=${role}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        document.getElementById('activityLogsContainer').innerHTML = `<div style="color:#ef4444; text-align:center; padding:20px;">${res.message}</div>`;
                        document.getElementById('logsShowMoreContainer').style.display = 'none';
                        return;
                    }
                    allLogsData = res.data;
                    renderActivityLogs();
                })
                .catch(() => {
                    document.getElementById('activityLogsContainer').innerHTML = '<div style="color:#ef4444; text-align:center; padding:20px;">Connection failed while loading logs.</div>';
                    document.getElementById('logsShowMoreContainer').style.display = 'none';
                });
        }

        function renderActivityLogs() {
            const container = document.getElementById('activityLogsContainer');
            const btnContainer = document.getElementById('logsShowMoreContainer');
            
            if (allLogsData.length === 0) {
                container.innerHTML = '<div class="empty-state" style="text-align:center; padding:30px;"><p>No activity logs match the filters.</p></div>';
                btnContainer.style.display = 'none';
                return;
            }
            
            const visibleData = allLogsData.slice(0, logsLimit);
            let html = '';
            const actionIcon = {
                'User Login': 'fa-sign-in-alt',
                'Appointment Confirmed': 'fa-check-circle',
                'Appointment Created': 'fa-plus-circle',
                'Notes Added': 'fa-notes-medical',
                'Profile Updated': 'fa-user-edit',
                'User Registered': 'fa-user-plus',
                'Appointment Booked': 'fa-calendar-plus',
                'User Status Changed': 'fa-toggle-on',
                'User Deleted': 'fa-trash-alt',
                'Database Optimization': 'fa-hammer',
                'Logs Cleared': 'fa-broom'
            };
            
            visibleData.forEach(log => {
                const icon = actionIcon[log.action] || 'fa-info-circle';
                const time = new Date(log.created_at).toLocaleString();
                const ipText = log.ip_address ? `<span style="margin-left: 15px; color:#6b7280;"><i class="fas fa-network-wired"></i> ${log.ip_address}</span>` : '';
                
                html += `
                    <div class="activity-log-item" style="border-bottom: 1px solid #f3f4f6; padding:15px 0;">
                        <div class="log-icon" style="background: rgba(0, 173, 181, 0.1); color:#00adb5; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; float:left; margin-right:15px;">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="log-content" style="margin-left: 55px;">
                            <div class="log-action" style="font-size:14px;">
                                <strong>${log.user_name || 'System'}</strong> 
                                <span class="role-badge" style="font-size:11px; padding:2px 6px; background:#e5e7eb; border-radius:4px; margin-left:5px;">${log.role || 'system'}</span>
                                - ${log.action}
                            </div>
                            ${log.details ? `<div class="log-details" style="font-size:12px; color:#4b5563; margin-top:4px;">${log.details}</div>` : ''}
                            <div class="log-time" style="font-size:11px; color:#9ca3af; margin-top:4px;">
                                <i class="fas fa-clock"></i> ${time} ${ipText}
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
            
            if (allLogsData.length > logsLimit) {
                btnContainer.style.display = 'block';
            } else {
                btnContainer.style.display = 'none';
            }
        }

        function showMoreLogs() {
            logsLimit += 15;
            renderActivityLogs();
        }


        // --- SECTION 3: ADMIN PROFILE EDITOR ---

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
            feedback.textContent = 'Updating configuration...';

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
                    
                    // Update header greeting instantly
                    const newName = document.getElementById('prof_first_name').value + ' ' + document.getElementById('prof_last_name').value;
                    document.getElementById('header-welcome').textContent = 'Welcome back, ' + newName;
                    
                    // Clear passwords
                    document.getElementById('prof_current_password').value = '';
                    document.getElementById('prof_new_password').value = '';
                } else {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = res.message;
                }
            })
            .catch(() => {
                feedback.style.color = '#ef4444';
                feedback.textContent = 'Profile update connection error.';
            });
        });


        // --- SECTION 4: USER MANAGEMENT CRUD ---

        function loadUsers() {
            const search = document.getElementById('userSearch').value;
            const role = document.getElementById('userRoleFilter').value;
            
            usersLimit = 15;
            
            fetch(`../php/admin_api.php?action=get_users&q=${encodeURIComponent(search)}&role=${role}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        document.getElementById('usersTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444;">${res.message}</td></tr>`;
                        document.getElementById('usersShowMoreContainer').style.display = 'none';
                        return;
                    }
                    allUsersData = res.data;
                    renderUsers();
                })
                .catch(() => {
                    document.getElementById('usersTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444;">Failed to sync user list.</td></tr>`;
                    document.getElementById('usersShowMoreContainer').style.display = 'none';
                });
        }

        function renderUsers() {
            const body = document.getElementById('usersTableBody');
            const container = document.getElementById('usersShowMoreContainer');
            
            if (allUsersData.length === 0) {
                body.innerHTML = `<tr><td colspan="8" style="text-align:center;">No matching users found.</td></tr>`;
                container.style.display = 'none';
                return;
            }
            
            const visibleData = allUsersData.slice(0, usersLimit);
            let html = '';
            visibleData.forEach(user => {
                const statusClass = user.status === 'active' ? 'success' : 'cancelled';
                const toggleIcon = user.status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off';
                const toggleTitle = user.status === 'active' ? 'Deactivate User' : 'Activate User';
                
                html += `
                    <tr>
                        <td>#${user.user_id}</td>
                        <td><strong>${user.first_name} ${user.last_name}</strong></td>
                        <td>${user.email}</td>
                        <td><span class="role-badge" style="background:#e0f2f1; color:#0e545f; padding:3px 8px; border-radius:4px; font-weight:700;">${user.role.toUpperCase()}</span></td>
                        <td>${user.phone || 'N/A'}</td>
                        <td><span class="status-badge ${statusClass}">${user.status.toUpperCase()}</span></td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        <td>
                            <div class="btn-action-group">
                                <button class="btn-small" onclick="openEditUser(${JSON.stringify(user).replace(/"/g, '&quot;')})" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-small" onclick="toggleUserStatus(${user.user_id}, '${user.status}')" title="${toggleTitle}">
                                    <i class="fas ${toggleIcon}"></i>
                                </button>
                                <button class="btn-small danger" onclick="deleteUser(${user.user_id})" title="Delete User">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            body.innerHTML = html;
            
            if (allUsersData.length > usersLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMoreUsers() {
            usersLimit += 15;
            renderUsers();
        }

        // Toggle role fields in Add/Edit user modal
        function toggleRoleFields() {
            const role = document.getElementById('modal_role').value;
            
            // Hide all role-specific fields
            document.querySelectorAll('.role-specific').forEach(div => {
                div.style.display = 'none';
            });
            
            if (role === 'patient') {
                document.querySelectorAll('.patient-only').forEach(div => div.style.display = '');
            } else if (role === 'doctor') {
                document.querySelectorAll('.doctor-only').forEach(div => div.style.display = '');
                document.querySelectorAll('.employee-only').forEach(div => div.style.display = '');
            } else if (role === 'nurse' || role === 'staff') {
                document.querySelectorAll('.employee-only').forEach(div => div.style.display = '');
            }
        }

        // Open Add User Modal
        function openAddUserModal(defaultRole = 'patient') {
            document.getElementById('userModalForm').reset();
            document.getElementById('modal_user_id').value = '';
            document.getElementById('userModalTitle').textContent = 'Add New System User';
            document.getElementById('modal_role').value = defaultRole;
            document.getElementById('modal_role').disabled = false;
            document.getElementById('modal_password').required = true;
            document.getElementById('label_modal_password').textContent = 'Password';
            document.getElementById('modal_password').placeholder = 'Enter secure password';
            
            toggleRoleFields();
            document.getElementById('userModalFeedback').textContent = '';
            openModal('userModal');
        }

        // Open Edit User Modal
        function openEditUser(user) {
            document.getElementById('userModalForm').reset();
            document.getElementById('modal_user_id').value = user.user_id;
            document.getElementById('userModalTitle').textContent = `Edit User Details - #${user.user_id}`;
            document.getElementById('modal_role').value = user.role;
            document.getElementById('modal_role').disabled = true; // role shouldn't change to prevent integrity issues
            document.getElementById('modal_status').value = user.status;
            document.getElementById('modal_first_name').value = user.first_name;
            document.getElementById('modal_last_name').value = user.last_name;
            document.getElementById('modal_email').value = user.email;
            document.getElementById('modal_phone').value = user.phone || '';
            document.getElementById('modal_age').value = user.age || '';
            document.getElementById('modal_gender').value = user.gender || '';
            document.getElementById('modal_specialization').value = user.specialization || '';
            document.getElementById('modal_license_number').value = user.license_number || '';
            document.getElementById('modal_department').value = user.department || '';
            document.getElementById('modal_address').value = user.address || '';
            
            document.getElementById('modal_password').required = false;
            document.getElementById('label_modal_password').textContent = 'Password (Optional)';
            document.getElementById('modal_password').placeholder = 'Leave blank to keep current password';
            
            toggleRoleFields();
            document.getElementById('userModalFeedback').textContent = '';
            openModal('userModal');
        }

        // Submit Add/Edit user form
        document.getElementById('userModalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const userId = document.getElementById('modal_user_id').value;
            const action = userId ? 'edit_user' : 'add_user';
            const feedback = document.getElementById('userModalFeedback');
            feedback.style.color = '#4b5563';
            feedback.textContent = 'Saving details...';

            const formData = new FormData(this);
            formData.append('action', action);
            if (userId) {
                formData.append('user_id', userId);
                // Also need role since disabled select isn't submitted in form
                formData.append('role', document.getElementById('modal_role').value);
            }

            fetch('../php/admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    feedback.style.color = '#10b981';
                    feedback.textContent = res.message;
                    setTimeout(() => {
                        closeModal('userModal');
                        loadUsers();
                        // Also update respective tab contents if active
                        if (window.location.hash === '#doctors') loadDoctors();
                        if (window.location.hash === '#patients') loadPatients();
                    }, 1200);
                } else {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = res.message;
                }
            })
            .catch(() => {
                feedback.style.color = '#ef4444';
                feedback.textContent = 'Failed to submit form.';
            });
        });

        // Toggle user status
        function toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            if (!confirm(`Are you sure you want to change user status to ${newStatus}?`)) return;

            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('user_id', userId);
            formData.append('status', newStatus);

            fetch('../php/admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    loadUsers();
                    if (window.location.hash === '#doctors') loadDoctors();
                    if (window.location.hash === '#patients') loadPatients();
                } else {
                    alert('Status toggle failed: ' + res.message);
                }
            });
        }

        // Delete user
        function deleteUser(userId) {
            if (!confirm('CAUTION: Deleting a user will clear their associated login access. This action CANNOT be undone. Are you sure you want to proceed?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', userId);

            fetch('../php/admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    loadUsers();
                    if (window.location.hash === '#doctors') loadDoctors();
                    if (window.location.hash === '#patients') loadPatients();
                } else {
                    alert('Deletion failed: ' + res.message);
                }
            });
        }


        // --- SECTION 5: DOCTORS DIRECTORY ---

        function loadDoctors() {
            const search = document.getElementById('doctorSearch').value;
            doctorsLimit = 15;
            
            fetch(`../php/admin_api.php?action=get_users&role=doctor&q=${encodeURIComponent(search)}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        document.getElementById('doctorsTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444;">${res.message}</td></tr>`;
                        document.getElementById('doctorsShowMoreContainer').style.display = 'none';
                        return;
                    }
                    allDoctorsData = res.data;
                    renderDoctors();
                })
                .catch(() => {
                    document.getElementById('doctorsTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444;">Failed to sync doctor logs.</td></tr>`;
                    document.getElementById('doctorsShowMoreContainer').style.display = 'none';
                });
        }

        function renderDoctors() {
            const body = document.getElementById('doctorsTableBody');
            const container = document.getElementById('doctorsShowMoreContainer');
            
            if (allDoctorsData.length === 0) {
                body.innerHTML = `<tr><td colspan="8" style="text-align:center;">No doctors registered matching terms.</td></tr>`;
                container.style.display = 'none';
                return;
            }
            
            const visibleData = allDoctorsData.slice(0, doctorsLimit);
            let html = '';
            visibleData.forEach(user => {
                const statusClass = user.status === 'active' ? 'success' : 'cancelled';
                html += `
                    <tr>
                        <td>#${user.user_id}</td>
                        <td><strong>Dr. ${user.first_name} ${user.last_name}</strong></td>
                        <td>${user.email}</td>
                        <td><span style="font-weight:600; color:#0e545f;">${user.specialization || 'General Practice'}</span></td>
                        <td>${user.department || 'N/A'}</td>
                        <td>${user.license_number || 'N/A'}</td>
                        <td><span class="status-badge ${statusClass}">${user.status.toUpperCase()}</span></td>
                        <td>
                            <div class="btn-action-group">
                                <button class="btn-small" onclick='openEditUser(${JSON.stringify(user).replace(/'/g, "&#39;")})'><i class="fas fa-edit"></i> Edit</button>
                                <button class="btn-small danger" onclick="toggleUserStatus(${user.user_id}, '${user.status}')"><i class="fas fa-power-off"></i> Toggle</button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            body.innerHTML = html;
            
            if (allDoctorsData.length > doctorsLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMoreDoctors() {
            doctorsLimit += 15;
            renderDoctors();
        }


        // --- SECTION 6: PATIENTS DIRECTORY ---

        function loadPatients() {
            const search = document.getElementById('patientSearch').value;
            patientsLimit = 15;
            
            fetch(`../php/admin_api.php?action=get_users&role=patient&q=${encodeURIComponent(search)}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        document.getElementById('patientsTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444;">${res.message}</td></tr>`;
                        document.getElementById('patientsShowMoreContainer').style.display = 'none';
                        return;
                    }
                    allPatientsData = res.data;
                    renderPatients();
                })
                .catch(() => {
                    document.getElementById('patientsTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center; color:#ef4444;">Failed to sync patients list.</td></tr>`;
                    document.getElementById('patientsShowMoreContainer').style.display = 'none';
                });
        }

        function renderPatients() {
            const body = document.getElementById('patientsTableBody');
            const container = document.getElementById('patientsShowMoreContainer');
            
            if (allPatientsData.length === 0) {
                body.innerHTML = `<tr><td colspan="8" style="text-align:center;">No patient profiles match the terms.</td></tr>`;
                container.style.display = 'none';
                return;
            }
            
            const visibleData = allPatientsData.slice(0, patientsLimit);
            let html = '';
            visibleData.forEach(user => {
                const statusClass = user.status === 'active' ? 'success' : 'cancelled';
                const age = user.age ? user.age : 'N/A';
                const gender = user.gender ? user.gender : 'N/A';
                
                html += `
                    <tr>
                        <td>#${user.user_id}</td>
                        <td><strong>${user.first_name} ${user.last_name}</strong></td>
                        <td>${user.email}</td>
                        <td>${age}</td>
                        <td>${gender}</td>
                        <td>${user.phone || 'N/A'}</td>
                        <td><span class="status-badge ${statusClass}">${user.status.toUpperCase()}</span></td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                    </tr>
                `;
            });
            body.innerHTML = html;
            
            if (allPatientsData.length > patientsLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMorePatients() {
            patientsLimit += 15;
            renderPatients();
        }


        // --- SECTION 7: SETTINGS & AI CONFIGS ---

        function loadSettings() {
            loadSymptomKeywords();
            loadDbStats();
        }

        // AI Keywords CRUD
        function loadSymptomKeywords() {
            const container = document.getElementById('keywordsContainer');
            fetch('../php/admin_api.php?action=get_keywords')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) {
                        container.innerHTML = `<div style="color:#ef4444;">${res.message}</div>`;
                        return;
                    }
                    const data = res.data;
                    if (data.length === 0) {
                        container.innerHTML = '<p>No keyword configurations registered.</p>';
                        return;
                    }
                    
                    let html = '';
                    data.forEach(kw => {
                        html += `
                            <div class="keyword-item">
                                <div>
                                    <span class="keyword-text">${kw.keyword}</span>
                                    <span class="priority-badge ${kw.priority_level}" style="font-size: 10px; margin-left: 8px;">${kw.priority_level}</span>
                                    <div class="keyword-meta">${kw.description || 'No explanation specified.'}</div>
                                </div>
                                <div class="keyword-actions">
                                    <button class="btn-small" onclick='openEditKeyword(${JSON.stringify(kw).replace(/'/g, "&#39;")})'><i class="fas fa-pencil-alt"></i></button>
                                    <button class="btn-small danger" onclick="deleteKeyword(${kw.keyword_id})"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                });
        }

        function openAddKeyword() {
            document.getElementById('keywordForm').reset();
            document.getElementById('key_id').value = '';
            document.getElementById('keywordModalTitle').textContent = 'Add AI Priority Keyword';
            document.getElementById('keywordFeedback').textContent = '';
            openModal('keywordModal');
        }

        function openEditKeyword(kw) {
            document.getElementById('key_id').value = kw.keyword_id;
            document.getElementById('keywordModalTitle').textContent = 'Edit AI Keyword';
            document.getElementById('key_word').value = kw.keyword;
            document.getElementById('key_priority').value = kw.priority_level;
            document.getElementById('key_desc').value = kw.description || '';
            document.getElementById('keywordFeedback').textContent = '';
            openModal('keywordModal');
        }

        document.getElementById('keywordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const kwId = document.getElementById('key_id').value;
            const action = kwId ? 'edit_keyword' : 'add_keyword';
            const feedback = document.getElementById('keywordFeedback');
            feedback.style.color = '#4b5563';
            feedback.textContent = 'Saving keyword...';

            const formData = new FormData(this);
            formData.append('action', action);
            if (kwId) formData.append('keyword_id', kwId);

            fetch('../php/admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    feedback.style.color = '#10b981';
                    feedback.textContent = res.message;
                    setTimeout(() => {
                        closeModal('keywordModal');
                        loadSymptomKeywords();
                    }, 1200);
                } else {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = res.message;
                }
            })
            .catch(() => {
                feedback.style.color = '#ef4444';
                feedback.textContent = 'Failed to sync settings.';
            });
        });

        function deleteKeyword(id) {
            if (!confirm('Are you sure you want to remove this keyword priority config?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_keyword');
            formData.append('keyword_id', id);

            fetch('../php/admin_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    loadSymptomKeywords();
                } else {
                    alert('Failed: ' + res.message);
                }
            });
        }

        // Database Statistics counts
        function loadDbStats() {
            const container = document.getElementById('dbStatsContainer');
            fetch('../php/admin_api.php?action=get_db_stats')
                .then(r => r.json())
                .then(res => {
                    if (!res.success) return;
                    const d = res.data;
                    
                    container.innerHTML = `
                        <div class="db-stat-item"><div class="db-stat-val">${d.users.total}</div><div class="db-stat-lbl">Total Registered Users</div></div>
                        <div class="db-stat-item"><div class="db-stat-val">${d.appointments.total}</div><div class="db-stat-lbl">Total Appointments Scheduled</div></div>
                        <div class="db-stat-item"><div class="db-stat-val">${d.beds.total}</div><div class="db-stat-lbl">Total Hospital Beds</div></div>
                        <div class="db-stat-item"><div class="db-stat-val">${d.keywords_count}</div><div class="db-stat-lbl">AI Symptom Keywords</div></div>
                        <div class="db-stat-item"><div class="db-stat-val">${d.db_size_mb} MB</div><div class="db-stat-lbl">MySQL Database Size</div></div>
                    `;
                });
        }

        // Optimize Database
        function optimizeDatabase() {
            const feedback = document.getElementById('settingsFeedback');
            feedback.style.color = '#00adb5';
            feedback.textContent = 'Optimizing database indexes and tables...';
            
            fetch('../php/admin_api.php?action=optimize_db', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        feedback.style.color = '#10b981';
                        feedback.textContent = res.message;
                        loadDbStats();
                    } else {
                        feedback.style.color = '#ef4444';
                        feedback.textContent = res.message;
                    }
                })
                .catch(() => {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = 'Failed to send optimization command.';
                });
        }

        // Clear activity logs > 30 days
        function clearActivityLogs() {
            if (!confirm('Are you sure you want to purge system activity logs older than 30 days? This action helps free up table space.')) return;
            const feedback = document.getElementById('settingsFeedback');
            feedback.style.color = '#00adb5';
            feedback.textContent = 'Purging logs...';
            
            fetch('../php/admin_api.php?action=clear_logs', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        feedback.style.color = '#10b981';
                        feedback.textContent = res.message;
                        loadDbStats();
                        if (window.location.hash === '#activity') loadActivityLogs();
                    } else {
                        feedback.style.color = '#ef4444';
                        feedback.textContent = res.message;
                    }
                })
                .catch(() => {
                    feedback.style.color = '#ef4444';
                    feedback.textContent = 'Failed to execute purge request.';
                });
        }
    </script>

    <!-- HospiLink Notification System -->
    <script>window.HOSPILINK_USER_ROLE = 'admin';</script>
    <script src="../js/notifications.js"></script>
    <script src="../js/notificationPanel.js"></script>
</body>
</html>

<?php $conn->close(); ?>
