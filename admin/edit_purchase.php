<?php
require_once '../functions.php';

// Cek role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

$purchase_id = null;
$purchase_info = null;
$payments_history = [];
$message = '';
$error = '';

if (isset($_GET['purchase_id'])) {
    $purchase_id = filter_var($_GET['purchase_id'], FILTER_VALIDATE_INT);
    if ($purchase_id) {
        $purchase_info = $farma->getPurchaseWithPaymentSummary($purchase_id);
        if ($purchase_info) {
            $payments_history = $farma->getPurchasePayments($purchase_id);
        } else {
            $error = "Data pembelian dengan ID #{$purchase_id} tidak ditemukan.";
        }
    } else {
        $error = "ID pembelian tidak valid.";
    }
} else {
    $error = "ID pembelian tidak disertakan.";
    // Jika tidak ada ID, mungkin redirect ke halaman daftar pembelian
    // header("Location: list_purchases.php");
    // exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_payment']) && $purchase_id && $purchase_info) {
    $payment_data = [
        'payment_date' => $_POST['payment_date'] ?? null,
        'amount_paid' => $_POST['amount_paid'] ?? 0,
        'payment_method' => $_POST['payment_method'] ?? null,
        'reference' => $_POST['reference'] ?? null,
        'proof_document_path' => $_POST['proof_document_path'] ?? null, // Path manual dulu
    ];

    // Handle file upload jika ada (contoh sederhana, perlu pengembangan lebih lanjut)
    // Untuk saat ini, 'proof_document_path' diisi manual.
    // Jika ingin upload:
    if (isset($_FILES['proof_document']) && $_FILES['proof_document']['error'] == 0) {
        $target_dir = "../uploads/purchase_proofs/";
         if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
         $file_extension = strtolower(pathinfo($_FILES["proof_document"]["name"], PATHINFO_EXTENSION));
         $safe_filename = "proof_" . $purchase_id . "_" . time() . "." . $file_extension;
         $target_file = $target_dir . $safe_filename;
         // Validasi file type, size, dll.
         if (move_uploaded_file($_FILES["proof_document"]["tmp_name"], $target_file)) {
             $payment_data['proof_document_path'] = $target_file;
         } else {
             $error = "Gagal mengunggah bukti pembayaran.";
         }
    }


    if (empty($error)) { // Hanya proses jika tidak ada error dari upload (jika diimplementasikan)
        $result = $farma->addPurchasePayment($purchase_id, $payment_data, $_SESSION['user_id'] ?? null);
        if ($result['success']) {
            $message = $result['message'];
            // Refresh data setelah pembayaran berhasil
            $purchase_info = $farma->getPurchaseWithPaymentSummary($purchase_id);
            $payments_history = $farma->getPurchasePayments($purchase_id);
            // Kosongkan form
            $_POST = [];
        } else {
            $error = $result['message'];
        }
    }
}

