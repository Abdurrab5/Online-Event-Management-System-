<?php
ob_start();
include __DIR__ . '/../header.php';
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ---------------- Add Booking ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    $user_id        = intval($_POST['user_id']);
    $event_id       = intval($_POST['event_id']);
    $payment_method = $_POST['payment_method'];
    $reference_no   = trim($_POST['reference_no']);
    $total_cost     = floatval($_POST['total_cost']);
    $slip_path      = "";

    // Handle slip upload
    if (!empty($_FILES['slip']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/slips/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext       = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
        $fileName  = time() . "_" . uniqid() . "." . $ext;
        $slip_path = "uploads/slips/" . $fileName;

        move_uploaded_file($_FILES['slip']['tmp_name'], __DIR__ . '/../' . $slip_path);
    }

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, status, total_cost) VALUES (?, ?, 'pending', ?)");
    $stmt->bind_param("iid", $user_id, $event_id, $total_cost);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $stmt->close();

    // Insert payment
    $stmt = $conn->prepare("INSERT INTO payments (booking_id, payment_method, amount, slip_path, reference_no, status) 
                            VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isdss", $booking_id, $payment_method, $total_cost, $slip_path, $reference_no);
    $stmt->execute();
    $stmt->close();

    $success = "Booking added successfully with payment record.";
}

/* ---------------- Edit Booking ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_booking'])) {
    $booking_id     = intval($_POST['booking_id']);
    $user_id        = intval($_POST['user_id']);
    $event_id       = intval($_POST['event_id']);
    $payment_method = $_POST['payment_method'];
    $reference_no   = trim($_POST['reference_no']);
    $total_cost     = floatval($_POST['total_cost']);
    $slip_path      = $_POST['existing_slip'] ?? "";

    // Handle slip upload (replace if uploaded new one)
    if (!empty($_FILES['slip']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/slips/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext       = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
        $fileName  = time() . "_" . uniqid() . "." . $ext;
        $slip_path = "uploads/slips/" . $fileName;

        move_uploaded_file($_FILES['slip']['tmp_name'], __DIR__ . '/../' . $slip_path);
    }

    // Update booking
    $stmt = $conn->prepare("UPDATE bookings SET user_id=?, event_id=?, total_cost=? WHERE booking_id=?");
    $stmt->bind_param("iidi", $user_id, $event_id, $total_cost, $booking_id);
    $stmt->execute();
    $stmt->close();

    // Update payment
    $stmt = $conn->prepare("UPDATE payments SET payment_method=?, amount=?, slip_path=?, reference_no=? WHERE booking_id=?");
    $stmt->bind_param("sdssi", $payment_method, $total_cost, $slip_path, $reference_no, $booking_id);
    $stmt->execute();
    $stmt->close();

    $success = "Booking updated successfully.";
}

/* ---------------- Delete Booking ---------------- */
if (isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);

    // Delete payment first
    $conn->query("DELETE FROM payments WHERE booking_id=$booking_id");

    // Delete booking
    $conn->query("DELETE FROM bookings WHERE booking_id=$booking_id");

    $success = "Booking deleted successfully.";
}

/* ---------------- Confirm Payment ---------------- */
if (isset($_GET['confirm'])) {
    $booking_id = intval($_GET['confirm']);

    // Update booking + payment
    $conn->query("UPDATE bookings SET status='confirmed' WHERE booking_id=$booking_id");
    $conn->query("UPDATE payments SET status='completed' WHERE booking_id=$booking_id");

    // Fetch user details
    $user = $conn->query("SELECT u.user_id, u.email, u.name 
                          FROM bookings b 
                          JOIN users u ON b.user_id=u.user_id 
                          WHERE b.booking_id=$booking_id")->fetch_assoc();

    if ($user) {
        $msg = "Your booking #$booking_id has been confirmed and payment verified.";

        // Save notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, booking_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user['user_id'], $booking_id, $msg);
        $stmt->execute();
        $stmt->close();

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = "smtp.gmail.com";
            $mail->SMTPAuth   = true;
            $mail->Username   = "your-email@gmail.com"; // change
            $mail->Password   = "your-app-password";    // change
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom("your-email@gmail.com", "Event Booking System");
            $mail->addAddress($user['email'], $user['name']);

            $mail->isHTML(true);
            $mail->Subject = "Booking Confirmed - OEMS";
            $mail->Body    = "Hi {$user['name']},<br><br>$msg<br><br>Regards,<br>Event Management Team";

            $mail->send();
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
        }
    }

    header("Location: manage_bookings.php?success=Payment+Confirmed");
    exit;
}
if (isset($_GET['complete'])) {
    $booking_id = intval($_GET['complete']);
    $conn->query("UPDATE bookings SET status='completed' WHERE booking_id=$booking_id");
    $conn->query("UPDATE payments SET status='completed' WHERE booking_id=$booking_id");
    header("Location: manage_bookings.php?success=Booking+Completed");
    exit;
}

