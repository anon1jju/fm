<?php
/**
 * cashier.php - Halaman utama kasir
 * 
 * Halaman untuk operasi POS Apotek oleh kasir
 * 
 * @version 1.0.0
 * @date 2025-04-17
 */

// Include file fungsi cashier
require_once '../functions.php';

if (!$farma->checkPersistentSession()) {
    header("Location: ../signin.php");
    exit;
}
// Cek session untuk autentikasi (implementasi sesuai sistem login Anda)
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

//$pdo = $farma->getPDO(); // Mendapatkan koneksi database
$products = $farma->getAllProducts(); // Mengambil semua produk

// Dapatkan kategori produk
$categories = $farma->getAllCategories();

// Dapatkan metode pembayaran
$paymentMethods = $farma->getActivePaymentMethods();

// Dapatkan dokter (untuk resep)
$doctors = $farma->getDoctorsForDropdown();

// Default tampilkan produk populer
$popularProducts = $farma->getPopularProductsForCashier(12);

// Penanganan pencarian produk via AJAX akan dilakukan di file terpisah
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farma Medika</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-blue-600 text-white shadow-md">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-pills text-2xl mr-2"></i>
                    <h1 class="text-xl font-semibold">Farma Medika - <? echo $_SESSION["username"]?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="hidden md:inline-block">Kasir: <span id="cashier-name"><? echo $_SESSION["username"]?></span></span>
                    <a href="../logout.php" class="bg-blue-700 hover:bg-blue-800 px-3 py-1 rounded-md inline-flex items-center text-white">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        <span class="hidden md:inline-block">Logout</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-grow flex flex-col md:flex-row">
            <!-- Left Panel - Products -->
            <div class="w-full md:w-2/3 p-4">
                <!-- Categories -->
                <div class="mb-4 overflow-x-auto">
                    <div class="flex space-x-2 pb-2">
                        <button class="category-btn bg-blue-600 text-white px-4 py-2 rounded-md whitespace-nowrap" data-id="0">
                            Semua
                        </button>
                        <?php foreach ($categories as $category): ?>
                            <button class="category-btn bg-white hover:bg-gray-100 px-4 py-2 rounded-md shadow whitespace-nowrap" data-id="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Search -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" id="product-search" placeholder="Cari obat atau scan barcode..." 
                               class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="absolute right-3 top-3">
                            <button id="clear-search" class="text-gray-400 hover:text-gray-600 mr-2" style="display: none;">
                                <i class="fas fa-times fa-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="products-container" class="max-h-[70vh] overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($popularProducts as $product): ?>
                        <div class="product-card bg-white rounded-lg shadow-md p-3 cursor-pointer hover:shadow-lg transition" 
                             data-product='<?php echo json_encode([
                                 "product_id" => $product['product_id'],
                                 "product_name" => $product['product_name'],
                                 "kode_item" => $product['kode_item'],
                                 "posisi" => $product['posisi'],
                                 "price" => $product['price'],
                                 "unit" => $product['unit'],
                                 "stock_quantity" => $product['stock_quantity'],
                                 "minimum_stock" => $product['minimum_stock'],
                                 "requires_prescription" => $product['requires_prescription']
                             ]); ?>'>
                            <div class="h-24 bg-gray-200 rounded-md mb-2 flex items-center justify-center relative">
                                <i class="fas fa-pills text-gray-400 text-3xl"></i>
                                <?php if ($product['requires_prescription']): ?>
                                    <span class="absolute top-0 right-0 bg-yellow-500 text-white text-xs px-1 rounded">Resep</span>
                                <?php endif; ?>
                            </div>
                            <h3 class="font-medium"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['kode_item']); ?></p>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-blue-600 font-bold">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                <span class="text-xs text-gray-500">Stok: <?php echo $product['stock_quantity']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Panel - Cart -->
            <div class="w-full md:w-1/3 bg-white shadow-md p-4 flex flex-col">
                <!-- Customer Info -->
                <div class="mb-4">
                    <h2 class="text-lg font-semibold mb-2">Pelanggan</h2>
                    <div class="flex space-x-2">
                        <input type="text" id="customer-name" placeholder="Nama Pelanggan" 
                               class="flex-grow px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Prescription Info (hidden by default) -->
                <div id="prescription-info" class="mb-4 hidden">
                    <h2 class="text-lg font-semibold mb-2">Informasi Resep</h2>
                    <div class="space-y-2">
                        <select id="doctor-id" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Dokter --</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>">
                                    <?php echo htmlspecialchars($doctor['doctor_name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="prescription-number" placeholder="Nomor Resep" 
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-grow overflow-y-auto mb-4">
                    <h2 class="text-lg font-semibold mb-2">Pesanan</h2>
                    
                    <div id="cart-items" class="space-y-2">
                        <!-- Cart items will be added here dynamically -->
                        <div class="text-center text-gray-500 py-4">
                            <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                            <p>Keranjang kosong</p>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="border-t pt-3 mb-4">
                    <div class="flex justify-between mb-1">
                        <span>Subtotal</span>
                        <span id="subtotal">Rp 0</span>
                    </div>
                    <div class="flex justify-between mb-1">
                        <span>Pajak (0%)</span>
                        <span id="tax-amount">Rp 0</span>
                    </div>
                    <div class="flex justify-between mb-1">
                        <span>Diskon</span>
                        <div class="flex items-center">
                            <span>Rp&nbsp;</span>
                            <input type="tel" id="discount-amount" placeholder="0" min="0" 
                                   class="w-20 px-2 py-1 border rounded-md text-right mr-1">
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-2xl mb-3 border-t pt-2">
                        <span>Total</span>
                        <span id="grand-total">Rp 0</span>
                    </div>
                </div>
                
                <!-- Payment Section -->
                <div>
                    <div class="mb-2">
                        <h3 class="text-md font-medium">Metode Pembayaran</h3>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="grid grid-cols-3 gap-3 mb-3">
                        <?php foreach ($paymentMethods as $method): ?>
                            <button class="payment-method-btn bg-blue-100 text-blue-600 py-2 rounded-md hover:bg-blue-200" 
                                    data-id="<?php echo $method['payment_method_id']; ?>">
                                <?php if ($method['method_name'] == 'Tunai'): ?>
                                    <i class="fas fa-money-bill mr-1"></i>
                                <?php elseif ($method['method_name'] == 'Kredit / Debit'): ?>
                                    <i class="fas fa-credit-card mr-1"></i>
                                <?php elseif ($method['method_name'] == 'QRIS'): ?>
                                    <i class="fas fa-qrcode mr-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-money-check mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($method['method_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Cash Payment Input (only shown for cash payments) -->
                    <div id="cash-payment-fields" class="mb-3 hidden">
                        <div class="mb-2">
                            <label class="block text-sm font-medium mb-1">Jumlah Dibayar (Rp)</label>
                            <div class="flex items-center border rounded-md overflow-hidden focus-within:ring-2 focus-within:ring-blue-500">
                                <span class="px-3 py-2 bg-gray-100 text-gray-500">Rp</span>
                                <input type="text" id="cash-amount" class="w-full px-3 py-2 border-0 focus:outline-none" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="block text-sm font-medium mb-1">Kembalian</label>
                            <p class="text-2xl font-bold text-green-600" id="change-amount">Rp 0</p>
                        </div>
                    </div>
                    
                    <button id="process-payment-btn" class="bg-green-600 hover:bg-green-700 text-white w-full py-3 rounded-md font-medium disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                        <i class="fas fa-check-circle mr-1"></i> Proses Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg w-full max-w-md mx-4">
            <div class="p-4 text-center">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Pembayaran Berhasil</h3>
                <p class="text-gray-600 mb-4">Transaksi telah berhasil disimpan</p>
                <p class="font-medium mb-4">No. Invoice: <span id="success-invoice"></span></p>
            </div>
            <div class="p-4 border-t flex justify-center space-x-4">
                <button id="success-print" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fas fa-print mr-1"></i> Cetak Struk
                </button>
                <button id="success-new" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fas fa-plus mr-1"></i> Transaksi Baru
                </button>
            </div>
        </div>
    </div>
    <script src="js/cashier.js"></script>
</body>
</html>
