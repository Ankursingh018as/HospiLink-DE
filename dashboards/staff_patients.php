<?php
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get all admitted patients with real data from patient_admissions + users tables
$patientsQuery = "SELECT pa.*, 
                  u.first_name, u.last_name, u.phone, u.email,
                  b.ward_name, b.bed_number, b.bed_type,
                  CONCAT(u.first_name, ' ', u.last_name) as patient_name,
                  pa.admission_reason as disease
                  FROM patient_admissions pa 
                  JOIN users u ON pa.patient_id = u.user_id
                  LEFT JOIN beds b ON pa.bed_id = b.bed_id
                  ORDER BY 
                    CASE WHEN pa.discharge_date IS NULL THEN 0 ELSE 1 END,
                    CASE pa.status
                        WHEN 'critical' THEN 1
                        WHEN 'moderate' THEN 2
                        WHEN 'stable' THEN 3
                        WHEN 'active' THEN 4
                        ELSE 5
                    END,
                    pa.admission_date DESC";
$patientsResult = $conn->query($patientsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Patients - HospiLink Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/doctor-dashboard-enhanced.css">
    <link rel="icon" href="../images/hosp_favicon.png" type="image/png">
    <style>
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
            max-width: 600px;
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
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            font-size: 20px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .modal-header h3 i {
            color: #00adb5;
        }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #9ca3af;
            transition: all 0.3s;
        }
        .modal-close-btn:hover {
            color: #ef4444;
            transform: rotate(90deg);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group label i {
            color: #00adb5;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00adb5;
            box-shadow: 0 0 0 4px rgba(0, 173, 181, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .filters-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-item select, .filter-item input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filter-item select:focus, .filter-item input:focus {
            outline: none;
            border-color: #00adb5;
            box-shadow: 0 0 0 3px rgba(0, 173, 181, 0.1);
        }
        .patients-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .patients-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .patients-table thead {
            background: linear-gradient(135deg, #00adb5 0%, #0e545f 100%);
            color: white;
        }
        .patients-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .patients-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .patients-table tbody tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-stable { background: #d4edda; color: #155724; }
        .status-active { background: #d4edda; color: #155724; }
        .status-moderate { background: #fff3cd; color: #856404; }
        .status-critical { background: #f8d7da; color: #721c24; }
        .status-discharged { background: #e2e3e5; color: #383d41; }
        .bed-info {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #00adb5;
            font-weight: 500;
        }
        .no-bed {
            color: #dc3545;
            font-style: italic;
        }
        .action-btns {
            display: flex;
            gap: 5px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-view {
            background: #00adb5;
            color: white;
        }
        .btn-view:hover {
            background: #0e545f;
        }
        .btn-discharge {
            background: #dc3545;
            color: white;
        }
        .btn-discharge:hover {
            background: #c82333;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .summary-card h3 {
            font-size: 32px;
            margin: 10px 0;
            color: #00adb5;
        }
        .summary-card p {
            color: #666;
            font-size: 14px;
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
                <a href="staff_patients.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>All Patients</span>
                </a>
                <a href="staff_beds.php" class="nav-item">
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
                <h1><i class="fas fa-users"></i> All Patients</h1>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <span class="user-role"><i class="fas fa-user-nurse"></i> Staff</span>
                </div>
            </header>

            <!-- Summary Stats -->
            <div class="stats-summary">
                <?php
                $totalPatients = 0;
                $activePatients = 0;
                $dischargedPatients = 0;
                $criticalCount = 0;
                
                if ($patientsResult) {
                    $tempResult = $conn->query($patientsQuery);
                    while ($row = $tempResult->fetch_assoc()) {
                        $totalPatients++;
                        if ($row['discharge_date'] === null) {
                            $activePatients++;
                            if ($row['status'] === 'critical') $criticalCount++;
                        } else {
                            $dischargedPatients++;
                        }
                    }
                }
                ?>
                <div class="summary-card">
                    <p>Total Patients</p>
                    <h3><?php echo $totalPatients; ?></h3>
                </div>
                <div class="summary-card">
                    <p>Active Patients</p>
                    <h3><?php echo $activePatients; ?></h3>
                </div>
                <div class="summary-card">
                    <p>Critical Patients</p>
                    <h3 style="color: #dc3545;"><?php echo $criticalCount; ?></h3>
                </div>
                <div class="summary-card">
                    <p>Discharged</p>
                    <h3 style="color: #6c757d;"><?php echo $dischargedPatients; ?></h3>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-bar">
                <div class="filter-group">
                    <div class="filter-item" style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999;"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, disease, or ID..." style="width: 300px; padding-left: 40px;">
                    </div>
                    <div class="filter-item">
                        <select id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="active">Active Only</option>
                            <option value="stable">Stable</option>
                            <option value="moderate">Moderate</option>
                            <option value="critical">Critical</option>
                            <option value="discharged">Discharged</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <select id="bedFilter">
                            <option value="all">All Beds</option>
                            <option value="assigned">Bed Assigned</option>
                            <option value="unassigned">No Bed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Patients Table -->
            <div class="patients-table">
                <table id="patientsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Disease</th>
                            <th>Status</th>
                            <th>Bed</th>
                            <th>Admission Date</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($patientsResult && $patientsResult->num_rows > 0): ?>
                            <?php while ($patient = $patientsResult->fetch_assoc()): ?>
                                <tr data-status="<?php echo $patient['status']; ?>" 
                                    data-bed="<?php echo $patient['bed_id'] ? 'assigned' : 'unassigned'; ?>"
                                    data-active="<?php echo $patient['discharge_date'] ? 'discharged' : 'active'; ?>">
                                    <td>#<?php echo $patient['patient_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($patient['patient_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($patient['disease']); ?></td>
                                    <td>
                                        <?php if ($patient['discharge_date']): ?>
                                            <span class="status-badge status-discharged">Discharged</span>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo $patient['status']; ?>">
                                                <?php echo ucfirst($patient['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($patient['bed_id']): ?>
                                            <div class="bed-info">
                                                <i class="fas fa-bed"></i>
                                                <?php echo htmlspecialchars($patient['ward_name'] . ' - ' . $patient['bed_number']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-bed">No bed assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($patient['admission_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn-sm btn-view" onclick="viewPatient('<?php echo htmlspecialchars($patient['qr_code_token'] ?? ''); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if (!$patient['discharge_date']): ?>
                                                <button class="btn-sm btn-discharge" onclick="openDischargeModal(<?php echo $patient['patient_id']; ?>, '<?php echo htmlspecialchars($patient['patient_name']); ?>')">
                                                    <i class="fas fa-sign-out-alt"></i> Discharge
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-user-injured" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    No patients found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);
        document.getElementById('bedFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const bedFilter = document.getElementById('bedFilter').value;
            const rows = document.querySelectorAll('#patientsTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.dataset.status;
                const bedStatus = row.dataset.bed;
                const activeStatus = row.dataset.active;

                let matchesSearch = text.includes(searchTerm);
                let matchesStatus = true;
                let matchesBed = true;

                // Status filter
                if (statusFilter === 'active') {
                    matchesStatus = activeStatus === 'active';
                } else if (statusFilter === 'discharged') {
                    matchesStatus = activeStatus === 'discharged';
                } else if (statusFilter !== 'all') {
                    matchesStatus = status === statusFilter;
                }

                // Bed filter
                if (bedFilter !== 'all') {
                    matchesBed = bedStatus === bedFilter;
                }

                row.style.display = (matchesSearch && matchesStatus && matchesBed) ? '' : 'none';
            });
        }

        function viewPatient(token) {
            if (token && token.trim() !== '') {
                window.location.href = '../patient-status.php?token=' + encodeURIComponent(token);
            } else {
                alert('No secure bedside QR token found for this patient.');
            }
        }

        function openDischargeModal(patientId, patientName) {
            document.getElementById('dischargePatientId').value = patientId;
            document.getElementById('dischargePatientName').value = patientName;
            document.getElementById('dischargeModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }

        document.getElementById('dischargeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Discharging...';
            
            fetch('../php/discharge_patient.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Patient discharged successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Discharge Patient';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while discharging the patient.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Discharge Patient';
            });
        });
    </script>

    <!-- Discharge Modal -->
    <div id="dischargeModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-sign-out-alt" style="color:#ef4444;"></i> Discharge Patient</h3>
                <button onclick="closeModal('dischargeModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
            </div>
            <form id="dischargeForm">
                <input type="hidden" id="dischargePatientId" name="patient_id">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Patient Name</label>
                    <input type="text" id="dischargePatientName" readonly class="readonly-input" style="background:#f3f4f6; cursor:not-allowed; opacity:0.8;">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-file-medical"></i> Discharge Summary</label>
                    <textarea name="discharge_summary" rows="4" placeholder="Enter discharge summary and instructions..." required></textarea>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-sm btn-view" style="background:#6c757d;" onclick="closeModal('dischargeModal')">Cancel</button>
                    <button type="submit" class="btn-sm btn-discharge"><i class="fas fa-check"></i> Discharge Patient</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
