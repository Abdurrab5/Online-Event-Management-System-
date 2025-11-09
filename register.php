<?php ob_start();
include __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $contact = $_POST['contact'];

    $stmt = $conn->prepare("INSERT INTO users (name,email,password,contact_number) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $email, $password, $contact);

    if ($stmt->execute()) {
        $success = "Registration successful. <a href='login.php'>Login here</a>";
    } else {
        $error = "Error: Email already exists or invalid input.";
    }
}
?>

<section class="vh-100 d-flex align-items-center" style="background: url('<?= $base_url; ?>assets/images/register_bg.jpg') center/cover no-repeat;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <h3 class="card-title text-center mb-4">Register</h3>
                        
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php elseif(!empty($success)): ?>
                            <div class="alert alert-success"><?= $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Enter your full name" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Enter your email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter a strong password" required>
                            </div>

                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact Number</label>
                                <input type="text" class="form-control form-control-lg" id="contact" name="contact" placeholder="Optional">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg shadow-sm">Register</button>
                            </div>
                        </form>

                        <p class="text-center mt-3">Already have an account? <a href="login.php" class="text-primary fw-bold">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Optional overlay on background */
section.vh-100::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1;
}
.card {
    position: relative;
    z-index: 2;
}
/* Hover effect for the button */
.btn-primary:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}
/* Smooth focus effect on inputs */
.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}
</style>

<?php include __DIR__ . '/footer.php'; ob_end_flush();?>
