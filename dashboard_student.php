<?php
session_start();
include 'db_connect.php';

// --- PHP Setup & Security (Kept from original) ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit;
}

$student_bus = $_SESSION['bus_number'];
$user_id = $_SESSION['user_id'];

// Get data (using original query structure, but highly recommend prepared statements in production)
$driver_result = $conn->query("SELECT * FROM users WHERE role='driver' AND bus_number='$student_bus'");
$driver = $driver_result->fetch_assoc();
$mentors_result = $conn->query("SELECT * FROM users WHERE role='mentor' AND bus_number='$student_bus'");
$other_students_result = $conn->query("SELECT id, name, email, register_number FROM users WHERE role='student' AND bus_number='$student_bus' AND id != {$user_id}");

$current_student_query = $conn->query("SELECT register_number FROM users WHERE id = {$user_id}");
$current_student = $current_student_query->fetch_assoc();
$register_number = $current_student['register_number'] ?? 'N/A';

$first_letter = strtoupper(substr($_SESSION['name'], 0, 1));

// Function to truncate name with ellipsis for display
function truncateName($name, $maxLength = 20) {
    if (strlen($name) > $maxLength) {
        return substr($name, 0, $maxLength) . '...';
    }
    return $name;
}

$displayName = truncateName($_SESSION['name']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-black: #121212;
            --secondary-black: #1e1e1e;
            --accent-yellow: #FFD700;
            --light-yellow: #FFF9C4;
            --text-light: #f5f5f5;
            --text-gray: #b0b0b0;
            --card-bg: #2a2a2a;
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
            font-size: 14px; /* Default body font size slightly smaller */
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-black);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            z-index: 100;
            transition: width 0.3s ease; /* Added transition */
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

        .nav-links li {
            margin-bottom: 10px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 10px 15px; /* Slightly smaller padding */
            color: var(--text-gray);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px; /* Smaller font size */
        }

        .nav-links a:hover, .nav-links a.active {
            background-color: rgba(255, 215, 0, 0.1);
            color: var(--accent-yellow);
        }

        .nav-links i {
            margin-right: 10px;
            font-size: 16px; /* Smaller icon size */
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease; /* Added transition */
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
            /* Added min-width to ensure space for ellipsis */
            min-width: 0; 
        }

        .avatar {
            width: 45px; /* Slightly smaller avatar */
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
            flex-shrink: 0; /* Prevents avatar from shrinking */
        }

        .user-details {
            overflow: hidden; /* Needed for text-overflow to work */
        }
        
        /* Ellipsis Implementation */
        .user-details h2 {
            font-size: 18px; /* Smaller font size */
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%; /* Important for overflow */
        }

        .user-details p {
            color: var(--text-gray);
            font-size: 12px; /* Smaller helper text */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Logout Button - Converted to Icon-Only */
        .logout-btn {
            background-color: transparent; /* Changed to transparent */
            color: var(--accent-yellow); /* Changed color for icon */
            border: none;
            padding: 10px; /* Reduced padding for icon */
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex; /* Use flex to center icon */
            align-items: center;
            justify-content: center;
            font-size: 20px; /* Increased icon size */
        }

        .logout-btn:hover {
            background-color: rgba(255, 215, 0, 0.1); /* Subtle hover effect */
            color: var(--accent-yellow);
            transform: none; /* Removed scale animation */
        }
        
        .logout-btn i {
            margin: 0; /* Remove margin from icon */
        }

        .logout-btn span {
            display: none; /* Hide the text */
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Adjusted min-width for better fit */
            gap: 15px; /* Reduced gap */
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--card-bg);
            border-radius: 8px; /* Slightly smaller radius */
            padding: 15px; /* Reduced padding */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-left: 3px solid var(--accent-yellow); /* Slightly thinner border */
        }

        .stat-card:hover {
            transform: translateY(-3px); /* Reduced hover lift */
        }

        .stat-card h3 {
            font-size: 14px; /* Smaller font size */
            color: var(--text-gray);
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 24px; /* Smaller number size */
            font-weight: bold;
            color: var(--accent-yellow);
        }

        /* Content Sections */
        .section-title {
            font-size: 20px; /* Smaller title size */
            margin: 25px 0 15px; /* Adjusted margins */
            padding-bottom: 8px;
            border-bottom: 2px solid var(--accent-yellow);
            display: inline-block;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px; /* Reduced gap */
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .user-card {
            display: flex;
            align-items: center;
            margin-bottom: 15px; /* Reduced margin */
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .user-card:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .user-avatar {
            width: 50px; /* Slightly smaller avatar */
            height: 50px;
            border-radius: 50%;
            background-color: var(--accent-yellow);
            color: var(--primary-black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .user-details-card h4 {
            font-size: 16px; /* Smaller font size */
            margin-bottom: 3px;
        }

        .user-details-card p {
            color: var(--text-gray);
            font-size: 12px; /* Smaller font size */
            margin-bottom: 2px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px; /* Smaller table font */
        }

        table th {
            background-color: var(--secondary-black);
            padding: 10px 12px; /* Reduced padding */
            text-align: left;
            font-weight: 600;
            color: var(--accent-yellow);
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #333;
        }

        /* Ensure student names in table also have ellipsis if needed */
        .table-name-cell {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px; /* Define a max-width for the name cell */
        }
        
        /* Inner div for table name with avatar */
        .table-name-cell > div {
            display: flex;
            align-items: center;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table-name-cell > div > div {
            flex-shrink: 0;
            width: 25px !important; 
            height: 25px !important;
            font-size: 12px !important;
        }

        /* Responsive Design (Best Display for all devices) */
        /* Tablet & smaller desktop */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                padding: 15px 0;
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
                padding: 15px; /* Reduced padding */
            }

            .header {
                padding: 5px 0 15px;
            }
            
            .user-info {
                max-width: calc(100% - 70px); /* Account for logout button width */
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: row; /* Keep elements side-by-side if space allows */
                align-items: center;
                gap: 10px;
            }
            
            .user-info {
                margin-bottom: 0;
                flex: 1; /* Allow user-info to take up max space */
            }

            .main-content {
                padding: 10px; /* Further reduced padding */
            }
        }

        /* Small Mobile */
        @media (max-width: 576px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 15px; /* Slightly adjusted position */
                left: 15px;
                z-index: 101;
                background-color: var(--accent-yellow);
                color: var(--primary-black);
                border: none;
                width: 35px; /* Smaller button */
                height: 35px;
                border-radius: 5px;
                font-size: 18px;
                cursor: pointer;
            }
            
            .sidebar.active { /* Class added by JS for mobile open state */
                width: 250px;
            }
            
            .main-content.sidebar-open {
                /* Optional: Add an overlay or push content when sidebar is open */
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
                <div class="logo-text">BusTrack</div>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="#"><i class="fas fa-map-marker-alt"></i> <span>Bus Location</span></a></li>
                <li><a href="#"><i class="fas fa-users"></i> <span>Bus Personnel</span></a></li>
                <li><a href="#"><i class="fas fa-history"></i> <span>Journey History</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> <span>Help & Support</span></a></li>
            </ul>
        </div>

        <div class="main-content" id="mainContent">
            <div class="header">
                <div class="user-info">
                    <div class="avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h2>Welcome, <?php echo $displayName; ?></h2> 
                        <p>Track your bus and view assigned personnel</p>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span> 
                </a>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <h3>Your Bus</h3>
                    <div class="number"><?php echo $student_bus; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Your Role</h3>
                    <div class="number">Student</div>
                </div>
                <div class="stat-card">
                    <h3>Register Number</h3>
                    <div class="number"><?php echo !empty($register_number) ? $register_number : 'N/A'; ?></div>
                </div>
            </div>

            <h2 class="section-title">Driver Information</h2>
            <div class="content-grid">
                <?php if($driver): ?>
                <div class="card">
                    <div class="user-card">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($driver['name'], 0, 1)); ?>
                        </div>
                        <div class="user-details-card">
                            <h4><?php echo $driver['name']; ?></h4>
                            <p><strong>Email:</strong> <?php echo $driver['email']; ?></p>
                            <p><strong>Bus Number:</strong> <?php echo $driver['bus_number']; ?></p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <p>No driver assigned to this bus yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <h2 class="section-title">Mentors on Your Bus</h2>
            <div class="content-grid">
                <?php if($mentors_result->num_rows > 0): ?>
                    <?php while($mentor = $mentors_result->fetch_assoc()): ?>
                    <div class="card">
                        <div class="user-card">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($mentor['name'], 0, 1)); ?>
                            </div>
                            <div class="user-details-card">
                                <h4><?php echo $mentor['name']; ?></h4>
                                <p><strong>Email:</strong> <?php echo $mentor['email']; ?></p>
                                <p><strong>Bus Number:</strong> <?php echo $mentor['bus_number']; ?></p>
                                <p style="font-style: italic; font-size: 11px;">Contact your mentor for any assistance during the journey</p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <p>No mentors assigned to this bus.</p>
                    </div>
                <?php endif; ?>
            </div>

          
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            // Toggle the 'active' class on the sidebar
            sidebar.classList.toggle('active');

            // Adjust main content for small screens when sidebar opens
            if (window.innerWidth <= 576) {
                if (sidebar.classList.contains('active')) {
                    mainContent.style.marginLeft = '0';
                    sidebar.style.width = '250px';
                } else {
                    sidebar.style.width = '0';
                }
            } else {
                // For non-mobile screens, use the CSS media query logic for collapse
                if (sidebar.style.width === '250px' || sidebar.style.width === '') {
                    sidebar.style.width = '80px';
                    mainContent.style.marginLeft = '80px';
                } else {
                    sidebar.style.width = '250px';
                    mainContent.style.marginLeft = '250px';
                }
            }
        });

        // Close sidebar when clicking outside on small mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const mobileBtn = document.getElementById('mobileMenuBtn');
            
            if (window.innerWidth <= 576 && 
                !sidebar.contains(event.target) && 
                !mobileBtn.contains(event.target) && 
                sidebar.classList.contains('active')) {
                
                sidebar.classList.remove('active');
                sidebar.style.width = '0'; // Explicitly set width to 0 for the close action
            }
        });

        // Re-apply responsive margins on resize to handle width transitions gracefully
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');

            if (window.innerWidth > 992) {
                sidebar.style.width = '250px';
                mainContent.style.marginLeft = '250px';
            } else if (window.innerWidth > 576) {
                sidebar.style.width = '80px';
                mainContent.style.marginLeft = '80px';
            } else {
                sidebar.style.width = '0';
                mainContent.style.marginLeft = '0';
            }
            sidebar.classList.remove('active'); // Remove active class on resize for better behavior
        });
    </script>
</body>
</html>