if (isset($_GET['cancel'])) {
    $booking_id = intval($_GET['cancel']);
    $conn->query("UPDATE bookings SET status='cancelled' WHERE booking_id=$booking_id");
    $conn->query("UPDATE payments SET status='cancelled' WHERE booking_id=$booking_id");
    header("Location: manage_bookings.php?success=Booking+Cancelled");
    exit;
}

$users    = $conn->query("SELECT * FROM users WHERE role='customer'");
$events   = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
$packages = $conn->query("SELECT * FROM packages ORDER BY package_name");
$services = $conn->query("SELECT * FROM services ORDER BY service_name");
/* ---------------- Fetch All Bookings ---------------- */
$bookings = $conn->query("SELECT b.*, u.name AS user_name, e.event_name, p.package_name
                          FROM bookings b
                          JOIN users u ON b.user_id=u.user_id
                          JOIN events e ON b.event_id=e.event_id
                          LEFT JOIN packages p ON b.package_id=p.package_id
                          ORDER BY b.created_at DESC");
$allServices = [];
while ($s = $services->fetch_assoc()) {
    $allServices[$s['service_id']] = $s;
}

$booking_services = [];
$res = $conn->query("SELECT booking_id, service_id, quantity FROM booking_services");
while ($bs = $res->fetch_assoc()) {
    $booking_services[$bs['booking_id']][$bs['service_id']] = $bs['quantity'];
}
?>

<div class="container my-5">
    <h3 class="fw-bold">Manage Bookings</h3>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Add Booking Form -->
   <!-- Add Booking Form -->
<div class="card shadow mb-4">
  <div class="card-body">
    <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle"></i> Add New Booking</h5>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="add_booking" value="1">
      <div class="row g-3">

        <div class="col-md-3">
          <label class="form-label">User</label>
          <select name="user_id" class="form-select" required>
            <option value="">-- Select User --</option>
            <?php $users->data_seek(0); while ($u = $users->fetch_assoc()) { ?>
              <option value="<?= $u['user_id'] ?>"><?= $u['name'] ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Event</label>
          <select name="event_id" class="form-select" required>
            <option value="">-- Select Event --</option>
            <?php $events->data_seek(0); while ($e = $events->fetch_assoc()) { ?>
              <option value="<?= $e['event_id'] ?>"><?= $e['event_name'] ?> (<?= $e['event_date'] ?>)</option>
            <?php } ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Package (Optional)</label>
          <select name="package_id" class="form-select">
            <option value="">-- Optional Package --</option>
            <?php $packages->data_seek(0); while ($p = $packages->fetch_assoc()) { ?>
              <option value="<?= $p['package_id'] ?>"><?= $p['package_name'] ?> ($<?= $p['price'] ?>)</option>
            <?php } ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Event Date</label>
          <input type="date" name="event_date" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="cancelled">Cancelled</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Total Cost</label>
          <input type="number" step="0.01" name="total_cost" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" class="form-select" required>
            <option value="easypaisa">Easypaisa</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Reference No</label>
          <input type="text" name="reference_no" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Slip Upload</label>
          <input type="file" name="slip" class="form-control" accept="image/*,.pdf" required>
        </div>

        <div class="col-md-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Add Booking
          </button>
        </div>

      </div>
    </form>
  </div>
</div>

    <!-- Bookings Table -->
    <div class="card shadow">
        <div class="card-body">
            <h5 class="fw-bold">All Bookings</h5>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Cost</th>
                        <th>Payment Method</th>
                        <th>Reference</th>
                        <th>Slip</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $bookings->fetch_assoc()): 
                    $payment = $conn->query("SELECT * FROM payments WHERE booking_id={$row['booking_id']}")->fetch_assoc();
                ?>
                    <tr>
                        <td><?= $row['booking_id'] ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['event_name']) ?></td>
                        <td><?= ucfirst($row['status']) ?></td>
                        <td>$<?= number_format($row['total_cost'],2) ?></td>
                        <td><?= $payment['payment_method'] ?? '-' ?></td>
                        <td><?= $payment['reference_no'] ?? '-' ?></td>
                        <td>
                            <?php if (!empty($payment['slip_path'])): ?>
                                <a href="../<?= $payment['slip_path'] ?>" target="_blank" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] !== 'confirmed'): ?>
                                <a href="manage_bookings.php?confirm=<?= $row['booking_id'] ?>" 
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Confirm this payment?')">
                                   <i class="bi bi-check2-circle"></i> Confirm
                                </a>
                            <?php endif;                      
                            ?>
                            <?php if ($row['status'] === 'confirmed'): ?>
  <a href="manage_bookings.php?complete=<?= $row['booking_id'] ?>" 
     class="btn btn-sm btn-secondary"
     onclick="return confirm('Mark booking as completed?')">
     <i class="bi bi-check2-all"></i> Complete
  </a>
