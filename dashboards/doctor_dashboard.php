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

// Get today's appointments sorted by priority
$today = date('Y-m-d');
$appointmentsQuery = "SELECT a.*, u.first_name, u.last_name, u.phone as patient_phone
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.user_id
                      WHERE a.doctor_id = ? OR a.doctor_id IS NULL
                      ORDER BY 
                        CASE a.priority_level
                            WHEN 'critical' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'medium' THEN 3
                            WHEN 'low' THEN 4
                        END,
                        a.priority_score DESC,
                        a.appointment_date ASC";
$stmt = $conn->prepare($appointmentsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get today's stats
$todayStatsQuery = "SELECT 
                        COUNT(*) as total_today,
                        COALESCE(SUM(CASE WHEN priority_level = 'critical' THEN 1 ELSE 0 END), 0) as critical_count,
                        COALESCE(SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END), 0) as high_count,
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
                        COALESCE(SUM(CASE WHEN priority_level = 'critical' THEN 1 ELSE 0 END), 0) as critical_total,
                        COALESCE(SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END), 0) as high_total,
                        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_total
                    FROM appointments 
                    WHERE doctor_id = ? OR doctor_id IS NULL";
$allStatsStmt = $conn->prepare($allTimeStatsQuery);
$allStatsStmt->bind_param("i", $user_id);
$allStatsStmt->execute();
$allTimeStats = $allStatsStmt->get_result()->fetch_assoc();

// Ensure values are numbers, not null
$todayStats['total_today'] = intval($todayStats['total_today'] ?? 0);
$todayStats['critical_count'] = intval($todayStats['critical_count'] ?? 0);
$todayStats['high_count'] = intval($todayStats['high_count'] ?? 0);
$todayStats['pending_count'] = intval($todayStats['pending_count'] ?? 0);

