<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Directory to store event images
$uploadDir = __DIR__ . '/../assets/images/';

// Handle Create (Add Event)
if (isset($_POST['add_event'])) {
    $event_name   = $_POST['event_name'];
    $event_type   = $_POST['event_type'];
    $description  = $_POST['description'];
    $venue        = $_POST['venue'];
    $event_date   = $_POST['event_date'];
    $capacity     = $_POST['capacity'];
    $cost         = $_POST['cost'];
    $created_by   = $_SESSION['user_id'] ?? 1;

    $imageName = 'default_event.jpg';
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'event_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $conn->prepare("INSERT INTO events (event_name, event_type, description, venue, event_date, capacity, cost, created_by, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssidis", $event_name, $event_type, $description, $venue, $event_date, $capacity, $cost, $created_by, $imageName);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit;
}

// Handle Update
if (isset($_POST['update_event'])) {
    $event_id     = $_POST['event_id'];
    $event_name   = $_POST['event_name'];
    $event_type   = $_POST['event_type'];
    $description  = $_POST['description'];
    $venue        = $_POST['venue'];
    $event_date   = $_POST['event_date'];
    $capacity     = $_POST['capacity'];
    $cost         = $_POST['cost'];

    $imageName = $_POST['existing_image'];
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = 'event_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    }

    $stmt = $conn->prepare("UPDATE events SET event_name=?, event_type=?, description=?, venue=?, event_date=?, capacity=?, cost=?, image=? WHERE event_id=?");
    $stmt->bind_param("sssssidsi", $event_name, $event_type, $description, $venue, $event_date, $capacity, $cost, $imageName, $event_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $event_id = intval($_GET['delete']);
    $img = $conn->query("SELECT image FROM events WHERE event_id=$event_id")->fetch_assoc()['image'];
    if ($img && $img != 'default_event.jpg' && file_exists($uploadDir . $img)) {
        unlink($uploadDir . $img);
    }
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_events.php");
    exit;
}

// Fetch Events
$result = $conn->query("SELECT * FROM events ORDER BY created_at DESC");
?>

<main class="site-content container py-5">

    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Add New Event</h4>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Event Name</label>
                    <input type="text" name="event_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Event Type</label>
                    <select name="event_type" class="form-select" required>
                        <option value="">-- Select Event Type --</option>
                        <option value="wedding">Wedding</option>
                        <option value="seminar">Seminar</option>
                        <option value="conference">Conference</option>
                        <option value="birthday">Birthday</option>
                        <option value="concert">Concert</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Venue</label>
                    <input type="text" name="venue" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Date</label>
                    <input type="date" name="event_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Capacity</label>
                    <input type="number" name="capacity" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Cost</label>
                    <input type="number" step="0.01" name="cost" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Event Image</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12 d-grid">
                    <button type="submit" name="add_event" class="btn btn-success btn-lg">Add Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Table -->
    <div class="card shadow-lg border-0">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Manage Events</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th><th>Name</th><th>Type</th><th>Venue</th><th>Date</th>
                        <th>Capacity</th><th>Cost</th><th>Image</th><th>Description</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['event_id'] ?></td>
                        <td><?= htmlspecialchars($row['event_name']) ?></td>
                        <td><?= ucfirst($row['event_type']) ?></td>
                        <td><?= htmlspecialchars($row['venue']) ?></td>
                        <td><?= $row['event_date'] ?></td>
                        <td><?= $row['capacity'] ?></td>
                        <td>$<?= number_format($row['cost'], 2) ?></td>
                        <td><img src="../assets/images/<?= $row['image'] ?>" width="70" class="rounded shadow-sm"></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick='editEvent(<?= json_encode($row) ?>)'>Edit</button>
                            <a href="?delete=<?= $row['event_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this event?')">Delete</a>
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
        <h5 class="modal-title">Edit Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body row g-3">
        <input type="hidden" name="event_id" id="edit_event_id">
        <input type="hidden" name="existing_image" id="edit_existing_image">

        <div class="col-md-6">
            <label class="form-label fw-bold">Event Name</label>
            <input type="text" name="event_name" id="edit_event_name" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Event Type</label>
            <select name="event_type" id="edit_event_type" class="form-select" required>
                <option value="">-- Select Event Type --</option>
                <option value="wedding">Wedding</option>
                <option value="seminar">Seminar</option>
                <option value="conference">Conference</option>
                <option value="birthday">Birthday</option>
                <option value="concert">Concert</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Venue</label>
            <input type="text" name="venue" id="edit_venue" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Date</label>
            <input type="date" name="event_date" id="edit_event_date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Capacity</label>
            <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Cost</label>
            <input type="number" step="0.01" name="cost" id="edit_cost" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">Event Image</label>
            <input type="file" name="image" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="submit" name="update_event" class="btn btn-success">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
</div>
</div>

<script>
function editEvent(event) {
    document.getElementById('edit_event_id').value = event.event_id;
    document.getElementById('edit_existing_image').value = event.image;
    document.getElementById('edit_event_name').value = event.event_name;
    document.getElementById('edit_event_type').value = event.event_type;
    document.getElementById('edit_venue').value = event.venue;
    document.getElementById('edit_event_date').value = event.event_date;
    document.getElementById('edit_capacity').value = event.capacity;
    document.getElementById('edit_cost').value = event.cost;
    document.getElementById('edit_description').value = event.description;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
