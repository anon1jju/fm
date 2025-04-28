<?php
require_once '../functions.php';


// Periksa apakah pengguna memiliki izin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

// Periksa apakah username tersedia di URL
if (!isset($_GET['username']) || empty(trim($_GET['username']))) {
    $_SESSION['error_message'] = "Username tidak ditemukan.";
    header("Location: ../admin/list_users.php");
    exit();
}

$username = trim($_GET['username']);

// Panggil metode deleteUser
$result = $farma->deleteUser($username);

// Redirect dengan pesan berdasarkan hasil
if ($result['success']) {
    $_SESSION['success_message'] = $result['message'];
} else {
    $_SESSION['error_message'] = $result['message'];
}
header("Location: ../admin/list_users.php");
exit();
?>