$sisa_tagihan = 0;
if ($purchase_info) {
    $sisa_tagihan = (float)$purchase_info['total_amount'] - (float)$purchase_info['total_amount_paid'];
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">
<head>
    <?php include "includes/meta.php";?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include "includes/switch.php";?>
    <div id="loader"><img src="../assets/images/media/loader.svg" alt=""></div>
    <div class="page">
        <?php include "includes/header.php";?>
        <?php include "includes/sidebar.php";?>
        
        <div class="main-content app-content">
            <div class="container-fluid">
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header justify-between">
                                <div class="box-title">
                                    Manajemen Pembayaran Pembelian <?php echo $purchase_info ? '#'.htmlspecialchars($purchase_info['invoice_number']) : ''; ?>
                                </div>
                                <a href="pembelian_supplier.php" class="ti-btn ti-btn-sm ti-btn-light">Kembali ke Daftar Pembelian</a>
                            </div>
                            <div class="box-body p-6">
                                <?php if ($message): ?>
                                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                                    <strong class="font-bold">Berhasil!</strong> <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                    <strong class="font-bold">Error!</strong> <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($purchase_info): ?>
                                    <div class="mb-8 p-4 border border-gray-200 dark:border-gray-700 rounded-md">
                                        <h3 class="text-lg font-semibold mb-3">Ringkasan Pembelian</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div><strong>Supplier:</strong> <?php echo htmlspecialchars($purchase_info['supplier_name']); ?></div>
                                            <div><strong>Tgl. Pembelian:</strong> <?php echo htmlspecialchars(date('d M Y', strtotime($purchase_info['purchase_date']))); ?></div>
                                            <div><strong>Status Bayar:</strong> 
                                                <span class="capitalize badge <?php 
                                                    switch ($purchase_info['payment_status']) {
                                                        case 'lunas': echo 'bg-green-500 text-white'; break;
                                                        case 'cicil': echo 'bg-yellow-500 text-black'; break;
                                                        case 'hutang': echo 'bg-red-500 text-white'; break;
                                                        default: echo 'bg-gray-500 text-white'; break;
                                                    }
                                                ?>">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', $purchase_info['payment_status'])); ?>
                                                </span>
                                            </div>
                                            <div><strong>Total Tagihan:</strong> Rp <?php echo htmlspecialchars(number_format($purchase_info['total_amount'], 0, ',', '.')); ?></div>
                                            <div><strong>Sudah Dibayar:</strong> Rp <?php echo htmlspecialchars(number_format($purchase_info['total_amount_paid'], 0, ',', '.')); ?></div>
                                            <div class="<?php echo $sisa_tagihan > 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold';?>">
                                                <strong>Sisa Tagihan:</strong> Rp <?php echo htmlspecialchars(number_format($sisa_tagihan, 0, ',', '.')); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($sisa_tagihan > 0 || $purchase_info['payment_status'] !== 'lunas'): ?>
                                    <form method="POST" action="edit_purchase.php?purchase_id=<?php echo $purchase_id; ?>" enctype="multipart/form-data" class="mb-10">
                                        <input type="hidden" name="add_payment" value="1">
                                        <h3 class="text-lg font-semibold mb-3">Tambah Pembayaran Baru</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <div>
                                                <label for="payment_date" class="form-label">Tanggal Bayar <span class="text-red-500">*</span></label>
                                                <input type="text" class="ti-form-input flatpickr-date" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                            <div>
                                                <label for="amount_paid" class="form-label">Jumlah Bayar <span class="text-red-500">*</span></label>
                                                <input type="tel" step="0.01" class="ti-form-input" id="amount_paid" name="amount_paid" min="0.01" max="<?php echo $sisa_tagihan > 0 ? $sisa_tagihan : ''; ?>" placeholder="Maks: <?php echo number_format($sisa_tagihan,0,',','.'); ?>" required>
                                            </div>
                                            <div>
                                                <label for="payment_method" class="form-label">Metode Bayar <span class="text-red-500">*</span></label>
                                                <input type="text" class="ti-form-input" id="payment_method" name="payment_method" value="<?php echo isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method']) : ''; ?>" placeholder="e.g. Transfer Bank, Tunai" required>
                                            </div>
                                            <div class="md:col-span-1 lg:col-span-1">
                                                <label for="reference" class="form-label">No. Referensi</label>
                                                <input type="text" class="ti-form-input" id="reference" name="reference" value="<?php echo isset($_POST['reference']) ? htmlspecialchars($_POST['reference']) : ''; ?>" placeholder="e.g. No. Transaksi Bank">
                                            </div>
                                            <div class="md:col-span-2 lg:col-span-2">
                                                <label for="proof_document_path" class="form-label">Path Bukti Bayar (Manual)</label>
                                                <input type="text" class="ti-form-input" id="proof_document_path" name="proof_document_path" value="<?php echo isset($_POST['proof_document_path']) ? htmlspecialchars($_POST['proof_document_path']) : ''; ?>" placeholder="Path/URL ke file bukti">
                                                <label for="proof_document" class="form-label">Unggah Bukti Bayar (Opsional)</label>
                                                <input type="file" class="ti-form-input" id="proof_document" name="proof_document">
                                                <p class="text-xs text-gray-500 mt-1">File: JPG, PNG, PDF. Maks 2MB.</p>
                                            </div>
                                        </div>
                                        <div class="mt-6">
                                            <button type="submit" class="ti-btn ti-btn-primary">Simpan Pembayaran</button>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-10" role="alert">
                                            <strong class="font-bold">Informasi:</strong> Pembelian ini sudah lunas.
                                        </div>
                                    <?php endif; ?>

                                    <h3 class="text-lg font-semibold mb-3">Histori Pembayaran</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tgl. Bayar</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metode</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Referensi</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bukti</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                <?php if (!empty($payments_history)): ?>
                                                    <?php foreach ($payments_history as $payment): ?>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars(date('d M Y', strtotime($payment['payment_date']))); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">Rp <?php echo htmlspecialchars(number_format($payment['amount_paid'], 0, ',', '.')); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($payment['reference'] ?? '-'); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                                <?php if (!empty($payment['proof_document_path'])): ?>
                                                                    <a href="<?php echo htmlspecialchars($payment['proof_document_path']); ?>" target="_blank" class="text-blue-600 hover:underline">Lihat Bukti</a>
                                                                <?php else: echo '-'; endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center px-6 py-10 text-sm text-gray-500">Belum ada histori pembayaran.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif(!$error) : // Jika purchase_id ada tapi $purchase_info null (setelah pengecekan error awal) ?>
                                     <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Peringatan!</strong> Tidak dapat memuat detail pembelian. Pastikan ID pembelian benar.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "includes/footer.php";?>
    </div>
    <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
    <script src="../assets/js/switch.js"></script>
    <script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>
    <script src="../assets/libs/preline/preline.js"></script>
    <script src="../assets/js/defaultmenu.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/js/sticky.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/js/simplebar.js"></script>
    <script src="../assets/js/custom.js"></script>
    <script src="../assets/js/custom-switcher.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        });
    </script>
</body>
</html>
