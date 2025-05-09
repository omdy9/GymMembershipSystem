<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    // If not logged in, redirect to login page
    header("Location: index.php");
    exit;
}

// User is logged in, retrieve user information
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Page</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="home-container">
    <h1>Welcome, <?php echo $user['fullname']; ?>!</h1>
    <p>You are logged in.</p>
    <p><a href="logout.php">Logout</a></p>
  </div>
</body>
</html>
