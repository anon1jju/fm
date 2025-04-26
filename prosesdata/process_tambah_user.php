<?php
require_once '../functions.php'; // Memastikan session_start() sudah berjalan di sini

// Periksa apakah metode permintaan adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form
    $nama_pengguna = isset($_POST['nama_pengguna']) ? trim($_POST['nama_pengguna']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $hak_akses = isset($_POST['hak_akses']) ? trim($_POST['hak_akses']) : '';

    // Validasi data
    if (empty($nama_pengguna) || empty($username) || empty($password) || empty($hak_akses)) {
        $_SESSION['error_message'] = "Semua field wajib diisi!";
        header("Location: ../admin/list_users.php");
        exit();
    }
    
    // Periksa apakah username sudah ada
    try {
        $pdo = $farma->getPDO();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            $_SESSION['error_message'] = "Username sudah digunakan!";
            header("Location: ../admin/list_users.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database Error (check username): " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat memeriksa username.";
        header("Location: ../admin/list_users.php");
        exit();
    }

    // Persiapkan data pengguna untuk didaftarkan
    $userData = [
        'name' => $nama_pengguna,
        'username' => $username,
        'password' => $password,
        'role' => $hak_akses,
    ];

    // Panggil fungsi registerUser
    $result = $farma->registerUser($userData);

    if ($result['success']) {
        // Set pesan sukses
        $_SESSION['success_message'] = "Pengguna berhasil ditambahkan!";
        header("Location: ../admin/list_users.php");
        exit();
    } else {
        // Set pesan error
        $_SESSION['error_message'] = $result['message'];
        header("Location: ../admin/list_users.php");
        exit();
    }
} else {
    // Jika metode bukan POST, kembalikan ke halaman form
    $_SESSION['error_message'] = "Metode tidak valid.";
    header("Location: ../admin/list_users.php");
    exit();
}
