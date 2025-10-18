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
                    <i class="fas fa-calendar-check"></i>
                    <span>My Appointments</span>
                </a>
                <a href="#history" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Medical History</span>
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
                <h2>Dashboard Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #4CAF50;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php 
                                $countQuery = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = 'confirmed'";
                                $countStmt = $conn->prepare($countQuery);
                                $countStmt->bind_param("i", $user_id);
                                $countStmt->execute();
                                $result = $countStmt->get_result();
                                echo $result->fetch_assoc()['count'];
                            ?></h3>
                            <p>Confirmed Appointments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #FF9800;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php 
                                $pendingQuery = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND status = 'pending'";
                                $pendStmt = $conn->prepare($pendingQuery);
                                $pendStmt->bind_param("i", $user_id);
                                $pendStmt->execute();
                                $result = $pendStmt->get_result();
                                echo $result->fetch_assoc()['count'];
                            ?></h3>
                            <p>Pending Appointments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2196F3;">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php 
                                $histCount = "SELECT COUNT(*) as count FROM medical_history WHERE patient_id = ?";
                                $histStmt2 = $conn->prepare($histCount);
                                $histStmt2->bind_param("i", $user_id);
                                $histStmt2->execute();
                                $result = $histStmt2->get_result();
                                echo $result->fetch_assoc()['count'];
                            ?></h3>
                            <p>Medical Records</p>
                        </div>
                    </div>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="../appointment.html" class="action-btn primary">
                            <i class="fas fa-plus"></i> Book New Appointment
                        </a>
                        <a href="../beds.html" class="action-btn secondary">
                            <i class="fas fa-bed"></i> Check Bed Availability
                        </a>
                        <a href="../contact.html" class="action-btn secondary">
                            <i class="fas fa-phone"></i> Contact Hospital
                        </a>
                    </div>
                </div>
            </section>

            <!-- Appointments Section -->
            <section id="appointments" class="content-section">
                <h2>My Appointments</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Symptoms</th>
                                <th>Priority</th>
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
                            ?>
                            <tr>
                                <td>#<?php echo $apt['appointment_id']; ?></td>
                                <td>
                                    <?php echo date('d M Y', strtotime($apt['appointment_date'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($apt['doctor_name'] ?: 'Not Assigned'); ?><br>
                                    <small><?php echo htmlspecialchars($apt['specialization'] ?: ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($apt['symptoms'], 0, 50)) . '...'; ?></td>
                                <td>
                                    <span class="priority-badge <?php echo $priorityClass; ?>">
                                        <?php echo strtoupper($apt['priority_level']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($apt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-small" onclick="viewDetails(<?php echo $apt['appointment_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No appointments found. Book your first appointment!</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Medical History Section -->
            <section id="history" class="content-section">
                <h2>Medical History</h2>
                <div class="history-timeline">
                    <?php 
                    if ($history->num_rows > 0):
                        while($record = $history->fetch_assoc()): 
                    ?>
                    <div class="history-item">
                        <div class="history-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d M Y', strtotime($record['visit_date'])); ?>
                        </div>
                        <div class="history-content">
                            <h4>Visit with <?php echo htmlspecialchars($record['doctor_name']); ?></h4>
                            <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($record['diagnosis'] ?: 'N/A'); ?></p>
                            <p><strong>Treatment:</strong> <?php echo htmlspecialchars($record['treatment'] ?: 'N/A'); ?></p>
                            <p><strong>Medications:</strong> <?php echo htmlspecialchars($record['medications'] ?: 'N/A'); ?></p>
                            <?php if($record['notes']): ?>
                            <p><strong>Notes:</strong> <?php echo htmlspecialchars($record['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <p>No medical history recorded yet.</p>
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
    </script>
</body>
</html>

<?php
$stmt->close();
$histStmt->close();
$conn->close();
?>
