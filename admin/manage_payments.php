<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}

// ================= CREATE PAYMENT ====================
if (isset($_POST['add_payment'])) {
    $booking_id     = $_POST['booking_id'];
    $payment_method = $_POST['payment_method'];
    $amount         = $_POST['amount'];
    $status         = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO payments (booking_id, payment_method, amount, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $booking_id, $payment_method, $amount, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_payments.php");
    exit;
}

// ================= UPDATE PAYMENT ====================
if (isset($_POST['update_payment'])) {
    $payment_id     = $_POST['payment_id'];
    $booking_id     = $_POST['booking_id'];
    $payment_method = $_POST['payment_method'];
    $amount         = $_POST['amount'];
    $status         = $_POST['status'];

    $stmt = $conn->prepare("UPDATE payments SET booking_id=?, payment_method=?, amount=?, status=? WHERE payment_id=?");
    $stmt->bind_param("isdsi", $booking_id, $payment_method, $amount, $status, $payment_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_payments.php");
    exit;
}

// ================= DELETE PAYMENT ====================
if (isset($_GET['delete'])) {
    $payment_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id=?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_payments.php");
    exit;
}

// Fetch payments
$payments = $conn->query("SELECT p.*, b.booking_id, u.name AS customer_name, e.event_name
                          FROM payments p
                          JOIN bookings b ON p.booking_id = b.booking_id
                          JOIN users u ON b.user_id = u.user_id
                          JOIN events e ON b.event_id = e.event_id
                          ORDER BY p.transaction_date DESC");

// Fetch bookings
$bookings = $conn->query("SELECT b.booking_id, b.total_cost, u.name, e.event_name 
                          FROM bookings b 
                          JOIN users u ON b.user_id=u.user_id 
                          JOIN events e ON b.event_id=e.event_id 
                          ORDER BY b.created_at DESC");

$allBookings = [];
while($b = $bookings->fetch_assoc()) {
    $allBookings[$b['booking_id']] = $b;
}
?>

<main class="site-content container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="bi bi-cash-stack"></i> Manage Payments</h2>
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addPaymentForm">
            <i class="bi bi-plus-circle"></i> Add Payment
        </button>
    </div>

    <!-- Add Payment Form -->
    <div class="collapse mb-4" id="addPaymentForm">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title mb-3 text-secondary">Add New Payment</h5>
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Select Booking</label>
                        <select name="booking_id" id="bookingSelect" class="form-select" required>
                            <option value="">-- Select Booking --</option>
                            <?php foreach($allBookings as $b): ?>
                                <option value="<?= $b['booking_id'] ?>" data-amount="<?= $b['total_cost'] ?>">
                                    Booking #<?= $b['booking_id'] ?> - <?= $b['name'] ?> (<?= $b['event_name'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="paypal">PayPal</option>
                            <option value="stripe">Stripe</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Amount</label>
                        <input type="number" step="0.01" id="amountField" name="amount" class="form-control" placeholder="Amount" readonly required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" name="add_payment" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="card-title text-secondary mb-3">All Payments</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th><th>Booking</th><th>Customer</th><th>Event</th>
                            <th>Method</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $payments->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['payment_id'] ?></td>
                            <td><span class="badge bg-info">#<?= $row['booking_id'] ?></span></td>
                            <td><?= $row['customer_name'] ?></td>
                            <td><?= $row['event_name'] ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$row['payment_method'])) ?></span></td>
                            <td class="fw-bold text-success">$<?= number_format($row['amount'], 2) ?></td>
                            <td>
                                <span class="badge 
                                    <?= $row['status']=='completed'?'bg-success':
                                        ($row['status']=='pending'?'bg-warning text-dark':'bg-danger') ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= $row['transaction_date'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editPayment<?= $row['payment_id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?delete=<?= $row['payment_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Payment Modal -->
                        <div class="modal fade" id="editPayment<?= $row['payment_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Edit Payment #<?= $row['payment_id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                            <label class="form-label">Booking</label>
                                            <select name="booking_id" class="form-select mb-2" required>
                                                <?php foreach($allBookings as $b): ?>
                                                    <option value="<?= $b['booking_id'] ?>" <?= $row['booking_id']==$b['booking_id']?'selected':'' ?>>
                                                        Booking #<?= $b['booking_id'] ?> - <?= $b['name'] ?> (<?= $b['event_name'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="form-label">Payment Method</label>
                                            <select name="payment_method" class="form-select mb-2" required>
                                                <?php foreach (['paypal','stripe','credit_card','bank_transfer','cash'] as $method): ?>
                                                    <option value="<?= $method ?>" <?= $row['payment_method']==$method?'selected':'' ?>>
                                                        <?= ucfirst(str_replace('_',' ',$method)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="form-label">Amount</label>
                                            <input type="number" step="0.01" name="amount" value="<?= $row['amount'] ?>" class="form-control mb-2" readonly required>
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select mb-2">
                                                <?php foreach (['pending','completed','failed'] as $st): ?>
                                                    <option value="<?= $st ?>" <?= $row['status']==$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="update_payment" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Save Changes
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('bookingSelect').addEventListener('change', function() {
    let selected = this.options[this.selectedIndex];
    let amount = selected.getAttribute('data-amount');
    document.getElementById('amountField').value = amount ? amount : '';
});
</script>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
