<?php ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch quick stats
$totalBookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id=$user_id")->fetch_assoc()['total'];
$confirmedBookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id=$user_id AND status='confirmed'")->fetch_assoc()['total'];
$pendingPayments = $conn->query("
    SELECT COUNT(*) as total FROM payments p 
    JOIN bookings b ON p.booking_id=b.booking_id 
    WHERE b.user_id=$user_id AND p.status='pending'
")->fetch_assoc()['total'];

// Upcoming bookings
$upcomingBookings = $conn->query("
    SELECT b.booking_id, e.event_name, b.event_date, b.status 
    FROM bookings b 
    JOIN events e ON b.event_id = e.event_id 
    WHERE b.user_id=$user_id AND b.event_date >= CURDATE()
    ORDER BY b.event_date ASC LIMIT 3
");

// Recent payments
$recentPayments = $conn->query("
    SELECT p.payment_id, p.amount, p.status, p.transaction_date, e.event_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN events e ON b.event_id = e.event_id
    WHERE b.user_id=$user_id
    ORDER BY p.transaction_date DESC LIMIT 3
");

// Notifications
$notifications = $conn->query("
    SELECT message, created_at, status 
    FROM notifications 
    WHERE user_id=$user_id 
    ORDER BY created_at DESC LIMIT 5
");
?>

<div class="container my-5">
    <!-- Welcome -->
    <div class="text-center mb-5">
        <h2 class="fw-bold">Welcome, <?= htmlspecialchars($_SESSION['name']); ?> 👋</h2>
        <p class="text-muted">Here’s an overview of your activity.</p>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-5 text-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <h5>Total Bookings</h5>
                <h2 class="text-primary"><?= $totalBookings; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <h5>Confirmed Events</h5>
                <h2 class="text-success"><?= $confirmedBookings; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 rounded-4 p-4">
                <h5>Pending Payments</h5>
                <h2 class="text-warning"><?= $pendingPayments; ?></h2>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Bookings -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-primary text-white rounded-top-4">
                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Upcoming Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if($upcomingBookings->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($b = $upcomingBookings->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($b['event_name']); ?></strong><br>
                                        <small class="text-muted"><?= $b['event_date']; ?></small>
                                    </div>
                                    <span class="badge 
                                        <?= $b['status']=='confirmed' ? 'bg-success' : ($b['status']=='pending' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                        <?= ucfirst($b['status']); ?>
                                    </span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No upcoming bookings.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Recent Payments</h5>
                </div>
                <div class="card-body">
                    <?php if($recentPayments->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($p = $recentPayments->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($p['event_name']); ?></strong><br>
                                        <small class="text-muted"><?= $p['transaction_date']; ?></small>
                                    </div>
                                    <span class="badge 
                                        <?= $p['status']=='completed' ? 'bg-success' : ($p['status']=='pending' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                        $<?= $p['amount']; ?> - <?= ucfirst($p['status']); ?>
                                    </span>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No recent payments.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-info text-white rounded-top-4">
            <h5 class="mb-0"><i class="bi bi-bell"></i> Notifications</h5>
        </div>
        <div class="card-body">
            <?php if($notifications->num_rows > 0): ?>
                <ul class="list-group list-group-flush">
                    <?php while($n = $notifications->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?= htmlspecialchars($n['message']); ?><br>
                                <small class="text-muted"><?= $n['created_at']; ?></small>
                            </div>
                            <?php if($n['status'] == 'unread'): ?>
                                <span class="badge bg-danger">New</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No notifications.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
