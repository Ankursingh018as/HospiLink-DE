<?php
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['user_role'], ['staff', 'nurse'])) {
    header("Location: ../sign_new.html");
    exit();
}

include '../php/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - HospiLink Staff</title>
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
                <a href="staff_dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="staff_patients.php" class="nav-item">
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
                <a href="staff_profile.php" class="nav-item active">
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
                <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
                <div class="user-info">
                    <span class="user-role"><i class="fas fa-user-nurse"></i> Staff</span>
                </div>
            </header>

            <!-- Profile Edit Section -->
            <section class="content-section">
                <div class="profile-container">
                    <div class="profile-form-wrapper">
                        <form id="profileForm" class="profile-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="first_name">
                                        <i class="fas fa-user"></i> First Name
                                    </label>
                                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name">
                                        <i class="fas fa-user"></i> Last Name
                                    </label>
                                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars(explode(' ', $user_name)[1] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i> Email Address
                                    </label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly style="background: #f8f9fa; cursor: not-allowed; opacity: 0.7;">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone">
                                        <i class="fas fa-phone"></i> Phone Number
                                    </label>
                                    <input type="tel" id="phone" name="phone" value="" placeholder="Enter phone number">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="department">
                                        <i class="fas fa-building"></i> Department
                                    </label>
                                    <select id="department" name="department">
                                        <option value="">Select Department</option>
                                        <option value="General Ward">General Ward</option>
                                        <option value="ICU">ICU</option>
                                        <option value="Emergency">Emergency</option>
                                        <option value="Pediatrics">Pediatrics</option>
                                        <option value="Maternity">Maternity</option>
                                        <option value="Administration">Administration</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="staff_id">
                                        <i class="fas fa-id-badge"></i> Staff ID
                                    </label>
                                    <input type="text" id="staff_id" name="staff_id" value="" placeholder="Enter staff ID">
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="address">
                                    <i class="fas fa-map-marker-alt"></i> Address
                                </label>
                                <textarea id="address" name="address" rows="3" placeholder="Enter your address"></textarea>
                            </div>
                            
                            <div class="form-divider">
                                <span>Change Password (Optional)</span>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="current_password">
                                        <i class="fas fa-lock"></i> Current Password
                                    </label>
                                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">
                                        <i class="fas fa-key"></i> New Password
                                    </label>
                                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-save">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn-cancel" onclick="window.location.href='staff_dashboard.php'">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </button>
                            </div>
                        </form>
                        
                        <div id="profileMessage" class="profile-message"></div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Load profile data
        async function loadProfileData() {
            try {
                const response = await fetch('../php/get_profile.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('first_name').value = data.data.first_name || '';
                    document.getElementById('last_name').value = data.data.last_name || '';
                    document.getElementById('email').value = data.data.email || '';
                    document.getElementById('phone').value = data.data.phone || '';
                    document.getElementById('department').value = data.data.department || '';
                    document.getElementById('staff_id').value = data.data.staff_id || '';
                    document.getElementById('address').value = data.data.address || '';
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
        }

        // Handle profile form submission
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('profileMessage');
            
            try {
                const response = await fetch('../php/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                messageDiv.className = 'profile-message ' + (result.success ? 'success' : 'error');
                messageDiv.textContent = result.message;
                
                if (result.success) {
                    // Clear password fields
                    document.getElementById('current_password').value = '';
                    document.getElementById('new_password').value = '';
                    
                    // Redirect to dashboard after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'staff_dashboard.php';
                    }, 2000);
                }
            } catch (error) {
                messageDiv.className = 'profile-message error';
                messageDiv.textContent = 'An error occurred. Please try again.';
            }
        });

        // Load profile data on page load
        loadProfileData();
    </script>
</body>
</html>

<?php
$conn->close();
?>
