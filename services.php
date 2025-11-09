<?php ob_start(); include __DIR__ . '/header.php'; ?>

<?php
// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Fetch all services including image
$services = $conn->query("SELECT service_id, service_name, category, description, price, image, created_at FROM services ORDER BY created_at DESC");
?>

<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Our Services</h2>
        <div class="row g-4">
            <?php if($services->num_rows > 0): ?>
                <?php while($s = $services->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 hover-zoom">
                            <img src="assets/images/<?= $s['image'] ?? 'default_service.jpg' ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($s['service_name']); ?>" 
                                 style="height: 220px; object-fit: cover;">

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($s['service_name']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?= ucfirst($s['category']); ?></h6>
                                <p class="card-text flex-grow-1"><?= nl2br(htmlspecialchars($s['description'])); ?></p>
                                <p class="fw-bold mt-auto">₨ <?= number_format($s['price'], 2); ?></p>
                                <?php if($is_logged_in): ?>
                                    <a href="book_service.php?id=<?= $s['service_id']; ?>" class="btn btn-primary mt-2">Book Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary mt-2">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No services available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* Hover zoom effect for cards */
.hover-zoom:hover {
    transform: translateY(-5px) scale(1.02);
    transition: all 0.3s ease;
}

/* Optional: subtle shadow on hover for buttons */
.btn-primary:hover, .btn-outline-primary:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}
</style>

<?php include __DIR__ . '/footer.php'; ob_end_flush(); ?>
