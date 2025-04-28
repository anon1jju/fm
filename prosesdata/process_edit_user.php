<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original_username = trim($_POST['original_username']); // Username lama
    $username = trim($_POST['username']); // Username baru
    $password = trim($_POST['password']);
    $nama_pengguna = trim($_POST['nama_pengguna']);
    $hak_akses = trim($_POST['hak_akses']);

    // Debugging
    error_log("Data diterima: Original Username={$original_username}, Username Baru={$username}, Nama Pengguna={$nama_pengguna}, Hak Akses={$hak_akses}");

    // Validasi input wajib
    if (empty($original_username) || empty($username) || empty($nama_pengguna) || empty($hak_akses)) {
        $_SESSION['error_message'] = "Semua field wajib diisi.";
        header("Location: ../admin/list_users.php");
        exit();
    }

    // Periksa apakah admin ID tersedia di session
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Akses tidak valid.";
        header("Location: ../admin/list_users.php");
        exit();
    }
    $adminId = $_SESSION['user_id']; // ID admin yang memperbarui data

    // Array data untuk pembaruan
    $data = [
        'username' => $username, // Username baru
        'name' => $nama_pengguna,
        'role' => $hak_akses,
    ];

    // Tambahkan password jika diisi
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Hash password baru
        $data['password'] = $hashedPassword;
    }

    try {
        // Panggil fungsi updateUser dengan ID admin
        $result = $farma->updateUser($original_username, $data, $adminId); // Gunakan username lama
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
    } catch (Exception $e) {
        error_log("Error in process_edit_user: " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui pengguna.";
    }

    header("Location: ../admin/list_users.php");
    exit();
}
