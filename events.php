<?php ob_start(); include __DIR__ . '/header.php'; ?>

<?php
// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_role = $_SESSION['role'] ?? null;

// Fetch upcoming events
$events = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC");
?>

<div class="container my-5">
    <h2 class="text-center fw-bold mb-4">✨ Upcoming Events</h2>
    <div class="row g-4">
        <?php if($events->num_rows > 0): ?>
            <?php while($row = $events->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-lg border-0 rounded-3 overflow-hidden event-card">
                        <!-- Event Image -->
                        <img src="<?= $base_url; ?>assets/images/<?= $row['image'] ?? 'default_event.jpg' ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($row['event_name']); ?>" 
                             style="height: 220px; object-fit: cover;">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-primary"><?= htmlspecialchars($row['event_name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?= nl2br(htmlspecialchars($row['description'])); ?></p>
                            
                            <ul class="list-unstyled small mb-3">
                                <li><i class="bi bi-calendar-event me-2 text-primary"></i><strong>Date:</strong> <?= $row['event_date']; ?></li>
                                <li><i class="bi bi-geo-alt me-2 text-danger"></i><strong>Venue:</strong> <?= htmlspecialchars($row['venue']); ?></li>
                            </ul>
                            
                            <p class="fw-bold fs-5 text-success mb-3">₨ <?= number_format($row['cost'], 2); ?></p>

                            <?php if(($is_logged_in) && ($user_role === 'customer')): ?>
                                <a href="<?= $base_url; ?>customer/book_event.php?event_id=<?= $row['event_id']; ?>" 
                                   class="btn btn-success w-100 mt-auto">
                                   <i class="bi bi-ticket-perforated me-1"></i> Book Now
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-primary w-100 mt-auto" data-bs-toggle="modal" data-bs-target="#loginPromptModal">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Book Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">No upcoming events at the moment 🎉</div>
        <?php endif; ?>
    </div>
</div>

<!-- Login Prompt Modal -->
<div class="modal fade" id="loginPromptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-lock-fill me-2"></i>Login Required</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-3">You must <a href="login.php" class="fw-bold text-decoration-none">login</a> to book an event.</p>
                <a href="login.php" class="btn btn-primary px-4"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ob_end_flush(); ?>

 
<style>
    .event-card:hover {
        transform: translateY(-5px);
        transition: all 0.3s ease-in-out;
    }
</style>
