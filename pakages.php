<?php include __DIR__ . '/header.php'; ?>

<?php
// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Fetch all packages
$packages = $conn->query("SELECT * FROM packages ORDER BY created_at DESC");
?>

<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Our Packages</h2>
        <div class="row g-4">
            <?php if($packages->num_rows > 0): ?>
                <?php while($p = $packages->fetch_assoc()): ?>
                    <?php
                        // Fetch services included in this package
                        $stmt = $conn->prepare("
                            SELECT s.service_name 
                            FROM package_services ps
                            JOIN services s ON ps.service_id = s.service_id
                            WHERE ps.package_id = ?
                        ");
                        $stmt->bind_param("i", $p['package_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $services_included = [];
                        while($s = $result->fetch_assoc()) {
                            $services_included[] = $s['service_name'];
                        }
                        $stmt->close();
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 hover-zoom">
                            <img src="<?= $base_url; ?>/assets/images/<?= $p['image'] ?? 'default_package.jpg' ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($p['package_name']); ?>" 
                                 style="height: 220px; object-fit: cover;">

                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($p['package_name']); ?></h5>
                                <p class="card-text flex-grow-1"><?= nl2br(htmlspecialchars($p['description'])); ?></p>
                                <p class="fw-bold">Price: ₨ <?= number_format($p['price'],2); ?></p>
                                
                                <div class="mb-3">
                                    <strong>Included Services:</strong><br>
                                    <?php if(!empty($services_included)): ?>
                                        <?php foreach($services_included as $service): ?>
                                            <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($service); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </div>

                                <?php if($is_logged_in): ?>
                                    <a href="book_package.php?id=<?= $p['package_id']; ?>" class="btn btn-primary mt-auto">Book Package</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary mt-auto">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No packages available at the moment.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* Card hover zoom effect */
.hover-zoom:hover {
    transform: translateY(-5px) scale(1.02);
    transition: all 0.3s ease;
}

/* Buttons hover effect */
.btn-primary:hover, .btn-outline-primary:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
