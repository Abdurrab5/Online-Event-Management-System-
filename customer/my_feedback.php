<?php ob_start();
include __DIR__ . '/../header.php';

if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'customer')) {
    header("Location: ../login.php");
    exit;
}  

 

$user_id = $_SESSION['user_id'];
$message = '';
$type = 'success';

// ================= FETCH CUSTOMER BOOKINGS ====================
$bookings = $conn->query("
    SELECT b.booking_id, e.event_name, b.event_date
    FROM bookings b
    JOIN events e ON b.event_id = e.event_id
    WHERE b.user_id = $user_id
    ORDER BY b.event_date DESC
");

$feedbacks = $conn->query("
    SELECT f.*, e.event_name
    FROM feedback f
    JOIN bookings b ON f.booking_id = b.booking_id
    JOIN events e ON b.event_id = e.event_id
    WHERE f.user_id = $user_id
    ORDER BY f.created_at DESC
");

// ================= ADD FEEDBACK ====================
if(isset($_POST['add_feedback'])){
    $booking_id = $_POST['booking_id'];
    $rating     = $_POST['rating'];
    $comments   = trim($_POST['comments']);

    if(empty($booking_id) || empty($rating) || empty($comments)){
        $message = "All fields are required.";
        $type = "danger";
    } else {
        // Check duplicate
        $check = $conn->prepare("SELECT * FROM feedback WHERE booking_id=? AND user_id=?");
        $check->bind_param("ii", $booking_id, $user_id);
        $check->execute();
        $check->store_result();
        if($check->num_rows > 0){
            $message = "You already submitted feedback for this booking.";
            $type = "danger";
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (booking_id, user_id, rating, comments) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $booking_id, $user_id, $rating, $comments);
            if($stmt->execute()){
                $message = "Feedback added successfully.";
            } else {
                $message = "Failed to add feedback.";
                $type = "danger";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// ================= UPDATE FEEDBACK ====================
if(isset($_POST['update_feedback'])){
    $feedback_id = $_POST['feedback_id'];
    $rating      = $_POST['rating'];
    $comments    = trim($_POST['comments']);

    if(empty($rating) || empty($comments)){
        $message = "Rating and comments are required.";
        $type = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE feedback SET rating=?, comments=? WHERE feedback_id=? AND user_id=?");
        $stmt->bind_param("isii", $rating, $comments, $feedback_id, $user_id);
        if($stmt->execute()){
            $message = "Feedback updated successfully.";
        } else {
            $message = "Failed to update feedback.";
            $type = "danger";
        }
        $stmt->close();
    }
}

// ================= DELETE FEEDBACK ====================
if(isset($_GET['delete'])){
    $feedback_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id=? AND user_id=?");
    $stmt->bind_param("ii", $feedback_id, $user_id);
    if($stmt->execute()){
        $message = "Feedback deleted successfully.";
    } else {
        $message = "Failed to delete feedback.";
        $type = "danger";
    }
    $stmt->close();
}
?>

 
<main class="container mt-5">
    <h2 class="mb-4">My Feedback</h2>

    <!-- Message -->
    <?php if($message): ?>
        <div class="alert alert-<?= $type ?>"><?= $message ?></div>
    <?php endif; ?>

    <!-- Add Feedback Form -->
    <form method="post" class="mb-4 border p-3 rounded bg-light">
        <h5>Add Feedback</h5>
        <select name="booking_id" class="form-select mb-2" required>
            <option value="">-- Select Booking --</option>
            <?php while($b = $bookings->fetch_assoc()): ?>
                <option value="<?= $b['booking_id'] ?>">
                    <?= $b['event_name'] ?> (<?= $b['event_date'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <input type="number" name="rating" min="1" max="5" placeholder="Rating (1-5)" class="form-control mb-2" required>
        <textarea name="comments" placeholder="Comments" class="form-control mb-2" required></textarea>
        <button type="submit" name="add_feedback" class="btn btn-primary">Submit Feedback</button>
    </form>

    <!-- Feedback Table -->
    <div class="row">
        <?php while($f = $feedbacks->fetch_assoc()): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= $f['event_name'] ?></h5>
                        <p class="card-text">
                            Rating: <?= $f['rating'] ?> / 5<br>
                            Comments: <?= htmlspecialchars($f['comments']) ?><br>
                            Date: <?= $f['created_at'] ?>
                        </p>
                        <a href="?delete=<?= $f['feedback_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this feedback?')">Delete</a>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editFeedback<?= $f['feedback_id'] ?>">Edit</button>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editFeedback<?= $f['feedback_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="post">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Feedback</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="feedback_id" value="<?= $f['feedback_id'] ?>">
                                <input type="number" name="rating" min="1" max="5" value="<?= $f['rating'] ?>" class="form-control mb-2" required>
                                <textarea name="comments" class="form-control mb-2" required><?= htmlspecialchars($f['comments']) ?></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update_feedback" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</main>
 <?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
