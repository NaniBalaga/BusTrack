<?php
session_start();
// NOTE: db_connect.php must be included here
include 'db_connect.php'; 

// --- Access Control: Only Admins Can Access ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- Fetch All Users by Role (Using prepared statements - Good!) ---
function fetchUsersByRole($conn, $role) {
    // NOTE: Register number is NULL for drivers/mentors, but including it for students
    $stmt = $conn->prepare("SELECT id, name, email, register_number, bus_number FROM users WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch data
$students = fetchUsersByRole($conn, 'student');
$drivers  = fetchUsersByRole($conn, 'driver');
$mentors  = fetchUsersByRole($conn, 'mentor');

// Store counts for the stat cards
$student_count = $students->num_rows;
$driver_count = $drivers->num_rows;
$mentor_count = $mentors->num_rows;
$total_users = $student_count + $driver_count + $mentor_count;

// Move result pointers back to the beginning after counting, so we can loop through them later
$students->data_seek(0);
$drivers->data_seek(0);
$mentors->data_seek(0);

$conn->close();

// Default admin name if not set
$admin_name = $_SESSION['name'] ?? 'Admin';
$first_letter = strtoupper(substr($admin_name, 0, 1));

// Function to truncate name with ellipsis for display (matching student dashboard)
function truncateName($name, $maxLength = 20) {
    if (strlen($name) > $maxLength) {
        return substr($name, 0, $maxLength) . '...';
    }
    return $name;
}

$displayName = truncateName($admin_name);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BusTrack</title>
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
            transition: width 0.3s ease;
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
        
        /* Ellipsis for Nav Text on Collapse */
        .nav-links span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            overflow: hidden; /* For ellipsis on name */
            flex-grow: 1; /* Allow user info to grow and push logout right */
            min-width: 0; /* Ensures content respects flex boundary */
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
            overflow: hidden; /* Ensures h2 respects width constraint */
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

        /* Logout Icon-Button (Anchored right) */
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
            flex-shrink: 0; /* Ensures the icon doesn't shrink */
        }
        
        .logout-btn span {
            display: none; 
        }

        .logout-btn:hover {
            background-color: rgba(255, 0, 76, 0.1);
            color: var(--red-danger);
        }

        /* --- Stats Cards --- */
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
        
        .stat-card.total {
             border-left: 3px solid var(--red-danger);
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

        .stat-card.total .number {
            color: var(--red-danger);
        }


        /* --- Content Sections and Tables --- */
        .section-title {
            font-size: 20px;
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--accent-yellow); 
            display: block;
        }

        .table-wrapper {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto; /* KEY: Enables horizontal scroll when table content exceeds container width */
            padding: 15px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            min-width: 700px; /* Increased min-width to ensure scrolling is necessary on tablets */
            border-collapse: collapse;
            font-size: 13px;
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
        
        /* Highlighting Bus Number */
        .bus-number-cell {
            font-weight: bold;
            color: var(--accent-yellow);
        }

        /* --- Responsive Design (Best Display) --- */
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

        @media (max-width: 768px) {
            /* Keep header components side-by-side */
            .header {
                flex-direction: row; 
                align-items: center;
                gap: 10px;
                padding: 10px 0;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .main-content {
                padding: 10px;
            }
        }

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
            
            .sidebar.active { 
                width: 250px;
            }
            
            /* Give more space to user info and ensure logout is always right */
            .user-info {
                max-width: calc(100% - 60px); /* Leave space for the logout icon */
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
                <div class="logo-text">BusTrack Admin</div>
            </div>
            <ul class="nav-links">
                <li><a href="#overview" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="#students"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a></li>
                <li><a href="#drivers"><i class="fas fa-truck"></i> <span>Manage Drivers</span></a></li>
                <li><a href="#mentors"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Mentors</span></a></li>
                <li><a href="#"><i class="fas fa-route"></i> <span>View Routes</span></a></li>
            </ul>
        </div>

        <div class="main-content" id="mainContent">
            <div class="header">
                <div class="user-info">
                    <div class="avatar">
                        <?php echo $first_letter; ?>
                    </div>
                    <div class="user-details">
                        <h2>Welcome Back, <?php echo htmlspecialchars($displayName); ?></h2> 
                        <p>Administrator Panel</p>
                    </div>
                </div>
                
                <a href="logout.php" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>

            <h2 class="section-title" id="overview">Overview Statistics</h2>
            <div class="stats-container">
                <div class="stat-card total">
                    <h3>Total Users</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <div class="number"><?php echo $student_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Drivers</h3>
                    <div class="number"><?php echo $driver_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Mentors</h3>
                    <div class="number"><?php echo $mentor_count; ?></div>
                </div>
            </div>

            <h2 class="section-title" id="students">All Students</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Register Number</th>
                            <th>Email</th>
                            <th>Bus Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $students->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars(truncateName($row['name'], 25)) ?></td>
                                <td><?= htmlspecialchars($row['register_number']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td class="bus-number-cell"><?= htmlspecialchars($row['bus_number']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title" id="drivers">All Drivers</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Bus Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $drivers->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars(truncateName($row['name'], 25)) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td class="bus-number-cell"><?= htmlspecialchars($row['bus_number']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <h2 class="section-title" id="mentors">All Mentors</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Bus Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $mentors->fetch_assoc()) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars(truncateName($row['name'], 25)) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td class="bus-number-cell"><?= htmlspecialchars($row['bus_number']) ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('active');

            if (window.innerWidth <= 576) {
                // Mobile: toggle full sidebar width
                sidebar.style.width = sidebar.classList.contains('active') ? '250px' : '0';
            } else {
                // Tablet/Desktop: toggle between 250px and 80px mini-sidebar
                let isExpanded = sidebar.style.width === '250px' || sidebar.style.width === '';
                let newWidth = isExpanded ? '80px' : '250px';
                
                // Logic to handle click when already at 80px
                if (window.innerWidth <= 992) {
                     if(sidebar.style.width === '80px') {
                         newWidth = '250px';
                     } else if (sidebar.style.width === '250px' || sidebar.style.width === '') {
                         newWidth = '80px';
                     }
                }
                
                sidebar.style.width = newWidth;
                mainContent.style.marginLeft = newWidth;
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
                sidebar.style.width = '0';
            }
        });
        
        // Handle sidebar state on resize
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
            sidebar.classList.remove('active');
        });
    </script>
</body>
</html>