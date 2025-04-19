// Cart data array
let cartItems = [];
let selectedPaymentMethod = null;
let lastTransactionId = null;
let lastInvoiceNumber = null;

// Barcode scanner detection variables
let barcodeBuffer = '';
let lastKeyTime = 0;
let avgKeyInterval = 0;
let keyCount = 0;
let isBarcodeScanning = false;
let barcodeTimeout;

// Format number to currency
function formatCurrency(number) {
    return 'Rp ' + number.toLocaleString('id-ID');
}

// Update cart summary
function updateCartSummary() {
    let subtotal = 0;
    
    cartItems.forEach(item => {
        subtotal += item.total;
    });
    
    const taxRate = 0; // No tax (pajak 0%)
    const discountAmount = parseInt($('#discount-amount').val()) || 0;
    const taxAmount = subtotal * taxRate;
    const grandTotal = subtotal + taxAmount - discountAmount;
    
    $('#subtotal').text(formatCurrency(subtotal));
    $('#tax-amount').text(formatCurrency(taxAmount));
    $('#grand-total').text(formatCurrency(grandTotal));
    
    // Aktifkan tombol pembayaran jika ada item di keranjang dan metode pembayaran dipilih
    if (cartItems.length > 0 && selectedPaymentMethod !== null) {
        $('#process-payment-btn').prop('disabled', false);
    } else {
        $('#process-payment-btn').prop('disabled', true);
    }
    
    // Show/hide prescription info section
    const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
    if (needsPrescription) {
        $('#prescription-info').removeClass('hidden');
    } else {
        $('#prescription-info').addClass('hidden');
    }
}

