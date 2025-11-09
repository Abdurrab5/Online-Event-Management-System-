<?php ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'customer')) {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch notifications
$notifications = $conn->query("
    SELECT notification_id, message, status, created_at
    FROM notifications
    WHERE user_id = $user_id
    ORDER BY created_at DESC
");

// Count unread notifications
$unreadCount = $conn->query("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = $user_id AND status='unread'
")->fetch_assoc()['total'];

// Mark as read
if(isset($_GET['mark_read'])){
    $nid = intval($_GET['mark_read']);
    $conn->query("UPDATE notifications SET status='read' WHERE notification_id=$nid AND user_id=$user_id");
    header("Location: notifications.php");
    exit;
}
?>

<div class="container mt-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-bell-fill"></i> Notifications</h4>
            <span class="badge bg-danger fs-6 px-3"><?= $unreadCount ?></span>
        </div>
        <div class="card-body p-4">
            <?php if($notifications->num_rows > 0): ?>
                <ul class="list-group list-group-flush">
                    <?php while($n = $notifications->fetch_assoc()): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start <?= $n['status'] === 'unread' ? 'bg-light border-start border-5 border-warning' : '' ?>">
                            <div>
                                <p class="mb-1 fw-semibold"><?= htmlspecialchars($n['message']); ?></p>
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= date("M d, Y h:i A", strtotime($n['created_at'])); ?></small>
                            </div>
                            <?php if($n['status'] === 'unread'): ?>
                                <a href="notifications.php?mark_read=<?= $n['notification_id'] ?>" class="btn btn-sm btn-outline-success ms-3">
                                    <i class="bi bi-check-circle"></i> Mark as Confirm
                                </a>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="bi bi-check2"></i> Confirm</span>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-info text-center py-4">
                    <i class="bi bi-info-circle fs-4"></i> No notifications at the moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
