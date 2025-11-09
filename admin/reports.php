<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}

// Booking Reports
$bookingReport = $conn->query("
    SELECT b.booking_id, u.name AS customer, e.event_name, b.status, b.total_cost, b.event_date
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN events e ON b.event_id = e.event_id
    ORDER BY b.created_at DESC
");

// Financial Reports
$financialReport = $conn->query("
    SELECT p.payment_id, u.name AS customer, p.amount, p.payment_method, p.status, p.transaction_date
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    ORDER BY p.transaction_date DESC
");

// Service Popularity
$servicePopularity = $conn->query("
    SELECT s.service_name, COUNT(bs.booking_id) AS bookings_count
    FROM booking_services bs
    JOIN services s ON bs.service_id = s.service_id
    GROUP BY s.service_id
    ORDER BY bookings_count DESC
");

// User Activity
$userActivity = $conn->query("
    SELECT u.user_id, u.name, u.email, COUNT(b.booking_id) AS total_bookings
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.user_id
    GROUP BY u.user_id
    ORDER BY total_bookings DESC
");
?>

<main class="site-content container py-4">
    <h2 class="mb-4 fw-bold text-primary"><i class="bi bi-graph-up"></i> Reports Dashboard</h2>

    <!-- Tabs -->
    <ul class="nav nav-tabs shadow-sm rounded bg-white mb-4" id="reportTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="booking-tab" data-bs-toggle="tab" href="#booking" role="tab">
                <i class="bi bi-calendar-check"></i> Booking Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="financial-tab" data-bs-toggle="tab" href="#financial" role="tab">
                <i class="bi bi-cash-stack"></i> Financial Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="service-tab" data-bs-toggle="tab" href="#service" role="tab">
                <i class="bi bi-star"></i> Service Popularity
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="user-tab" data-bs-toggle="tab" href="#user" role="tab">
                <i class="bi bi-people"></i> User Activity
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Booking Reports -->
        <div class="tab-pane fade show active" id="booking" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white fw-bold">Booking Reports</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Total Cost</th>
                                <th>Event Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $bookingReport->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $row['booking_id'] ?></td>
                                    <td><?= $row['customer'] ?></td>
                                    <td><?= $row['event_name'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status']=='confirmed'?'success':($row['status']=='pending'?'warning':'danger') ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>$<?= number_format($row['total_cost'], 2) ?></td>
                                    <td><?= $row['event_date'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Reports -->
        <div class="tab-pane fade" id="financial" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white fw-bold">Financial Reports</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Payment ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Transaction Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $financialReport->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $row['payment_id'] ?></td>
                                    <td><?= $row['customer'] ?></td>
                                    <td>$<?= number_format($row['amount'], 2) ?></td>
                                    <td><?= ucfirst(str_replace("_"," ",$row['payment_method'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status']=='completed'?'success':'danger' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['transaction_date'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Service Popularity -->
        <div class="tab-pane fade" id="service" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-warning fw-bold">Service Popularity</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Service Name</th>
                                <th>Bookings Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $servicePopularity->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['service_name'] ?></td>
                                    <td><span class="badge bg-primary"><?= $row['bookings_count'] ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Activity -->
        <div class="tab-pane fade" id="user" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white fw-bold">User Activity</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Total Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $userActivity->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $row['user_id'] ?></td>
                                    <td><?= $row['name'] ?></td>
                                    <td><?= $row['email'] ?></td>
                                    <td><span class="badge bg-dark"><?= $row['total_bookings'] ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
