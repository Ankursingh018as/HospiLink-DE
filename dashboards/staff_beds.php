<?php
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get all beds with their status
$bedsQuery = "SELECT b.*, 
              ap.patient_name, ap.disease, ap.status as patient_status,
              ap.admission_date, ap.patient_id
              FROM beds b
              LEFT JOIN admitted_patients ap ON b.bed_id = ap.bed_id AND ap.discharge_date IS NULL
              ORDER BY b.ward_name, b.bed_number";
$bedsResult = $conn->query($bedsQuery);

// Get bed statistics
$statsQuery = "SELECT 
                COUNT(*) as total_beds,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_beds,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_beds,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_beds
              FROM beds";
$statsResult = $conn->query($statsQuery);
$bedStats = $statsResult ? $statsResult->fetch_assoc() : ['total_beds' => 0, 'available_beds' => 0, 'occupied_beds' => 0, 'maintenance_beds' => 0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Management - HospiLink Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/doctor-dashboard-enhanced.css">
    <link rel="icon" href="../images/hosp_favicon.png" type="image/png">
    <style>
        .bed-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid;
        }
        .stat-card.total { border-left-color: #00adb5; }
        .stat-card.available { border-left-color: #28a745; }
        .stat-card.occupied { border-left-color: #dc3545; }
        .stat-card.maintenance { border-left-color: #ffc107; }
        .stat-card h3 {
            font-size: 36px;
            margin: 10px 0;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .filters-section select, .filters-section input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filters-section select:focus, .filters-section input:focus {
            outline: none;
            border-color: #00adb5;
            box-shadow: 0 0 0 3px rgba(0, 173, 181, 0.1);
        }
        .beds-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .bed-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid;
        }
        .bed-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .bed-card.available { border-left-color: #28a745; }
        .bed-card.occupied { border-left-color: #dc3545; }
        .bed-card.maintenance { border-left-color: #ffc107; }
        .bed-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .bed-number {
            font-size: 20px;
            font-weight: 700;
            color: #0e545f;
        }
        .bed-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .bed-status.available {
            background: #d4edda;
            color: #155724;
        }
        .bed-status.occupied {
            background: #f8d7da;
            color: #721c24;
        }
        .bed-status.maintenance {
            background: #fff3cd;
            color: #856404;
        }
        .bed-info {
            margin: 10px 0;
        }
        .bed-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
            font-size: 14px;
        }
        .bed-info-item i {
            color: #00adb5;
            width: 20px;
        }
        .patient-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .patient-info h4 {
            margin: 0 0 10px 0;
            color: #0e545f;
            font-size: 16px;
        }
        .bed-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .btn-bed {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-release {
            background: #28a745;
            color: white;
        }
        .btn-release:hover {
            background: #218838;
        }
        .btn-maintenance {
            background: #ffc107;
            color: #000;
        }
        .btn-maintenance:hover {
            background: #e0a800;
        }
        .btn-details {
            background: #00adb5;
            color: white;
        }
        .btn-details:hover {
            background: #0e545f;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
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
                <a href="staff_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="staff_patients.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>All Patients</span>
                </a>
                <a href="staff_beds.php" class="nav-item active">
                    <i class="fas fa-bed"></i>
                    <span>Bed Management</span>
                </a>
                <a href="../admit.html" class="nav-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Admit Patient</span>
                </a>
                <a href="staff_profile.php" class="nav-item">
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
                <h1><i class="fas fa-bed"></i> Bed Management</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role"><i class="fas fa-user-nurse"></i> Staff</span>
                </div>
            </header>

            <!-- Bed Statistics -->
            <div class="bed-stats">
                <div class="stat-card total">
                    <i class="fas fa-bed" style="font-size: 32px; color: #00adb5;"></i>
                    <h3><?php echo $bedStats['total_beds']; ?></h3>
                    <p>Total Beds</p>
                </div>
                <div class="stat-card available">
                    <i class="fas fa-check-circle" style="font-size: 32px; color: #28a745;"></i>
                    <h3><?php echo $bedStats['available_beds']; ?></h3>
                    <p>Available</p>
                </div>
                <div class="stat-card occupied">
                    <i class="fas fa-user-injured" style="font-size: 32px; color: #dc3545;"></i>
                    <h3><?php echo $bedStats['occupied_beds']; ?></h3>
                    <p>Occupied</p>
                </div>
                <div class="stat-card maintenance">
                    <i class="fas fa-tools" style="font-size: 32px; color: #ffc107;"></i>
                    <h3><?php echo $bedStats['maintenance_beds']; ?></h3>
                    <p>Maintenance</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div style="position: relative; flex: 1; max-width: 300px;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                    <input type="text" id="searchBed" placeholder="Search by bed number or ward..." style="width: 100%; padding-left: 40px;">
                </div>
                <select id="filterStatus">
                    <option value="all">All Status</option>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
                <select id="filterWard">
                    <option value="all">All Wards</option>
                    <?php
                    $wardsQuery = "SELECT DISTINCT ward_name FROM beds ORDER BY ward_name";
                    $wardsResult = $conn->query($wardsQuery);
                    if ($wardsResult) {
                        while ($ward = $wardsResult->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($ward['ward_name']) . '">' . htmlspecialchars($ward['ward_name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Beds Grid -->
            <div class="beds-grid" id="bedsGrid">
                <?php if ($bedsResult && $bedsResult->num_rows > 0): ?>
                    <?php while ($bed = $bedsResult->fetch_assoc()): ?>
                        <div class="bed-card <?php echo $bed['status']; ?>" 
                             data-status="<?php echo $bed['status']; ?>"
                             data-ward="<?php echo htmlspecialchars($bed['ward_name']); ?>">
                            <div class="bed-header">
                                <div class="bed-number">
                                    <i class="fas fa-bed"></i> <?php echo htmlspecialchars($bed['bed_number']); ?>
                                </div>
                                <span class="bed-status <?php echo $bed['status']; ?>">
                                    <?php echo ucfirst($bed['status']); ?>
                                </span>
                            </div>
                            
                            <div class="bed-info">
                                <div class="bed-info-item">
                                    <i class="fas fa-hospital"></i>
                                    <span><strong>Ward:</strong> <?php echo htmlspecialchars($bed['ward_name']); ?></span>
                                </div>
                                <div class="bed-info-item">
                                    <i class="fas fa-tag"></i>
                                    <span><strong>Type:</strong> <?php echo htmlspecialchars($bed['bed_type']); ?></span>
                                </div>
                            </div>

                            <?php if ($bed['status'] === 'occupied' && $bed['patient_name']): ?>
                                <div class="patient-info">
                                    <h4><i class="fas fa-user-injured"></i> Current Patient</h4>
                                    <div class="bed-info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($bed['patient_name']); ?></span>
                                    </div>
                                    <div class="bed-info-item">
                                        <i class="fas fa-notes-medical"></i>
                                        <span><?php echo htmlspecialchars($bed['disease']); ?></span>
                                    </div>
                                    <div class="bed-info-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>Admitted: <?php echo date('M d, Y', strtotime($bed['admission_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="bed-actions">
                                    <button class="btn-bed btn-details" onclick="viewPatient(<?php echo $bed['patient_id']; ?>)">
                                        <i class="fas fa-eye"></i> View Patient
                                    </button>
                                </div>
                            <?php elseif ($bed['status'] === 'maintenance'): ?>
                                <div class="bed-actions">
                                    <button class="btn-bed btn-release" onclick="changeBedStatus(<?php echo $bed['bed_id']; ?>, 'available')">
                                        <i class="fas fa-check"></i> Mark Available
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="bed-actions">
                                    <button class="btn-bed btn-maintenance" onclick="changeBedStatus(<?php echo $bed['bed_id']; ?>, 'maintenance')">
                                        <i class="fas fa-tools"></i> Maintenance
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <i class="fas fa-bed"></i>
                        <h3>No Beds Found</h3>
                        <p>No bed records available in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Filter functionality
        document.getElementById('searchBed').addEventListener('keyup', filterBeds);
        document.getElementById('filterStatus').addEventListener('change', filterBeds);
        document.getElementById('filterWard').addEventListener('change', filterBeds);

        function filterBeds() {
            const search = document.getElementById('searchBed').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const ward = document.getElementById('filterWard').value;
            const cards = document.querySelectorAll('.bed-card');

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                const cardStatus = card.dataset.status;
                const cardWard = card.dataset.ward;

                const matchesSearch = text.includes(search);
                const matchesStatus = status === 'all' || cardStatus === status;
                const matchesWard = ward === 'all' || cardWard === ward;

                card.style.display = (matchesSearch && matchesStatus && matchesWard) ? '' : 'none';
            });
        }

        function changeBedStatus(bedId, newStatus) {
            if (confirm('Are you sure you want to change this bed status to ' + newStatus + '?')) {
                fetch('../php/update_bed_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ bed_id: bedId, status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function viewPatient(patientId) {
            window.location.href = 'staff_dashboard.php';
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
