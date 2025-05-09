<?php
// Include Razorpay PHP SDK
require('Razorpay.php');
use Razorpay\Api\Api;

// Start session
session_start();

// Razorpay API Keys (replace with your Razorpay credentials)
$razorpay_key_id = 'rzp_test_eHSDxGE46Bjkt3';
$razorpay_key_secret = 'TJRbP1lfV0ULMnQK50coUwu1';

// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: index.php");
    exit();
}
$query = "SELECT username FROM users WHERE uid = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);  // Bind the user ID as an integer
$stmt->execute();
$result = $stmt->get_result();

// Check if the user was found
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = $user['username'];  // Get the username
} else {
    // Handle the case where no user is found (optional)
    $username = "Unknown User";
}
// Fetch membership details
$query = "SELECT membership_status, expiry_date, membership_type, payment_status FROM memberships WHERE uid = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$membership = $result->fetch_assoc();
$stmt->close();

// Check if membership is expired or active
$membership_status = 'Inactive';
$expiry_date = null;
$payment_status = null;
$membership_type = null;

if ($membership) {
    $expiry_date = $membership['expiry_date'];
    $payment_status = $membership['payment_status'];
    $membership_type = $membership['membership_type'];

    $current_date = date('Y-m-d');

    // Check if membership is expired
    if ($payment_status == 'Completed' && $expiry_date >= $current_date) {
        $membership_status = 'Active';
    } else {
        $membership_status = 'Expired';
    }
}

// Handle Razorpay payment verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $payment_id = $data['payment_id'] ?? null;
    $membership_type = $data['membership_type'] ?? null;
    $amount = $data['amount'] ?? null;

    if ($payment_id && $membership_type && $amount) {
        try {
            $api = new Api($razorpay_key_id, $razorpay_key_secret);

            // Fetch payment details from Razorpay
            $payment = $api->payment->fetch($payment_id);
            error_log("Fetched payment details: " . json_encode($payment));  // Log Razorpay payment details

            // Check if payment is authorized but not captured
            if ($payment->status == 'authorized') {
                // Manually capture the payment
                $capture = $payment->capture(array('amount' => $payment->amount));
                error_log("Capture response: " . json_encode($capture));  // Log capture response
                
                // Re-fetch payment after capture to verify status
                $payment = $api->payment->fetch($payment_id);
                error_log("Re-fetched payment details after capture: " . json_encode($payment));

                if ($payment->status != 'captured') {
                    error_log("Payment capture failed: Payment ID: $payment_id, Status: " . $payment->status);
                    echo json_encode(['success' => false, 'message' => 'Payment capture failed']);
                    exit();
                }
            }

            // Verify payment amount
            if ($payment->amount != $amount * 100) {
                error_log("Amount mismatch: Expected: " . ($amount * 100) . ", Received: " . $payment->amount);
                echo json_encode(['success' => false, 'message' => 'Amount mismatch']);
                exit();
            }

            // Payment successfully captured, insert membership data into the database
            $expiry_date = date('Y-m-d', strtotime('+1 year'));

            $stmt = $conn->prepare("INSERT INTO memberships (uid, membership_type, amount, expiry_date, membership_status, payment_status) VALUES (?, ?, ?, ?, 'Active', 'Completed')");
            $stmt->bind_param("isds", $user_id, $membership_type, $amount, $expiry_date);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Payment verification failed: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Payment</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        /* Your existing styles */
        body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    color: #333;
}

h2, h3 {
    text-align: center;
    color: #333;
}

.membership-container {
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.membership-status {
    text-align: center;
    margin-bottom: 20px;
    padding: 20px;
    background-color: #f8f8f8;
    border-radius: 8px;
}

.membership-status p {
    font-size: 16px;
    margin: 5px 0;
}

.membership-status strong {
    font-weight: bold;
    font-size: 18px;
}

.membership-options {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.membership-option {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 45%;
    padding: 20px;
    text-align: center;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.membership-option:hover {
    transform: translateY(-10px);
}

.membership-option h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

.membership-option p {
    font-size: 18px;
    color: #555;
}

.membership-option button {
    background-color: #3399cc;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.membership-option button:hover {
    background-color: #1e75b6;
}

.btn {
    background-color: #3399cc;
    color: white;
    font-size: 18px;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.btn:hover {
    background-color: #1e75b6;
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

        .main-content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
            margin-top: 350px;
        }

        .main-content.shifted {
            margin-left: 250px;
        }
    </style>
<link rel="stylesheet" href="style.css">
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
<!-- Logo centered on the page -->
<div class="logo-container">
    <a href="home.php"><img src="images/logo1.png" alt="Home Logo" class="logo"></a>
</div>
    <div class="membership-container">
        <h2>Membership Details</h2>

        <!-- Display Membership Status -->
        <div class="membership-status">
            <p>Status: <strong><?php echo $membership_status; ?></strong></p>
            <?php if ($membership_status == 'Active'): ?>
                <p>Your membership is active until: <strong><?php echo $expiry_date; ?></strong></p>
            <?php elseif ($membership_status == 'Expired'): ?>
                <p>Your membership has expired. Please renew to continue.</p>
            <?php else: ?>
                <p>You do not have an active membership.</p>
            <?php endif; ?>
        </div>

        <!-- Show payment buttons if membership is expired or inactive -->
        <?php if ($membership_status != 'Active'): ?>
            <h3>Select a Membership Plan</h3>
            <div class="membership-options">
                <div class="membership-option">
                    <h3>Normal Membership</h3>
                    <p>₹12,000</p>
                    <button type="button" class="btn" id="normal-payment-btn">Pay ₹12,000</button>
                </div>

                <div class="membership-option">
                    <h3>Gold Membership</h3>
                    <p>₹18,000</p>
                    <button type="button" class="btn" id="gold-payment-btn">Pay ₹18,000</button>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // JavaScript function to initiate payment
        document.getElementById("normal-payment-btn").addEventListener("click", function () {
            initiatePayment("normal", 12000); // Normal Membership: ₹12,000
        });

        document.getElementById("gold-payment-btn").addEventListener("click", function () {
            initiatePayment("gold", 18000); // Gold Membership: ₹18,000
        });

        function initiatePayment(membershipType, amount) {
            const options = {
                key: "<?php echo $razorpay_key_id; ?>", // Razorpay key from PHP
                amount: amount * 100, // Convert to paise
                currency: "INR",
                name: "FitnessKulture",
                description: `${membershipType.charAt(0).toUpperCase() + membershipType.slice(1)} Membership`,
                handler: function (response) {
                    console.log("Razorpay Payment Response:", response);  // Log payment response

                    fetch("membership.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            payment_id: response.razorpay_payment_id,
                            membership_type: membershipType,
                            amount: amount,
                        }),
                    })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            alert("Payment Successful!");
                            window.location.reload(); // Reload the page to show updated membership status
                        } else {
                            alert("Payment verification failed: " + data.message);
                        }
                    })
                    .catch((error) => {
                        console.error("Error in processing payment:", error);
                        alert("Error in processing payment. Please try again.");
                    });
                },
                theme: {
                    color: "#3399cc",
                },
            };

            const rzp = new Razorpay(options);
            rzp.open();
        }
        function toggleNavbar() {
        const navbar = document.getElementById('navbar');
        const mainContent = document.querySelector('.main-content');
        navbar.style.left = (navbar.style.left === '0px') ? '-250px' : '0px';
        mainContent.classList.toggle('shifted');
    }
    </script>
</body>
</html>
