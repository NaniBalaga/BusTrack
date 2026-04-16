<?php
session_start();
include 'db_connect.php';

// --- PHP Setup & Security ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'mentor') {
    header("Location: login.php");
    exit;
}

$mentor_bus = $_SESSION['bus_number'];
$user_id = $_SESSION['user_id'];
$mentor_name = $_SESSION['name'];
$first_letter = strtoupper(substr($mentor_name, 0, 1));

// --- Database Queries ---

// Get driver details for the same bus (Using Prepared Statement for best practice)
$driver = null;
if ($stmt = $conn->prepare("SELECT name, email, bus_number FROM users WHERE role='driver' AND bus_number=?")) {
    $stmt->bind_param("s", $mentor_bus);
    $stmt->execute();
    $driver_result = $stmt->get_result();
    $driver = $driver_result->fetch_assoc();
    $stmt->close();
}

// Get student details for the same bus
$students_result = $conn->query("SELECT id, name, email, register_number FROM users WHERE role='student' AND bus_number='$mentor_bus'");

// Get other mentors for the same bus
$other_mentors_result = $conn->query("SELECT name, email FROM users WHERE role='mentor' AND bus_number='$mentor_bus' AND id != {$user_id}");

// Get total student count
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND bus_number='$mentor_bus'")->fetch_assoc()['total'];

// --- Utility Function for Display ---
function truncateName($name, $maxLength = 20) {
    if (strlen($name) > $maxLength) {
        return substr($name, 0, $maxLength) . '...';
    }
    return $name;
}

