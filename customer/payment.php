<?php ob_start();
include __DIR__ . '/../header.php';
require __DIR__ . '/../vendor/autoload.php'; // Composer autoload for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$slipPath = "";
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;
if(!$booking_id){
    echo "<div class='alert alert-danger'>Invalid booking!</div>";
    include __DIR__ . '/../footer.php';
    exit;
}

// Fetch booking details
$stmt = $conn->prepare("SELECT b.*, e.event_name, u.email, u.name 
    FROM bookings b 
    JOIN events e ON b.event_id = e.event_id 
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id=? AND b.user_id=?");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if(!$booking){
    echo "<div class='alert alert-danger'>Booking not found!</div>";
    include __DIR__ . '/../footer.php';
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $method = $_POST['payment_method'];
$amount = $booking['total_cost'];
$referenceNo = !empty($_POST['reference_no']) ? $_POST['reference_no'] : ""; // empty string instead of null
$slipPath = ""; // default empty string
echo $method;
// Handle slip upload
if (($method === 'bank_transfer' || $method === 'easypaisa') && 
    isset($_FILES['slip']) && $_FILES['slip']['error'] === 0) {

    $uploadDir = __DIR__ . '/../assets/slip/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
    $fileName = time() . "_" . uniqid() . "." . $ext;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['slip']['tmp_name'], $targetPath)) {
        $slipPath = "assets/slip/" . $fileName; // save relative path
    } else {
        die("<div class='alert alert-danger'>Slip upload failed! Check folder permissions or PHP upload size.</div>");
    }
}

$stmt = $conn->prepare("INSERT INTO payments 
    (booking_id, payment_method, amount, slip_path, reference_no, status) 
    VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("isdss", $booking_id, $method, $amount, $slipPath, $referenceNo);

if($stmt->execute()){
  echo "<script>alert('Request saved Successfully & Admin will Confirm Booking!');window.location='dashboard.php';</script>";
    exit;
} else {
    echo "<div class='alert alert-danger'>Payment failed. Try again.</div>";
}

}
?>

<div class="container my-5">
    <div class="card shadow border-0">
        <div class="card-body">
            <h3 class="fw-bold text-success">Payment for <?= htmlspecialchars($booking['event_name']); ?></h3>
            <p><strong>Total Amount:</strong> $<?= number_format($booking['total_cost'],2); ?></p>

           <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label fw-semibold">Select Payment Method</label>
        <select name="payment_method" id="payment_method" class="form-select" required>
            <option value="easypaisa" selected>Easypaisa</option>
            <option value="bank_transfer">Bank Transfer</option>
        </select>
    </div>

    <!-- Dynamic account details -->
    <div id="payment_info" class="alert alert-info"></div>

    <!-- Shared inputs -->
    <div class="mb-3">
        <label class="form-label">Upload Payment Slip</label>
        <input type="file" name="slip" class="form-control" accept="image/*,application/pdf" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Reference Number (Optional)</label>
        <input type="text" name="reference_no" class="form-control">
    </div>

    <button type="submit" class="btn btn-success w-100">
        <i class="bi bi-credit-card"></i> Confirm Payment
    </button>
</form>

<script>
const methodSelect = document.getElementById('payment_method');
const paymentInfo = document.getElementById('payment_info');

function updatePaymentInfo() {
    if (methodSelect.value === 'easypaisa') {
        paymentInfo.innerHTML = `
            <strong>Admin Easypaisa Account:</strong><br>
            Easypaisa Number: 0345-1234567<br>
            Account Title: Event Booking Admin
        `;
    } else {
        paymentInfo.innerHTML = `
            <strong>Admin Bank Account:</strong><br>
            Bank: HBL<br>
            Account No: 1234-567890123<br>
            Title: Event Booking Admin
        `;
    }
}

// run once on load
document.addEventListener("DOMContentLoaded", updatePaymentInfo);
// run on change
methodSelect.addEventListener("change", updatePaymentInfo);
</script>


        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ob_end_flush(); ?>
