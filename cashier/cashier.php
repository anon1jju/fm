<?php
   /**
    * cashier.php - Halaman utama kasir
    * 
    * Halaman untuk operasi POS Apotek oleh kasir
    * 
    * @version 1.0.6
    * @date 2025-05-16
    */
   
   // Include file fungsi cashier
   require_once '../functions.php'; // Pastikan path ini benar
   
   if (!$farma->checkPersistentSession()) {
       header("Location: ../signin.php");
       exit;
   }
   
   $products = $farma->getAllProducts(); 
   $paymentMethods = $farma->getActivePaymentMethods(); // Digunakan untuk render tombol metode pembayaran
   
   
   ?>
<!DOCTYPE html>
<html lang="id">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Farma Medika</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <!-- SweetAlert2 CSS (digunakan oleh cashier.js) -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
      <?php include "../admin/includes/meta.php"; ?>
      <style>
         #search-input-wrapper { /* Parent dari input dan dropdown */
         position: relative; /* Penting agar child dengan position:absolute relatif terhadap ini */
         }
         #search-results-container { 
         position: absolute;
         width: 100%; /* Mengikuti lebar parent/input */
         left: 0;
         top: 100%; /* Tepat di bawah elemen di atasnya (input) */
         z-index: 100; /* Pastikan di atas elemen lain */
         background-color: white;
         border: 1px solid #e5e7eb;
         border-top: none; 
         border-radius: 0 0 0.375rem 0.375rem;
         box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
         max-height: 300px; 
         overflow-y: auto; /* Scroll jika item banyak */
         }
         #search-results-container.hidden {
         display: none;
         }
      </style>
   </head>
   <body class="bg-gray-100">
      <!-- Data Inisial dari PHP untuk JavaScript (jika dibutuhkan oleh cashier.js) -->
      <div class="container mx-auto p-4 max-w-6xl">
         <!-- Desain Container Asli Anda -->
         <!-- Header -->
         <div class="flex justify-between items-center mb-4">
            <div class="font-bold text-xl text-gray-800">Farma Medika</div>
            <div class="text-sm text-gray-600">
               <div>Kasir: <? echo $_SESSION["username"]?></div>
               <div>Tanggal: <?php echo date('Y-m-d H:i:s'); ?></div>
            </div>
         </div>
         <!-- Main Layout -->
         <div class="space-y-4">
            <!-- Search Bar - Top (Fixed) -->
            <div class="fixed top-0 left-0 right-0 z-20 bg-gray-100 p-4 shadow">
               <!-- z-index dinaikkan -->
               <div class="container mx-auto max-w-6xl">
                  <!-- Search Bar -->
                  <div id="search-input-wrapper" class="bg-white p-4 rounded-lg shadow-sm mb-4">
                     <div class="relative">
                        <!-- ID input ini penting untuk cashier.js, pastikan sama: #product-search atau #search-input -->
                        <input type="text" id="product-search" placeholder="Cari produk, kode item atau scan barcode" 
                           class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="absolute left-3 top-3.5 text-gray-400">
                           <i class="fas fa-search"></i>
                        </div>
                        <!-- Tombol clear search, pastikan ID #clear-search dikenali cashier.js -->
                        <button id="clear-search" style="display: none;" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                        </button>
                     </div>
                     <!-- Kontainer ini untuk hasil pencarian dari input manual, JIKA cashier.js Anda menggunakannya -->
                     <!-- Jika cashier.js merender produk di tempat lain (misal #products-container-grid), ini bisa dikosongkan/dihapus -->
                     <div id="search-results-container" class="max-h-60 overflow-y-auto hidden">
                        <!-- Hasil pencarian dari input manual akan ditambahkan di sini oleh JavaScript -->
                     </div>
                  </div>
                  <!-- Payment Total Card -->
                  <div class="bg-indigo-700 rounded-lg p-4 text-white flex justify-between items-center mb-4">
                    <div>
                        <div class="text-sm">Total Pembayaran</div>
                        <div class="text-4xl font-bold" id="grand-total">Rp 0</div>
                    </div>
                    <div class="text-right">
                        <div>Kasir: <span class="text-xl font-bold"><?php echo $_SESSION["username"]; ?></span></div>
                        <div>Tanggal: <?php echo date('Y-m-d H:i:s'); ?></div>
                    </div>
                </div>
                  <!-- Action Buttons (Bayar, Tahan, Batal) -->
                  <!-- Pastikan ID #process-payment-btn, #btn-tahan, #btn-batal dikenali cashier.js -->
                  <!-- Jika cashier.js menggunakan ID lain untuk tombol bayar, sesuaikan -->
                  <div class="grid grid-cols-3 gap-3">
                     <button id="process-payment-btn" class="bg-blue-500 hover:bg-blue-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                     <i class="fas fa-money-bill-wave mr-1"></i> Bayar
                     </button>
                     <button id="btn-tahan" class="bg-yellow-500 hover:bg-yellow-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                     <i class="fas fa-pause mr-1"></i> Tahan
                     </button>
                     <button id="btn-batal" class="bg-red-500 hover:bg-red-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                     <i class="fas fa-times mr-1"></i> Batal
                     </button>
                  </div>
               </div>
            </div>
            <div class="h-64"></div>
            <!-- Spacer -->
            <!-- Cart Section -->
            <div class="bg-white rounded-lg shadow-sm p-4">
               <div id="cart-items" class="space-y-3 mb-6 min-h-[100px]">
                  <!-- Konten keranjang diisi oleh cashier.js -->
                  <div class="text-center text-gray-500 py-4">
                     <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                     <p>Keranjang kosong</p>
                  </div>
               </div>
               <!-- Info Resep (jika ada item yang memerlukan) - Pastikan ID #prescription-info, #doctor-id, #prescription-number dikenali -->
               <!--<div id="prescription-info" class="hidden mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                  <h3 class="text-sm font-semibold text-yellow-700 mb-1"><i class="fas fa-prescription mr-1"></i> Informasi Resep Dokter</h3>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                     <div>
                        <label for="doctor-id" class="block text-xs font-medium text-gray-700">Dokter</label>
                        <select id="doctor-id" name="doctor_id" class="mt-0.5 block w-full pl-3 pr-10 py-1.5 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                           <option value="">Pilih Dokter</option>
                           <option value="1">Dr. Budi Santoso</option>
                           
                        </select>
                     </div>
                     <div>
                        <label for="prescription-number" class="block text-xs font-medium text-gray-700">No. Resep</label>
                        <input type="text" name="prescription_number" id="prescription-number" class="mt-0.5 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-1.5">
                     </div>
                  </div>
               </div>-->
            </div>
            <!-- Elemen lain yang mungkin dibutuhkan oleh cashier.js -->
            <!-- Misalnya: Subtotal, Diskon, Pajak, Metode Pembayaran, Input Tunai, Kembalian -->
            <!-- Pastikan ID elemen-elemen ini (#subtotal, #discount-amount, #tax-amount, .payment-method-btn, #cash-amount, #change-amount) -->
            <!-- sesuai dengan yang digunakan di cashier.js -->
            <!-- Kontainer untuk menampilkan produk dari AJAX (jika cashier.js Anda memisahkannya dari search result) -->
            <!-- ID #products-container ini penting jika cashier.js merender produk di sini -->
            <div id="products-container" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
               <!-- Produk dari AJAX akan dirender di sini oleh cashier.js -->
            </div>
            <!-- Input Nama Pelanggan (jika ada di cashier.js) -->
         </div>
         <!-- End Main Layout space-y-4 -->
      </div>
      <!-- Modal Sukses Transaksi (Struktur dari HTML Anda, pastikan ID #success-modal, #success-invoice, #success-print, #success-new sesuai dengan cashier.js) -->
      <div id="success-modal" class="fixed inset-0 hidden bg-black bg-opacity-70 flex items-center justify-center z-[100]">
         <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="text-center">
               <div class="text-green-500 text-6xl mb-4">
                  <i class="fas fa-check-circle"></i>
               </div>
               <h3 class="text-xl font-bold mb-2">Transaksi Berhasil!</h3>
               <p class="mb-1">No. Faktur:</p>
               <p id="success-invoice" class="text-lg font-semibold mb-4"></p>
               <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                  <button id="success-print" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-md transition-colors">
                  <i class="fas fa-print mr-1"></i> Cetak Struk
                  </button>
                  <button id="success-new" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 px-4 rounded-md transition-colors">
                  <i class="fas fa-plus-circle mr-1"></i> Transaksi Baru
                  </button>
               </div>
            </div>
         </div>
      </div>
      <!-- jQuery (diperlukan oleh cashier.js Anda) -->
      <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
      <!-- SweetAlert2 JS (digunakan oleh cashier.js) -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script src="js/cashier.js?v=<?php echo time(); ?>"></script> <!-- Versi untuk cache busting -->
      <script>
         // Inisialisasi tanggal dan nama kasir (bisa juga dilakukan di cashier.js)
         document.addEventListener('DOMContentLoaded', () => {
             const currentDateDisplay = document.getElementById('current-date');
             const cashierNameDisplay = document.getElementById('cashier-name-display');
         
             if (currentDateDisplay) {
                 const today = new Date();
                 const options = { day: 'numeric', month: 'long', year: 'numeric' };
                 currentDateDisplay.textContent = today.toLocaleDateString('id-ID', options);
             }
             if (cashierNameDisplay && typeof authenticatedCashierName !== 'undefined') {
                 cashierNameDisplay.textContent = authenticatedCashierName;
             }
         
             // Jika cashier.js Anda memiliki fungsi inisialisasi global, panggil di sini.
             // Contoh: if (typeof initializeCashierPage === 'function') { initializeCashierPage(); }
             // Atau, jika cashier.js menggunakan $(document).ready() sendiri, ini mungkin tidak perlu.
         
             // Fokus ke input pencarian utama saat halaman dimuat (jika cashier.js belum melakukannya)
             const productSearchInput = document.getElementById('product-search');
             if (productSearchInput) {
                 // productSearchInput.focus(); // Anda bisa mengaktifkan ini jika perlu
             }
         });
      </script>
   </body>
</html>