// Render cart items
function renderCartItems() {
    if (cartItems.length === 0) {
        $('#cart-items').html(`
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                <p>Keranjang kosong</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    
    cartItems.forEach((item, index) => {
        html += `
            <div class="border-b py-2">
                <div class="flex justify-between mb-1">
                    <span class="font-medium">${item.name}</span>
                    <span class="text-blue-600">Rp ${item.price.toLocaleString('id-ID')}</span>
                </div>
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <button class="cart-minus bg-gray-200 w-6 h-6 rounded flex items-center justify-center" data-index="${index}">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <span>${item.quantity}</span>
                        <button class="cart-plus bg-gray-200 w-6 h-6 rounded flex items-center justify-center" data-index="${index}">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                    <div>
                        <span class="mr-2">Rp ${item.total.toLocaleString('id-ID')}</span>
                        <button class="cart-remove text-red-500" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                ${item.requires_prescription === 1 ? '<div class="text-xs text-yellow-600 mt-1"><i class="fas fa-prescription mr-1"></i>Memerlukan resep</div>' : ''}
            </div>
        `;
    });
    
    $('#cart-items').html(html);
    
    // Attach event handlers
    $('.cart-minus').click(function() {
        const index = $(this).data('index');
        if (cartItems[index].quantity > 1) {
            cartItems[index].quantity--;
            cartItems[index].total = cartItems[index].price * cartItems[index].quantity;
            renderCartItems();
            updateCartSummary();
        }
    });
    
    $('.cart-plus').click(function() {
        const index = $(this).data('index');
        if (cartItems[index].quantity < cartItems[index].stock) {
            cartItems[index].quantity++;
            cartItems[index].total = cartItems[index].price * cartItems[index].quantity;
            renderCartItems();
            updateCartSummary();
        } else {
            alert('Stok tidak mencukupi');
        }
    });
    
    $('.cart-remove').click(function() {
        const index = $(this).data('index');
        cartItems.splice(index, 1);
        renderCartItems();
        updateCartSummary();
    });
}

// Add product to cart directly
function addProductToCart(productData, quantity = 1) {
    const { product_id, product_name, kode_item, price, stock_quantity, unit, requires_prescription } = productData;
    
    // Parse price to ensure it's a number
    const productPrice = parseFloat(price);
    
    // Check if product is already in cart
    const existingItemIndex = cartItems.findIndex(item => item.id === product_id);
    
    if (existingItemIndex !== -1) {
        // Update quantity if product is already in cart
        const newQuantity = cartItems[existingItemIndex].quantity + quantity;
        
        if (newQuantity > stock_quantity) {
            alert('Stok tidak mencukupi');
            return;
        }
        
        cartItems[existingItemIndex].quantity = newQuantity;
        cartItems[existingItemIndex].total = productPrice * newQuantity;
    } else {
        // Add new item to cart
        cartItems.push({
            id: product_id,
            name: product_name,
            kode_item: kode_item || '',
            price: productPrice,
            unit: unit,
            quantity: quantity,
            total: productPrice * quantity,
            stock: stock_quantity,
            requires_prescription: requires_prescription == 1 ? 1 : 0
        });
    }
    
    // Update cart display
    renderCartItems();
    updateCartSummary();
    
    // Show a brief notification
    showAddedToCartNotification(product_name);
}

// Show notification when product is added to cart
function showAddedToCartNotification(productName) {
    // Remove existing notification if any
    $('#cart-notification').remove();
    
    // Create new notification element - now positioned at top-right
    $('body').append(`
        <div id="cart-notification" 
             class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg opacity-0 transition-all duration-300 transform -translate-y-8 z-50">
             <i class="fas fa-check-circle mr-1"></i> ${productName} ditambahkan ke keranjang
        </div>
    `);
    
    let notification = $('#cart-notification');
    
    // Show notification (slide down and fade in)
    setTimeout(() => {
        notification.removeClass('opacity-0 -translate-y-8');
    }, 10);
    
    // Hide and remove after 2 seconds
    setTimeout(() => {
        notification.addClass('opacity-0 translate-y-0');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Load products by category
function loadProductsByCategory(categoryId) {
    $.ajax({
        url: 'ajax_get_products.php',
        type: 'GET',
        data: { category_id: categoryId },
        dataType: 'json',
        success: function(response) {
            renderProducts(response);
        },
        error: function(xhr, status, error) {
            console.error('Error loading products:', error);
        }
    });
}

// Realtime search products
let searchTimeout;
function searchProducts(keyword, isBarcode = false) {
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    // If empty, show popular products
    if (keyword.length === 0) {
        $('.category-btn[data-id="0"]').click();
        return;
    }
    
    // Minimum 2 characters for search unless it's a barcode
    if (keyword.length < 2 && !isBarcode) return;
    
    // No delay for barcode searches, immediate execution
    const delay = isBarcode ? 0 : 300;
    
    // Set a timeout to prevent too many requests
    searchTimeout = setTimeout(function() {
        $.ajax({
            url: 'ajax_search_products.php',
            type: 'GET',
            data: { 
                keyword: keyword,
                is_barcode: isBarcode ? 1 : 0 // Kirim parameter tambahan untuk barcode
            },
            dataType: 'json',
            success: function(response) {
                // Periksa apakah response adalah array atau objek
                if (Array.isArray(response)) {
                    // Jika pencarian barcode dan tepat 1 hasil, tambahkan langsung ke keranjang
                    if (isBarcode && response.length === 1) {
                        addProductToCart(response[0]);
                        
                        // Reset input search setelah ditambahkan
                        $('#product-search').val('').focus();
                    } else {
                        // Tampilkan hasil pencarian seperti biasa
                        renderProducts(response);
                    }
                } else if (response.error) {
                    // Tangani kesalahan jika ada
                    console.error('Error from server:', response.error);
                } else {
                    // Response tidak valid
                    console.error('Invalid response format:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error searching products:', error);
            }
        });
    }, delay);
}

// Render products in grid
function renderProducts(products) {
    let html = '';
    
    if (products.length === 0) {
        html = '<div class="col-span-full text-center py-8 text-gray-500">Tidak ada produk yang ditemukan</div>';
    } else {
        products.forEach(product => {
            html += `
                <div class="product-card bg-white rounded-lg shadow-md p-3 cursor-pointer hover:shadow-lg transition"
                     data-product='${JSON.stringify(product)}'>
                    <div class="h-24 bg-gray-200 rounded-md mb-2 flex items-center justify-center relative">
                        <i class="fas fa-pills text-gray-400 text-3xl"></i>
                        ${product.requires_prescription == 1 ? 
                        '<span class="absolute top-0 right-0 bg-yellow-500 text-white text-xs px-1 rounded">Resep</span>' : ''}
                    </div>
                    <h3 class="font-medium">${product.product_name}</h3>
                    <div class="flex justify-between items-center mt-1">
                        <p class="text-sm text-gray-500">${product.kode_item || ''}</p>
                        <span class="bg-blue-100 text-blue-900 px-2 py-1 rounded text-sm">${product.posisi}</span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <p class="text-blue-600 font-bold">Rp ${parseInt(product.price).toLocaleString('id-ID')}</p>
                        ${
                            product.stock_quantity === 0
                                ? `<span class="bg-red-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`
                                : product.stock_quantity < product.minimum_stock * 2
                                ? `<span class="bg-yellow-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`
                                : `<span class="bg-green-100 text-gray-900 px-2 py-1 rounded text-sm">Stok: ${product.stock_quantity}</span>`
                        }
                    </div>
                </div>
            `;
        });
    }
    
    $('#products-container').html(html);
    
    // Attach click event to product cards - now adds directly to cart
    $('.product-card').click(function() {
        const productData = $(this).data('product');
        addProductToCart(productData, 1);
    });
}

// Save transaction
function saveTransaction() {
    // Check if prescription info is needed but not provided
    const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
    if (needsPrescription) {
        const doctorId = $('#doctor-id').val();
        const prescriptionNumber = $('#prescription-number').val();
        
        if (!doctorId || !prescriptionNumber) {
            alert('Informasi resep dokter diperlukan untuk obat resep');
            return false;
        }
    }
    
    // Calculate totals
    let subtotal = 0;
    cartItems.forEach(item => {
        subtotal += item.total;
    });
    
    const taxRate = 0; // No tax
    const discountAmount = parseInt($('#discount-amount').val()) || 0;
    const taxAmount = subtotal * taxRate;
    const grandTotal = subtotal + taxAmount - discountAmount;
    
    // Prepare transaction data
    const transactionData = {
        customer_name: $('#customer-name').val(),
        doctor_id: needsPrescription ? $('#doctor-id').val() : null,
        prescription_number: needsPrescription ? $('#prescription-number').val() : null,
        user_id: 1, // Replace with actual user ID from session
        subtotal: subtotal,
        tax_amount: taxAmount,
        discount_amount: discountAmount,
        total_amount: grandTotal,
        payment_method_id: selectedPaymentMethod,
        payment_status: 'paid',
        notes: '',
        items: cartItems.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: item.price,
            discount_percent: 0
        }))
    };
    
    // Send transaction data to server
    $.ajax({
        url: 'save_transaction.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(transactionData),
        success: function(response) {
            if (response.success) {
                lastTransactionId = response.sale_id;
                lastInvoiceNumber = response.invoice_number;
                
                // Show success modal
                $('#payment-modal').addClass('hidden');
                $('#success-invoice').text(response.invoice_number);
                $('#success-modal').removeClass('hidden');
                
                // Clear cart
                cartItems = [];
                renderCartItems();
                updateCartSummary();
                
                // Reset form fields
                $('#customer-name').val('');
                $('#doctor-id').val('');
                $('#prescription-number').val('');
                $('#discount-amount').val('0');
                $('#cash-amount').val('');
                $('#change-amount').text('Rp 0');
                
                // Reset payment method
                $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
                selectedPaymentMethod = null;
                
                // Hide cash payment fields
                $('#cash-payment-fields').addClass('hidden');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving transaction:', error);
            alert('Terjadi kesalahan saat menyimpan transaksi. Silakan coba lagi.');
        }
    });
    
    return true;
}

// Fungsi yang diperbarui untuk penanganan pembayaran
function togglePaymentMethod(methodId, methodName) {
    selectedPaymentMethod = methodId;
    
    // Reset cash payment fields
    $('#cash-amount').val('');
    $('#change-amount').text('Rp 0');
    
    // Show/hide cash fields based on payment method
    if (methodName.toLowerCase().includes('tunai')) {
        $('#cash-payment-fields').removeClass('hidden');
    } else {
        $('#cash-payment-fields').addClass('hidden');
    }
    
    // Aktifkan tombol pembayaran jika ada item di keranjang dan metode pembayaran dipilih
    if (cartItems.length > 0 && selectedPaymentMethod !== null) {
        $('#process-payment-btn').prop('disabled', false);
    } else {
        $('#process-payment-btn').prop('disabled', true);
    }
}

// Update payment button state
function updatePaymentButtonState() {
    const hasItems = cartItems.length > 0;
    const hasPaymentMethod = selectedPaymentMethod !== null;
    const isCashPayment = $('#cash-payment-fields').is(':visible');
    
    if (!hasItems || !hasPaymentMethod) {
        $('#process-payment-btn').prop('disabled', true);
        return;
    }
    
    if (isCashPayment) {
        // For cash payments, check if entered amount is sufficient
        const totalAmount = parseInt($('#grand-total').text().replace(/[^0-9.-]+/g, '')) || 0;
        const cashAmount = parseInt($('#cash-amount').val().replace(/\D/g, '')) || 0;
        
        $('#process-payment-btn').prop('disabled', cashAmount < totalAmount);
    } else {
        // For non-cash payments, just enable the button
        $('#process-payment-btn').prop('disabled', false);
    }
}

// Calculate change amount for cash payments - opsional
function calculateChange() {
    if (!$('#cash-amount').val().trim()) {
        // Jika tidak ada nilai tunai yang dimasukkan, kosongkan kembalian
        $('#change-amount').text('Rp 0');
        return {
            totalAmount: 0,
            cashAmount: 0,
            changeAmount: 0
        };
    }
    
    // Dapatkan total dari display
    const grandTotalText = $('#grand-total').text().trim();
    const totalAmount = parseInt(grandTotalText.replace(/Rp\s?/, '').replace(/\./g, '')) || 0;
    
    // Dapatkan jumlah tunai dari input
    const cashAmountText = $('#cash-amount').val().trim();
    const cashAmount = parseInt(cashAmountText.replace(/\D/g, '')) || 0;
    
    // Hitung kembalian
    const changeAmount = Math.max(0, cashAmount - totalAmount);
    
    // Tampilkan jumlah kembalian
    $('#change-amount').text(formatCurrency(changeAmount));
    
    return {
        totalAmount,
        cashAmount,
        changeAmount
    };
}


// Format the cash input with thousand separators as the user types
function formatCashInput(input) {
    // Store cursor position
    const cursorPos = input.selectionStart;
    
    // Get the raw value without non-numeric characters
    const rawValue = input.value.replace(/\D/g, '');
    
    // Format the number with thousand separators
    const formattedValue = rawValue === '' ? '' : new Intl.NumberFormat('id-ID').format(rawValue);
    
    // Count the number of thousand separators before the cursor position
    const originalValue = input.value;
    let separatorCountBefore = 0;
    for (let i = 0; i < cursorPos; i++) {
        if (originalValue[i] === '.') {
            separatorCountBefore++;
        }
    }
    
    // Set the formatted value
    input.value = formattedValue;
    
    // Calculate the new cursor position
    const newSeparatorCountBefore = formattedValue.substr(0, cursorPos + separatorCountBefore).split('.').length - 1;
    const newCursorPos = cursorPos + (newSeparatorCountBefore - separatorCountBefore);
    
    // Set the cursor position
    setTimeout(() => {
        if (input.setSelectionRange) {
            input.setSelectionRange(newCursorPos, newCursorPos);
        }
    }, 0);
}

// Deteksi apakah input adalah dari barcode scanner
function detectBarcodeScanner(e) {
    const currentTime = new Date().getTime();
    
    // Hitung interval waktu antara keypress
    if (lastKeyTime > 0) {
        const interval = currentTime - lastKeyTime;
        
        // Update average interval untuk mendeteksi apakah ini adalah scanner barcode
        // Scanner barcode mengirimkan karakternya dengan interval waktu yang sangat kecil
        if (keyCount > 0) {
            avgKeyInterval = ((avgKeyInterval * keyCount) + interval) / (keyCount + 1);
        } else {
            avgKeyInterval = interval;
        }
        keyCount++;
    }
    
    lastKeyTime = currentTime;
    
    // Reset timer untuk barcodeBuffer
    clearTimeout(barcodeTimeout);
    barcodeTimeout = setTimeout(function() {
        // Jika sudah tidak ada penekanan tombol dalam 500ms, reset buffer
        if (barcodeBuffer.length >= 8 && avgKeyInterval < 50) {
            // Jika panjang buffer > 8 karakter dan interval ketikan sangat cepat, 
            // kemungkinan besar ini adalah scan barcode
            searchProducts(barcodeBuffer, true);
        }
        
        // Reset buffer dan statistik
        barcodeBuffer = '';
        keyCount = 0;
        avgKeyInterval = 0;
        isBarcodeScanning = false;
    }, 500);
}

// Document ready
$(document).ready(function() {
    // Implementasi deteksi barcode scanner
    $(document).on('keypress', function(e) {
        // Abaikan jika fokus ada di input forms (kecuali product-search)
        if ($('input:focus, textarea:focus').length > 0 && !$('#product-search').is(':focus')) {
            // Kecuali jika user menekan Enter di #product-search
            if (e.which === 13 && $('#product-search').is(':focus')) {
                e.preventDefault();
                const keyword = $('#product-search').val().trim();
                if (keyword.length > 0) {
                    searchProducts(keyword, true); // Asumsikan pencarian dengan Enter adalah barcode
                }
            }
            return;
        }
        
        // Jika Enter ditekan dan buffer ada isinya
        if (e.which === 13 && barcodeBuffer.length > 0) {
            e.preventDefault();
            
            if (barcodeBuffer.length >= 8 || avgKeyInterval < 50) {
                // Jika panjang buffer > 8 karakter atau ketikan sangat cepat,
                // kemungkinan besar ini adalah scan barcode
                searchProducts(barcodeBuffer, true);
                
                // Reset buffer setelah pencarian
                barcodeBuffer = '';
                keyCount = 0;
                avgKeyInterval = 0;
                isBarcodeScanning = false;
            }
            return;
        }
        
        // Abaikan karakter non-printable
        if (e.which < 32 || e.which > 126) return;
        
        // Tambahkan karakter ke buffer
        barcodeBuffer += String.fromCharCode(e.which);
        
        // Update status scanning
        detectBarcodeScanner(e);
        
        // Auto-focus dan update input pencarian jika ini terdeteksi sebagai barcode scan
        if (avgKeyInterval < 50 && barcodeBuffer.length > 3) {
            isBarcodeScanning = true;
            const searchInput = $('#product-search');
            if (!searchInput.is(':focus')) {
                searchInput.focus().val(barcodeBuffer);
            } else {
                searchInput.val(barcodeBuffer);
            }
        }
    });
    
    // Category buttons click
    $('.category-btn').click(function() {
        $('.category-btn').removeClass('bg-blue-600 text-white').addClass('bg-white hover:bg-gray-100');
        $(this).removeClass('bg-white hover:bg-gray-100').addClass('bg-blue-600 text-white');
        
        const categoryId = $(this).data('id');
        loadProductsByCategory(categoryId);
    });
    
    // Product search - Realtime
    $('#product-search').on('input', function() {
        // Jika bukan dari barcode scanner
        if (!isBarcodeScanning) {
            const keyword = $(this).val().trim();
            searchProducts(keyword, false);
        }
    });
    
    // Clear search when clicking the X
    $('#clear-search').click(function() {
        $('#product-search').val('');
        $(this).hide();
        $('.category-btn[data-id="0"]').click(); // Show popular products
    });
    
    // Show/hide clear search button
    $('#product-search').on('input', function() {
        if ($(this).val().length > 0) {
            $('#clear-search').show();
        } else {
            $('#clear-search').hide();
        }
    });
    
    // Enter key in search field
    $('#product-search').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            const keyword = $(this).val().trim();
            if (keyword.length > 0) {
                // Nilai true mengindikasikan bahwa ini mungkin scan barcode atau pencarian langsung
                searchProducts(keyword, true);
            }
        }
    });
    
    // Payment method selection
    $('.payment-method-btn').click(function() {
        $('.payment-method-btn').removeClass('bg-blue-500 text-white').addClass('bg-blue-100 text-blue-600');
        $(this).removeClass('bg-blue-100 text-blue-600').addClass('bg-blue-500 text-white');
        
        const methodId = $(this).data('id');
        const methodName = $(this).text().trim();
        
        togglePaymentMethod(methodId, methodName);
    });
    
    // Cash amount input handling
    $('#cash-amount').on('input', function() {
        formatCashInput(this);
        calculateChange();
    });
    
    // Handle keydown in cash input to allow navigation keys
    $('#cash-amount').on('keydown', function(e) {
        if (e.key === 'Enter') {
            // Submit payment if valid
            const { totalAmount, cashAmount } = calculateChange();
            
            if (cashAmount >= totalAmount) {
                $('#process-payment-btn').click();
            }
            e.preventDefault();
        }
    });
    
    // Process payment button
    $('#process-payment-btn').click(function() {
        if (cartItems.length === 0) {
            alert('Keranjang kosong');
            return;
        }
        
        if (selectedPaymentMethod === null) {
            alert('Pilih metode pembayaran');
            return;
        }
        
        // Check if prescription info is needed
        const needsPrescription = cartItems.some(item => item.requires_prescription === 1);
        if (needsPrescription) {
            const doctorId = $('#doctor-id').val();
            const prescriptionNumber = $('#prescription-number').val();
            
            if (!doctorId || !prescriptionNumber) {
                alert('Informasi resep dokter diperlukan untuk obat resep');
                return;
            }
        }
        
        // Untuk pembayaran tunai, jika diisi, validasi jumlahnya
        if ($('#cash-payment-fields').is(':visible') && $('#cash-amount').val().trim()) {
            const { totalAmount, cashAmount } = calculateChange();
            
            if (cashAmount < totalAmount) {
                alert('Jumlah pembayaran kurang');
                $('#cash-amount').focus();
                return;
            }
        }
        
        // Save transaction
        saveTransaction();
    });
    
    // Discount amount input
    $('#discount-amount').on('input', function() {
        updateCartSummary();
    });
    
    // Success modal print button
    $('#success-print').click(function() {
        if (lastTransactionId) {
            window.open('print_receipt.php?id=' + lastTransactionId, '_blank');
        }
    });
    
    // Success modal new transaction button
    $('#success-new').click(function() {
        $('#success-modal').addClass('hidden');
        // No need to reset anything - already done after saving transaction
    });
    
    // Close modals when clicking outside
    $(window).click(function(e) {
        if ($(e.target).is('#payment-modal')) {
            $('#payment-modal').addClass('hidden');
        }
        if ($(e.target).is('#success-modal')) {
            $('#success-modal').addClass('hidden');
        }
    });
    
    // Initialize cart display
    renderCartItems();
    updateCartSummary();
    
    // Load initial products (popular)
    $('.category-btn[data-id="0"]').click();
    
    // Set focus pada input pencarian untuk memudahkan scan barcode
    $('#product-search').focus();
});