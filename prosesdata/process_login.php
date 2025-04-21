<?php
require_once '../../functions.php'; // Memastikan session_start() sudah berjalan di sini

// Periksa apakah metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data username dan password dari form
    $username = trim($_POST['userName']);
    $password = trim($_POST['password']);

    // Validasi input kosong
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username dan Password wajib diisi!";
        header("Location: signin.php");
        exit;
    }

    // Panggil fungsi loginUser untuk autentikasi
    $user = $farma->loginUser($username, $password);

    if ($user) {
        // Login berhasil, simpan data user ke dalam session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect berdasarkan role pengguna
        switch ($user['role']) {
            case 'admin':
                header("Location: admin_dashboard.php");
                break;
            /*case 'pharmacist':
                header("Location: pharmacist_dashboard.php");
                break;*/
            case 'cashier':
                header("Location: ../cashier/cashier1.php");
                break;
            default:
                header("Location: index.php");
        }
        exit;
    } else {
        // Login gagal, tampilkan error
        $_SESSION['error'] = "Username atau Password salah!";
        header("Location: signin.php");
        exit;
    }
} else {
    // Jika metode request bukan POST, redirect ke halaman login
    header("Location: signin.php");
    exit;
}
