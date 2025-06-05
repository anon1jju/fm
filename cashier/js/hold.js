document.addEventListener('DOMContentLoaded', () => {
    const holdButton = document.getElementById('btn-tahan'); // Tombol "Tahan"
    const retrieveButton = document.getElementById('btn-retrieve'); // Tombol "Transaksi Tertahan"
    const cartItemsContainer = document.getElementById('cart-items'); // Tempat keranjang belanja

    // Fungsi untuk menyimpan transaksi ke localStorage
    function holdTransaction() {
        if (!cartItems || cartItems.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Keranjang Kosong',
                text: 'Tidak ada item di keranjang untuk ditahan.',
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }

        const heldTransactions = JSON.parse(localStorage.getItem('heldTransactions')) || [];

        // Simpan item keranjang ke daftar transaksi tertahan
        heldTransactions.push({
            id: Date.now(), // ID unik untuk transaksi
            items: cartItems,
            total: document.getElementById('grand-total').textContent, // Total pembayaran
        });

        localStorage.setItem('heldTransactions', JSON.stringify(heldTransactions));

        cartItems = [];
        cartItemsContainer.innerHTML = `
            <div class="text-center text-gray-500 py-4">
                <i class="fas fa-shopping-cart text-3xl mb-2"></i>
                <p>Keranjang kosong</p>
            </div>
        `;

        Swal.fire({
            icon: 'success',
            title: 'Transaksi Ditahan',
            text: 'Transaksi berhasil ditahan.',
            showConfirmButton: false,
            timer: 1200
        }).then(() => {
            location.reload();
        });
    }

    // Fungsi untuk menampilkan daftar transaksi tertahan
    function showHeldTransactions() {
        const heldTransactions = JSON.parse(localStorage.getItem('heldTransactions')) || [];

        if (heldTransactions.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Tidak Ada Transaksi Tertahan',
                text: 'Tidak ada transaksi tertahan untuk diambil.',
                showConfirmButton: false,
                timer: 1200
            });
            return;
        }

        // Tampilkan daftar transaksi tertahan
        const transactionList = heldTransactions.map((transaction, index) => {
            return `
                <div class="mb-2">
                    <button class="retrieve-transaction-btn w-full text-left text-lg text-gray-500 font-bold p-4 border rounded-md bg-gray-200 hover:bg-gray-200"
                        data-index="${index}">
                        <strong>Transaksi ${index + 1}</strong> - Total: ${transaction.total}
                    </button>
                </div>
            `;
        }).join('');

        // Tampilkan dialog dengan daftar transaksi
        Swal.fire({
            title: 'Pilih Transaksi Tertahan',
            html: `
                <div>
                    ${transactionList}
                </div>
            `,
            showConfirmButton: false,
            didOpen: () => {
                // Tambahkan event listener untuk tombol
                document.querySelectorAll('.retrieve-transaction-btn').forEach(button => {
                    button.addEventListener('click', () => {
                        const index = button.getAttribute('data-index');
                        retrieveTransactionByIndex(index);
                    });
                });
            }
        });
    }

    // Fungsi untuk mengambil transaksi berdasarkan indeks
    function retrieveTransactionByIndex(index) {
        const heldTransactions = JSON.parse(localStorage.getItem('heldTransactions')) || [];

        if (index < 0 || index >= heldTransactions.length) {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                text: 'Transaksi tidak ditemukan.',
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }

        // Ambil transaksi yang dipilih
        const selectedTransaction = heldTransactions.splice(index, 1)[0];

        // Masukkan kembali ke keranjang
        cartItems = selectedTransaction.items;
        renderCartItems();

        // Perbarui total pembayaran
        document.getElementById('grand-total').textContent = selectedTransaction.total;

        // Simpan perubahan ke localStorage
        localStorage.setItem('heldTransactions', JSON.stringify(heldTransactions));

        Swal.fire({
            icon: 'success',
            title: 'Transaksi Diambil',
            text: 'Transaksi berhasil diambil kembali.',
            showConfirmButton: false,
            timer: 1500
        });
    }

    // Fungsi untuk merender item keranjang
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
            let unitSelectorHtml = '<div class="unit-selector-group text-sm mt-1" data-index="' + index + '">';
            unitSelectorHtml += '<span class="text-gray-700 mr-1 font-medium"></span>';
            const baseUnitOriginalCase = item.unit; 
            const selectedUnit = item.selected_unit || baseUnitOriginalCase;

            if (baseUnitOriginalCase && baseUnitOriginalCase.toLowerCase() === 'strip') {
                const optionStrip = baseUnitOriginalCase; 
                const optionPcs = "Tablet"; 
                unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${optionStrip}" class="unit-radio mr-0.5" data-item-index="${index}" ${ selectedUnit.toLowerCase() === optionStrip.toLowerCase() ? 'checked' : '' }>${optionStrip}</label>`;
                unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${optionPcs}" class="unit-radio mr-0.5" data-item-index="${index}" ${ selectedUnit.toLowerCase() === optionPcs.toLowerCase() ? 'checked' : '' }>${optionPcs}</label>`;
            } else {
                const displayUnit = baseUnitOriginalCase || 'N/A'; 
                unitSelectorHtml += `<label class="mr-2 cursor-pointer"><input type="radio" name="unit-options-${index}" value="${displayUnit}" class="unit-radio mr-0.5" data-item-index="${index}" checked>${displayUnit}</label>`;
            }
            unitSelectorHtml += '</div>';

            html += `
                <div class="border-b bg-white p-3 rounded-md shadow-sm mb-2">
                    <div class="flex justify-between mb-1">
                        <span class="text-md font-semibold">${item.name}</span>
                        <span class="text-blue-600">Rp ${item.price.toLocaleString('id-ID')} / ${item.unit}</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-1">
                                <button class="cart-qty-btn cart-qty-minus-btn p-1 border rounded-md hover:bg-gray-100" data-index="${index}" aria-label="Kurangi kuantitas">
                                    <i class="fas fa-minus fa-xs"></i>
                                </button>
                                <label for="qty-${index}" class="sr-only">Kuantitas untuk ${item.name}</label>
                                <input type="tel" id="qty-${index}" class="cart-quantity-input w-16 px-3 py-1 text-center border rounded-md" value="${item.display_quantity}" data-index="${index}" step="any" min="0"> 
                                <button class="cart-qty-btn cart-qty-plus-btn p-1 border rounded-md hover:bg-gray-100" data-index="${index}" aria-label="Tambah kuantitas">
                                    <i class="fas fa-plus fa-xs"></i>
                                </button>
                            </div>
                            ${unitSelectorHtml}
                        </div>
                        <div class="text-right">
                            <span class="block">Rp ${item.total.toLocaleString('id-ID')}</span>
                            <button class="cart-remove text-red-500 mt-1" data-index="${index}">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                    ${item.requires_prescription === 1 ? '<div class="text-xs text-yellow-600 mt-1"><i class="fas fa-prescription mr-1"></i>Memerlukan resep</div>' : ''}
                </div>
            `;
        });
        $('#cart-items').html(html);
        $('.cart-remove').click(function() {
            const index = $(this).data('index');
            cartItems.splice(index, 1);
            renderCartItems();
            updateCartSummary();
        });
    }

    // Event listener untuk tombol "Tahan"
    if (holdButton) {
        holdButton.addEventListener('click', holdTransaction);
    }

    // Event listener untuk tombol "Transaksi Tertahan"
    if (retrieveButton) {
        retrieveButton.addEventListener('click', showHeldTransactions);
    }
});
