<?php
// Start session (optional for registration, but good practice if using sessions later)
session_start();

// 🔑 DATABASE CONNECTION: INCLUDE YOUR CONFIGURATION
include 'db_connect.php'; 

// Initialize variables for post-back data and error message
$register_error = '';
$posted_data = [
    'role' => $_POST['role'] ?? '',
    'name' => $_POST['name'] ?? '',
    'email' => $_POST['email'] ?? '',
    'register_number' => $_POST['register_number'] ?? '',
    'bus_number' => $_POST['bus_number'] ?? ''
];

// --- Registration Form Submission Logic ---
if (isset($_POST['register'])) {
    $role = trim($_POST['role']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];
    
    // Validate required fields based on role
    $required_fields_ok = true;
    
    if ($role === 'student' && empty($_POST['register_number'])) {
        $register_error = "Please provide a Register Number for a student role.";
        $required_fields_ok = false;
    }
    if (in_array($role, ['driver', 'mentor', 'student']) && empty($_POST['bus_number'])) {
        if ($required_fields_ok) {
             $register_error = "Please provide a Bus Number for the selected role.";
             $required_fields_ok = false;
        }
    }

    if ($conn->connect_error) {
        $register_error = "🚨 System Error: Database connection failed.";
    } elseif ($required_fields_ok) {
        // Prepare data for insertion
        $register_number = $posted_data['register_number'] ?: null;
        $bus_number = $posted_data['bus_number'] ?: null;
        $password_hash = password_hash($password_raw, PASSWORD_BCRYPT);
        
        // Check if email already exists
        $check_sql = "SELECT email FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $register_error = "❌ Email already registered. Please login or use a different email.";
        } else {
            // Use prepared statement for secure insertion
            $sql = "INSERT INTO users (role, name, email, register_number, bus_number, password) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $role, $name, $email, $register_number, $bus_number, $password_hash);

            if ($stmt->execute()) {
                // Success: Redirect to login page
                echo "<script>alert('Registered Successfully! You can now log in.');window.location='login.php';</script>";
                exit;
            } else {
                $register_error = "Error: Registration failed. Code: " . $stmt->errno;
            }
            if (isset($stmt)) $stmt->close();
        }
        if (isset($check_stmt)) $check_stmt->close();
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
    <title>Register - Bus Tracking</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* --- CSS Global Setup (Same as Login Page) --- */
        :root {
            --color-primary: #FFD700; 
            --color-secondary: #1a1a1a; 
            --color-background: #2c2c2c; 
            --color-error: #B71C1C;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--color-background);
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh; 
            width: 100vw;
            align-items: center; 
            justify-content: center;
            padding: 0;
        }
        
        .login-card {
            display: flex;
            width: 90%;
            max-width: 1100px; 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
            border-radius: 12px; 
            overflow: hidden;
            margin: auto;
        }
        
        /* Left Side: Branding (The Yellow) - 40% Width */
        .image-side {
            flex: 2; 
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

        /* Right Side: Form Container (The Black) - 60% Width */
        .form-container {
            flex: 3; 
            background-color: var(--color-secondary); 
            padding: 40px 60px;
            color: var(--color-text);
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 100vh; 
            overflow-y: auto; /* Allows scrolling if form is very long on smaller screens */
        }

        .form-container h2 {
            font-size: 2em; 
            margin-bottom: 30px;
            color: var(--color-primary);
            text-align: center;
            font-weight: 700;
        }
        .form-container h2 .fas {
            margin-right: 10px;
        }

        /* --- Input Field Group FIX --- */
        .input-group {
            position: relative;
            margin-bottom: 15px; 
        }

        .input-group label {
             display: block;
             margin-bottom: 5px;
             font-size: 0.9em;
             color: #aaa;
        }
        
        /* 🔑 FIX: Corrected Icon Positioning */
        .input-group .input-icon {
            position: absolute;
            /* Position relative to the top of the entire input-group container */
            top: 60%; 
            left: 15px;
            /* This ensures the icon is perfectly centered vertically with the input box, 
               by offsetting it by half its own height. */
            transform: translateY(-50%); 
            color: #666; 
            font-size: 1.1em;
            pointer-events: none;
            z-index: 10;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 15px 15px 15px 50px; /* Space for the icon */
            border: none;
            border-radius: 8px;
            background-color: #333;
            color: #fff;
            font-size: 1.05em;
            box-sizing: border-box; 
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.4);
            transition: background-color 0.3s, box-shadow 0.3s;
        }
        
        /* Select element does not use an icon, so we adjust padding */
        select {
            width: 100%;
            padding: 15px; 
            border: none;
            border-radius: 8px;
            background-color: #333;
            color: #fff;
            font-size: 1.05em;
            box-sizing: border-box; 
            box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.4);
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none; 
        }

        input:focus, select:focus {
            background-color: #3e3e3e;
            box-shadow: 0 0 0 2px var(--color-primary); 
            outline: none;
        }

        /* --- Error Message Styling --- */
        .error-message {
            background-color: var(--color-error); 
            color: #FFFFFF;
            padding: 15px;
            margin-bottom: 25px;
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
        button[name="register"] {
            background-color: var(--color-primary);
            color: var(--color-secondary);
            border: none;
            padding: 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 25px;
            transition: background-color 0.3s, transform 0.1s;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
        }

        button[name="register"]:hover {
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

        /* --- Mobile Responsiveness --- */
        @media (max-width: 768px) {
            .login-wrapper {
                min-height: 100vh;
                padding: 0;
                align-items: flex-start; 
            }
            .login-card {
                flex-direction: column; 
                width: 100%;
                box-shadow: none;
                border-radius: 0;
            }

            .image-side {
                order: 1; 
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
                order: 2; 
                flex: none;
                padding: 40px 30px; 
            }

            .form-container h2 {
                font-size: 1.8em;
                margin-bottom: 25px;
            }
        }
    </style>

    <script>
    // JavaScript function to toggle fields based on role selection
    function toggleFields() {
        let role = document.getElementById('role').value;
        
        let regField = document.getElementById('registerNumberField');
        let busField = document.getElementById('busNumberField');
        let regInput = document.getElementById('reg_num_input');
        let busInput = document.getElementById('bus_num_input');
        
        // Show/Hide Fields
        regField.style.display = (role === 'student') ? 'block' : 'none';
        busField.style.display = (role === 'driver' || role === 'mentor' || role === 'student') ? 'block' : 'none';
        
        // Update Required Attributes for client-side validation
        regInput.required = (role === 'student');
        busInput.required = (role === 'driver' || role === 'mentor' || role === 'student');

        // Reset non-required fields to prevent sending irrelevant data
        if (role !== 'student') {
             regInput.value = '';
        }
        if (role === 'admin') {
             busInput.value = '';
        }
    }
    
    // Call toggleFields on page load to set initial state based on posted data
    window.onload = function() {
        toggleFields();
    };
    </script>
</head>
<body>

<div class="login-wrapper">

    <div class="login-card">
        <div class="image-side">
            <span class="icon"><i class="fas fa-user-plus"></i></span>
            <h1>NEW USER REGISTRATION</h1>
            <p>Join the system to access your dashboard and tracking services.</p>
        </div>

        <div class="form-container">
            <h2><i class="fas fa-clipboard-list"></i> REGISTER ACCOUNT</h2>

            <?php if (!empty($register_error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($register_error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="register.php">
                
                <div class="input-group">
                    <label for="role">SELECT ROLE</label>
                    <select name="role" id="role" onchange="toggleFields()" required>
                        <option value="" disabled <?php echo empty($posted_data['role']) ? 'selected' : ''; ?>>-- Select Role --</option>
                        <option value="admin" <?php echo $posted_data['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="driver" <?php echo $posted_data['role'] == 'driver' ? 'selected' : ''; ?>>Driver</option>
                        <option value="mentor" <?php echo $posted_data['role'] == 'mentor' ? 'selected' : ''; ?>>Mentor</option>
                        <option value="student" <?php echo $posted_data['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="name">FULL NAME</label>
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" id="name" name="name" required placeholder="Your Full Name" 
                           value="<?php echo htmlspecialchars($posted_data['name']); ?>">
                </div>

                <div class="input-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" id="email" name="email" required placeholder="name@example.com" 
                           value="<?php echo htmlspecialchars($posted_data['email']); ?>">
                </div>

                <div id="registerNumberField" class="input-group" style="display:none;">
                    <label for="reg_num_input">REGISTER NUMBER (Student Only)</label>
                    <span class="input-icon"><i class="fas fa-id-card"></i></span>
                    <input type="text" id="reg_num_input" name="register_number" placeholder="Enter Register Number"
                           value="<?php echo htmlspecialchars($posted_data['register_number']); ?>">
                </div>

                <div id="busNumberField" class="input-group" style="display:none;">
                    <label for="bus_num_input">BUS NUMBER (Driver/Mentor/Student)</label>
                    <span class="input-icon"><i class="fas fa-bus"></i></span>
                    <input type="text" id="bus_num_input" name="bus_number" placeholder="Enter Bus Number (e.g., B05)"
                           value="<?php echo htmlspecialchars($posted_data['bus_number']); ?>">
                </div>

                <div class="input-group">
                    <label for="password">PASSWORD</label>
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="password" id="password" name="password" required placeholder="Create a Password" autocomplete="new-password">
                </div>

                <button type="submit" name="register"><i class="fas fa-sign-in-alt"></i> COMPLETE REGISTRATION</button>
                
                <p>Already registered? <a href="login.php">Login here <i class="fas fa-arrow-right"></i></a></p>
            </form>
        </div>
    </div>
</div>

</body>
</html>