$displayName = truncateName($mentor_name);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard - BusTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- Colors and Defaults (Black & Yellow Theme) --- */
        :root {
            --primary-black: #121212;
            --secondary-black: #1e1e1e;
            --accent-yellow: #FFD700; /* Primary accent */
            --light-yellow: #FFF9C4;
            --text-light: #f5f5f5;
            --text-gray: #b0b0b0;
            --card-bg: #2a2a2a;
            --red-danger: #ff004c; /* For Logout/Critical elements */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--primary-black);
            color: var(--text-light);
            line-height: 1.6;
            font-size: 14px;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar Navigation --- */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-black);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 100;
            transition: width 0.3s ease, left 0.3s ease; 
        }

        .logo {
            display: flex;
            align-items: center;
            padding: 0 20px 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }

        .logo-icon {
            background-color: var(--accent-yellow);
            color: var(--primary-black);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
            font-size: 20px;
        }

        .logo-text {
            font-size: 20px;
            font-weight: bold;
        }

        .nav-links {
            list-style: none;
            padding: 0 15px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: var(--text-gray);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .nav-links a:hover, .nav-links a.active {
            background-color: rgba(255, 215, 0, 0.1);
            color: var(--accent-yellow);
        }

        .nav-links i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        /* --- Main Content --- */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            overflow: hidden; 
            flex-grow: 1; 
            min-width: 0; 
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--accent-yellow);
            color: var(--primary-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .user-details {
            overflow: hidden;
        }

        .user-details h2 {
            font-size: 20px;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .user-details p {
            color: var(--accent-yellow);
            font-size: 14px;
            font-weight: 600;
        }

        /* Logout Icon-Button */
        .logout-btn {
            background-color: transparent; 
            color: var(--red-danger); 
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            flex-shrink: 0; 
        }
        
        .logout-btn span {
            display: none; 
        }

        .logout-btn:hover {
            background-color: rgba(255, 0, 76, 0.1);
            color: var(--red-danger);
        }

        /* --- Stats Cards --- */
        .section-title {
            font-size: 20px;
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--accent-yellow); 
            display: block;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-left: 3px solid var(--accent-yellow); 
        }
        
        .stat-card:nth-child(2) { /* Highlight student count */
             border-left-color: var(--red-danger);
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card h3 {
            font-size: 14px;
            color: var(--text-gray);
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: var(--accent-yellow);
        }

        .stat-card:nth-child(2) .number {
             color: var(--red-danger);
        }

        /* --- Table/User Info Cards --- */
        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .user-info-card {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--accent-yellow);
            color: var(--primary-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 24px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .user-details-card h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .user-details-card p {
            color: var(--text-gray);
            font-size: 14px;
            margin-bottom: 3px;
        }

        /* Table Styling */
        table {
            width: 100%;
            min-width: 650px; /* Ensures horizontal scroll */
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 10px;
        }

        table th {
            background-color: var(--secondary-black);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--light-yellow); 
            border: none;
            border-bottom: 2px solid var(--accent-yellow);
        }

        table td {
            padding: 10px 15px;
            border-bottom: 1px solid #333;
            text-align: left;
        }

        table tr:nth-child(even) {
            background-color: #262626;
        }

        table tr:hover {
            background-color: #383838;
        }
        
        .table-name-cell {
             white-space: nowrap;
             overflow: hidden;
             text-overflow: ellipsis;
        }

        /* --- Responsive Design --- */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .logo-text, .nav-links span {
                display: none;
            }
            
            .nav-links a {
                justify-content: center;
                padding: 15px;
            }
            
            .nav-links i {
                margin-right: 0;
                font-size: 22px;
            }
            
            .main-content {
                margin-left: 80px;
                padding: 15px;
            }
        }

        /* Mobile Devices (max-width: 576px) */
        @media (max-width: 576px) {
            .sidebar {
                width: 250px;
                left: -250px; 
                position: fixed;
            }
            
            .sidebar.active { 
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .header {
                flex-direction: row; 
                align-items: center;
                gap: 10px;
            }
            
            .user-info {
                max-width: calc(100% - 60px); 
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 101;
                background-color: var(--accent-yellow);
                color: var(--primary-black);
                border: none;
                width: 35px;
                height: 35px;
                border-radius: 5px;
                font-size: 18px;
                cursor: pointer;
            }

            .main-content .header {
                margin-top: 50px; 
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        .mobile-menu-btn {
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>

        <div class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-bus"></i>
                </div>
                <div class="logo-text">BusTrack Mentor</div>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="#driver-info"><i class="fas fa-truck"></i> <span>Driver Contact</span></a></li>
                <li><a href="#students-list"><i class="fas fa-user-graduate"></i> <span>Student List</span></a></li>
                <li><a href="#mentors-list"><i class="fas fa-users"></i> <span>Team Mentors</span></a></li>
                <li><a href="#"><i class="fas fa-map-marker-alt"></i> <span>Bus Location</span></a></li>
            </ul>
        </div>

        <div class="main-content" id="mainContent">
            <div class="header">
                <div class="user-info">
                    <div class="avatar">
                        <?php echo $first_letter; ?>
                    </div>
                    <div class="user-details">
                        <h2>Welcome, <?php echo htmlspecialchars($displayName); ?></h2> 
                        <p>Bus Mentor Panel</p>
                    </div>
                </div>
                
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>

            <h2 class="section-title">Bus Overview</h2>
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Assigned Bus</h3>
                    <div class="number"><?php echo htmlspecialchars($mentor_bus); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $total_students; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Your Role</h3>
                    <div class="number">Mentor</div>
                </div>
            </div>

            <h2 class="section-title" id="driver-info">Driver Information</h2>
            <?php if($driver): ?>
            <div class="card">
                <div class="user-info-card">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($driver['name'], 0, 1)); ?>
                    </div>
                    <div class="user-details-card">
                        <h4><?php echo htmlspecialchars($driver['name']); ?></h4>
                        <p><strong>Role:</strong> Driver</p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($driver['email']); ?></p>
                        <p><strong>Bus:</strong> <?php echo htmlspecialchars($driver['bus_number']); ?></p>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <p>No driver assigned to this bus yet.</p>
            <?php endif; ?>

            <h2 class="section-title" id="students-list">Students on Bus <?php echo htmlspecialchars($mentor_bus); ?></h2>
            <div class="card">
                <?php if($students_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Register Number</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($student = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="table-name-cell"><?php echo htmlspecialchars(truncateName($student['name'], 25)); ?></td>
                                    <td><?php echo htmlspecialchars($student['register_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No students assigned to this bus.</p>
                <?php endif; ?>
            </div>

            <h2 class="section-title" id="mentors-list">Other Mentors on This Bus</h2>
            <div class="card">
                <?php if($other_mentors_result->num_rows > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($mentor = $other_mentors_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="table-name-cell"><?php echo htmlspecialchars(truncateName($mentor['name'], 25)); ?></td>
                                    <td><?php echo htmlspecialchars($mentor['email']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>You are the only mentor assigned to this bus.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');

        // Function to handle sidebar toggle on mobile (slide in/out)
        function toggleMobileSidebar() {
            sidebar.classList.toggle('active');
        }

        // Function to handle sidebar toggle on desktop/tablet (expand/collapse)
        function toggleDesktopSidebar() {
            let isExpanded = sidebar.style.width === '250px' || sidebar.style.width === '';
            let newWidth = isExpanded ? '80px' : '250px';
            
            sidebar.style.width = newWidth;
            mainContent.style.marginLeft = newWidth;
        }

        mobileMenuBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            if (window.innerWidth <= 576) {
                toggleMobileSidebar();
            } else {
                toggleDesktopSidebar();
            }
        });

        // Close sidebar when clicking outside on small mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 576 && 
                !sidebar.contains(event.target) && 
                !mobileMenuBtn.contains(event.target) && 
                sidebar.classList.contains('active')) {
                
                sidebar.classList.remove('active');
            }
        });
        
        // Handle sidebar state on resize/load
        function handleResize() {
            if (window.innerWidth > 992) {
                sidebar.style.width = '250px';
                mainContent.style.marginLeft = '250px';
                sidebar.classList.remove('active'); 
            } else if (window.innerWidth > 576) {
                sidebar.style.width = '80px';
                mainContent.style.marginLeft = '80px';
                sidebar.classList.remove('active'); 
            } else {
                // Mobile state: sidebar should be off-screen
                sidebar.style.width = '250px'; 
                sidebar.style.left = '-250px';
                mainContent.style.marginLeft = '0';
                sidebar.classList.remove('active'); 
            }
        }

        window.addEventListener('resize', handleResize);
        window.addEventListener('load', handleResize);
    </script>
</body>
</html>