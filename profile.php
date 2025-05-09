<?php
$servername = "localhost";
$db_username = "root";  // Replace with your database username
$db_password = "";  // Replace with your database password
$dbname = "gym";  // Database name

session_start();

// Check if the user is logged in, if not, redirect to index.php
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user ID from session

// Establish database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logged-in user's details from the users table
$query = "SELECT fullname, username, email FROM users WHERE uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);  // Bind the user ID as an integer
$stmt->execute();
$result = $stmt->get_result();

// Fetch the user information
$user = $result->fetch_assoc();
$fullname = $user['fullname'];
$username = $user['username'];
$email = $user['email'];

// Fetch the user's profile details if they exist
$query = "SELECT * FROM profiles WHERE uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$profile = null;
if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    $address = $profile['address'];
    $phone_number = $profile['phone_number'];
    $gender = $profile['gender'];
    $height = $profile['height'];
    $weight = $profile['weight'];
    $bmi = $profile['bmi'];
} else {
    $address = $phone_number = $gender = $height = $weight = $bmi = "";
}

// Fetch the user's membership status and type
$membership_status = "";
$membership_type = "";
$query = "SELECT membership_status, membership_type, expiry_date FROM memberships WHERE uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $membership = $result->fetch_assoc();
    $membership_status = $membership['membership_status'];
    $membership_type = $membership['membership_type'];
    $expiry_date = $membership['expiry_date'];

    // Check if the membership has expired
    if (strtotime($expiry_date) < time()) {
        $membership_status = 'Expired';
    } else {
        $membership_status = 'Active';
    }
}

// Handle save and update actions
$action = '';  // Keep track of the action: 'save' or 'update'
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];

    // Calculate BMI if height and weight are provided
    $bmi = 0;
    if ($height > 0 && $weight > 0) {
        $bmi = round($weight / (($height / 100) * ($height / 100)), 1);
    }

    // Save action: Insert data if profile does not exist
    if (isset($_POST['save'])) {
        $query = "INSERT INTO profiles (uid, address, phone_number, gender, height, weight, bmi) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssdi", $user_id, $address, $phone_number, $gender, $height, $weight, $bmi);
        $stmt->execute();
        $action = 'saved';
    }

    // Update action: Update existing profile data
    if (isset($_POST['update'])) {
        $query = "UPDATE profiles SET address = ?, phone_number = ?, gender = ?, height = ?, weight = ?, bmi = ?, updated_at = CURRENT_TIMESTAMP WHERE uid = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssddi", $address, $phone_number, $gender, $height, $weight, $bmi, $user_id);
        $stmt->execute();
        $action = 'updated';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: rgba(0, 0, 0, 0.2);
            width: 60%;
            margin-top: 300px;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.43);
            color:white;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color:white;
        }
        .logo-container {
            padding-top:0px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 150px;
            padding: 20px;
        }
        .logo {
            width: 120px;
            height: auto;
            cursor: pointer;
        }
        .btn {
            background-color:rgb(255, 0, 0);
            color: white;
            padding: 12px 20px;
            border: none;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color:rgba(255, 0, 0, 0.64);
        }
        .btn-secondary {
            background-color:rgba(33, 149, 243, 0.6);
        }
        .btn-secondary:hover {
            background-color:#2196F3;
        }
        .membership-status {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            color: #f44336;
            width: 300px;
            border: 0.2px solid rgb(0, 0, 0);
            background-color: rgb(255, 255, 255);
        }
        .membership-status.active {
            color: #4CAF50;
            width: 300px;
            border: 0.2px solid rgb(0, 0, 0);
            background-color: rgb(49, 49, 49);
        }
        .membership-type {
            font-weight: bold;
            padding: 5px;
            border-radius: 4px;
            color: white;
        }
        .gold {
            background-color: gold;
        }
         /* Navbar Styles (From home and exercise) */
         .navbar {
            height: 100%;
    width: 250px;
    position: fixed;
    top: 0;
    left: -250px;
    background-color: #333;
    color: white;
    transition: 0.3s;
    padding-top: 60px;
        }

        .navbar a {
            padding: 12px 20px;
    text-decoration: none;
    font-size: 18px;
    color: white;
    display: block;
    transition: 0.3s;
    margin: 5px 0;
        }

        .navbar a:hover {
            color: #f1f1f1;
            background-color:rgb(255, 0, 0);
        }

        .hamburger {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 30px;
            cursor: pointer;
            color: white;
            z-index: 1001;
        }
        form{
            background-color: rgba(0, 0, 0, 0.2);
            
            margin: 0 auto 30px;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0px 4px 10px rgba(5, 5, 5, 0.43);
        }
    </style>
</head>
<body>
<div class="hamburger" onclick="toggleNavbar()">&#9776;</div>
<div class="navbar" id="navbar">
<h1 style="align-text:left;">Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
    <a href="home.php">Home</a>
    <a href="exercise.php">Exercise</a>
    <a href="profile.php">Profile</a>
    <a href="diet.php">Diet</a>
    <a href="membership.php">Membership</a>
</div>
<div class="logo-container">
    <a href="home.php"><img src="images/logo1.png" alt="Home Logo" class="logo"></a>
</div>
<div class="container">
    <h1>Profile</h1>

    <!-- Display user information -->
    <p><strong>Full Name:</strong> <?php echo $fullname; ?></p>
    <p><strong>Username:</strong> <?php echo $username; ?></p>
    <p><strong>Email:</strong> <?php echo $email; ?></p>

    <!-- Display membership status -->
    <div class="membership-status <?php echo ($membership_status == 'Active') ? 'active' : ''; ?>">
        <?php 
            echo $membership_status == 'Active' ? 'Your membership is active.' : 
                 ($membership_status == 'Expired' ? 'Your membership has expired.' : 'No active membership found.');
        ?>
    </div>
    <?php if ($membership_status == 'Active' && $membership_type): ?>
        <p><strong>Membership Type:</strong> <span class="membership-type <?php echo ($membership_type == 'gold') ? 'gold' : ''; ?>"><?php echo ucfirst($membership_type); ?></span></p>
    <?php endif; ?>

    <!-- Profile Edit Form -->
    <h3>Edit Profile</h3>
    <form method="POST">
        <label for="address">Address:</label>
        <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Enter your address" required>

        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" placeholder="Enter your phone number" required>

        <label for="gender">Gender:</label>
        <select name="gender" id="gender" required>
            <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo ($gender == 'Other') ? 'selected' : ''; ?>>Other</option>
        </select>

        <label for="height">Height (in cm):</label>
        <input type="number" name="height" id="height" value="<?php echo htmlspecialchars($height); ?>" placeholder="Enter your height" required>

        <label for="weight">Weight (in kg):</label>
        <input type="number" name="weight" id="weight" value="<?php echo htmlspecialchars($weight); ?>" placeholder="Enter your weight" required><br><br>

        <!-- Save and Update buttons -->
        <?php if ($action == 'saved'): ?>
            <script>
                alert('Profile saved successfully!');
            </script>
        <?php elseif ($action == 'updated'): ?>
            <script>
                alert('Profile updated successfully!');
            </script>
        <?php endif; ?>

        <button type="submit" name="save" class="btn">Save Profile</button>
        <button type="submit" name="update" class="btn btn-secondary">Update Profile</button>
    </form>
</div>

</body><script>
    function toggleNavbar() {
        const navbar = document.getElementById('navbar');
        const mainContent = document.querySelector('.main-content');
        navbar.style.left = (navbar.style.left === '0px') ? '-250px' : '0px';
        mainContent.classList.toggle('shifted');
    }
</script>
</html>
