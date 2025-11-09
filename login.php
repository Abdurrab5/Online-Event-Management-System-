<?php ob_start();
include __DIR__ . '/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        header("Location: " . ($user['role'] === 'admin' ? "admin/dashboard.php" : "customer/dashboard.php"));
        exit;
    } else {
        $error = "Invalid email or password";
    }
}
?>

<section class="vh-100 d-flex align-items-center" style="background: url('<?= $base_url; ?>assets/images/login_bg.jpg') center/cover no-repeat;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <h3 class="card-title text-center mb-4">Login</h3>
                        <?php if (isset($_GET['message'])) { echo "<p style='color:red'>" . $_GET['message'] . "</p>"; } ?>

                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" placeholder="Enter your email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg shadow-sm">Login</button>
                            </div>
                        </form>
                        <p class="text-center mt-3">Don't have an account? <a href="register.php" class="text-primary fw-bold">Register</a></p>
                        <p class="text-center"><a href="forgot_password.php" class="text-muted small">Forgot Password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Optional: subtle overlay on background */
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
</style>

<?php include __DIR__ . '/footer.php'; ob_end_flush();?>
