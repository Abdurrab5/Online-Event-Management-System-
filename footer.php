<footer class="site-footer bg-primary text-white mt-5 shadow-lg">
  <div class="container py-5">
    <div class="row align-items-center">
      
      <!-- Brand / Logo -->
      <div class="col-md-4 text-center text-md-start mb-4 mb-md-0">
        <h3 class="fw-bold mb-1">EventManager</h3>
        <p class="small mb-0 opacity-75">Streamlining your events, one booking at a time.</p>
      </div>

      <!-- Navigation Links -->
      <div class="col-md-4 text-center mb-4 mb-md-0">
        <ul class="list-unstyled d-flex justify-content-center gap-4 mb-0">
          <li><a href="<?= $base_url; ?>index.php" class="text-white text-decoration-none fw-semibold footer-link">Home</a></li>
          <li><a href="<?= $base_url; ?>about.php" class="text-white text-decoration-none fw-semibold footer-link">About</a></li>
          <li><a href="<?= $base_url; ?>contact.php" class="text-white text-decoration-none fw-semibold footer-link">Contact</a></li>
        </ul>
      </div>

      <!-- Social Media -->
      <div class="col-md-4 text-center text-md-end">
        <div class="d-flex justify-content-center justify-content-md-end gap-3 mb-2">
          <a href="#" class="text-white fs-5"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-white fs-5"><i class="bi bi-twitter"></i></a>
          <a href="#" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
          <a href="#" class="text-white fs-5"><i class="bi bi-linkedin"></i></a>
        </div>
        <small class="d-block">&copy; <?= date("Y"); ?> Event Management System</small>
        <small class="opacity-75">All Rights Reserved.</small>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
  .footer-link:hover {
    text-decoration: underline;
    opacity: 0.85;
  }
</style>
