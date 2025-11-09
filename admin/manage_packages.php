<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$uploadDir = __DIR__ . '/../assets/images/';

/* ---------------- Handle Create Package ---------------- */
if (isset($_POST['add_package'])) {
    $package_name = $_POST['package_name'];
    $description  = $_POST['description'];
    $price        = $_POST['price'];
    $services     = $_POST['services'] ?? [];

    $imageName = 'default_package.jpg';
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'package_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $conn->prepare("INSERT INTO packages (package_name, description, price, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdss", $package_name, $description, $price, $imageName);
    $stmt->execute();
    $package_id = $stmt->insert_id;
    $stmt->close();

    foreach ($services as $sid) {
        $stmt = $conn->prepare("INSERT INTO package_services (package_id, service_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $package_id, $sid);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_packages.php");
    exit;
}

/* ---------------- Handle Update Package ---------------- */
if (isset($_POST['update_package'])) {
    $package_id   = $_POST['package_id'];
    $package_name = $_POST['package_name'];
    $description  = $_POST['description'];
    $price        = $_POST['price'];
    $services     = $_POST['services'] ?? [];
    $imageName    = $_POST['existing_image'];

    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'package_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $conn->prepare("UPDATE packages SET package_name=?, description=?, price=?, image=? WHERE package_id=?");
    $stmt->bind_param("sdssi", $package_name, $description, $price, $imageName, $package_id);
    $stmt->execute();
    $stmt->close();

    $conn->query("DELETE FROM package_services WHERE package_id=$package_id");
    foreach ($services as $sid) {
        $stmt = $conn->prepare("INSERT INTO package_services (package_id, service_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $package_id, $sid);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_packages.php");
    exit;
}

/* ---------------- Handle Delete Package ---------------- */
if (isset($_GET['delete'])) {
    $package_id = intval($_GET['delete']);
    $img = $conn->query("SELECT image FROM packages WHERE package_id=$package_id")->fetch_assoc()['image'];
    if ($img && $img != 'default_package.jpg' && file_exists($uploadDir . $img)) unlink($uploadDir . $img);

    $stmt = $conn->prepare("DELETE FROM packages WHERE package_id=?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_packages.php");
    exit;
}

/* ---------------- Fetch Data ---------------- */
$packages = $conn->query("SELECT * FROM packages ORDER BY created_at DESC");
$servicesList = $conn->query("SELECT service_id, service_name FROM services ORDER BY service_name");
$allServices = [];
while ($s = $servicesList->fetch_assoc()) $allServices[$s['service_id']] = $s['service_name'];

$pkg_services = [];
$res = $conn->query("SELECT package_id, service_id FROM package_services");
while ($ps = $res->fetch_assoc()) $pkg_services[$ps['package_id']][] = $ps['service_id'];
?>

<main class="site-content container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">📦 Manage Packages</h2>
    </div>

    <!-- Add Package Form -->
    <div class="card shadow-sm mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">➕ Add New Package</h5>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="package_name" class="form-control" placeholder="Package Name" required>
                    </div>
                    <div class="col-md-6">
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required>
                    </div>
                    <div class="col-12">
                        <textarea name="description" class="form-control" placeholder="Description"></textarea>
                    </div>
                    <div class="col-md-6">
                        <input type="file" name="image" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Select Services:</label>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($allServices as $id => $name): ?>
                                <div class="form-check me-3">
                                    <input type="checkbox" name="services[]" value="<?= $id ?>" class="form-check-input" id="service<?= $id ?>">
                                    <label class="form-check-label" for="service<?= $id ?>"><?= htmlspecialchars($name) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="submit" name="add_package" class="btn btn-primary">Save Package</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Packages Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">📋 Package List</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th><th>Name</th><th>Description</th><th>Price</th><th>Image</th><th>Services</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $packages->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['package_id'] ?></td>
                        <td class="fw-semibold"><?= $row['package_name'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td><span class="badge bg-success">$<?= number_format($row['price'], 2) ?></span></td>
                        <td><img src="../assets/images/<?= $row['image'] ?>" width="80" class="rounded shadow-sm"></td>
                        <td>
                            <?php 
                            if (!empty($pkg_services[$row['package_id']])) {
                                foreach ($pkg_services[$row['package_id']] as $sid) {
                                    echo "<span class='badge bg-info text-dark me-1'>{$allServices[$sid]}</span>";
                                }
                            } else {
                                echo "<em>No Services</em>";
                            }
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='editPackage(<?= json_encode($row) ?>, <?= json_encode($pkg_services[$row["package_id"]] ?? []) ?>)'>✏️ Edit</button>
                            <a href="?delete=<?= $row['package_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this package?')">🗑 Delete</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">✏️ Edit Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="package_id" id="edit_package_id">
                <input type="hidden" name="existing_image" id="edit_existing_image">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="package_name" id="edit_package_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <textarea name="description" id="edit_description" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6">
                        <input type="file" name="image" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Services:</label>
                        <div id="edit_services" class="d-flex flex-wrap">
                            <?php foreach ($allServices as $id => $name): ?>
                                <div class="form-check me-3">
                                    <input type="checkbox" name="services[]" value="<?= $id ?>" class="form-check-input service-checkbox" id="edit_service<?= $id ?>">
                                    <label class="form-check-label" for="edit_service<?= $id ?>"><?= htmlspecialchars($name) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update_package" class="btn btn-success">💾 Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPackage(pkg, selectedServices) {
    document.getElementById('edit_package_id').value = pkg.package_id;
    document.getElementById('edit_existing_image').value = pkg.image;
    document.getElementById('edit_package_name').value = pkg.package_name;
    document.getElementById('edit_description').value = pkg.description;
    document.getElementById('edit_price').value = pkg.price;

    document.querySelectorAll('.service-checkbox').forEach(cb => cb.checked = false);
    selectedServices.forEach(sid => {
        let checkbox = document.querySelector('.service-checkbox[value="'+sid+'"]');
        if (checkbox) checkbox.checked = true;
    });

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
