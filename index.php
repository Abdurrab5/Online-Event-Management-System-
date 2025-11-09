<?php include __DIR__ . '/header.php'; ?>

<!-- HERO SECTION -->
<section class="bg-dark text-white text-center py-5" style="background: url('<?= $base_url; ?>assets/images/hero_bg.jpg') center/cover no-repeat;">
    <div class="container py-5">
        <h1 class="display-4 fw-bold text-shadow">Make Your Event Memorable</h1>
        <p class="lead mb-4 text-shadow">Plan, organize, and manage weddings, conferences, concerts, and more seamlessly with OEMS.</p>
        <a href="services.php" class="btn btn-primary btn-lg me-2 shadow-sm">Explore Services</a>
        <a href="contact_us.php" class="btn btn-outline-light btn-lg shadow-sm">Contact Us</a>
    </div>
</section>

<!-- SERVICES SECTION -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Services</h2>
        <div class="row g-4">
            <?php
            $services = $conn->query("SELECT * FROM services ORDER BY created_at DESC LIMIT 6");
            if($services->num_rows > 0):
                while($s = $services->fetch_assoc()):
            ?>
            <div class="col-md-4">
                <div class="card h-100 shadow border-0 hover-zoom">
                    <img src="<?= $base_url; ?>assets/images/<?= $s['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($s['service_name']); ?>" style="height: 220px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($s['service_name']); ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($s['description'])); ?></p>
                        <p class="fw-bold mt-auto">Price: ₨ <?= number_format($s['price'],2); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="alert alert-info">No services available currently.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- PACKAGES SECTION -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Our Packages</h2>
        <div class="row g-4">
            <?php
            $packages = $conn->query("SELECT * FROM packages ORDER BY created_at DESC LIMIT 6");
            if($packages->num_rows > 0):
                while($p = $packages->fetch_assoc()):
            ?>
            <div class="col-md-4">
                <div class="card h-100 shadow border-0 hover-zoom">
                    <img src="<?= $base_url; ?>assets/images/<?= $p['image'] ?? 'default_package.jpg' ?>" class="card-img-top" alt="<?= htmlspecialchars($p['package_name']); ?>" style="height: 220px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($p['package_name']); ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($p['description'])); ?></p>
                        <p class="fw-bold mt-auto">Price: ₨ <?= number_format($p['price'],2); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="alert alert-info">No packages available currently.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- UPCOMING EVENTS -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Upcoming Events</h2>
        <div class="row g-4">
            <?php
            $events = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 6");
            if($events->num_rows > 0):
                while($e = $events->fetch_assoc()):
            ?>
            <div class="col-md-4">
                <div class="card h-100 shadow border-0 hover-zoom">
                    <img src="<?= $base_url; ?>assets/images/<?= $e['image'] ?? 'default_event.jpg' ?>" class="card-img-top" alt="<?= htmlspecialchars($e['event_name']); ?>" style="height: 220px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($e['event_name']); ?></h5>
                        <p class="card-text"><?= htmlspecialchars($e['description']); ?></p>
                        <p><strong>Date:</strong> <?= $e['event_date']; ?></p>
                        <p><strong>Venue:</strong> <?= htmlspecialchars($e['venue']); ?></p>
                        <p class="fw-bold mt-auto">Cost: ₨ <?= number_format($e['cost'],2); ?></p>
                          <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'customer')): ?>
                                <a href="<?= $base_url; ?>/customer/book_event.php?event_id=<?= $e['event_id']; ?>" 
                                   class="btn btn-success w-100 mt-auto">
                                   <i class="bi bi-ticket-perforated me-1"></i> Book Now
                                </a>
                            <?php else: ?>
                              <a href="login.php" 
                                   class="btn btn-success w-100 mt-auto">
                                   <i class="bi bi-ticket-perforated me-1"></i> Login To Book
                                </a>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="alert alert-info">No upcoming events currently.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <h2 class="text-center mb-5">What Our Customers Say</h2>
        <div class="row g-4">
            <?php
            $testimonials = [
                ["text"=>"OEMS made our wedding planning stress-free. Highly recommend!", "author"=>"Sarah Khan"],
                ["text"=>"The event packages are affordable and well-organized. Great service.", "author"=>"Ali Raza"],
                ["text"=>"Easy to book services and track notifications. Very convenient.", "author"=>"Maria Ahmed"]
            ];
            foreach($testimonials as $t):
            ?>
            <div class="col-md-4">
                <div class="card bg-light text-dark h-100 shadow border-0 hover-translate">
                    <div class="card-body">
                        <p>"<?= $t['text'] ?>"</p>
                        <h6 class="fw-bold">- <?= $t['author'] ?></h6>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-5 text-center">
    <div class="container">
        <h2 class="mb-3">Ready to Plan Your Event?</h2>
        <p class="mb-4">Book your services, packages, or events now and make your event unforgettable.</p>
        <a href="services.php" class="btn btn-primary btn-lg me-2 shadow-sm">Explore Services</a>
        <a href="contact.php" class="btn btn-outline-primary btn-lg shadow-sm">Contact Us</a>
    </div>
</section>

<style>
/* Hover zoom effect */
.hover-zoom:hover {
    transform: translateY(-5px) scale(1.02);
    transition: all 0.3s ease;
}

/* Hover translate effect for testimonials */
.hover-translate:hover {
    transform: translateY(-5px);
    transition: all 0.3s ease;
}

/* Text shadow for hero section */
.text-shadow {
    text-shadow: 2px 2px 10px rgba(0,0,0,0.6);
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
