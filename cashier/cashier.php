 <?php
    /**
     * cashier.php - Halaman utama kasir
     * 
     * Halaman untuk operasi POS Apotek oleh kasir
     * 
     * @version 1.0.7
     * @date 2025-05-16
     */
    
    // Include file fungsi cashierTotal Tagihan
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
          .modal-hidden {
              display: none !important; /* Tambahkan !important untuk memastikan override jika ada konflik */
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
                         <div><span class="text-xl font-bold"><?php echo $_SESSION["username"]; ?></span></div>
                         <div><?php echo date('Y-m-d H:i:s'); ?></div>
                     </div>
                 </div>
                   <!-- Action Buttons (Bayar, Tahan, Batal) -->
                   <!-- Tombol "Bayar" sekarang akan membuka modal pembayaran -->
                   <div class="grid grid-cols-4 gap-4">
                      <button id="open-payment-modal-btn" class="bg-blue-500 hover:bg-blue-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                      <i class="fas fa-money-bill-wave mr-1"></i> Bayar
                      </button>
                      <button id="btn-tahan" class="bg-yellow-500 hover:bg-yellow-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                      <i class="fas fa-pause mr-1"></i> Tahan
                      </button>
                      <button id="btn-retrieve" class="bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                       <i class="fas fa-list mr-1"></i> Transaksi Tertahan
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
             </div>
             <div id="products-container" class="mt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                <!-- Produk dari AJAX akan dirender di sini oleh cashier.js -->
             </div>
          </div>
       </div>

       <!-- Modal Sukses Transaksi -->
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

       <!-- Modal Pembayaran -->
        <div id="payment-modal" class="fixed inset-0 modal-hidden bg-black bg-opacity-60 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-auto">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-800">Proses Pembayaran</h3>
                    <button id="close-payment-modal-btn" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Total Tagihan</label>
                    <p id="modal-total-tagihan" class="text-2xl font-bold text-indigo-600">Rp 0</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Metode Pembayaran</label>
                    <div class="flex space-x-14">
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" id="pay-tunai" value="tunai" class="form-radio h-6 w-6 text-indigo-600 payment-method-radio" data-id="1" data-name="Tunai" checked>
                            <label for="pay-tunai" class="ml-2 text-md font-semibold text-gray-700">TUNAI</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" id="pay-qris" value="qris" class="form-radio h-6 w-6 text-indigo-600 payment-method-radio" data-id="3" data-name="QRIS">
                            <label for="pay-qris" class="ml-2 text-md font-semibold text-gray-700">QRIS</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="payment_method" id="pay-debit" value="debit" class="form-radio h-6 w-6 text-indigo-600 payment-method-radio" data-id="2" data-name="Kredit/Debit">
                            <label for="pay-debit" class="ml-2 text-md font-semibold text-gray-700">KARTU</label>
                        </div>
                    </div>
                </div>

                <div class="mb-4 relative hidden">
                  <label for="discount-amount" class="block text-sm font-medium text-gray-700 mb-2">Diskon (Rp)</label>
                  <input type="tel" id="discount-amount" disabled placeholder="0"
                         class="mt-1 block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                  
                  
                  <button type="button" id="clear-discount" class="absolute top-9 right-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <div class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700">Total Setelah Diskon</label>
                    <p id="modal-total-setelah-diskon" class="text-xl font-semibold text-gray-800">Rp 0</p>
                </div>

                <div id="cash-payment-details" class="mb-4 space-y-3">
                    <div class="mb-4 relative">
                      <label for="cash-amount" class="block text-sm font-medium text-gray-700 mb-2">Jumlah Tunai (Rp)</label>
                      <input type="tel" id="cash-amount" placeholder="0"
                             class="mt-1 block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                      
                      <!-- Tombol Clear -->
                      <!--<button type="button" id="clear-cash-paid"
                              class="absolute top-1/2 right-3 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">-->
                    <button type="button" id="clear-cash-paid" class="absolute top-9 right-3 text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                      </button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kembalian</label>
                        <p id="change-amount" class="text-xl font-bold text-green-600">Rp 0</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button id="cancel-payment-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md">Batal</button>
                    <button id="confirm-payment-btn" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">Konfirmasi Pembayaran</button>
                </div>
            </div>
        </div>

       <!-- jQuery (diperlukan oleh cashier.js Anda) -->
       <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
       <!-- SweetAlert2 JS (digunakan oleh cashier.js) -->
       <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
       <script src="js/cashier.js"></script>
       <script src="js/hold.js"></script>
       <script src="js/focus.js"></script>
       <!--<script src="js/balek.js"></script>-->
       <script>
          // Inisialisasi tanggal dan nama kasir (bisa juga dilakukan di cashier.js)
          document.addEventListener('DOMContentLoaded', () => {
             
              // --- Logika untuk Modal Pembayaran (Contoh, idealnya di cashier.js) ---
              const paymentModal = document.getElementById('payment-modal');
              

              const openPaymentModalBtn = document.getElementById('open-payment-modal-btn');
              const closePaymentModalBtn = document.getElementById('close-payment-modal-btn');
              const cancelPaymentBtn = document.getElementById('cancel-payment-btn');
              
              const modalTotalTagihan = document.getElementById('modal-total-tagihan');
              const discountInput = document.getElementById('discount-amount');
              const modalTotalSetelahDiskon = document.getElementById('modal-total-setelah-diskon');
              const cashPaidInput = document.getElementById('cash-amount');
              const changeDisplay = document.getElementById('change-amount');
              const cashPaymentDetails = document.getElementById('cash-payment-details');
              const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');

              function formatCurrency(amount) {
                  return 'Rp ' + parseFloat(amount).toLocaleString('id-ID');
              }
              
              function setupClearButton(inputId, buttonId) {
                const input = document.getElementById(inputId);
                const button = document.getElementById(buttonId);
            
                button.addEventListener('click', function () {
                  input.value = '';
                  input.focus();
                });
              }
            
              setupClearButton('discount-amount', 'clear-discount');
              setupClearButton('cash-amount', 'clear-cash-paid');
              
              const confirmPaymentBtn = document.getElementById('confirm-payment-btn');

                if (confirmPaymentBtn) {
                    confirmPaymentBtn.addEventListener('click', () => {
                        
                        if (typeof cartItems === 'undefined' || !cartItems || cartItems.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Keranjang Kosong',
                                text: 'Tidak ada item di keranjang untuk diproses.',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                
                        // 2. Dapatkan metode pembayaran yang dipilih dari modal
                        const selectedPaymentMethodRadio = document.querySelector('input[name="payment_method"]:checked');
                        if (!selectedPaymentMethodRadio) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Metode Pembayaran',
                                text: 'Silakan pilih metode pembayaran.',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                        
                        
                        window.selectedPaymentMethod = selectedPaymentMethodRadio.value;
                
                        
                        if (typeof saveTransaction === 'function') {
                            
                            const initialValidationPassed = saveTransaction(); 
                
                            if (initialValidationPassed) {
                                
                                if (paymentModal) { 
                                    paymentModal.classList.add('modal-hidden');
                                }
                            }
                            
                        } else {
                            console.error('Fungsi saveTransaction tidak ditemukan.');
                            Swal.fire({
                                icon: 'error',
                                title: 'Kesalahan Sistem',
                                text: 'Fungsi untuk menyimpan transaksi tidak ditemukan. Harap hubungi administrator.',
                            });
                        }
                    });
                }

              function calculateTotals() {
                  const grandTotalText = document.getElementById('grand-total').textContent.replace(/[^0-9,-]+/g,"").replace(',','.');
                  const grandTotal = parseFloat(grandTotalText) || 0;
                  
                  const discount = parseFloat(discountInput.value) || 0;
                  const totalAfterDiscount = grandTotal - discount;

                  modalTotalTagihan.textContent = formatCurrency(grandTotal);
                  modalTotalSetelahDiskon.textContent = formatCurrency(totalAfterDiscount < 0 ? 0 : totalAfterDiscount);

                  if (document.querySelector('input[name="payment_method"]:checked').value === 'tunai') {
                      cashPaymentDetails.style.display = 'block';
                      const cashPaid = parseFloat(cashPaidInput.value) || 0;
                      const change = cashPaid - totalAfterDiscount;
                      changeDisplay.textContent = formatCurrency(change < 0 ? 0 : change);
                  } else {
                      cashPaymentDetails.style.display = 'none';
                      changeDisplay.textContent = formatCurrency(0);
                  }
              }

              if (openPaymentModalBtn) {
                  openPaymentModalBtn.addEventListener('click', () => {
                      if (paymentModal) {
                          // Ambil total dari #grand-total dan set ke modal
                          const grandTotalText = document.getElementById('grand-total').textContent;
                          if (modalTotalTagihan) modalTotalTagihan.textContent = grandTotalText;
                          
                          // Reset form di modal
                          if(discountInput) discountInput.value = '';
                          if(cashPaidInput) cashPaidInput.value = '';
                          document.getElementById('pay-tunai').checked = true; // Default ke tunai

                          calculateTotals(); // Hitung saat modal dibuka
                          paymentModal.classList.remove('modal-hidden');
                      }
                  });
              }

              if (closePaymentModalBtn) {
                  closePaymentModalBtn.addEventListener('click', () => {
                      if (paymentModal) paymentModal.classList.add('modal-hidden');
                  });
              }
              if (cancelPaymentBtn) {
                cancelPaymentBtn.addEventListener('click', () => {
                      if (paymentModal) paymentModal.classList.add('modal-hidden');
                  });
              }

              // Event listeners untuk kalkulasi dinamis di modal
              if(discountInput) discountInput.addEventListener('input', calculateTotals);
              if(cashPaidInput) cashPaidInput.addEventListener('input', calculateTotals);
              paymentMethodRadios.forEach(radio => {
                  radio.addEventListener('change', calculateTotals);
              });

              // Klik di luar modal untuk menutup (opsional)
              if (paymentModal) {
                  paymentModal.addEventListener('click', (event) => {
                      if (event.target === paymentModal) { // Jika klik pada area overlay gelap
                          paymentModal.classList.add('modal-hidden');
                      }
                  });
              }
              // --- Akhir Logika Modal Pembayaran ---
          });
       </script>
    </body>
 </html>
