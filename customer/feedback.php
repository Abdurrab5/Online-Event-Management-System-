<?php ob_start();
 
 include __DIR__ . '/../header.php';
 if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'customer')) {
    header("Location: ../login.php");
    exit;
}  
$user_id = $_SESSION['user_id'];

// Fetch only completed bookings for this user which haven't been reviewed yet
$bookings = $conn->query("
    SELECT b.booking_id, e.event_name, b.event_date 
    FROM bookings b
    JOIN events e ON b.event_id = e.event_id
    WHERE b.user_id = $user_id 
      AND b.status = 'completed' 
      AND b.booking_id NOT IN (SELECT booking_id FROM feedback WHERE user_id=$user_id)
    ORDER BY b.event_date DESC
");

$errors = [];
$success = "";

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $booking_id = $_POST['booking_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comments = trim($_POST['comments'] ?? '');

    // Validation
    if(!$booking_id) $errors[] = "Invalid booking selected.";
    if(!$rating || $rating < 1 || $rating > 5) $errors[] = "Please provide a valid rating (1-5).";

    // Check if booking exists and belongs to this user
    $check = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id=? AND user_id=? AND status='completed'");
    $check->bind_param("ii", $booking_id, $user_id);
    $check->execute();
    $check->store_result();
    if($check->num_rows === 0) $errors[] = "Booking not found or not eligible for feedback.";

    if(empty($errors)){
        // Insert feedback
        $stmt = $conn->prepare("INSERT INTO feedback (booking_id, user_id, rating, comments) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $booking_id, $user_id, $rating, $comments);
        if($stmt->execute()){
            $success = "Feedback submitted successfully!";
        } else {
            $errors[] = "Failed to submit feedback. Please try again.";
        }
    }
}
?>

 
<div class="container mt-5">
    <h2>Submit Feedback</h2>

    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach($errors as $e) echo "<li>$e</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success"><?= $success; ?></div>
    <?php endif; ?>

    <?php if($bookings->num_rows === 0 && !$success): ?>
        <div class="alert alert-info">No completed bookings available for feedback.</div>
    <?php else: ?>
    <form method="POST">
        <div class="mb-3">
            <label>Select Booking</label>
            <select name="booking_id" class="form-select" required>
                <option value="">-- Choose Booking --</option>
                <?php while($b = $bookings->fetch_assoc()): ?>
                    <option value="<?= $b['booking_id']; ?>">
                        <?= htmlspecialchars($b['event_name']); ?> (<?= $b['event_date']; ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Rating</label>
            <select name="rating" class="form-select" required>
                <option value="">-- Select Rating --</option>
                <?php for($i=1;$i<=5;$i++): ?>
                    <option value="<?= $i; ?>"><?= $i; ?> Star<?= $i > 1 ? 's' : ''; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Comments (Optional)</label>
            <textarea name="comments" class="form-control" rows="3"></textarea>
        </div>

        <button class="btn btn-success">Submit Feedback</button>
        <a href="view_events.php" class="btn btn-secondary">Cancel</a>
    </form>
    <?php endif; ?>
</div>
 <?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
