<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'doctor') {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get all activity logs for this doctor
$logsQuery = "SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC";
$logsStmt = $conn->prepare($logsQuery);
$logsStmt->bind_param("i", $user_id);
$logsStmt->execute();
$logs = $logsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Activity Logs - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/doctor-dashboard-enhanced.css">
    <link rel="icon" href="../images/hosp_favicon.png" type="image/png">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../images/logo.png" alt="HospiLink">
            </div>
            <nav class="sidebar-nav">
                <a href="doctor_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Overview</span>
                </a>
                <a href="doctor_dashboard.php#appointments" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments Queue</span>
                </a>
                <a href="activity_logs.php" class="nav-item active">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="doctor_profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="doctor_dashboard.php#patients" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>My Patients</span>
                </a>
                <a href="doctor_dashboard.php#schedule" class="nav-item">
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
                    <h1>Activity Logs</h1>
                    <p class="subtitle">Complete history of your activities</p>
                </div>
                <div class="header-actions">
                    <button class="btn-primary" onclick="window.location.href='doctor_dashboard.php'">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </button>
                </div>
            </header>

            <section id="activity-logs" class="content-section">
                <div class="activity-logs-container">
                    <?php 
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
                                <?php if(!empty($log['ip_address'])): ?>
                                <span style="margin-left: 15px; color: #666;"><i class="fas fa-network-wired"></i> <?php echo htmlspecialchars($log['ip_address']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 10px;"></i>
                        <p>No activity logs found.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
