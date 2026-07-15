<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get doctor's information
$doctorQuery = "SELECT * FROM users WHERE user_id = ?";
$docStmt = $conn->prepare($doctorQuery);
$docStmt->bind_param("i", $user_id);
$docStmt->execute();
$doctorInfo = $docStmt->get_result()->fetch_assoc();

// Get appointments sorted by priority (assigned to this doctor OR unassigned)
$today = date('Y-m-d');
$appointmentsQuery = "SELECT a.*, u.first_name, u.last_name, u.phone as patient_phone
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.user_id
                      WHERE a.doctor_id = ? OR a.doctor_id IS NULL
                      ORDER BY 
                        CASE a.priority_level
                            WHEN 'high' THEN 1
                            WHEN 'medium' THEN 2
                            WHEN 'low' THEN 3
                        END,
                        a.priority_score DESC,
                        a.appointment_date ASC";
$stmt = $conn->prepare($appointmentsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get today's stats (this doctor's appointments + unassigned)
$todayStatsQuery = "SELECT 
                        COUNT(*) as total_today,
                        COALESCE(SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END), 0) as high_count,
                        COALESCE(SUM(CASE WHEN priority_level = 'medium' THEN 1 ELSE 0 END), 0) as medium_count,
                        COALESCE(SUM(CASE WHEN priority_level = 'low' THEN 1 ELSE 0 END), 0) as low_count,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count
                    FROM appointments 
                    WHERE (doctor_id = ? OR doctor_id IS NULL)
                    AND appointment_date = ?";
$statsStmt = $conn->prepare($todayStatsQuery);
$statsStmt->bind_param("is", $user_id, $today);
$statsStmt->execute();
$todayStats = $statsStmt->get_result()->fetch_assoc();

// Get all-time stats
$allTimeStatsQuery = "SELECT 
                        COUNT(*) as total_appointments,
                        COALESCE(SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END), 0) as high_total,
                        COALESCE(SUM(CASE WHEN priority_level = 'medium' THEN 1 ELSE 0 END), 0) as medium_total,
                        COALESCE(SUM(CASE WHEN priority_level = 'low' THEN 1 ELSE 0 END), 0) as low_total,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_total
                    FROM appointments 
                    WHERE doctor_id = ? OR doctor_id IS NULL";
$allStatsStmt = $conn->prepare($allTimeStatsQuery);
$allStatsStmt->bind_param("i", $user_id);
$allStatsStmt->execute();
$allTimeStats = $allStatsStmt->get_result()->fetch_assoc();

// Ensure values are numbers, not null
$todayStats['total_today'] = intval($todayStats['total_today'] ?? 0);
$todayStats['high_count'] = intval($todayStats['high_count'] ?? 0);
$todayStats['medium_count'] = intval($todayStats['medium_count'] ?? 0);
$todayStats['low_count'] = intval($todayStats['low_count'] ?? 0);
$todayStats['pending_count'] = intval($todayStats['pending_count'] ?? 0);

$allTimeStats['total_appointments'] = intval($allTimeStats['total_appointments'] ?? 0);
$allTimeStats['high_total'] = intval($allTimeStats['high_total'] ?? 0);
$allTimeStats['medium_total'] = intval($allTimeStats['medium_total'] ?? 0);
$allTimeStats['low_total'] = intval($allTimeStats['low_total'] ?? 0);
$allTimeStats['pending_total'] = intval($allTimeStats['pending_total'] ?? 0);

// Get doctor's patients
$patientsQuery = "SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.age, u.gender 
                  FROM users u 
                  WHERE u.role = 'patient' AND u.user_id IN (
                      SELECT patient_id FROM appointments WHERE doctor_id = ? 
                      UNION 
                      SELECT patient_id FROM patient_admissions WHERE assigned_doctor_id = ?
                  )";
$patStmt = $conn->prepare($patientsQuery);
$patStmt->bind_param("ii", $user_id, $user_id);
$patStmt->execute();
$patientsList = $patStmt->get_result();

