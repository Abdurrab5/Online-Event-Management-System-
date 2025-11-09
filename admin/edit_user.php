<?php ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = intval($_GET['id']);
$user = $conn->query("SELECT * FROM users WHERE user_id=$id")->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name='$name', email='$email', password='$password', role='$role' WHERE user_id=$id";
    } else {
        $sql = "UPDATE users SET name='$name', email='$email', role='$role' WHERE user_id=$id";
    }

    if ($conn->query($sql)) {
        header("Location: manage_users.php");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<main class="site-content container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Edit User</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="needs-validation" novalidate>
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($user['name']) ?>" required>
                            <div class="invalid-feedback">Please enter a name.</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                            <div class="invalid-feedback">Please enter a valid email.</div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Leave blank to keep unchanged">
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label fw-bold">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="customer" <?= $user['role']=='customer'?'selected':'' ?>>Customer</option>
                                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Update User</button>
                            <a href="manage_users.php" class="btn btn-outline-secondary mt-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
// Bootstrap 5 form validation
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})();
</script>
<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>