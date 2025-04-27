<?php
require_once '../functions.php';

// Periksa apakah form telah disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_produk = $_POST['nama_produk'] ?? '';
    $barcode = $_POST['barcode'] ?? '';
    $kategori = $_POST['category_id'] ?? 0; // Gunakan ID kategori
    $harga_modal = $_POST['harga_modal'] ?? 0;
    $harga_jual = $_POST['harga_jual'] ?? 0;
    $expire = $_POST['expire'] ?? '';
    $kode_item = $_POST['kode_item'] ?? '';
    $posisi = $_POST['posisi'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $stok_barang = $_POST['stok_barang'] ?? 0;
    $stok_minimum = $_POST['stok_minimum'] ?? 0; // Pastikan stok minimum punya field sendiri

    // Validasi data
    if (empty($nama_produk) || empty($kategori) || empty($unit) || empty($stok_barang)) {
        die("Field yang wajib harus diisi!");
    }

    // Tambahkan barang ke database
    $hasil = $farma->tambahBarang(
        $nama_produk,
        $kode_item,
        $barcode,
        $kategori, // Harus berupa ID kategori
        $harga_modal,
        $harga_jual,
        $unit,
        $stok_barang,
        $stok_minimum
    );

    // Redirect dengan pesan berhasil/gagal
    if ($hasil) {
        header("Location: ../admin/list_barang.php?status=success");
    } else {
        header("Location: ../admin/list_barang.php?status=error");
    }
    exit();
} else {
    die("Akses tidak valid!");
}
