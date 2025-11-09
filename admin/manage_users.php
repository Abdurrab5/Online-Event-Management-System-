<?php  ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}

// Fetch all users
$result = $conn->query("SELECT * FROM users WHERE role!='admin' ORDER BY created_at DESC");
?>

<div class="container my-5">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">👥 Manage Users</h4>
            <a href="add_user.php" class="btn btn-light btn-sm">
                ➕ Add New User
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">#ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Role</th>
                            <th scope="col">Created At</th>
                            <th scope="col" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?= $row['user_id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= ucfirst($row['role']) ?></span></td>
                            <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="btn btn-sm btn-warning me-2">
                                    ✏️ Edit
                                </a>
                                <a href="delete_user.php?id=<?= $row['user_id'] ?>" 
                                   onclick="return confirm('Are you sure you want to delete this user?')" 
                                   class="btn btn-sm btn-danger">
                                    🗑 Delete
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
