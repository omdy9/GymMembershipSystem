<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include Razorpay PHP SDK
require('Razorpay.php');
use Razorpay\Api\Api;

$razorpay_key_id = 'rzp_test_eHSDxGE46Bjkt3'; // Your Razorpay API key
$razorpay_key_secret = 'TJRbP1lfV0ULMnQK50coUwu1'; // Your Razorpay API secret

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_id = $_POST['razorpay_payment_id'];
    $order_id = $_POST['razorpay_order_id'];
    $signature = $_POST['razorpay_signature'];

    // Verify the payment signature
    // Debug: Show POST data received from Razorpay
echo '<pre>';
print_r($_POST);
echo '</pre>';

// Verify the payment signature
$api = new Api($razorpay_key_id, $razorpay_key_secret);

try {
    // Debug: Show the data sent to Razorpay for signature verification
    echo '<pre>';
    print_r([
        'razorpay_order_id' => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ]);
    echo '</pre>';

    $api->utility->verifyPaymentSignature([
        'razorpay_order_id' => $order_id,
        'razorpay_payment_id' => $payment_id,
        'razorpay_signature' => $signature
    ]);

    // Payment is successful, proceed to store membership details
    $user_id = $_GET['user_id'];
    $membership_type = $_GET['membership_type'];
    $price = ($membership_type == 'gold') ? 18000 : 12000;
    $expiry_date = date('Y-m-d', strtotime('+1 year')); // Set expiry date to 1 year from now

    // Database connection
    $conn = new mysqli('localhost', 'root', '', 'gym');
    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // Insert the membership details
    $stmt = $conn->prepare("INSERT INTO memberships (uid, membership_type, payment_status, expiry_date, amount) VALUES (?, ?, 'Completed', ?, ?)");
    $stmt->bind_param('issi', $user_id, $membership_type, $expiry_date, $price);
    $stmt->execute();

    // Redirect to membership page
    header('Location: membership.php');
    exit();
} catch (Exception $e) {
    echo 'Payment verification failed: ' . $e->getMessage();
}
}
?>
