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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - HospiLink</title>
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
                    <span>Dashboard</span>
                </a>
                <a href="patient_profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
                <a href="../appointment.html" class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Book Appointment</span>
                </a>
                <a href="../beds.html" class="nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Bed Availability</span>
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
                <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
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
                <h2 style="text-align: center; margin-bottom: 30px;">My Appointments</h2>
                
                <div class="appointments-container-centered">
                    <?php 
                    if ($appointments->num_rows > 0):
                        while($apt = $appointments->fetch_assoc()): 
                    ?>
                    <div class="appointment-card-enhanced" data-status="<?php echo $apt['status']; ?>">
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
                            <p><?php echo htmlspecialchars(substr($apt['symptoms'], 0, 80)) . '...'; ?></p>
                        </div>
                        
                        <div class="appointment-footer">
                            <span class="status-badge-enhanced <?php echo $apt['status']; ?>">
                                <?php echo ucfirst($apt['status']); ?>
                            </span>
                            <div class="appointment-actions">
                                <button class="action-btn primary" onclick="viewDetails(<?php echo $apt['appointment_id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
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
            </section>
        </main>
    </div>

    <script>
        function viewDetails(appointmentId) {
            alert('Viewing details for appointment #' + appointmentId);
            // Implement modal or redirect to details page
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

        // Animate stat numbers on load
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current);
                    }
                }, 30);
            });
        });

        // Real-time updates simulation
        let lastUpdateTime = Date.now();
        
        function checkForUpdates() {
            // Simulate checking for appointment updates
            const now = Date.now();
            if (now - lastUpdateTime > 30000) { // Every 30 seconds
                console.log('Checking for appointment updates...');
                lastUpdateTime = now;
            }
        }
        
        // Check for updates every 10 seconds
        setInterval(checkForUpdates, 10000);
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
