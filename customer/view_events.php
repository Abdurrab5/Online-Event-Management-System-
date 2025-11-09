<?php ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'customer')) {
    header("Location: ../login.php");
    exit;
}  

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT b.booking_id, b.event_date, b.status, b.total_cost,
           e.event_name, e.venue, p.package_name
    FROM bookings b
    LEFT JOIN events e ON b.event_id = e.event_id
    LEFT JOIN packages p ON b.package_id = p.package_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-calendar-check me-2"></i> My Bookings</h2>
        <a href="<?= $base_url; ?>/../events.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Book New Event
        </a>
    </div>

    <?php if($bookings->num_rows > 0): ?>
        <div class="row g-4">
            <?php while($b = $bookings->fetch_assoc()): ?>
                <?php
                    // Fetch services for this booking
                    $stmt2 = $conn->prepare("
                        SELECT s.service_name, s.price, bs.quantity
                        FROM booking_services bs
                        JOIN services s ON bs.service_id = s.service_id
                        WHERE bs.booking_id = ?
                    ");
                    $stmt2->bind_param("i", $b['booking_id']);
                    $stmt2->execute();
                    $services_res = $stmt2->get_result();
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-lg rounded-4 h-100">
                        <div class="card-body">
                            <h5 class="card-title text-primary fw-bold">
                                <i class="bi bi-star-fill me-2 text-warning"></i>
                                <?= htmlspecialchars($b['event_name']); ?>
                            </h5>
                            <p class="mb-1"><i class="bi bi-geo-alt-fill me-2 text-danger"></i><strong>Venue:</strong> <?= htmlspecialchars($b['venue']); ?></p>
                            <p class="mb-1"><i class="bi bi-calendar-event me-2 text-success"></i><strong>Date:</strong> <?= $b['event_date']; ?></p>
                            <p class="mb-1"><i class="bi bi-box me-2 text-info"></i><strong>Package:</strong> <?= $b['package_name'] ?: '<span class="text-muted">N/A</span>'; ?></p>

                            <p class="mb-1"><i class="bi bi-tools me-2 text-secondary"></i><strong>Services:</strong></p>
                            <ul class="small">
                                <?php if($services_res->num_rows > 0): ?>
                                    <?php while($s = $services_res->fetch_assoc()): ?>
                                        <li><?= htmlspecialchars($s['service_name']); ?> × <?= $s['quantity']; ?> 
                                            <span class="text-muted">($<?= $s['price']; ?> each)</span>
                                        </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li class="text-muted">No extra services</li>
                                <?php endif; ?>
                            </ul>

                            <p class="fw-bold mb-1"><i class="bi bi-cash-coin me-2 text-success"></i>Total Cost: 
                                <span class="text-dark">$<?= number_format($b['total_cost'],2); ?></span>
                            </p>
                            <p>
                                <span class="badge rounded-pill 
                                    <?= $b['status']=='pending'?'bg-warning text-dark':
                                        ($b['status']=='confirmed'?'bg-success':
                                        ($b['status']=='cancelled'?'bg-danger':'bg-primary')) ?>">
                                    <?= ucfirst($b['status']); ?>
                                </span>
                            </p>

                            <div class="d-flex justify-content-between">
                                <a href="invoice.php?booking_id=<?= $b['booking_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i> Invoice
                                </a>
                                <?php if($b['status']=='pending'): ?>
                                    <a href="cancel_booking.php?booking_id=<?= $b['booking_id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Cancel this booking?');">
                                        <i class="bi bi-x-circle me-1"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info shadow-sm p-3">
            <i class="bi bi-info-circle me-2"></i>No bookings yet. 
            <a href="events.php" class="alert-link">Book an event now!</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
