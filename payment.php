<?php
session_start();

// Check if user is logged in
if (!isset($_GET['user_id']) || !isset($_GET['amount']) || !isset($_GET['membership_type'])) {
    header("Location: membership.php");
    exit();
}

$user_id = $_GET['user_id'];
$amount = $_GET['amount'];
$membership_type = $_GET['membership_type'];

// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simulate payment processing (this is where you'd integrate a payment gateway like Razorpay, PayPal, etc.)
$payment_success = true;  // Simulating a successful payment for demo purposes

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
            border-radius: 10px;
        }

        h1 {
            color: #333;
            text-align: center;
        }

        .timer {
            font-size: 20px;
            font-weight: bold;
            color: #ff6347;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment <?php echo $payment_success ? "Successful" : "Failed"; ?></h1>
        <?php
        if ($payment_success) {
            $expiry_date = date('Y-m-d', strtotime('+1 year'));  // Add 1 year for new membership

            // Update the membership status
            $stmt = $conn->prepare("INSERT INTO memberships (uid, membership_type, amount, expiry_date, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isds", $user_id, $membership_type, $amount, $expiry_date);
            $stmt->execute();

            echo "<p>Your $membership_type membership has been activated. Thank you for your payment!</p>";
        } else {
            echo "<p>There was an issue with your payment. Please try again.</p>";
        }
        ?>
        <div class="timer">Redirecting in 15 seconds...</div>
    </div>

    <script>
        // Set the timer for 15 seconds
        var countdown = 15;
        var countdownElement = document.querySelector('.timer');
        
        var timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = "Redirecting in " + countdown + " seconds...";
            if (countdown <= 0) {
                clearInterval(timer); // Stop the timer
                window.location.href = "membership.php"; // Redirect to membership.php
            }
        }, 1000);
    </script>
</body>
</html>
