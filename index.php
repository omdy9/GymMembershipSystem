<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $servername = "localhost"; 
    $username = "root"; 
    $password = ""; 
    $dbname = "gym"; 

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get login credentials
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if admin login
    if ($username === "admin@fitnesskulture.in" && $password === "admin") {
        $_SESSION['admin'] = true; // Store admin session
        header("Location: admin.php"); // Redirect to admin page
        exit();
    }

    // Query to check user credentials
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            // Store user data in session
            $_SESSION['user_id'] = $row['uid'];
            $_SESSION['username'] = $row['username'];
            header("Location: home.php"); // Redirect to home page if login successful
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Logo at the top center -->
  <div class="logo-container">
    <a href="images/Logo1.png" class="logo">
      <img src="images/Logo1.png" alt="Logo" class="logo-img">
    </a>
  </div>
  </div>
  <div class="login-container">
    <h2 style="color:white;">Login</h2>
    <form method="POST" action="index.php">
        <label for="username">Username:</label>
        <input type="text" name="username" required><br>
        <label for="password">Password:</label>
        <input type="password" name="password" required><br>
        <button type="submit" class="btn">Login</button>
        <p class="signup-link">Don't have an account?<a href="signup.php">signup</a></p>
    </form>
    <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
</div>
</body>
</html>



