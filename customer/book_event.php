<?php ob_start();
include __DIR__ . '/../header.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}  

$event_id = intval($_GET['event_id']);
$user_id  = $_SESSION['user_id'];

// Fetch event, packages, services
$event    = $conn->query("SELECT * FROM events WHERE event_id=$event_id")->fetch_assoc();
$packages = $conn->query("SELECT * FROM packages");
$services = $conn->query("SELECT * FROM services");

// Get already booked dates for this event
$booked_dates = [];
$res = $conn->query("SELECT event_date FROM bookings WHERE event_id=$event_id AND status IN ('pending','confirmed')");
while($row = $res->fetch_assoc()){
    $booked_dates[] = $row['event_date'];
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $package_id = $_POST['package_id'] ?: NULL;
    $event_date = $_POST['event_date'];
    $selected_services = $_POST['services'] ?? [];
    $services_qty = $_POST['services_qty'] ?? [];
    $total_cost = 0;

    // Validate date
    if (strtotime($event_date) < strtotime(date("Y-m-d")) || in_array($event_date, $booked_dates)) {
        echo "<script>alert('Invalid date selected. Please choose another.');window.history.back();</script>";
        exit;
    }

    if($package_id){
        $stmt = $conn->prepare("SELECT price FROM packages WHERE package_id=?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $package = $stmt->get_result()->fetch_assoc();
        $total_cost += $package['price'];
    }

    foreach($selected_services as $sid){
        $qty = isset($services_qty[$sid]) ? intval($services_qty[$sid]) : 1;
        $stmt = $conn->prepare("SELECT price FROM services WHERE service_id=?");
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $price = $stmt->get_result()->fetch_assoc()['price'];
        $total_cost += $price * $qty;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, event_id, package_id, event_date, total_cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisd", $user_id, $event_id, $package_id, $event_date, $total_cost);
        $stmt->execute();
        $booking_id = $conn->insert_id;

        if($selected_services){
            $stmt2 = $conn->prepare("INSERT INTO booking_services (booking_id, service_id, quantity) VALUES (?, ?, ?)");
            foreach($selected_services as $sid){
                $qty = isset($services_qty[$sid]) ? intval($services_qty[$sid]) : 1;
                $stmt2->bind_param("iii", $booking_id, $sid, $qty);
                $stmt2->execute();
            }
        }

        // $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, booking_id, message) VALUES (?, ?, ?)");
        // $message = "Your booking for {$event['event_name']} on {$event_date} is successful!";
        // $stmt3->bind_param("iis", $user_id, $booking_id, $message);
        // $stmt3->execute();

        $conn->commit();
        header("Location: payment.php?booking_id=$booking_id");
        exit;
    } catch(Exception $e){
        $conn->rollback();
        echo "<script>alert('Booking failed: {$e->getMessage()}');window.history.back();</script>";
        exit;
    }
}
?>

<script>
function updateTotal(){
    let total = 0;
    const packageSelect = document.getElementById('package_id');
    const packagePrice = packageSelect.options[packageSelect.selectedIndex]?.dataset.price || 0;
    total += parseFloat(packagePrice);

    document.querySelectorAll('.service-check').forEach(s => {
        if(s.checked){
            const price = parseFloat(s.dataset.price);
            const qty = parseInt(document.getElementById('qty_'+s.value).value) || 1;
            total += price * qty;
        }
    });
    document.getElementById('total_cost').innerText = total.toFixed(2);
}

document.addEventListener("DOMContentLoaded", () => {
    updateTotal();

    // Disable already booked dates
    const bookedDates = <?= json_encode($booked_dates); ?>;
    const dateInput = document.getElementById("event_date");
    dateInput.setAttribute("min", new Date().toISOString().split("T")[0]);

    dateInput.addEventListener("input", () => {
        if(bookedDates.includes(dateInput.value)){
            alert("This date is already booked. Please select another date.");
            dateInput.value = "";
        }
    });
});
</script>

<div class="container my-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-gradient text-white p-3 rounded-top-4" style="background: linear-gradient(135deg,#007bff,#0056b3);">
            <h2 class="mb-0 fw-bold"><i class="bi bi-calendar-check"></i> Book Event: <?= htmlspecialchars($event['event_name']); ?></h2>
        </div>
        <div class="card-body p-4">
            <form method="POST" class="needs-validation" novalidate>
                
                <!-- Event Date -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Event Date</label>
                    <input type="date" id="event_date" name="event_date" class="form-control" required>
                    <small class="text-muted">Unavailable dates will be restricted automatically.</small>
                </div>

                <!-- Package -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Choose Package (Optional)</label>
                    <select name="package_id" id="package_id" class="form-select" onchange="updateTotal()">
                        <option value="" data-price="0">-- Select Package --</option>
                        <?php while($p = $packages->fetch_assoc()): ?>
                            <option value="<?= $p['package_id']; ?>" data-price="<?= $p['price']; ?>">
                                <?= htmlspecialchars($p['package_name']); ?> ($<?= $p['price']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Services -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Select Additional Services</label>
                    <div class="row">
                        <?php while($s = $services->fetch_assoc()): ?>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center border rounded p-2 bg-light">
                                    <div class="form-check me-3">
                                        <input class="form-check-input service-check" type="checkbox" name="services[]" value="<?= $s['service_id']; ?>" data-price="<?= $s['price']; ?>" onchange="updateTotal()">
                                        <label class="form-check-label"><?= htmlspecialchars($s['service_name']); ?> ($<?= $s['price']; ?>)</label>
                                    </div>
                                    <input type="number" id="qty_<?= $s['service_id']; ?>" name="services_qty[<?= $s['service_id']; ?>]" value="1" min="1" class="form-control form-control-sm ms-auto" style="width:70px;" onchange="updateTotal()">
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Total Cost -->
                <div class="mb-4 text-end">
                    <h5 class="fw-bold">Total Cost: 
                        <span class="badge bg-success fs-6">$<span id="total_cost">0.00</span></span>
                    </h5>
                </div>

                <!-- Buttons -->
                <div class="d-flex justify-content-end">
                    <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button class="btn btn-primary px-4">
                        <i class="bi bi-check-circle"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ob_end_flush();?>
