<?php
// Database connection details
$servername = "localhost"; // Your database server
$username = "root"; // Your database username
$password = ""; // Your database password
$dbname = "gym"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get JSON data from POST request
$data = json_decode(file_get_contents("php://input"));

// Check if required fields are present
if (isset($data->uid) && isset($data->fullname) && isset($data->username) && isset($data->email)) {
    $uid = $data->uid;
    $fullname = $data->fullname;
    $username = $data->username;
    $email = $data->email;
    $createdAt = $data->createdAt;

    // Prepare and bind the SQL statement
    $stmt = $conn->prepare("INSERT INTO users (uid, fullname, username, email, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $uid, $fullname, $username, $email, $createdAt);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "User data saved successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error saving user data: " . $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
}

// Close the database connection
$conn->close();
?>
