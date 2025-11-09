<?php ob_start();
include __DIR__ . '/../header.php';
if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'customer')) {
    header("Location: ../login.php");
    exit;
}  
$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'];

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.*, e.event_name, e.venue, p.package_name
    FROM bookings b
    LEFT JOIN events e ON b.event_id = e.event_id
    LEFT JOIN packages p ON b.package_id = p.package_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if(!$booking) die("Booking not found.");

// Fetch services
$stmt2 = $conn->prepare("
    SELECT s.service_name, s.price, bs.quantity
    FROM booking_services bs
    JOIN services s ON bs.service_id = s.service_id
    WHERE bs.booking_id = ?
");
$stmt2->bind_param("i", $booking_id);
$stmt2->execute();
$services = $stmt2->get_result();
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4">
                    <h3 class="mb-0"><i class="bi bi-receipt"></i> Invoice</h3>
                </div>
                <div class="card-body p-4">
                    <!-- Booking Details -->
                    <div class="mb-4">
                        <h5 class="fw-bold text-secondary">Booking Details</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Booking ID:</strong> <?= $booking['booking_id']; ?></p>
                                <p><strong>Event:</strong> <?= htmlspecialchars($booking['event_name']); ?></p>
                                <p><strong>Venue:</strong> <?= htmlspecialchars($booking['venue']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Event Date:</strong> <?= $booking['event_date']; ?></p>
                                <p><strong>Package:</strong> <?= $booking['package_name'] ?: 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Services Table -->
                    <h5 class="fw-bold text-secondary">Services</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                while($s = $services->fetch_assoc()):
                                    $subtotal = $s['price'] * $s['quantity'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($s['service_name']); ?></td>
                                    <td>$<?= number_format($s['price'], 2); ?></td>
                                    <td><?= $s['quantity']; ?></td>
                                    <td class="fw-bold">$<?= number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr class="table-primary">
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="fw-bold">$<?= number_format($booking['total_cost'], 2); ?></th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="view_events.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                        <button onclick="window.print()" class="btn btn-success">
                            <i class="bi bi-printer"></i> Print Invoice
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