<?php endif; ?>

<?php if ($row['status'] !== 'cancelled' && $row['status'] !== 'completed'): ?>
  <a href="manage_bookings.php?cancel=<?= $row['booking_id'] ?>" 
     class="btn btn-sm btn-dark"
     onclick="return confirm('Cancel this booking?')">
     <i class="bi bi-x-circle"></i> Cancel
  </a>
<?php endif; ?>

                            <!-- Edit Button -->
                            <button class="btn btn-sm btn-warning" 
                                    onclick="editBooking(<?= htmlspecialchars(json_encode($row)) ?>, <?= htmlspecialchars(json_encode($payment)) ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <!-- Delete Button -->
                            <a href="manage_bookings.php?delete=<?= $row['booking_id'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this booking?')">
                               <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_booking" value="1">
        <input type="hidden" name="booking_id" id="edit_booking_id">
        <input type="hidden" name="existing_slip" id="edit_existing_slip">

        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Booking</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body row g-3">

          <div class="col-md-4">
            <label class="form-label">User ID</label>
            <input type="number" name="user_id" id="edit_user_id" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Event ID</label>
            <input type="number" name="event_id" id="edit_event_id" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Total Cost</label>
            <input type="number" step="0.01" name="total_cost" id="edit_total_cost" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" id="edit_payment_method" class="form-select" required>
              <option value="easypaisa">Easypaisa</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Reference No</label>
            <input type="text" name="reference_no" id="edit_reference_no" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Slip Upload <small>(leave empty to keep existing)</small></label>
            <input type="file" name="slip" class="form-control" accept="image/*,.pdf">
          </div>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Update Booking
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
function editBooking(booking, payment) {
    document.getElementById("edit_booking_id").value = booking.booking_id;
    document.getElementById("edit_user_id").value = booking.user_id;
    document.getElementById("edit_event_id").value = booking.event_id;
    document.getElementById("edit_total_cost").value = booking.total_cost;
    document.getElementById("edit_payment_method").value = payment.payment_method || '';
    document.getElementById("edit_reference_no").value = payment.reference_no || '';
    document.getElementById("edit_existing_slip").value = payment.slip_path || '';
    new bootstrap.Modal(document.getElementById("editModal")).show();
}
</script>

<?php include __DIR__ . '/../footer.php'; ob_end_flush(); ?>
