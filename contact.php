<?php ob_start(); include __DIR__ . '/header.php'; ?>


<?php
$message = '';
$type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);

    // Basic validation
    if(empty($name) || empty($email) || empty($subject) || empty($message_text)){
        $message = "⚠️ All fields are required!";
        $type = "danger";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $message = "❌ Invalid email address!";
        $type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message_text);

        if($stmt->execute()){
            $message = "✅ Your message has been sent successfully. We'll get back to you soon!";
            $type = "success";
        } else {
            $message = "❌ Failed to send message. Please try again later.";
            $type = "danger";
        }
        $stmt->close();
    }
} 
?>

 

<div class="container my-5">
    <h2 class="text-center fw-bold mb-4">📩 Contact Us</h2>

    <?php if($message): ?>
        <div class="alert alert-<?= $type ?> shadow-sm text-center fw-semibold">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Contact Form -->
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 fw-bold text-primary">Send us a Message</h5>
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Name</label>
                            <input type="text" name="name" class="form-control form-control-lg" placeholder="Your full name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="you@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label fw-semibold">Subject</label>
                            <input type="text" name="subject" class="form-control form-control-lg" placeholder="Message subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label fw-semibold">Message</label>
                            <textarea name="message" class="form-control form-control-lg" rows="5" placeholder="Write your message here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-send-fill me-1"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Contact Info -->
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-3 bg-light h-100">
                <div class="card-body p-4">
                    <h5 class="card-title mb-3 fw-bold text-primary">Get in Touch</h5>
                    <p><i class="bi bi-geo-alt-fill text-danger me-2"></i><strong>Address:</strong> 123 Event Street, City, Country</p>
                    <p><i class="bi bi-telephone-fill text-success me-2"></i><strong>Phone:</strong> +123 456 7890</p>
                    <p><i class="bi bi-envelope-fill text-primary me-2"></i><strong>Email:</strong> info@oems.com</p>
                    <p><i class="bi bi-globe text-info me-2"></i><strong>Website:</strong> www.oems.com</p>

                    <h5 class="mt-4 fw-bold">Follow Us</h5>
                    <div class="d-flex gap-3 mt-2">
                        <a href="#" class="text-primary fs-4"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-info fs-4"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-danger fs-4"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php';  ob_end_flush();?>

 
<style>
    .card:hover {
        transform: translateY(-5px);
        transition: all 0.3s ease-in-out;
    }
</style>
