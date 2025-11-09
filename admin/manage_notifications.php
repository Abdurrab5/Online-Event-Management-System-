<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}
$message = '';
$type = 'success';

// ================= CREATE NOTIFICATION ====================
if (isset($_POST['add_notification'])) {
    $user_id    = $_POST['user_id'];
    $booking_id = !empty($_POST['booking_id']) ? $_POST['booking_id'] : null;
    $notif_msg  = $_POST['message'];
    $status     = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, booking_id, message, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $booking_id, $notif_msg, $status);
    $message = $stmt->execute() ? "✅ Notification added successfully." : "❌ Failed to add notification.";
    $type = $stmt->execute() ? "success" : "danger";
    $stmt->close();
}

// ================= UPDATE NOTIFICATION ====================
if (isset($_POST['update_notification'])) {
    $notif_id   = $_POST['notification_id'];
    $user_id    = $_POST['user_id'];
    $booking_id = !empty($_POST['booking_id']) ? $_POST['booking_id'] : null;
    $notif_msg  = $_POST['message'];
    $status     = $_POST['status'];

    $stmt = $conn->prepare("UPDATE notifications SET user_id=?, booking_id=?, message=?, status=? WHERE notification_id=?");
    $stmt->bind_param("iissi", $user_id, $booking_id, $notif_msg, $status, $notif_id);
    $message = $stmt->execute() ? "✅ Notification updated successfully." : "❌ Failed to update notification.";
    $type = $stmt->execute() ? "success" : "danger";
    $stmt->close();
}

// ================= DELETE NOTIFICATION ====================
if (isset($_GET['delete'])) {
    $notif_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id=?");
    $stmt->bind_param("i", $notif_id);
    $message = $stmt->execute() ? "✅ Notification deleted successfully." : "❌ Failed to delete notification.";
    $type = $stmt->execute() ? "success" : "danger";
    $stmt->close();
}

// ================= FETCH DATA ====================
$notifications = $conn->query("
    SELECT n.*, u.name AS user_name, b.event_id
    FROM notifications n
    JOIN users u ON n.user_id = u.user_id
    LEFT JOIN bookings b ON n.booking_id = b.booking_id
    ORDER BY n.created_at DESC
");

$bookings = $conn->query("
    SELECT b.booking_id, u.name AS customer_name, e.event_name
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN events e ON b.event_id = e.event_id
    ORDER BY b.created_at DESC
");
$allBookings = [];
while ($b = $bookings->fetch_assoc()) {
    $allBookings[$b['booking_id']] = $b;
}

$users = $conn->query("SELECT user_id, name FROM users ORDER BY name ASC");
$allUsers = [];
while ($u = $users->fetch_assoc()) {
    $allUsers[$u['user_id']] = $u['name'];
}
?>

<main class="site-content container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">📢 Manage Notifications</h2>
    </div>

    <!-- Display Message -->
    <?php if($message): ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add Notification Form -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">➕ Add New Notification</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">User</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">-- Select User --</option>
                        <?php foreach($allUsers as $uid => $uname): ?>
                            <option value="<?= $uid ?>"><?= $uname ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Booking (Optional)</label>
                    <select name="booking_id" class="form-select">
                        <option value="">-- Select Booking --</option>
                        <?php foreach($allBookings as $b): ?>
                            <option value="<?= $b['booking_id'] ?>">
                                Booking #<?= $b['booking_id'] ?> - <?= $b['customer_name'] ?> (<?= $b['event_name'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Message</label>
                    <textarea name="message" class="form-control" rows="3" placeholder="Notification Message..." required></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" name="add_notification" class="btn btn-success px-4">
                        <i class="bi bi-plus-circle"></i> Add Notification
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="mb-0">📋 Notifications List</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Booking</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $notifications->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['notification_id'] ?></td>
                            <td><span class="badge bg-primary"><?= $row['user_name'] ?></span></td>
                            <td><?= $row['booking_id'] ? "#{$row['booking_id']}" : '<span class="text-muted">N/A</span>' ?></td>
                            <td><?= $row['message'] ?></td>
                            <td>
                                <span class="badge <?= $row['status']=='unread' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= date("M d, Y H:i", strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editNotif<?= $row['notification_id'] ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="?delete=<?= $row['notification_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this notification?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editNotif<?= $row['notification_id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title">✏️ Edit Notification #<?= $row['notification_id'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body row g-3">
                                            <input type="hidden" name="notification_id" value="<?= $row['notification_id'] ?>">

                                            <div class="col-md-6">
                                                <label class="form-label">User</label>
                                                <select name="user_id" class="form-select" required>
                                                    <?php foreach($allUsers as $uid => $uname): ?>
                                                        <option value="<?= $uid ?>" <?= $row['user_id']==$uid?'selected':'' ?>><?= $uname ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Booking</label>
                                                <select name="booking_id" class="form-select">
                                                    <option value="">-- Select Booking --</option>
                                                    <?php foreach($allBookings as $b): ?>
                                                        <option value="<?= $b['booking_id'] ?>" <?= $row['booking_id']==$b['booking_id']?'selected':'' ?>>
                                                            Booking #<?= $b['booking_id'] ?> - <?= $b['customer_name'] ?> (<?= $b['event_name'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label">Message</label>
                                                <textarea name="message" class="form-control" rows="3" required><?= $row['message'] ?></textarea>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="unread" <?= $row['status']=='unread'?'selected':'' ?>>Unread</option>
                                                    <option value="read" <?= $row['status']=='read'?'selected':'' ?>>Read</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="update_notification" class="btn btn-primary">
                                                💾 Save Changes
                                            </button>
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
</main>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
