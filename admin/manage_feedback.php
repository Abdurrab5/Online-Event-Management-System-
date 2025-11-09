<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$message = '';
$type = 'success';

// ================= CREATE FEEDBACK ====================
if (isset($_POST['add_feedback'])) {
    $booking_id = $_POST['booking_id'];
    $user_id    = $_POST['user_id'];
    $rating     = $_POST['rating'];
    $comments   = $_POST['comments'];

    $check = $conn->prepare("SELECT * FROM feedback WHERE booking_id=? AND user_id=?");
    $check->bind_param("ii", $booking_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "Feedback already exists for this booking by this user.";
        $type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (booking_id, user_id, rating, comments) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $booking_id, $user_id, $rating, $comments);
        if ($stmt->execute()) {
            $message = "✅ Feedback added successfully.";
        } else {
            $message = "❌ Failed to add feedback.";
            $type = "danger";
        }
        $stmt->close();
    }
    $check->close();
}

// ================= UPDATE FEEDBACK ====================
if (isset($_POST['update_feedback'])) {
    $feedback_id = $_POST['feedback_id'];
    $booking_id  = $_POST['booking_id'];
    $user_id     = $_POST['user_id'];
    $rating      = $_POST['rating'];
    $comments    = $_POST['comments'];

    $check = $conn->prepare("SELECT * FROM feedback WHERE booking_id=? AND user_id=? AND feedback_id<>?");
    $check->bind_param("iii", $booking_id, $user_id, $feedback_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "Another feedback already exists for this booking by this user.";
        $type = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE feedback SET booking_id=?, user_id=?, rating=?, comments=? WHERE feedback_id=?");
        $stmt->bind_param("iiisi", $booking_id, $user_id, $rating, $comments, $feedback_id);
        if ($stmt->execute()) {
            $message = "✅ Feedback updated successfully.";
        } else {
            $message = "❌ Failed to update feedback.";
            $type = "danger";
        }
        $stmt->close();
    }
    $check->close();
}

// ================= DELETE FEEDBACK ====================
if (isset($_GET['delete'])) {
    $feedback_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id=?");
    $stmt->bind_param("i", $feedback_id);
    if ($stmt->execute()) {
        $message = "🗑️ Feedback deleted successfully.";
    } else {
        $message = "❌ Failed to delete feedback.";
        $type = "danger";
    }
    $stmt->close();
}

// ================= FETCH DATA ====================
$feedbacks = $conn->query("SELECT f.*, u.name AS customer_name, e.event_name 
                           FROM feedback f
                           JOIN bookings b ON f.booking_id = b.booking_id
                           JOIN users u ON f.user_id = u.user_id
                           JOIN events e ON b.event_id = e.event_id
                           ORDER BY f.created_at DESC");

$bookings = $conn->query("SELECT b.booking_id, u.user_id, u.name AS customer_name, e.event_name 
                          FROM bookings b
                          JOIN users u ON b.user_id = u.user_id
                          JOIN events e ON b.event_id = e.event_id
                          ORDER BY b.created_at DESC");

$allBookings = [];
while ($b = $bookings->fetch_assoc()) {
    $allBookings[$b['booking_id']] = $b;
}
?>

<main class="site-content container py-4">
    <h2 class="mb-4 text-center fw-bold">📋 Manage Feedback</h2>

    <!-- Display Message -->
    <?php if($message): ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add Feedback Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">➕ Add New Feedback</h5>
            <form method="post" class="mt-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Booking</label>
                        <select name="booking_id" class="form-select" required>
                            <option value="">-- Select Booking --</option>
                            <?php foreach($allBookings as $b): ?>
                                <option value="<?= $b['booking_id'] ?>">
                                    Booking #<?= $b['booking_id'] ?> - <?= $b['customer_name'] ?> (<?= $b['event_name'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="user_id" id="user_id_field" required>
                    <div class="col-md-3">
                        <label class="form-label">Rating</label>
                        <input type="number" name="rating" min="1" max="5" class="form-control" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Comments</label>
                        <textarea name="comments" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <button type="submit" name="add_feedback" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-circle"></i> Add Feedback
                </button>
            </form>
        </div>
    </div>

    <!-- Feedback Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">📝 All Feedback</h5>
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th><th>Booking</th><th>Customer</th><th>Event</th><th>Rating</th><th>Comments</th><th>Date</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $feedbacks->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['feedback_id'] ?></td>
                                <td>#<?= $row['booking_id'] ?></td>
                                <td><span class="fw-semibold"><?= $row['customer_name'] ?></span></td>
                                <td><?= $row['event_name'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['rating'] >=4 ? 'success' : ($row['rating']==3?'warning':'danger') ?>">
                                        <?= $row['rating'] ?>/5
                                    </span>
                                </td>
                                <td><?= $row['comments'] ?></td>
                                <td><small><?= date("M d, Y H:i", strtotime($row['created_at'])) ?></small></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editFeedback<?= $row['feedback_id'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="?delete=<?= $row['feedback_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this feedback?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Edit Feedback Modal -->
                            <div class="modal fade" id="editFeedback<?= $row['feedback_id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="post">
                                            <div class="modal-header">
                                                <h5 class="modal-title">✏️ Edit Feedback #<?= $row['feedback_id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="feedback_id" value="<?= $row['feedback_id'] ?>">
                                                <select name="booking_id" class="form-select mb-2" required>
                                                    <?php foreach($allBookings as $b): ?>
                                                        <option value="<?= $b['booking_id'] ?>" <?= $row['booking_id']==$b['booking_id']?'selected':'' ?>>
                                                            Booking #<?= $b['booking_id'] ?> - <?= $b['customer_name'] ?> (<?= $b['event_name'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                                <input type="number" name="rating" min="1" max="5" value="<?= $row['rating'] ?>" class="form-control mb-2" required>
                                                <textarea name="comments" class="form-control mb-2" rows="3" required><?= $row['comments'] ?></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" name="update_feedback" class="btn btn-primary">
                                                    <i class="bi bi-save"></i> Save Changes
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
// Auto-set user_id when selecting booking
document.querySelector('select[name="booking_id"]').addEventListener('change', function(){
    let selected = this.options[this.selectedIndex];
    let bookingId = selected.value;
    <?php foreach($allBookings as $b): ?>
        if (bookingId == <?= $b['booking_id'] ?>) {
            document.getElementById('user_id_field').value = <?= $b['user_id'] ?>;
        }
    <?php endforeach; ?>
});
</script>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
