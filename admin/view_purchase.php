<?php
require_once '../functions.php';

// Cek apakah user memiliki role admin
if (!$farma->checkPersistentSession() || !isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php"); 
    exit();
}

$purchase_details = null;
$error_message = '';

if (isset($_GET['purchase_id'])) {
    $purchase_id = filter_var($_GET['purchase_id'], FILTER_VALIDATE_INT);
    if ($purchase_id) {
        $purchase_details = $farma->getPurchaseDetails($purchase_id);
        if (!$purchase_details) {
            $error_message = "Data pembelian dengan ID tersebut tidak ditemukan.";
        }
    } else {
        $error_message = "ID pembelian tidak valid.";
    }
} else {
    $error_message = "ID pembelian tidak disertakan.";
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">

<head>
    <?php include "includes/meta.php";?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .page {
                 padding: 0 !important;
                 margin: 0 !important;
            }
             .main-content {
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>
    <?php include "includes/switch.php";?>
    <div id="loader"><img src="../assets/images/media/loader.svg" alt=""></div>

    <div class="page">
        <?php include "includes/header.php";?>
        <?php include "includes/sidebar.php";?>
        
        <div class="main-content app-content">
            <div class="container-fluid">
                <?php if ($error_message): ?>
                    <div class="grid grid-cols-12 gap-x-6">
                        <div class="xl:col-span-12 col-span-12">
                            <div class="box">
                                <div class="box-body">
                                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                                        <strong class="font-bold">Error!</strong>
                                        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                                        <div class="mt-4">
                                            <a href="hutang_supplier.php" class="ti-btn ti-btn-primary">Kembali ke Daftar Pembelian</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($purchase_details && isset($purchase_details['header'])): 
                    $header = $purchase_details['header'];
                    $items = $purchase_details['items'];
                ?>
                    <div class="grid grid-cols-12 gap-x-6">
                        <div class="xl:col-span-12 col-span-12">
                            <div class="box print-area">
                                <div class="box-header justify-between no-print">
                                    <div class="box-title">
                                        Detail Pembelian #<?php echo htmlspecialchars($header['invoice_number'] ?? $header['purchase_id']); ?>
                                    </div>
                                    <div>
                                        <button onclick="window.print()" class="ti-btn ti-btn-sm ti-btn-primary me-2"><i class="ri-printer-line me-1"></i>Cetak</button>
                                        <a href="hutang_supplier.php" class="ti-btn ti-btn-sm ti-btn-light">Kembali ke Daftar</a>
                                    </div>
                                </div>
                                <div class="box-body p-6">
                                    <div class="text-center mb-8">
                                        <h2 class="text-2xl font-semibold">Detail Pembelian</h2>
                                        <p class="text-gray-700 text-lg">No. Invoice Supplier: <?php echo htmlspecialchars($header['invoice_number']); ?></p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <h4 class="font-semibold text-gray-700 dark:text-white">Supplier:</h4>
                                            <p><?php echo htmlspecialchars($header['supplier_name']); ?></p>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 dark:text-white">Tanggal Pembelian:</h4>
                                            <p><?php echo htmlspecialchars(date('d M Y', strtotime($header['purchase_date']))); ?></p>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 dark:text-white">Tanggal Jatuh Tempo:</h4>
                                            <p><?php echo $header['due_date'] ? htmlspecialchars(date('d M Y', strtotime($header['due_date']))) : '-'; ?></p>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 dark:text-white">Status Pembayaran:</h4>
                                            <span class="capitalize badge <?php 
                                                switch ($header['payment_status']) {
                                                    case 'lunas': echo 'bg-green-500 text-white'; break;
                                                    case 'cicil': echo 'bg-yellow-500 text-black'; break;
                                                    case 'hutang': echo 'bg-red-500 text-white'; break;
                                                    default: echo 'bg-gray-500 text-white'; break;
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $header['payment_status'])); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 dark:text-white">Status Penerimaan:</h4>
                                             <span class="capitalize badge <?php 
                                                switch ($header['received_status']) {
                                                    case 'diterima': echo 'bg-green-500 text-white'; break;
                                                    case 'diterima_sebagian': echo 'bg-yellow-500 text-black'; break;
                                                    case 'belum_diterima': echo 'bg-blue-500 text-white'; break;
                                                    default: echo 'bg-gray-500 text-white'; break;
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $header['received_status'])); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-700 dark:text-white">Otorisasi oleh:</h4>
                                            <p><?php echo htmlspecialchars($header['user_name']); ?></p>
                                        </div>
                                    </div>

                                    <?php if (!empty($header['notes'])): ?>
                                    <div class="mb-6">
                                        <h4 class="font-semibold text-gray-700 dark:text-white">Catatan:</h4>
                                        <p class="whitespace-pre-wrap"><?php echo htmlspecialchars($header['notes']); ?></p>
                                    </div>
                                    <?php endif; ?>

                                    <h3 class="text-xl font-semibold mb-4 mt-8">Item yang Dibeli</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-300 dark:bg-gray-800">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No.</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produk (Kode)</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Batch</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kadaluarsa</th>
                                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kuantitas</th>
                                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga Satuan</th>
                                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                                <?php if (!empty($items)): ?>
                                                    <?php foreach ($items as $index => $item): ?>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo $index + 1; ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                                                <span class="block text-xs text-gray-500">(<?php echo htmlspecialchars($item['kode_item']); ?>)</span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($item['batch_number'] ?? '-'); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo $item['expiry_date'] ? htmlspecialchars(date('d M Y', strtotime($item['expiry_date']))) : '-'; ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right"><?php echo htmlspecialchars($item['quantity']); ?> <?php echo htmlspecialchars($item['unit'] ?? '');?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">Rp <?php echo htmlspecialchars(number_format($item['unit_price'], 2, ',', '.')); ?></td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white text-right">Rp <?php echo htmlspecialchars(number_format($item['item_total'], 2, ',', '.')); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center px-6 py-10 text-sm text-gray-500">Tidak ada item dalam pembelian ini.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <tfoot class="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <td colspan="6" class="px-6 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-200">TOTAL PEMBELIAN</td>
                                                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">Rp <?php echo htmlspecialchars(number_format($header['total_amount'], 2, ',', '.')); ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="mt-8 text-center no-print">
                                         <button onclick="window.print()" class="ti-btn ti-btn-primary me-2"><i class="ri-printer-line me-1"></i>Cetak</button>
                                        <a href="hutang_supplier.php" class="ti-btn ti-btn-light">Kembali ke Daftar Pembelian</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php include "includes/footer.php";?>
    </div>

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
</body>
</html>
