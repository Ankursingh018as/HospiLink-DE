<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_name = $_SESSION['user_name'];

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
LIMIT 50";
$allAppointments = $conn->query($allAppointmentsQuery);

// Get recent activity logs
$activityQuery = "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.role
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.user_id
ORDER BY al.created_at DESC
LIMIT 20";
$activityLogs = $conn->query($activityQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HospiLink</title>
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
                <a href="#overview" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#appointments" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>All Appointments</span>
                </a>
                <a href="#activity" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="admin_profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="#users" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>User Management</span>
                </a>
                <a href="#doctors" class="nav-item">
                    <i class="fas fa-user-md"></i>
                    <span>Doctors</span>
                </a>
                <a href="#patients" class="nav-item">
                    <i class="fas fa-user-injured"></i>
                    <span>Patients</span>
                </a>
                <a href="#settings" class="nav-item">
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
                    <h1>Admin Dashboard</h1>
                    <p class="subtitle">Welcome back, <?php echo htmlspecialchars($user_name); ?></p>
                </div>
                <div class="user-info">
                    <span class="user-role"><i class="fas fa-user-shield"></i> Administrator</span>
                </div>
            </header>

            <!-- Overview Section -->
            <section id="overview" class="content-section">
                <h2>System Overview</h2>
                
                <div class="stats-grid-enhanced">
                    <div class="stat-card-enhanced" data-stat="users">
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
                    
                    <div class="stat-card-enhanced" data-stat="appointments">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced green">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-badge success">Active</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number"><?php echo $aptStats['total_appointments']; ?></div>
                            <div class="stat-label">Total Appointments</div>
                            <div class="stat-trend green">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo $aptStats['confirmed']; ?> Confirmed | <?php echo $aptStats['pending']; ?> Pending</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced" data-stat="critical">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced red">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="stat-badge urgent">Urgent</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number critical"><?php echo $aptStats['high']; ?></div>
                            <div class="stat-label">High Priority</div>
                            <div class="stat-trend red">
                                <i class="fas fa-bolt"></i>
                                <span><?php echo $aptStats['medium']; ?> Medium Priority</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card-enhanced" data-stat="doctors">
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
                <div class="chart-container">
                    <h3>Appointment Priority Distribution</h3>
                    <div class="priority-bars">
                        <?php
                        $priorityQuery = "SELECT priority_level, COUNT(*) as count FROM appointments GROUP BY priority_level";
                        $priorityResult = $conn->query($priorityQuery);
                        $priorities = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
                        while($row = $priorityResult->fetch_assoc()) {
                            $priorities[$row['priority_level']] = $row['count'];
                        }
                        $total = array_sum($priorities);
                        if ($total > 0):
                        foreach($priorities as $level => $count):
                            $percentage = ($count / $total) * 100;
                        ?>
                        <div class="priority-bar-item">
                            <div class="bar-label">
                                <span><?php echo ucfirst($level); ?></span>
                                <span><?php echo $count; ?> (<?php echo round($percentage, 1); ?>%)</span>
                            </div>
                            <div class="bar-bg">
                                <div class="bar-fill <?php echo $level; ?>" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </section>

            <!-- All Appointments Section -->
            <section id="appointments" class="content-section">
                <h2>All Appointments (AI-Prioritized)</h2>
                <div class="table-container">
                    <table class="data-table">
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
                            if ($allAppointments->num_rows > 0):
                                while($apt = $allAppointments->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>#<?php echo $apt['appointment_id']; ?></td>
                                <td>
                                    <span class="priority-badge <?php echo $apt['priority_level']; ?>">
                                        <?php echo strtoupper($apt['priority_level']); ?>
                                    </span>
                                    <br><small>Score: <?php echo $apt['priority_score']; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($apt['patient_name']); ?><br>
                                    <small><?php echo htmlspecialchars($apt['email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($apt['doctor_name'] ?: 'Not Assigned'); ?><br>
                                    <small><?php echo htmlspecialchars($apt['specialization'] ?: ''); ?></small>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($apt['symptoms'], 0, 50)) . '...'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $apt['status']; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-small" onclick="viewDetails(<?php echo $apt['appointment_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-small" onclick="assignDoctor(<?php echo $apt['appointment_id']; ?>)">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Activity Logs Section -->
            <section id="activity" class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                </div>
                <p class="section-subtitle">Latest system activities and updates</p>
                
                <div class="activity-logs-container">
                    <?php 
                    // Reset pointer and limit to 4 logs
                    $activityLogs->data_seek(0);
                    $logCount = 0;
                    if ($activityLogs->num_rows > 0):
                        while($log = $activityLogs->fetch_assoc()): 
                            if ($logCount >= 4) break;
                            $logCount++;
                            
                            $actionIcon = [
                                'User Login' => 'fa-sign-in-alt',
                                'Appointment Confirmed' => 'fa-check-circle',
                                'Appointment Created' => 'fa-plus-circle',
                                'Notes Added' => 'fa-notes-medical',
                                'Profile Updated' => 'fa-user-edit',
                                'User Registered' => 'fa-user-plus',
                                'Appointment Booked' => 'fa-calendar-plus'
                            ];
                            $icon = $actionIcon[$log['action']] ?? 'fa-info-circle';
                    ?>
                    <div class="activity-log-item">
                        <div class="log-icon">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="log-content">
                            <div class="log-action">
                                <strong><?php echo htmlspecialchars($log['user_name'] ?: 'System'); ?></strong>
                                <span class="role-badge"><?php echo ucfirst($log['role'] ?: 'system'); ?></span>
                                - <?php echo htmlspecialchars($log['action']); ?>
                            </div>
                            <?php if($log['details']): ?>
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
                
                <div class="see-more-container">
                    <button class="see-more-btn" onclick="window.location.href='all_activity_logs.php'">
                        <i class="fas fa-eye"></i> See All Activity Logs
                    </button>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section">
                <h2>System Settings</h2>
                <div class="settings-grid">
                    <div class="setting-card">
                        <h3><i class="fas fa-brain"></i> AI Configuration</h3>
                        <p>Configure AI symptom analysis parameters</p>
                        <button class="btn-primary">Configure</button>
                    </div>
                    <div class="setting-card">
                        <h3><i class="fas fa-database"></i> Database Management</h3>
                        <p>Backup and manage database</p>
                        <button class="btn-primary">Manage</button>
                    </div>
                    <div class="setting-card">
                        <h3><i class="fas fa-bell"></i> Notifications</h3>
                        <p>Configure system notifications</p>
                        <button class="btn-primary">Configure</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        function viewDetails(appointmentId) {
            window.location.href = 'appointment_details.php?id=' + appointmentId;
        }

        function assignDoctor(appointmentId) {
            alert('Doctor assignment feature - Opening modal...');
        }

        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
