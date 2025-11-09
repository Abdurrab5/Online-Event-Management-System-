<?php ob_start(); include __DIR__ . '/../header.php'; ?>
<?php
 
if (!isset($_SESSION['role']) && ($_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php");
    exit;
}
 
  

$id = intval($_GET['id']);
$conn->query("DELETE FROM users WHERE user_id=$id");

header("Location: manage_users.php");
exit;  ob_end_flush();
?>
