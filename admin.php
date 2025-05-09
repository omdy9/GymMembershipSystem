<?php

// Database connection
$servername = "localhost";
$db_username = "root";  // Replace with your database username
$db_password = "";  // Replace with your database password
$dbname = "gym";  // Database name

session_start();


// Establish database connection
$conn = new mysqli($servername, $db_username, $db_password, $dbname);
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $uid = $_POST['uid'];
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];

    $update_user_query = "UPDATE users SET fullname = ?, username = ?, email = ? WHERE uid = ?";
    $stmt = $conn->prepare($update_user_query);
    $stmt->bind_param("sssi", $fullname, $username, $email, $uid);
    $stmt->execute();
}

// Handle membership update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_membership'])) {
    $id = $_POST['id'];
    $membership_status = $_POST['membership_status'];
    $membership_type = $_POST['membership_type'];
    $expiry_date = $_POST['expiry_date'];

    $update_membership_query = "UPDATE memberships SET membership_status = ?, membership_type = ?, expiry_date = ? WHERE id = ?";
    $stmt = $conn->prepare($update_membership_query);
    $stmt->bind_param("sssi", $membership_status, $membership_type, $expiry_date, $id);
    $stmt->execute();
}

// Fetch all users
$users_query = "SELECT * FROM users";
$users_result = $conn->query($users_query);

// Fetch all memberships
$memberships_query = "SELECT m.*, u.fullname, u.username FROM memberships m JOIN users u ON m.uid = u.uid";
$memberships_result = $conn->query($memberships_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
            color: #333;
        }

        .navbar {
            background-color: #333;
            overflow: hidden;
        }

        .navbar a {
            float: left;
            display: block;
            color: white;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
        }

        .navbar a:hover {
            background-color: #ddd;
            color: black;
        }

        .container {
            width: 90%;
            margin: 30px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #333;
            color: white;
        }

        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #45a049;
        }

        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .form-inline input, .form-inline select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <a href="admin.php">Admin Panel</a>
        <a href="analytics.php">Analytics Panel</a>
    </div>
    
    <div class="container">
        <h1>Admin Panel</h1>

        <h2>Users</h2>
        <table>
            <thead>
                <tr>
                    <th>UID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['uid']; ?></td>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="uid" value="<?php echo $user['uid']; ?>">
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <button type="submit" name="update_user" class="btn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h2>Memberships</h2>
        <table>
            <thead>
                <tr>
                    <th>Membership ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th>Expiry Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($membership = $memberships_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $membership['id']; ?></td>
                        <td><?php echo htmlspecialchars($membership['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($membership['username']); ?></td>
                        <td><?php echo htmlspecialchars($membership['membership_status']); ?></td>
                        <td><?php echo htmlspecialchars($membership['membership_type']); ?></td>
                        <td><?php echo htmlspecialchars($membership['expiry_date']); ?></td>
                        <td>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="id" value="<?php echo $membership['id']; ?>">
                                <select name="membership_status">
                                    <option value="Active" <?php echo ($membership['membership_status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Expired" <?php echo ($membership['membership_status'] === 'Expired') ? 'selected' : ''; ?>>Expired</option>
                                    <option value="Pending" <?php echo ($membership['membership_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                </select>
                                <select name="membership_type">
                                    <option value="normal" <?php echo ($membership['membership_type'] === 'normal') ? 'selected' : ''; ?>>Normal</option>
                                    <option value="gold" <?php echo ($membership['membership_type'] === 'gold') ? 'selected' : ''; ?>>Gold</option>
                                </select>
                                <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($membership['expiry_date']); ?>" required>
                                <button type="submit" name="update_membership" class="btn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
