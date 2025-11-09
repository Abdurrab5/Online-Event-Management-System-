<?php ob_start(); include __DIR__ . '/../header.php'; ?>

<?php
if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}
// ================== CREATE BOOKING ==================
if (isset($_POST['add_booking'])) {
    $user_id    = $_POST['user_id'];
    $event_id   = $_POST['event_id'];
    $package_id = $_POST['package_id'] ?: NULL;
    $event_date = $_POST['event_date'];
    $status     = $_POST['status'];
    $services   = $_POST['services'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    $total_cost = 0;
    if ($package_id) {
        $pkg = $conn->query("SELECT price FROM packages WHERE package_id=$package_id")->fetch_assoc();
        $total_cost += $pkg['price'];
    }
    foreach ($services as $sid) {
        $svc = $conn->query("SELECT price FROM services WHERE service_id=$sid")->fetch_assoc();
        $qty = intval($quantities[$sid] ?? 1);
        $total_cost += $svc['price'] * $qty;
    }

    $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, package_id, event_date, status, total_cost) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissi", $user_id, $event_id, $package_id, $event_date, $status, $total_cost);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $stmt->close();

    foreach ($services as $sid) {
        $qty = intval($quantities[$sid] ?? 1);
        $stmt = $conn->prepare("INSERT INTO booking_services (booking_id, service_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $booking_id, $sid, $qty);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_bookings.php");
    exit;
}

// ================== DELETE BOOKING ==================
if (isset($_GET['delete'])) {
    $booking_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_bookings.php");
    exit;
}

// ================== UPDATE BOOKING ==================
if (isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $user_id    = $_POST['user_id'];
    $event_id   = $_POST['event_id'];
    $package_id = $_POST['package_id'] ?: NULL;
    $event_date = $_POST['event_date'];
    $status     = $_POST['status'];
    $services   = $_POST['services'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    $total_cost = 0;
    if ($package_id) {
        $pkg = $conn->query("SELECT price FROM packages WHERE package_id=$package_id")->fetch_assoc();
        $total_cost += $pkg['price'];
    }
    foreach ($services as $sid) {
        $svc = $conn->query("SELECT price FROM services WHERE service_id=$sid")->fetch_assoc();
        $qty = intval($quantities[$sid] ?? 1);
        $total_cost += $svc['price'] * $qty;
    }

    $stmt = $conn->prepare("UPDATE bookings SET user_id=?, event_id=?, package_id=?, event_date=?, status=?, total_cost=? WHERE booking_id=?");
    $stmt->bind_param("iiissii", $user_id, $event_id, $package_id, $event_date, $status, $total_cost, $booking_id);
    $stmt->execute();
    $stmt->close();

    $conn->query("DELETE FROM booking_services WHERE booking_id=$booking_id");
    foreach ($services as $sid) {
        $qty = intval($quantities[$sid] ?? 1);
        $stmt = $conn->prepare("INSERT INTO booking_services (booking_id, service_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $booking_id, $sid, $qty);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_bookings.php");
    exit;
}

// ================== FETCH DATA ==================
$bookings = $conn->query("SELECT b.*, u.name AS user_name, e.event_name, p.package_name
                          FROM bookings b
                          JOIN users u ON b.user_id=u.user_id
                          JOIN events e ON b.event_id=e.event_id
                          LEFT JOIN packages p ON b.package_id=p.package_id
                          ORDER BY b.created_at DESC");

$users    = $conn->query("SELECT * FROM users WHERE role='customer'");
$events   = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
$packages = $conn->query("SELECT * FROM packages ORDER BY package_name");
$services = $conn->query("SELECT * FROM services ORDER BY service_name");

$allServices = [];
while ($s = $services->fetch_assoc()) {
    $allServices[$s['service_id']] = $s;
}

$booking_services = [];
$res = $conn->query("SELECT booking_id, service_id, quantity FROM booking_services");
while ($bs = $res->fetch_assoc()) {
    $booking_services[$bs['booking_id']][$bs['service_id']] = $bs['quantity'];
}
?>
 

<main class="site-content container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-primary">📅 Manage Bookings</h2>
  </div>

  <!-- Create Booking -->
  <div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">➕ Add New Booking</h5>
    </div>
    <div class="card-body">
      <form method="post">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">User</label>
            <select name="user_id" class="form-select" required>
              <option value="">-- Select User --</option>
              <?php $users->data_seek(0); while ($u = $users->fetch_assoc()) { ?>
                <option value="<?= $u['user_id'] ?>"><?= $u['name'] ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Event</label>
            <select name="event_id" class="form-select" required>
              <option value="">-- Select Event --</option>
              <?php $events->data_seek(0); while ($e = $events->fetch_assoc()) { ?>
                <option value="<?= $e['event_id'] ?>"><?= $e['event_name'] ?> (<?= $e['event_date'] ?>)</option>
              <?php } ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Package (Optional)</label>
            <select name="package_id" class="form-select">
              <option value="">-- Optional Package --</option>
              <?php $packages->data_seek(0); while ($p = $packages->fetch_assoc()) { ?>
                <option value="<?= $p['package_id'] ?>"><?= $p['package_name'] ?> ($<?= $p['price'] ?>)</option>
              <?php } ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Event Date</label>
            <input type="date" name="event_date" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="cancelled">Cancelled</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>

        <div class="mt-4">
          <label class="form-label fw-bold">Extra Services</label>
          <div class="row">
            <?php foreach ($allServices as $sid => $s): ?>
              <div class="col-md-6 mb-2">
                <div class="input-group">
                  <div class="input-group-text">
                    <input type="checkbox" name="services[]" value="<?= $sid ?>">
                  </div>
                  <span class="form-control"><?= $s['service_name'] ?> ($<?= $s['price'] ?>)</span>
                  <input type="number" name="quantities[<?= $sid ?>]" value="1" class="form-control" style="max-width: 80px;">
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mt-3">
          <button type="submit" name="add_booking" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Booking
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Bookings Table -->
  <div class="card shadow-sm border-0">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0">📋 Current Bookings</h5>
    </div>
    <div class="card-body table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th><th>User</th><th>Event</th><th>Package</th><th>Services</th>
            <th>Status</th><th>Total Cost</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $bookings->fetch_assoc()) { ?>
          <tr>
            <td><span class="badge bg-dark"><?= $row['booking_id'] ?></span></td>
            <td><?= $row['user_name'] ?></td>
            <td><?= $row['event_name'] ?></td>
            <td><?= $row['package_name'] ?: '<em>-</em>' ?></td>
            <td>
              <?php 
              if (!empty($booking_services[$row['booking_id']])) {
                  foreach ($booking_services[$row['booking_id']] as $sid => $qty) {
                      echo "<span class='badge bg-info text-dark me-1'>{$allServices[$sid]['service_name']} x{$qty}</span>";
                  }
              } else {
                  echo "<em>No extra services</em>";
              }
              ?>
            </td>
            <td>
              <span class="badge 
                <?= $row['status']=='pending'?'bg-warning text-dark':'' ?>
                <?= $row['status']=='confirmed'?'bg-primary':'' ?>
                <?= $row['status']=='cancelled'?'bg-danger':'' ?>
                <?= $row['status']=='completed'?'bg-success':'' ?>">
                <?= ucfirst($row['status']) ?>
              </span>
            </td>
            <td><strong>$<?= number_format($row['total_cost'], 2) ?></strong></td>
            <td>
              <button class="btn btn-sm btn-outline-warning" 
                      data-bs-toggle="modal" 
                      data-bs-target="#editModal<?= $row['booking_id'] ?>">
                <i class="bi bi-pencil-square"></i>
              </button>
              <a href="?delete=<?= $row['booking_id'] ?>" 
                 class="btn btn-sm btn-outline-danger" 
                 onclick="return confirm('Delete this booking?')">
                 <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $row['booking_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form method="post">
                  <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Booking #<?= $row['booking_id'] ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">

                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">User</label>
                        <select name="user_id" class="form-select" required>
                          <?php $users->data_seek(0); while ($u = $users->fetch_assoc()) { ?>
                            <option value="<?= $u['user_id'] ?>" <?= $u['user_id']==$row['user_id']?'selected':'' ?>>
                              <?= $u['name'] ?>
                            </option>
                          <?php } ?>
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Event</label>
                        <select name="event_id" class="form-select" required>
                          <?php $events->data_seek(0); while ($e = $events->fetch_assoc()) { ?>
                            <option value="<?= $e['event_id'] ?>" <?= $e['event_id']==$row['event_id']?'selected':'' ?>>
                              <?= $e['event_name'] ?> (<?= $e['event_date'] ?>)
                            </option>
                          <?php } ?>
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Package</label>
                        <select name="package_id" class="form-select">
                          <option value="">-- Optional Package --</option>
                          <?php $packages->data_seek(0); while ($p = $packages->fetch_assoc()) { ?>
                            <option value="<?= $p['package_id'] ?>" <?= $p['package_id']==$row['package_id']?'selected':'' ?>>
                              <?= $p['package_name'] ?> ($<?= $p['price'] ?>)
                            </option>
                          <?php } ?>
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Event Date</label>
                        <input type="date" name="event_date" value="<?= $row['event_date'] ?>" class="form-control" required>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                          <option value="pending"   <?= $row['status']=='pending'?'selected':'' ?>>Pending</option>
                          <option value="confirmed" <?= $row['status']=='confirmed'?'selected':'' ?>>Confirmed</option>
                          <option value="cancelled" <?= $row['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                          <option value="completed" <?= $row['status']=='completed'?'selected':'' ?>>Completed</option>
                        </select>
                      </div>
                    </div>

                    <div class="mt-4">
                      <label class="form-label fw-bold">Extra Services</label>
                      <div class="row">
                        <?php foreach ($allServices as $sid => $s): 
                          $checked = isset($booking_services[$row['booking_id']][$sid]);
                          $qty = $booking_services[$row['booking_id']][$sid] ?? 1;
                        ?>
                          <div class="col-md-6 mb-2">
                            <div class="input-group">
                              <div class="input-group-text">
                                <input type="checkbox" name="services[]" value="<?= $sid ?>" <?= $checked?'checked':'' ?>>
                              </div>
                              <span class="form-control"><?= $s['service_name'] ?> ($<?= $s['price'] ?>)</span>
                              <input type="number" name="quantities[<?= $sid ?>]" value="<?= $qty ?>" class="form-control" style="max-width: 80px;">
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                      <button type="submit" name="update_booking" class="btn btn-success">
                        <i class="bi bi-save"></i> Save Changes
                      </button>
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../footer.php';  ob_end_flush(); ?>
