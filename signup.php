<?php
// signup.php - Handle the signup and store user data in the database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

    // Database connection
    $servername = "localhost"; // Change with your DB server
    $username_db = "root"; // Change with your DB username
    $password_db = ""; // Change with your DB password
    $dbname = "gym"; // Change with your DB name

    $conn = new mysqli($servername, $username_db, $password_db, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert user data into the database
    $sql = "INSERT INTO users (fullname, username, email, password) VALUES ('$fullname', '$username', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Sign up successful!'); window.location.href = 'index.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Logo at the top center -->
  <div class="logo-container">
    <a href="images/Logo1.png" class="logo">
      <img src="images/Logo1.png" alt="Logo" class="logo-img">
    </a>
  </div>

  <!-- Sign Up Form -->
  <div class="signup-container">
    <h1>Sign Up</h1>
    <form method="POST" action="signup.php">
      <div class="form-group">
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
      </div>
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Choose a username" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Create a password" required>
      </div>
      <button type="submit" class="btn">Sign Up</button>
      <p class="login-link">Already have an account? <a href="index.php">Log in</a></p>
      <div>
        <a href="admin.php">Admin Panel</a>
        <a href="trainer.php">Trainer Panel</a>
      </div>

    </form>
  </div>
</body>
</html>
