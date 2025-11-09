<?php
session_start();
include 'db.php';
$base_url = "http://localhost/event/"; // change for live server
$timeout = 300; // 300 seconds = 5 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    // Last request was more than 5 minutes ago
    session_unset();     // Unset session variables
    session_destroy();   // Destroy session
    header("Location: " . $base_url . "login.php?message=Session expired, please login again");
exit();
 }
$_SESSION['last_activity'] = time(); // Update last activity time
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Management System</title>
<!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base_url; ?>assets/css/style1.css">
<style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        .navbar {
            padding: 1rem 2rem;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.7rem;
        }

        .nav-link {
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-link:hover {
            color: #ffc107 !important; /* hover color */
        }

        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #0d6efd;
        }

        .site-content {
            padding-top: 2rem;
            min-height: 80vh;
        }

        footer {
            background-color: #0d6efd;
            color: #fff;
            padding: 2rem 0;
            text-align: center;
        }

        footer a {
            color: #ffc107;
            text-decoration: none;
        }
    </style>
</head>
<body>
<header class="site-header mb-4">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm rounded">
        <div class="container">
            <a class="navbar-brand" href="<?= $base_url; ?>index.php">EventManager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                    aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>events.php">All Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>pakages.php">Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>contact.php">Contact</a></li>
<?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= $_SESSION['role'] === 'admin' ? 'Admin Panel' : 'Customer Panel' ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                <?php if($_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_users.php">Manage Users</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_events.php">Manage Events</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_services.php">Manage Services</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_packages.php">Manage Packages</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_bookings.php">Manage Bookings</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_payments.php">Manage Payments</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_feedback.php">Manage Feedback</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/manage_notifications.php">Manage Notifications</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>admin/reports.php">Reports</a></li>
                               <li><a class="dropdown-item" href="<?= $base_url; ?>admin/contact_messages.php">Contact messages</a></li>
                             
                                    <?php else: ?>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>customer/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>customer/view_events.php">Events</a></li>
                                      <li><a class="dropdown-item" href="<?= $base_url; ?>customer/my_feedback.php">My Feedback</a></li>
                                   
                                    <li><a class="dropdown-item" href="<?= $base_url; ?>customer/notifications.php">Notifications</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $base_url; ?>register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

 
    <!-- Your main content goes here -->
 

 
