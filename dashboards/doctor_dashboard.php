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
                        SUM(CASE WHEN priority_level = 'critical' THEN 1 ELSE 0 END) as critical_count,
                        SUM(CASE WHEN priority_level = 'high' THEN 1 ELSE 0 END) as high_count,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
                    FROM appointments 
                    WHERE (doctor_id = ? OR doctor_id IS NULL) 
                    AND appointment_date = ?";
$statsStmt = $conn->prepare($todayStatsQuery);
$statsStmt->bind_param("is", $user_id, $today);
$statsStmt->execute();
$todayStats = $statsStmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - HospiLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
                <h2>Today's Overview - <?php echo date('l, F d, Y'); ?></h2>
                
                <!-- Priority Alert Banner -->
                <?php if ($todayStats['critical_count'] > 0): ?>
                <div class="alert-banner critical">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>URGENT:</strong> You have <?php echo $todayStats['critical_count']; ?> critical priority patient(s) requiring immediate attention!
                </div>
                <?php elseif ($todayStats['high_count'] > 0): ?>
                <div class="alert-banner high">
                    <i class="fas fa-info-circle"></i>
                    <strong>NOTICE:</strong> You have <?php echo $todayStats['high_count']; ?> high priority appointment(s) today.
                </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2196F3;">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $todayStats['total_today']; ?></h3>
                            <p>Total Appointments Today</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f44336;">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $todayStats['critical_count']; ?></h3>
                            <p>Critical Priority</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #FF9800;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $todayStats['high_count']; ?></h3>
                            <p>High Priority</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #FFC107;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $todayStats['pending_count']; ?></h3>
                            <p>Pending Review</p>
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
            </section>

            <style>
                .action-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
                }
            </style>

            <!-- AI-Prioritized Appointments Queue -->
            <section id="appointments" class="content-section">
                <h2>
                    <i class="fas fa-brain"></i> AI-Prioritized Appointments Queue
                    <span class="badge">Sorted by Urgency</span>
                </h2>
                <p class="section-description">
                    Appointments are automatically prioritized using AI analysis of patient symptoms. Critical cases appear first.
                </p>
                
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Patient Info</th>
                                <th>Date & Time</th>
                                <th>Symptoms</th>
                                <th>AI Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($appointments->num_rows > 0):
                                while($apt = $appointments->fetch_assoc()): 
                                    $priorityClass = $apt['priority_level'];
                                    $statusClass = $apt['status'];
                                    $isUrgent = in_array($apt['priority_level'], ['critical', 'high']);
                            ?>
                            <tr class="<?php echo $isUrgent ? 'urgent-row' : ''; ?>">
                                <td>
                                    <div class="priority-cell">
                                        <span class="priority-badge <?php echo $priorityClass; ?>">
                                            <?php 
                                            $icons = [
                                                'critical' => '🚨',
                                                'high' => '⚡',
                                                'medium' => '📋',
                                                'low' => '✓'
                                            ];
                                            echo $icons[$apt['priority_level']] . ' ' . strtoupper($apt['priority_level']); 
                                            ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($apt['full_name']); ?></strong><br>
                                    <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($apt['email']); ?></small><br>
                                    <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($apt['phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></strong><br>
                                    <small><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td>
                                    <div class="symptoms-cell">
                                        <?php echo htmlspecialchars(substr($apt['symptoms'], 0, 80)); ?>
                                        <?php if(strlen($apt['symptoms']) > 80): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="score-badge">
                                        <?php echo $apt['priority_score']; ?>/100
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons-inline">
                                        <button class="btn-small btn-primary" onclick="viewPatient(<?php echo $apt['appointment_id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($apt['status'] === 'pending'): ?>
                                        <button class="btn-small btn-success" onclick="confirmAppointment(<?php echo $apt['appointment_id']; ?>)" title="Confirm">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button class="btn-small btn-info" onclick="addNotes(<?php echo $apt['appointment_id']; ?>)" title="Add Notes">
                                            <i class="fas fa-notes-medical"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">
                                    <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 48px;"></i>
                                    <p>No appointments scheduled. Great job staying on top of your queue!</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
        function viewPatient(appointmentId) {
            window.location.href = 'appointment_details.php?id=' + appointmentId;
        }

        function confirmAppointment(appointmentId) {
            if (confirm('Confirm this appointment?')) {
                fetch('../php/update_appointment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'appointment_id=' + appointmentId + '&status=confirmed'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Appointment confirmed!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

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
                        alert('Notes saved successfully!');
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Smooth navigation
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

        // Auto-refresh every 2 minutes for critical appointments
        setInterval(() => {
            const hasCritical = document.querySelector('.urgent-row');
            if (hasCritical) {
                location.reload();
            }
        }, 120000);
    </script>
</body>
</html>

<?php
$stmt->close();
$docStmt->close();
$statsStmt->close();
$conn->close();
?>
