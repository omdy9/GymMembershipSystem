<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "gym";

// Get Razorpay payment ID and signature sent from Razorpay
$payment_id = $_POST['razorpay_payment_id'];
$order_id = $_POST['razorpay_order_id'];
$signature = $_POST['razorpay_signature'];

$secret = "your_secret_key";  // Razorpay secret key (make sure to replace this with your secret key)

$generated_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, $secret);

// Check if the signature matches
if ($generated_signature == $signature) {
    // Payment is verified. Update membership status.
    
    // Get the user ID and membership type (these values can be passed from the form)
    $user_id = $_POST['user_id'];
    $membership_type = $_POST['membership_type'];
    
    // Update membership status in the database
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $expiry_date = date('Y-m-d', strtotime('+1 year'));  // Set the membership expiry date to 1 year from now
    $query = "INSERT INTO memberships (uid, membership_type, expiry_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $membership_type, $expiry_date);
    $stmt->execute();
    
    // You can also update any other necessary tables, like user info or activity log

    // Redirect to membership.php with success message
    header("Location: membership.php?status=active");
    exit();
} else {
    // Signature mismatch: Payment failed
    header("Location: membership.php?status=failed");
    exit();
}
?>