// Get all recent activity logs for doctor (up to 200)
$allLogsQuery = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 200";
$allLogsStmt = $conn->prepare($allLogsQuery);
$allLogsStmt->bind_param("i", $user_id);
$allLogsStmt->execute();
$activityLogs = $allLogsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/doctor-dashboard-enhanced.css?v=6">
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

        /* Adjust hover transitions for stats cards */
        .stat-card-enhanced {
            cursor: pointer;
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
                    <span>Overview</span>
                </a>
                <a href="#appointments" class="nav-item" id="nav-appointments">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments Queue</span>
                </a>
                <a href="#activity-logs" class="nav-item" id="nav-activity-logs">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="#profile" class="nav-item" id="nav-profile">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="#patients" class="nav-item" id="nav-patients">
                    <i class="fas fa-users"></i>
                    <span>My Patients</span>
                </a>
                <a href="../scan.php" class="nav-item" id="nav-scan">
                    <i class="fas fa-qrcode"></i>
                    <span>Scan Patient QR</span>
                </a>
                <a href="../admit.html" class="nav-item" id="nav-admit">
                    <i class="fas fa-user-plus"></i>
                    <span>Admit Patient</span>
                </a>
                <a href="#schedule" class="nav-item" id="nav-schedule">
                    <i class="fas fa-clock"></i>
                    <span>My Schedule</span>
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
                    <h1 id="header-title">Doctor Dashboard</h1>
                    <p class="subtitle" id="header-welcome">Welcome back, Dr. <?php echo htmlspecialchars($user_name); ?></p>
                    <p class="subtitle" style="font-size: 12px; margin-top: 3px; color: #888;"><?php echo htmlspecialchars($doctorInfo['specialization']); ?> | <?php echo htmlspecialchars($doctorInfo['department']); ?></p>
                </div>
                <div class="user-info">
                    <span class="user-role"><i class="fas fa-user-md"></i> Doctor</span>
                </div>
            </header>

            <!-- Overview Section -->
            <section id="overview" class="content-section">
                <div class="section-header">
                    <h2>Today's Overview</h2>
                    <div class="live-indicator">
                        <span class="pulse-dot"></span>
                        <span>Live Updates</span>
                    </div>
                </div>
                <p class="section-subtitle"><?php echo date('l, F d, Y'); ?></p>
                
                <!-- Priority Alert Banner -->
                <?php if ($todayStats['high_count'] > 0): ?>
                <div class="alert-banner critical animate-pulse">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>URGENT ATTENTION REQUIRED</strong>
                        <p>You have <span class="highlight"><?php echo $todayStats['high_count']; ?></span> high priority patient(s) requiring immediate attention!</p>
                    </div>
                </div>
                <?php elseif ($todayStats['medium_count'] > 0): ?>
                <div class="alert-banner high">
                    <div class="alert-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="alert-content">
                        <strong>MEDIUM PRIORITY NOTICE</strong>
                        <p>You have <span class="highlight"><?php echo $todayStats['medium_count']; ?></span> medium priority appointment(s) today.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Enhanced Stats Grid -->
                <div class="stats-grid-enhanced">
                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced blue">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-badge">All Time</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php echo $allTimeStats['total_appointments']; ?></div>
                            <div class="stat-label">Total Appointments</div>
                            <div class="stat-trend">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $todayStats['total_today']; ?> Today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced red">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-badge urgent">Urgent</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number critical"><?php echo $allTimeStats['high_total']; ?></div>
                            <div class="stat-label">High Priority</div>
                            <div class="stat-trend red">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo $todayStats['high_count']; ?> Today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced orange">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="stat-badge warning">Medium</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number high"><?php echo $allTimeStats['medium_total']; ?></div>
                            <div class="stat-label">Medium Priority</div>
                            <div class="stat-trend orange">
                                <i class="fas fa-fire"></i>
                                <span><?php echo $todayStats['medium_count']; ?> Today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-badge success">Low</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number medium"><?php echo $allTimeStats['low_total']; ?></div>
                            <div class="stat-label">Low Priority</div>
                            <div class="stat-trend green">
                                <i class="fas fa-check"></i>
                                <span><?php echo $todayStats['low_count']; ?> Today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced yellow">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-badge info">Pending</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php echo $allTimeStats['pending_total']; ?></div>
                            <div class="stat-label">Awaiting Review</div>
                            <div class="stat-trend">
                                <i class="fas fa-hourglass-half"></i>
                                <span><?php echo $todayStats['pending_count']; ?> Today</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions for QR Patient Management -->
                <div class="qr-management-section" style="margin-top: 30px;">
                    <h3 style="margin-bottom: 20px; color: #0e545f; font-size: 20px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-qrcode" style="color: #00adb5;"></i> QR Patient Management
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <a href="../scan.php" class="qr-action-card" style="text-decoration: none; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; display: block; border: 2px solid transparent;">
                            <div style="display: flex; align-items: flex-start; gap: 20px;">
                                <div style="background: linear-gradient(135deg, #00adb5, #0e545f); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-camera" style="color: white; font-size: 24px;"></i>
                                </div>
                                <div>
                                    <h4 style="color: #00adb5; font-size: 18px; margin: 0 0 8px 0; font-weight: 600;">Scan QR Code</h4>
                                    <p style="color: #666; margin: 0; font-size: 14px; line-height: 1.5;">View patient status by scanning bedside QR code</p>
                                </div>
                            </div>
                        </a>
                        <a href="../admit.html" class="qr-action-card" style="text-decoration: none; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; display: block; border: 2px solid transparent;">
                            <div style="display: flex; align-items: flex-start; gap: 20px;">
                                <div style="background: linear-gradient(135deg, #4CAF50, #388E3C); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-user-plus" style="color: white; font-size: 24px;"></i>
                                </div>
                                <div>
                                    <h4 style="color: #4CAF50; font-size: 18px; margin: 0 0 8px 0; font-weight: 600;">Admit Patient</h4>
                                    <p style="color: #666; margin: 0; font-size: 14px; line-height: 1.5;">Admit new patient and generate QR code</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <style>
                    .qr-action-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 12px 28px rgba(0,0,0,0.15) !important;
                        border-color: #00adb5 !important;
                    }
                </style>
            </section>

            <!-- AI-Prioritized Appointments Queue -->
            <section id="appointments" class="content-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-brain"></i> AI-Prioritized Appointments Queue
                    </h2>
                </div>
                <p class="section-description">
                    <i class="fas fa-info-circle"></i>
                    Appointments are automatically prioritized using AI analysis of patient symptoms. High priority cases appear first.
                </p>
                
                <div class="table-controls" style="margin-top: 20px;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="aptSearch" placeholder="Search by patient name, email, symptoms..." onkeyup="filterAppointments()">
                    </div>
                    <div class="filter-controls">
                        <select id="aptPriorityFilter" class="filter-select" onchange="filterAppointments()">
                            <option value="">All Priorities</option>
                            <option value="critical">Critical</option>
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

                <div class="appointments-container">
                    <?php 
                    if ($appointments->num_rows > 0):
                        $appointments->data_seek(0); // Reset pointer
                        while($apt = $appointments->fetch_assoc()): 
                            $priorityClass = $apt['priority_level'];
                            $statusClass = $apt['status'];
                            $isUrgent = ($apt['priority_level'] === 'high');
                            
                            // Priority colors and icons
                            $priorityConfig = [
                                'critical' => ['color' => '#f44336', 'icon' => 'fa-heartbeat', 'label' => 'CRITICAL'],
                                'high' => ['color' => '#ff9800', 'icon' => 'fa-bolt', 'label' => 'HIGH'],
                                'medium' => ['color' => '#2196f3', 'icon' => 'fa-calendar-check', 'label' => 'MEDIUM'],
                                'low' => ['color' => '#4caf50', 'icon' => 'fa-check-circle', 'label' => 'LOW']
                            ];
                            $config = $priorityConfig[$priorityClass] ?? ['color' => '#9ca3af', 'icon' => 'fa-info-circle', 'label' => 'UNKNOWN'];
                    ?>
                    <div class="appointment-card <?php echo $isUrgent ? 'urgent' : ''; ?> apt-row" 
                         data-appointment-id="<?php echo $apt['appointment_id']; ?>"
                         data-priority="<?php echo $priorityClass; ?>" 
                         data-status="<?php echo $statusClass; ?>"
                         data-patient="<?php echo htmlspecialchars(strtolower($apt['full_name'])); ?>"
                         data-email="<?php echo htmlspecialchars(strtolower($apt['email'])); ?>"
                         data-phone="<?php echo htmlspecialchars($apt['phone']); ?>"
                         data-symptoms="<?php echo htmlspecialchars(strtolower($apt['symptoms'])); ?>">
                        <div class="appointment-priority-bar" style="background: <?php echo $config['color']; ?>"></div>
                        
                        <div class="appointment-header">
                            <div class="appointment-priority">
                                <div class="priority-icon" style="background: <?php echo $config['color']; ?>">
                                    <i class="fas <?php echo $config['icon']; ?>"></i>
                                </div>
                                <div class="priority-info">
                                    <span class="priority-label"><?php echo $config['label']; ?></span>
                                    <span class="priority-score">Score: <?php echo $apt['priority_score']; ?>/100</span>
                                </div>
                            </div>
                            <div class="appointment-status">
                                <span class="status-badge-modern <?php echo $statusClass; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo ucfirst($apt['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="appointment-body">
                            <div class="patient-info-card">
                                <div class="patient-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="patient-details">
                                    <h3 class="patient-name"><?php echo htmlspecialchars($apt['full_name']); ?></h3>
                                    <div class="patient-contact">
                                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($apt['email']); ?></span>
                                        <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($apt['phone']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="appointment-details-grid">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <div>
                                        <span class="detail-label">Date</span>
                                        <span class="detail-value"><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <span class="detail-label">Time</span>
                                        <span class="detail-value"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="symptoms-section">
                                <div class="symptoms-header">
                                    <i class="fas fa-stethoscope"></i>
                                    <span>Symptoms</span>
                                </div>
                                <p class="symptoms-text"><?php echo htmlspecialchars($apt['symptoms']); ?></p>
                            </div>
                        </div>

                        <div class="appointment-actions">
                            <button class="action-btn-modern primary" onclick="viewPatient(<?php echo $apt['appointment_id']; ?>)">
                                <i class="fas fa-eye"></i>
                                <span>View Details</span>
                            </button>
                            <?php if($apt['status'] === 'pending'): ?>
                            <button class="action-btn-modern success" onclick="confirmAppointment(<?php echo $apt['appointment_id']; ?>)">
                                <i class="fas fa-check"></i>
                                <span>Confirm</span>
                            </button>
                            <?php endif; ?>
                            <button class="action-btn-modern info" onclick="addNotes(<?php echo $apt['appointment_id']; ?>)">
                                <i class="fas fa-notes-medical"></i>
                                <span>Add Notes</span>
                            </button>
                            <button class="action-btn-modern secondary" onclick="viewAIDetails(<?php echo $apt['appointment_id']; ?>)">
                                <i class="fas fa-robot"></i>
                                <span>AI</span>
                            </button>
                            <?php if($apt['status'] === 'confirmed' || $apt['status'] === 'pending'): ?>
                            <button class="action-btn-modern" style="background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); color: white;" onclick="admitFromAppointment(<?php echo $apt['appointment_id']; ?>, <?php echo $apt['patient_id']; ?>)">
                                <i class="fas fa-bed"></i>
                                <span>Admit Patient</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>All Clear!</h3>
                        <p>No appointments scheduled. You're all caught up!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Activity Logs Section -->
            <section id="activity-logs" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Activity Logs</h2>
                </div>
                <p class="section-subtitle">Latest activities and updates</p>
                
                <div class="activity-logs-container">
                    <?php 
                    // Fetch recent activity logs (limit to 4)
                    $logsQuery = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 4";
                    $logsStmt = $conn->prepare($logsQuery);
                    $logsStmt->bind_param("i", $user_id);
                    $logsStmt->execute();
                    $logs = $logsStmt->get_result();
                    
                    if ($logs->num_rows > 0):
                        while($log = $logs->fetch_assoc()): 
                            $actionIcon = [
                                'User Login' => 'fa-sign-in-alt',
                                'Appointment Confirmed' => 'fa-check-circle',
                                'Appointment Created' => 'fa-plus-circle',
                                'Notes Added' => 'fa-notes-medical',
                                'Profile Updated' => 'fa-user-edit',
                                'Appointment Booked' => 'fa-calendar-plus'
                            ];
                            $icon = $actionIcon[$log['action']] ?? 'fa-info-circle';
                    ?>
                    <div class="activity-log-item">
                        <div class="log-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="log-content">
                            <div class="log-action"><?php echo htmlspecialchars($log['action']); ?></div>
                            <?php if(!empty($log['details'])): ?>
                            <div class="log-details"><?php echo htmlspecialchars($log['details']); ?></div>
                            <?php endif; ?>
                            <div class="log-time">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y - h:i A', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div class="empty-state">
                        <p>No recent activity logs found.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div id="aptShowMoreContainer" class="pagination-container" style="display: none;">
                    <button class="btn-small primary" onclick="showMoreAppointments()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Activity Logs Section -->
            <section id="activity-logs" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> My Activity Logs</h2>
                </div>
                <p class="section-subtitle">Audit trail of your activities and interactions</p>

                <div class="table-controls" style="margin-top: 20px;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="logSearch" placeholder="Search logs by action or details..." onkeyup="filterLogs()">
                    </div>
                </div>

                <div class="activity-logs-container" id="logsContainer" style="margin-top: 20px;">
                    <?php 
                    if ($activityLogs->num_rows > 0):
                        while($log = $activityLogs->fetch_assoc()): 
                            $actionIcon = [
                                'User Login' => 'fa-sign-in-alt',
                                'Appointment Confirmed' => 'fa-check-circle',
                                'Appointment Created' => 'fa-plus-circle',
                                'Notes Added' => 'fa-notes-medical',
                                'Profile Updated' => 'fa-user-edit',
                                'Appointment Booked' => 'fa-calendar-plus'
                            ];
                            $icon = $actionIcon[$log['action']] ?? 'fa-info-circle';
                    ?>
                    <div class="activity-log-item log-row" data-action="<?php echo htmlspecialchars(strtolower($log['action'])); ?>" data-details="<?php echo htmlspecialchars(strtolower($log['details'])); ?>">
                        <div class="log-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="log-content">
                            <div class="log-action"><?php echo htmlspecialchars($log['action']); ?></div>
                            <?php if(!empty($log['details'])): ?>
                            <div class="log-details"><?php echo htmlspecialchars($log['details']); ?></div>
                            <?php endif; ?>
                            <div class="log-time">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y - h:i A', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div class="empty-state">
                        <p>No activity logs found.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <div id="logsShowMoreContainer" class="pagination-container" style="display: none; text-align: center; margin-top: 20px;">
                    <button class="btn-small primary" onclick="showMoreLogs()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Patients Section -->
            <section id="patients" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> My Patients Directory</h2>
                </div>
                <p class="section-subtitle">Directory of patients under your care</p>

                <div class="table-controls" style="margin-top: 20px;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="patientSearch" placeholder="Search patients by name, email, phone..." onkeyup="filterPatients()">
                    </div>
                </div>

                <div class="patients-table" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px;">
                    <table style="width:100%; border-collapse:collapse; text-align:left;">
                        <thead style="background:#f9fafb; border-bottom:1px solid #eee;">
                            <tr>
                                <th style="padding:15px; font-weight:600; color:#374151;">Patient ID</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Patient Name</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Email</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Phone</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Age</th>
                                <th style="padding:15px; font-weight:600; color:#374151;">Gender</th>
                            </tr>
                        </thead>
                        <tbody id="patientsTableBody">
                            <?php if ($patientsList && $patientsList->num_rows > 0): 
                                $patientsList->data_seek(0);
                                while ($p = $patientsList->fetch_assoc()): ?>
                                <tr class="patient-row" data-name="<?php echo htmlspecialchars(strtolower($p['first_name'] . ' ' . $p['last_name'])); ?>" data-email="<?php echo htmlspecialchars(strtolower($p['email'])); ?>" data-phone="<?php echo htmlspecialchars($p['phone']); ?>">
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;">#<?php echo $p['user_id']; ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><?php echo htmlspecialchars($p['email']); ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><?php echo htmlspecialchars($p['phone'] ?: 'N/A'); ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><?php echo htmlspecialchars($p['age'] ?: 'N/A'); ?></td>
                                    <td style="padding:15px; border-bottom:1px solid #f3f4f6;"><span style="text-transform:capitalize;"><?php echo htmlspecialchars($p['gender'] ?: 'N/A'); ?></span></td>
                                </tr>
                                <?php endwhile; 
                            else: ?>
                                <tr>
                                    <td colspan="6" style="padding:30px; text-align:center; color:#9ca3af;">No patients found in your history.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="patientsShowMoreContainer" class="pagination-container" style="display: none; text-align: center; margin-top: 20px;">
                    <button class="btn-small primary" onclick="showMorePatients()"><i class="fas fa-plus"></i> Show More</button>
                </div>
            </section>

            <!-- Schedule Section -->
            <section id="schedule" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-alt"></i> My Schedule</h2>
                </div>
                <p class="section-subtitle">Manage your shifts and consultations</p>
                <div style="background: white; border-radius: 12px; padding: 40px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: 20px;">
                    <div style="width: 80px; height: 80px; background: rgba(0, 173, 181, 0.1); color: #00adb5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 32px;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 style="color: #0e545f; margin-bottom: 10px;">Consultation Scheduler</h3>
                    <p style="color: #666; max-width: 400px; margin: 0 auto; font-size: 14px; line-height: 1.6;">Your hospital shift calendar and interactive consultation booking scheduler are being prepared by the administration. Check back soon for updates!</p>
                </div>
            </section>

            <!-- Edit Profile Section -->
            <section id="profile" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
                </div>
                <p class="section-subtitle">Update your personal credentials and professional specialization</p>
                
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
                                <label for="prof_specialization"><i class="fas fa-stethoscope"></i> Specialization</label>
                                <input type="text" id="prof_specialization" name="specialization">
                            </div>
                            <div class="form-group">
                                <label for="prof_department"><i class="fas fa-hospital"></i> Department</label>
                                <input type="text" id="prof_department" name="department">
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

    <script>
        // PAGINATION & VISIBILITY STATE LIMITS
        let appointmentsLimit = 15;
        let lastQuery = '';
        let lastPriority = '';
        let lastStatus = '';

        let logsLimit = 15;
        let patientsLimit = 15;

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
                '#overview': 'Doctor Dashboard Overview',
                '#appointments': 'Appointments Queue',
                '#activity-logs': 'My Activity Logs',
                '#profile': 'Manage Profile',
                '#patients': 'My Patients Directory',
                '#schedule': 'My Schedule'
            };
            document.getElementById('header-title').textContent = titleMap[targetId] || 'Doctor Panel';

            // Trigger AJAX data loads
            if (targetId === '#profile') {
                loadProfile();
            }
        }

        window.addEventListener('hashchange', () => {
            showSection(window.location.hash);
        });

        // Animate counting numbers on page load
        function animateCount(element) {
            const target = parseInt(element.getAttribute('data-count'));
            if (isNaN(target)) return;
            const duration = 1000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 16);
        }

        // Initialize SPA and filters on page load
        document.addEventListener('DOMContentLoaded', () => {
            const initialHash = window.location.hash || '#overview';
            showSection(initialHash);
            filterAppointments();
            filterLogs();
            filterPatients();
            document.querySelectorAll('.stat-number[data-count]').forEach(animateCount);
        });

        // Filter appointments
        function filterAppointments() {
            const query = document.getElementById('aptSearch').value.toLowerCase();
            const priority = document.getElementById('aptPriorityFilter').value;
            const status = document.getElementById('aptStatusFilter').value;
            const cards = document.querySelectorAll('.appointments-container .apt-row');
            
            if (query !== lastQuery || priority !== lastPriority || status !== lastStatus) {
                appointmentsLimit = 15;
                lastQuery = query;
                lastPriority = priority;
                lastStatus = status;
            }

            let visibleCount = 0;
            cards.forEach(card => {
                const patient = card.getAttribute('data-patient') || '';
                const email = card.getAttribute('data-email') || '';
                const phone = card.getAttribute('data-phone') || '';
                const symptoms = card.getAttribute('data-symptoms') || '';
                const rPriority = card.getAttribute('data-priority') || '';
                const rStatus = card.getAttribute('data-status') || '';
                
                const matchesSearch = patient.includes(query) || email.includes(query) || phone.includes(query) || symptoms.includes(query);
                const matchesPriority = priority === '' || rPriority === priority;
                const matchesStatus = status === '' || rStatus === status;
                
                if (matchesSearch && matchesPriority && matchesStatus) {
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

        // Filter activity logs
        function filterLogs() {
            const query = document.getElementById('logSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#logsContainer .log-row');
            
            let visibleCount = 0;
            rows.forEach(row => {
                const action = row.getAttribute('data-action') || '';
                const details = row.getAttribute('data-details') || '';
                
                const matches = action.includes(query) || details.includes(query);
                
                if (matches) {
                    visibleCount++;
                    if (visibleCount <= logsLimit) {
                        row.style.display = 'flex';
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    row.style.display = 'none';
                }
            });
            
            const container = document.getElementById('logsShowMoreContainer');
            if (visibleCount > logsLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMoreLogs() {
            logsLimit += 15;
            filterLogs();
        }

        // Filter patients
        function filterPatients() {
            const query = document.getElementById('patientSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#patientsTableBody .patient-row');
            
            let visibleCount = 0;
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const phone = row.getAttribute('data-phone') || '';
                
                const matches = name.includes(query) || email.includes(query) || phone.includes(query);
                
                if (matches) {
                    visibleCount++;
                    if (visibleCount <= patientsLimit) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                } else {
                    row.style.display = 'none';
                }
            });
            
            const container = document.getElementById('patientsShowMoreContainer');
            if (visibleCount > patientsLimit) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        function showMorePatients() {
            patientsLimit += 15;
            filterPatients();
        }

        // View patient details
        function viewPatient(appointmentId) {
            window.location.href = 'appointment_details.php?id=' + appointmentId;
        }

        // Confirm appointment with animation
        function confirmAppointment(appointmentId) {
            if (confirm('Confirm this appointment?')) {
                const btn = event.target.closest('.action-btn-modern');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
                btn.disabled = true;
                
                fetch('../php/update_appointment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'appointment_id=' + appointmentId + '&status=confirmed'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Appointment confirmed successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                        btn.innerHTML = '<i class="fas fa-check"></i> <span>Confirm</span>';
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Confirm error:', error);
                    showNotification('Network error! Check connection.', 'error');
                    btn.innerHTML = '<i class="fas fa-check"></i> <span>Confirm</span>';
                    btn.disabled = false;
                });
            }
        }

        // Add notes with modal
        function addNotes(appointmentId) {
            const notes = prompt('Enter medical notes for this appointment:');
            if (notes !== null && notes.trim() !== '') {
                fetch('../php/update_appointment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'appointment_id=' + appointmentId + '&doctor_notes=' + encodeURIComponent(notes)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Notes saved successfully!', 'success');
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                });
            }
        }

        // Show notification toaster
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // AI details modal
        function viewAIDetails(id) {
            const modal = document.getElementById('aiModal');
            const content = document.getElementById('aiModalContent');
            modal.style.display = 'flex';
            content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:#00adb5;"></i><br><br>Running AI triager...</div>';

            fetch('../php/get_ai_analysis.php?appointment_id=' + id)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        content.innerHTML = '<div style="color:#ef4444; font-weight:600;">' + data.error + '</div>';
                        return;
                    }

                    const ai = data.ai || {};
                    let html = '';
                    html += '<p style="margin-bottom:10px;"><strong>Priority Assignment:</strong> <span class="priority-badge ' + (data.priority_level || 'low') + '">' + (data.priority_level || 'low').toUpperCase() + '</span> (Score: ' + (data.priority_score || '0') + '/100)</p>';
                    if (ai.urgency_reason) html += '<p style="margin-bottom:10px;"><strong>Triage Reason:</strong> ' + ai.urgency_reason + '</p>';
                    if (ai.suspected_conditions) html += '<p style="margin-bottom:10px;"><strong>Suspected Conditions:</strong> ' + (ai.suspected_conditions.join ? ai.suspected_conditions.join(', ') : ai.suspected_conditions) + '</p>';
                    if (ai.recommended_specialist) html += '<p style="margin-bottom:10px;"><strong>Recommended Specialty:</strong> ' + ai.recommended_specialist + '</p>';
                    if (ai.warning_signs) html += '<p style="margin-bottom:10px; color:#ef4444;"><strong>Warning Flags:</strong> ' + (ai.warning_signs.join ? ai.warning_signs.join(', ') : ai.warning_signs) + '</p>';
                    if (ai.next_steps) html += '<p style="margin-bottom:10px;"><strong>Clinical Next Steps:</strong><br>' + (ai.next_steps.join ? '<ul style="padding-left:20px; margin-top:5px;">' + ai.next_steps.map(s=>'<li>'+s+'</li>').join('') + '</ul>' : ai.next_steps) + '</p>';
                    html += '<div style="margin-top:20px; display:flex; justify-content:flex-end;"><button class="btn-primary" onclick="reanalyze('+id+')"><i class="fas fa-sync"></i> Re-run Analysis</button></div>';

                    content.innerHTML = html;
                })
                .catch(err => content.innerHTML = '<div style="color:#ef4444;">Error fetching AI analysis.</div>');
        }

        function closeAIModal() {
            document.getElementById('aiModal').style.display = 'none';
        }

        function reanalyze(id) {
            const content = document.getElementById('aiModalContent');
            content.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:#00adb5;"></i><br><br>Reprocessing symptom logs...</div>';
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
            .catch(() => content.innerHTML = '<div style="color:#ef4444;">Error running AI.</div>');
        }
        
        // Admit patient from appointment
        function admitFromAppointment(appointmentId, patientId) {
            if (confirm('Do you want to admit this patient? This will redirect you to the admission form with prefilled clinical tags.')) {
                window.location.href = `../admit.html?appointment_id=${appointmentId}&patient_id=${patientId}`;
            }
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
                        document.getElementById('prof_specialization').value = d.specialization || '';
                        document.getElementById('prof_department').value = d.department || '';
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
                    
                    const newName = document.getElementById('prof_first_name').value + ' ' + document.getElementById('prof_last_name').value;
                    document.getElementById('header-welcome').textContent = 'Welcome back, Dr. ' + newName;
                    
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

        
        // ========================================
        // REAL-TIME NOTIFICATIONS (Server-Sent Events)
        // ========================================
        
        let eventSource = null;
        let lastAppointmentId = 0;
        
        // Get highest current appointment ID
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.appointment-card');
            cards.forEach(card => {
                const id = parseInt(card.getAttribute('data-appointment-id'));
                if (id > lastAppointmentId) lastAppointmentId = id;
            });
            
            // DISABLED: Initialize real-time notifications (causing page reloads)
            // initializeRealtimeNotifications();
        });
        
        function initializeRealtimeNotifications() {
            if (typeof(EventSource) === "undefined") {
                console.log("Server-Sent Events not supported");
                return;
            }
            
            eventSource = new EventSource('../php/dashboard_events.php?last_id=' + lastAppointmentId);
            
            eventSource.addEventListener('connected', function(e) {
                console.log('Real-time notifications connected');
            });
            
            eventSource.addEventListener('new_appointment', function(e) {
                const data = JSON.parse(e.data);
                showNewAppointmentNotification(data);
                lastAppointmentId = data.appointment_id;
                
                // DISABLED: Auto-reload breaks confirm button processing
                // User can manually refresh to see new appointments
                // setTimeout(() => {
                //     location.reload();
                // }, 3000);
            });
            
            eventSource.addEventListener('heartbeat', function(e) {
                console.log('SSE heartbeat:', e.data);
            });
            
            eventSource.addEventListener('error', function(e) {
                console.error('SSE error:', e);
                // Reconnect after 10 seconds
                setTimeout(() => {
                    eventSource.close();
                    initializeRealtimeNotifications();
                }, 10000);
            });
        }
        
        function showNewAppointmentNotification(data) {
            // Create notification toast
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
                color: white;
                padding: 20px 25px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(244, 67, 54, 0.4);
                z-index: 10000;
                min-width: 350px;
                animation: slideInRight 0.5s ease-out;
            `;
            
            const priorityIcon = data.priority_level === 'critical' ? 'ri-error-warning-fill' : 'ri-flashlight-fill';
            const priorityText = data.priority_level.toUpperCase();
            
            toast.innerHTML = `
                <div style="display: flex; align-items: start; gap: 15px;">
                    <div style="font-size: 2rem;"><i class="${priorityIcon}"></i></div>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 8px;">
                            ${priorityText} PRIORITY APPOINTMENT
                        </div>
                        <div style="font-size: 0.9rem; line-height: 1.5; opacity: 0.95;">
                            <strong>${data.patient_name}</strong><br>
                            ${data.symptoms}<br>
                            <small style="opacity: 0.8;">
                                ${data.appointment_date} at ${data.appointment_time}
                            </small>
                        </div>
                        <div style="margin-top: 12px;">
                            <button onclick="this.parentElement.parentElement.parentElement.parentElement.remove()" 
                                    style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;">
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Play notification sound
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGJ0fPTgjMGHm7A7+OZURE=');
                audio.play();
            } catch (e) {
                console.log('Could not play notification sound');
            }
            
            // Auto-remove after 10 seconds
            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.5s ease-in';
                setTimeout(() => toast.remove(), 500);
            }, 10000);
        }
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
    <!-- AI Modal -->
    <div id="aiModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-robot" style="color: #00adb5;"></i> AI Triaging Insights</h3>
                <button onclick="closeAIModal()" class="modal-close-btn"><i class="ri-close-line"></i></button>
            </div>
            <div id="aiModalContent">Loading...</div>
        </div>
    </div>
    </div>
    <!-- HospiLink Notification System -->
    <script>window.HOSPILINK_USER_ROLE = 'doctor';</script>
    <script src="../js/notifications.js"></script>
    <script src="../js/notificationPanel.js"></script>

</body>
</html>

<?php
$stmt->close();
$docStmt->close();
$statsStmt->close();
$allStatsStmt->close();
$conn->close();
?>