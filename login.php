<?php
// Start session at the very beginning
session_start();

// 🔑 DATABASE CONNECTION: INCLUDE YOUR CONFIGURATION
// Ensure db_connect.php is in the same directory
include 'db_connect.php'; 

// Initialize error message variable
$login_error = '';

// Check if a user is already logged in, and if so, redirect them immediately.
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: dashboard_admin.php"); exit;
        case 'driver': header("Location: dashboard_driver.php"); exit;
        case 'mentor': header("Location: dashboard_mentor.php"); exit;
        case 'student': header("Location: dashboard_student.php"); exit;
        default: break;
    }
}

// --- Login Form Submission Logic ---
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($conn->connect_error) {
        // This is caught by the die() in db_connect, but good to have a backup error.
        $login_error = "🚨 System Error: Database connection failed. Please try again later.";
    } else {
        $sql = "SELECT id, password, role, name, bus_number, email, register_number FROM users WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // **Login Success**
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = trim(strtolower($user['role']));
                $_SESSION['name'] = $user['name'];
                $_SESSION['bus_number'] = $user['bus_number'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['register_number'] = $user['register_number'];

                switch ($_SESSION['role']) {
                    case 'admin': header("Location: dashboard_admin.php"); exit;
                    case 'driver': header("Location: dashboard_driver.php"); exit;
                    case 'mentor': header("Location: dashboard_mentor.php"); exit;
                    case 'student': header("Location: dashboard_student.php"); exit;
                    default:
                         $login_error = "Login successful, but role is undefined. Please contact support.";
                         session_unset(); 
                         session_destroy();
                        break;
                }
            } else {
                $login_error = "⚠️ Invalid Password. Please check your credentials and try again.";
            }
        } else {
            $login_error = "❌ User not found. The email address is not registered in our system.";
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

if (isset($conn) && !$conn->connect_error) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bus Tracking</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* 1. Global Setup (Full Width/Height) */
        :root {
            --color-primary: #FFD700; /* Gold/Yellow */
            --color-secondary: #1a1a1a; /* True Black */
            --color-background: #2c2c2c; /* Dark Grey */
            --color-error: #B71C1C;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-background);
        }

        /* 2. Full-Screen Flex Container */
        .login-wrapper {
            display: flex;
            /* Use min-height 100vh to ensure it covers the screen even with little content, 
               but still allows scrolling if needed on small screens */
            min-height: 100vh; 
            width: 100vw;
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
            padding: 0; /* Remove padding to maximize space usage */
        }
        
        /* 3. Login Card - Controls the overall size on large screens */
        .login-card {
            display: flex;
            width: 90%;
            max-width: 1100px; /* Optimal size for dual-column desktop view */
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
            border-radius: 12px; /* Re-introduce subtle rounding */
            overflow: hidden;
            /* Ensure the card is vertically centered within the wrapper */
            margin: auto;
        }
        
        /* 4. Left Side: Branding (The Yellow) - 40% Width */
        .image-side {
            flex: 2; /* 40% of the space (2/5) */
            background: linear-gradient(135deg, var(--color-primary), #FFA500); 
            color: var(--color-secondary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px 20px;
            text-align: center;
        }
        
        .image-side .icon {
            font-size: 6em;
            margin-bottom: 20px;
        }

        .image-side h1 {
            font-size: 2.2em;
            font-weight: 900;
            line-height: 1.2;
        }
        .image-side p {
            font-size: 1.1em;
            opacity: 0.9;
            margin-top: 15px;
        }

        /* 5. Right Side: Form Container (The Black) - 60% Width */
        .form-container {
            flex: 3; /* 60% of the space (3/5) */
            background-color: var(--color-secondary); 
            padding: 60px; 
            color: var(--color-text);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container h2 {
            font-size: 2.2em;
            margin-bottom: 40px;
            color: var(--color-primary);
            text-align: center;
            font-weight: 700;
        }
        .form-container h2 .fas {
            margin-right: 10px;
        }

        /* --- Input Field Group with Icon FIX --- */
        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        /* 🔑 FIX: Corrected Icon Positioning */
        .input-group .input-icon {
            position: absolute;
            top: 50%; /* Position icon relative to the top of the input-group */
            left: 15px;
            /* This transform centers it vertically within the input box, regardless of padding */
            transform: translateY(-50%); 
            color: #666; 
            font-size: 1.1em;
            pointer-events: none;
            z-index: 10; /* Ensure icon is above input field */
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 18px 15px 18px 50px; /* Sufficient space for the icon */
            border: none; /* Removed border for cleaner look */
            border-radius: 8px;
            background-color: #333; /* Slightly lighter input background for contrast */
            color: #fff;
            font-size: 1.05em;
            box-sizing: border-box; 
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.4);
            transition: background-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            background-color: #3e3e3e;
            box-shadow: 0 0 0 2px var(--color-primary); /* Yellow outline */
            outline: none;
        }

        /* --- Error Message Styling --- */
        .error-message {
            background-color: var(--color-error); 
            color: #FFFFFF;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            animation: shake 0.5s; 
            box-shadow: 0 4px 15px rgba(183, 28, 28, 0.4);
        }

        @keyframes shake {
            0%, 100% {transform: translateX(0);}
            20%, 60% {transform: translateX(-5px);}
            40%, 80% {transform: translateX(5px);}
        }

        /* Button & Link Styles */
        button[name="login"] {
            background-color: var(--color-primary);
            color: var(--color-secondary);
            border: none;
            padding: 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s, transform 0.1s;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        button[name="login"]:hover {
            background-color: #FFE066; 
            transform: translateY(-2px);
        }
        
        .form-container p {
            text-align: center;
            margin-top: 30px;
            font-size: 1em;
        }

        .form-container a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: bold;
        }

        /* --- Mobile Responsiveness (Best for All Devices) --- */
        @media (max-width: 768px) {
            .login-wrapper {
                min-height: 100vh;
                padding: 0;
                align-items: flex-start; /* Start stacking from the top */
            }
            .login-card {
                flex-direction: column; /* Stacked */
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }

            .image-side {
                order: 1; /* Branding on top */
                flex: none; 
                min-height: 150px;
                padding: 40px 20px;
            }
            
            .image-side .icon {
                font-size: 4em;
            }
            .image-side h1 {
                font-size: 1.8em;
            }
            
            .form-container {
                order: 2; /* Form below branding */
                flex: none;
                padding: 40px 30px; /* Reduced padding for mobile */
            }

            .form-container h2 {
                font-size: 1.8em;
                margin-bottom: 25px;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <div class="login-card">
        <div class="image-side">
            <span class="icon"><i class="fas fa-bus-simple"></i></span>
            <h1>TRANSPORT MANAGEMENT SYSTEM</h1>
            <p>Secure login for real-time fleet management and safety protocols.</p>
        </div>

        <div class="form-container">
            <h2><i class="fas fa-lock"></i> ACCOUNT LOGIN</h2>

            <?php if (!empty($login_error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($login_error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" id="email" name="email" required placeholder="Email Address" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" id="password" name="password" required placeholder="Password" autocomplete="current-password">
                </div>

                <button type="submit" name="login"><i class="fas fa-sign-in-alt"></i> SIGN IN</button>
                
                <p>Don't have an account? <a href="register.php">Register Now <i class="fas fa-arrow-right"></i></a></p>
            </form>
        </div>
    </div>
</div>

</body>
</html>