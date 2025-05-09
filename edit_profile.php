<?php
// Database connection details
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "gym"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['uid'])) {
    $uid = $_GET['uid'];

    // Fetch user data to pre-fill the form
    $stmt = $conn->prepare("SELECT * FROM users WHERE uid = ?");
    $stmt->bind_param("s", $uid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "<p>User not found.</p>";
        exit;
    }

    // Update the user profile if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $email = $_POST['email'];

        $updateStmt = $conn->prepare("UPDATE users SET fullname = ?, username = ?, email = ? WHERE uid = ?");
        $updateStmt->bind_param("ssss", $fullname, $username, $email, $uid);

        if ($updateStmt->execute()) {
            echo "<p>Profile updated successfully!</p>";
        } else {
            echo "<p>Error updating profile: " . $updateStmt->error . "</p>";
        }

        $updateStmt->close();
    }

    // Display the edit form with current data
    echo '<h1>Edit Profile</h1>';
    echo '<form method="POST">';
    echo '<label>Full Name:</label><input type="text" name="fullname" value="' . htmlspecialchars($user['fullname']) . '"><br>';
    echo '<label>Username:</label><input type="text" name="username" value="' . htmlspecialchars($user['username']) . '"><br>';
    echo '<label>Email:</label><input type="email" name="email" value="' . htmlspecialchars($user['email']) . '"><br>';
    echo '<button type="submit">Update</button>';
    echo '</form>';

    $stmt->close();
} else {
    echo "<p>No UID provided.</p>";
}

$conn->close();
?>
