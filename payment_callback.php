<?php
session_start();

// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Razorpay secret key
$razorpay_secret = "TJRbP1lfV0ULMnQK50coUwu1"; // Replace with your Razorpay secret key

// Retrieve payment data from the POST request
$payment_id = $_POST['razorpay_payment_id'] ?? null;
$order_id = $_POST['razorpay_order_id'] ?? null;
$signature = $_POST['razorpay_signature'] ?? null;

// Retrieve the user ID and membership type from session
$user_id = $_SESSION['user_id'] ?? null;
$membership_type = $_SESSION['membership_type'] ?? 'normal'; // Default to normal if not set
$amount = $_POST['amount'] / 100; // Convert from paise to INR

// Verify the Razorpay signature
$generated_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, $razorpay_secret);

if ($signature === $generated_signature) {
    // Payment is successfully verified

    // Set membership expiry date (example: 1 year from now)
    $expiry_date = date('Y-m-d', strtotime('+1 year'));

    // Prepare data for insertion into the memberships table
    $stmt = $conn->prepare("INSERT INTO memberships (uid, membership_status, expiry_date, membership_type, payment_status, amount, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $payment_status = "Completed"; // As payment is successful

    // Bind parameters and execute the statement
    $stmt->bind_param("issdss", $user_id, $membership_status, $expiry_date, $membership_type, $payment_status, $amount);
    $membership_status = 'Active'; // Set the membership to active upon successful payment

    if ($stmt->execute()) {
        // Payment success and membership activated
        $_SESSION['payment_success'] = true;
        header("Location: membership.php?user_id=$user_id");
        exit();
    } else {
        // Database error
        $_SESSION['payment_success'] = false;
        header("Location: membership.php?error=database");
        exit();
    }
} else {
    // Payment verification failed
    $_SESSION['payment_success'] = false;
    header("Location: membership.php?error=verification_failed");
    exit();
}
?>
