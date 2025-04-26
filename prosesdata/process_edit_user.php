<?php
require_once '../functions.php';

// Periksa apakah data dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $username = trim($_POST['username']);
    $nama_pengguna = trim($_POST['nama_pengguna']);
    $hak_akses = trim($_POST['hak_akses']);

    // Validasi input
    if (empty($username) || empty($nama_pengguna) || empty($hak_akses)) {
        $_SESSION['error_message'] = "Semua field wajib diisi.";
        header("Location: ../admin/list_users.php"); // Redirect kembali ke halaman daftar user
        exit();
    }
    // Data yang akan diperbarui
    $data = [
        'name' => $nama_pengguna,
        'role' => $hak_akses,
    ];

    try {
        // Panggil fungsi updateUser untuk memperbarui data di database
        $result = $farma->updateUser($username, $data);

        if ($result['success']) {
            // Jika berhasil, set pesan sukses
            $_SESSION['success_message'] = $result['message'];
        } else {
            // Jika gagal, set pesan error
            $_SESSION['error_message'] = $result['message'];
        }
    } catch (Exception $e) {
        error_log("Error in process_edit_user: " . $e->getMessage());
        $_SESSION['error_message'] = "Terjadi kesalahan saat memperbarui pengguna.";
    }

    // Redirect kembali ke halaman daftar user
    header("Location: ../admin/list_users.php");
    exit();
} else {
    // Jika metode bukan POST, set pesan error
    $_SESSION['error_message'] = "Metode tidak valid.";
    header("Location: ../admin/list_users.php");
    exit();
}