$allTimeStats['total_appointments'] = intval($allTimeStats['total_appointments'] ?? 0);
$allTimeStats['critical_total'] = intval($allTimeStats['critical_total'] ?? 0);
$allTimeStats['high_total'] = intval($allTimeStats['high_total'] ?? 0);
$allTimeStats['pending_total'] = intval($allTimeStats['pending_total'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HospiLink</title>
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
                    <i class="fas fa-home"></i>
                    <span>Overview</span>
                </a>
                <a href="#appointments" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments Queue</span>
                </a>
                <a href="#activity-logs" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="doctor_profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="#patients" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>My Patients</span>
                </a>
                <a href="../scan.php" class="nav-item">
                    <i class="fas fa-qrcode"></i>
                    <span>Scan Patient QR</span>
                </a>
                <a href="../admit.html" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Admit Patient</span>
                </a>
                <a href="#schedule" class="nav-item">
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
                    <h1>Dr. <?php echo htmlspecialchars($user_name); ?></h1>
                    <p class="subtitle"><?php echo htmlspecialchars($doctorInfo['specialization']); ?> | <?php echo htmlspecialchars($doctorInfo['department']); ?></p>
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
                <?php if ($todayStats['critical_count'] > 0): ?>
                <div class="alert-banner critical animate-pulse">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>URGENT ATTENTION REQUIRED</strong>
                        <p>You have <span class="highlight"><?php echo $todayStats['critical_count']; ?></span> critical priority patient(s) requiring immediate attention!</p>
                    </div>
                </div>
                <?php elseif ($todayStats['high_count'] > 0): ?>
                <div class="alert-banner high">
                    <div class="alert-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="alert-content">
                        <strong>HIGH PRIORITY NOTICE</strong>
                        <p>You have <span class="highlight"><?php echo $todayStats['high_count']; ?></span> high priority appointment(s) today.</p>
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
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div class="stat-badge urgent">Urgent</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number critical"><?php echo $allTimeStats['critical_total']; ?></div>
                            <div class="stat-label">Critical Priority</div>
                            <div class="stat-trend red">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?php echo $todayStats['critical_count']; ?> Today</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-enhanced">
                        <div class="stat-card-header">
                            <div class="stat-icon-enhanced orange">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="stat-badge warning">High</div>
                        </div>
                        <div class="stat-card-body">
                            <div class="stat-number high"><?php echo $allTimeStats['high_total']; ?></div>
                            <div class="stat-label">High Priority</div>
                            <div class="stat-trend orange">
                                <i class="fas fa-bolt"></i>
                                <span><?php echo $todayStats['high_count']; ?> Today</span>
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
                <div class="quick-actions" style="margin-top: 30px;">
                    <h3 style="margin-bottom: 15px; color: #333;">
                        <i class="fas fa-qrcode"></i> QR Patient Management
                    </h3>
                    <div class="stats-grid">
                        <a href="../scan.php" class="stat-card action-card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s;">
                            <div class="stat-icon" style="background: #00adb5;">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="stat-info">
                                <h3 style="color: #00adb5;">Scan QR Code</h3>
                                <p>View patient status by scanning bedside QR code</p>
                            </div>
                        </a>
                        <a href="../admit.html" class="stat-card action-card" style="text-decoration: none; cursor: pointer; transition: transform 0.2s;">
                            <div class="stat-icon" style="background: #4CAF50;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="stat-info">
                                <h3 style="color: #4CAF50;">Admit Patient</h3>
                                <p>Admit new patient and generate QR code</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Standard Quick Actions -->
                <div class="quick-actions" style="margin-top: 20px;">
                    <button class="quick-action-btn primary">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Consultation</span>
                    </button>
                    <button class="quick-action-btn secondary">
                        <i class="fas fa-file-prescription"></i>
                        <span>Write Prescription</span>
                    </button>
                    <button class="quick-action-btn secondary">
                        <i class="fas fa-notes-medical"></i>
                        <span>View Reports</span>
                    </button>
                    <button class="quick-action-btn secondary">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Manage Schedule</span>
                    </button>
                </div>
            </section>

            <style>
                .action-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                }
            </style>

            <!-- AI-Prioritized Appointments Queue -->
            <section id="appointments" class="content-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-brain"></i> AI-Prioritized Appointments Queue
                    </h2>
                    <div class="section-actions">
                        <button class="filter-btn active" data-filter="all">
                            <i class="fas fa-list"></i> All
                        </button>
                        <button class="filter-btn" data-filter="critical">
                            <i class="fas fa-exclamation-triangle"></i> Critical
                        </button>
                        <button class="filter-btn" data-filter="pending">
                            <i class="fas fa-clock"></i> Pending
                        </button>
                    </div>
                </div>
                <p class="section-description">
                    <i class="fas fa-info-circle"></i>
                    Appointments are automatically prioritized using AI analysis of patient symptoms. Critical cases appear first.
                </p>
                
                <div class="appointments-container">
                    <?php 
                    if ($appointments->num_rows > 0):
                        $appointments->data_seek(0); // Reset pointer
                        while($apt = $appointments->fetch_assoc()): 
                            $priorityClass = $apt['priority_level'];
                            $statusClass = $apt['status'];
                            $isUrgent = in_array($apt['priority_level'], ['critical', 'high']);
                            
                            // Priority colors and icons
                            $priorityConfig = [
                                'critical' => ['color' => '#f44336', 'icon' => 'fa-heartbeat', 'label' => 'CRITICAL'],
                                'high' => ['color' => '#ff9800', 'icon' => 'fa-bolt', 'label' => 'HIGH'],
                                'medium' => ['color' => '#2196f3', 'icon' => 'fa-calendar-check', 'label' => 'MEDIUM'],
                                'low' => ['color' => '#4caf50', 'icon' => 'fa-check-circle', 'label' => 'LOW']
                            ];
                            $config = $priorityConfig[$priorityClass];
                    ?>
                    <div class="appointment-card <?php echo $isUrgent ? 'urgent' : ''; ?>" data-priority="<?php echo $priorityClass; ?>" data-status="<?php echo $statusClass; ?>">
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
                
                <div class="see-more-container">
                    <button class="see-more-btn" onclick="window.location.href='activity_logs.php'">
                        <i class="fas fa-eye"></i> See All Activity Logs
                    </button>
                </div>
            </section>

            <!-- Patients Section -->
            <section id="patients" class="content-section">
                <h2>My Patients</h2>
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    This section will show all patients you've treated with their medical history.
                </div>
            </section>

            <!-- Schedule Section -->
            <section id="schedule" class="content-section">
                <h2>My Schedule</h2>
                <div class="info-box">
                    <i class="fas fa-calendar-alt"></i>
                    Calendar view and schedule management coming soon.
                </div>
            </section>
        </main>
    </div>

    <script>
        // Animate counting numbers on page load
        function animateCount(element) {
            const target = parseInt(element.getAttribute('data-count'));
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

        // Initialize count animations
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stat-number[data-count]').forEach(animateCount);
        });

        // Filter appointments
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                document.querySelectorAll('.appointment-card').forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else if (filter === 'critical') {
                        card.style.display = card.classList.contains('urgent') ? 'block' : 'none';
                    } else if (filter === 'pending') {
                        card.style.display = card.getAttribute('data-status') === 'pending' ? 'block' : 'none';
                    }
                });
            });
        });

        // View patient details
        function viewPatient(appointmentId) {
            // Create modal or redirect
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

        // Show notification
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

        // Smooth navigation
        document.querySelectorAll('.nav-item[href^="#"]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Auto-refresh page every 30 seconds to update stats
        setInterval(() => {
            location.reload();
        }, 30000);

        // Add pulse animation to critical cards
        document.querySelectorAll('.appointment-card.urgent').forEach(card => {
            card.style.animation = 'subtle-pulse 2s ease-in-out infinite';
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$docStmt->close();
$statsStmt->close();
$allStatsStmt->close();
$conn->close();
?>
