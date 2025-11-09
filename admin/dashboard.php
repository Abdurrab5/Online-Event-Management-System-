<?php ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
 
if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}
?>
 

<?php
// Fetch counts for dashboard stats
$users_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$events_count = $conn->query("SELECT COUNT(*) as total FROM events")->fetch_assoc()['total'];
$services_count = $conn->query("SELECT COUNT(*) as total FROM services")->fetch_assoc()['total'];
$packages_count = $conn->query("SELECT COUNT(*) as total FROM packages")->fetch_assoc()['total'];
$bookings_count = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
$payments_count = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status='completed'")->fetch_assoc()['total'];

// Fetch latest bookings
$latest_bookings = $conn->query("
    SELECT b.booking_id, u.name AS user_name, e.event_name, b.total_cost, b.status, b.created_at
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN events e ON b.event_id = e.event_id
    ORDER BY b.created_at DESC
    LIMIT 5
");
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <p class="card-text fs-3"><?= $users_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Events</h5>
                    <p class="card-text fs-3"><?= $events_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Services</h5>
                    <p class="card-text fs-3"><?= $services_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Packages</h5>
                    <p class="card-text fs-3"><?= $packages_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-secondary shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Bookings</h5>
                    <p class="card-text fs-3"><?= $bookings_count ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-white bg-dark shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Payments</h5>
                    <p class="card-text fs-3"><?= $payments_count ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Bookings Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            Latest Bookings
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Event</th>
                            <th>Total Cost</th>
                            <th>Status</th>
                            <th>Booked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($b = $latest_bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= $b['booking_id'] ?></td>
                            <td><?= htmlspecialchars($b['user_name']) ?></td>
                            <td><?= htmlspecialchars($b['event_name']) ?></td>
                            <td>$<?= number_format($b['total_cost'], 2) ?></td>
                            <td>
                                <span class="badge 
                                    <?= $b['status']=='pending'?'bg-warning':($b['status']=='confirmed'?'bg-success':($b['status']=='cancelled'?'bg-danger':'bg-secondary')) ?>">
                                    <?= ucfirst($b['status']) ?>
                                </span>
                            </td>
                            <td><?= $b['created_at'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Optional: You can add charts here with Chart.js for bookings trends -->

</div>

 



<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>