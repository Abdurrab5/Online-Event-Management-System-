<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}
$uploadDir = __DIR__ . '/../assets/images/';

/* ---------------- CREATE SERVICE ---------------- */
if (isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $category     = $_POST['category'];
    $description  = $_POST['description'];
    $price        = $_POST['price'];

    $imageName = 'default_service.jpg';
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'service_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $conn->prepare("INSERT INTO services (service_name, category, description, price, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $service_name, $category, $description, $price, $imageName);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_services.php");
    exit;
}

/* ---------------- UPDATE SERVICE ---------------- */
if (isset($_POST['update_service'])) {
    $service_id   = $_POST['service_id'];
    $service_name = $_POST['service_name'];
    $category     = $_POST['category'];
    $description  = $_POST['description'];
    $price        = $_POST['price'];
    $imageName    = $_POST['existing_image'];

    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'service_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $conn->prepare("UPDATE services SET service_name=?, category=?, description=?, price=?, image=? WHERE service_id=?");
    $stmt->bind_param("ssdssi", $service_name, $category, $description, $price, $imageName, $service_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_services.php");
    exit;
}

/* ---------------- DELETE SERVICE ---------------- */
if (isset($_GET['delete'])) {
    $service_id = intval($_GET['delete']);
    $img = $conn->query("SELECT image FROM services WHERE service_id=$service_id")->fetch_assoc()['image'];
    if ($img && $img != 'default_service.jpg' && file_exists($uploadDir . $img)) unlink($uploadDir . $img);

    $stmt = $conn->prepare("DELETE FROM services WHERE service_id=?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_services.php");
    exit;
}

/* ---------------- FETCH SERVICES ---------------- */
$result = $conn->query("SELECT * FROM services");
?>

<main class="site-content container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Manage Services</h2>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addServiceForm">
            ➕ Add New Service
        </button>
    </div>

    <!-- Add Service Form (Collapsible) -->
    <div id="addServiceForm" class="collapse mb-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="service_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <option value="catering">Catering</option>
                                <option value="decoration">Decoration</option>
                                <option value="music_entertainment">Music & Entertainment</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Price ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_service" class="btn btn-success mt-3">Save Service</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Services Table -->
    <div class="table-responsive shadow-sm">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Description</th><th>Image</th><th>Created At</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['service_id'] ?></td>
                    <td><?= $row['service_name'] ?></td>
                    <td><span class="badge bg-info text-dark"><?= ucfirst($row['category']) ?></span></td>
                    <td>$<?= number_format($row['price'], 2) ?></td>
                    <td><?= $row['description'] ?></td>
                    <td><img src="../assets/images/<?= $row['image'] ?>" width="80" class="rounded"></td>
                    <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick='editService(<?= json_encode($row) ?>)'>✏️ Edit</button>
                        <a href="?delete=<?= $row['service_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this service?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Edit Service Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Service</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="service_id" id="edit_service_id">
        <input type="hidden" name="existing_image" id="edit_existing_image">

        <label class="form-label">Service Name</label>
        <input type="text" name="service_name" id="edit_service_name" class="form-control mb-2" required>

        <label class="form-label">Category</label>
        <select name="category" id="edit_category" class="form-select mb-2" required>
            <option value="catering">Catering</option>
            <option value="decoration">Decoration</option>
            <option value="music_entertainment">Music & Entertainment</option>
            <option value="other">Other</option>
        </select>

        <label class="form-label">Price ($)</label>
        <input type="number" step="0.01" name="price" id="edit_price" class="form-control mb-2" required>

        <label class="form-label">Description</label>
        <textarea name="description" id="edit_description" class="form-control mb-2"></textarea>

        <label class="form-label">Image</label>
        <input type="file" name="image" class="form-control mb-2">
      </div>
      <div class="modal-footer">
        <button type="submit" name="update_service" class="btn btn-success">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editService(service) {
    document.getElementById('edit_service_id').value = service.service_id;
    document.getElementById('edit_existing_image').value = service.image;
    document.getElementById('edit_service_name').value = service.service_name;
    document.getElementById('edit_category').value = service.category;
    document.getElementById('edit_price').value = service.price;
    document.getElementById('edit_description').value = service.description;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
