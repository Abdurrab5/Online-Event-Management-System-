<?php
 ob_start();

include __DIR__ . '/../header.php';
if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'customer')) {
    header("Location: ../login.php");
    exit;
}  
$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Cancel only if booking belongs to the user and is pending
$stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=? AND user_id=? AND status='pending'");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();

header("Location: dashboard.php");
exit;
 ob_end_flush(); ?>
