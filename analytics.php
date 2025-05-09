<?php
// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch gender distribution from the `profiles` table
$gender_query = "
    SELECT p.gender, COUNT(*) AS count 
    FROM users u 
    JOIN profiles p ON u.uid = p.uid 
    GROUP BY p.gender
";
$gender_result = $conn->query($gender_query);

// Fetch membership type distribution
$membership_query = "
    SELECT m.membership_type, COUNT(*) AS count 
    FROM memberships m 
    GROUP BY m.membership_type
";
$membership_result = $conn->query($membership_query);

// Fetch new users by time periods
$new_users_query = "
    SELECT 
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS last_week,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS last_month,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) THEN 1 ELSE 0 END) AS last_year
    FROM users
";
$new_users_result = $conn->query($new_users_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .chart-container {
            width: 80%;
            margin: 30px auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <a href="admin.php">Admin Panel</a>
        <a href="analytics.php">Analytics Panel</a>
    </div>

    <div class="container"style="text-align:center;">
        <h1 style="text-align:center;">Analytics Panel</h1>
        <h2>New Users</h2>
        <p>Last Week: <?php echo $new_users_result['last_week']; ?></p>
        <p>Last Month: <?php echo $new_users_result['last_month']; ?></p>
        <p>Last Year: <?php echo $new_users_result['last_year']; ?></p>

        <h2>Gender Distribution</h2>
<div class="chart-container" style="width: 50%; margin: 0 auto;">
    <canvas id="genderChart" style="max-width: 300px; max-height: 300px;"></canvas>
</div>


        <h2>Membership Types</h2>
        <div class="chart-container" style="width: 50%; margin: 0 auto;">
            <canvas id="membershipChart" style="max-width: 300px; max-height: 300px;"></canvas>
        </div>
    </div>

    <script>
        // Gender Chart Data
        const genderData = {
            labels: [<?php while ($row = $gender_result->fetch_assoc()) { echo '"' . $row['gender'] . '",'; } ?>],
            datasets: [{
                label: 'Gender Distribution',
                data: [<?php mysqli_data_seek($gender_result, 0); while ($row = $gender_result->fetch_assoc()) { echo $row['count'] . ','; } ?>],
                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56']
            }]
        };

        const genderConfig = {
            type: 'pie',
            data: genderData
        };

        new Chart(document.getElementById('genderChart'), genderConfig);

        // Membership Chart Data
        const membershipData = {
            labels: [<?php while ($row = $membership_result->fetch_assoc()) { echo '"' . $row['membership_type'] . '",'; } ?>],
            datasets: [{
                label: 'Membership Types',
                data: [<?php mysqli_data_seek($membership_result, 0); while ($row = $membership_result->fetch_assoc()) { echo $row['count'] . ','; } ?>],
                backgroundColor: ['#4CAF50', '#FFC107']
            }]
        };

        const membershipConfig = {
            type: 'bar',
            data: membershipData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        };

        new Chart(document.getElementById('membershipChart'), membershipConfig);
    </script>
</body>
</html>
