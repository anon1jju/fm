$(document).ready(function() {
    
    // Fungsi untuk memformat angka menjadi format ribuan dengan titik
    function formatRupiah(input) {
        // Simpan posisi kursor
        var start = input.selectionStart;
        var end = input.selectionEnd;
        var oldLength = input.value.length;
        
        // Hapus semua karakter kecuali digit
        var value = input.value.replace(/[^\d]/g, '');
        
        // Format dengan pemisah ribuan
        var formatted = '';
        var remainder = value.length % 3;
        var rupiah = value.substr(0, remainder);
        var thousands = value.substr(remainder).match(/\d{3}/g);
        
        if (rupiah.length > 0) {
            formatted += rupiah + (thousands ? '.' : '');
        }
        
        if (thousands) {
            formatted += thousands.join('.');
        }
        
        // Update nilai input
        input.value = formatted;
        
        // Simpan nilai numerik original (untuk dikirim ke server)
        input.dataset.numericValue = value;
        
        // Atur ulang posisi kursor dengan mempertimbangkan perubahan panjang
        var newLength = input.value.length;
        var offset = newLength - oldLength;
        
        if (start < input.value.length) {
            input.setSelectionRange(start + offset, end + offset);
        }
    }
    
    // Event delegation untuk format harga
    $('#produkTable tbody').on('input', 'input[name="cost_price[]"], input[name="price[]"]', function() {
        formatRupiah(this);
    });
    
    // Reset nilai ke numerik saat form disubmit
    $('#tambahBarangForm').on('submit', function() {
        $('input[name="cost_price[]"], input[name="price[]"]').each(function() {
            if (this.dataset.numericValue) {
                this.value = this.dataset.numericValue;
            }
        });
    });
    
    // Fungsi untuk memformat semua input harga dalam sebuah row
    function applyPriceFormatting(row) {
        $(row).find('input[name="cost_price[]"], input[name="price[]"]').each(function() {
            if (this.value && !this.dataset.numericValue) {
                formatRupiah(this);
            }
        });
    }
    
    // Format harga untuk semua baris yang ada saat halaman dimuat
    $('#tableBody tr').each(function() {
        applyPriceFormatting(this);
    });
            function cleanToNumericString(value) {
                if (value === null || value === undefined) return '';
                let str = String(value);
                // Hapus semua karakter kecuali digit dan koma
                str = str.replace(/[^\d,]/g, "");
        
                // Pastikan hanya ada satu koma desimal
                let parts = str.split(',');
                if (parts.length > 2) { // Jika ada lebih dari satu koma
                    str = parts[0] + ',' + parts.slice(1).join(''); // Gabungkan sisa bagian setelah koma pertama
                }
                return str; // Hasilnya seperti "1000000" atau "123,45"
            }


            // Delegated event for Barcode validation (blur event)
            $('#produkTable tbody').on('blur', 'input[name="barcode[]"]', function() {
                var barcodeInput = $(this); // Input barcode yang sedang di-blur
                var barcodeVal = barcodeInput.val().trim();
                // Temukan div error yang merupakan saudara (sibling) dari input ini
                var barcodeErrorDiv = barcodeInput.next('.barcode-error-message');
        
                // Reset tampilan error sebelum validasi baru
                barcodeInput.removeClass('is-invalid');
                barcodeErrorDiv.text('').hide();
        
                if (barcodeVal !== '') {
                    // Tambahkan penundaan kecil untuk memberi kesan "loading" jika diperlukan, atau indikator loading
                    // barcodeErrorDiv.text('Memeriksa...').show(); 
        
                    $.ajax({
                        url: 'ajax_check_barcode.php', // Pastikan URL ini benar
                        type: 'POST',
                        data: { barcode: barcodeVal },
                        dataType: 'json',
                        success: function(response) {
                            if (response.exists) {
                                barcodeInput.addClass('is-invalid'); // Tambahkan kelas untuk border merah
                                barcodeErrorDiv.text('Barcode ini sudah terdaftar.').show(); // Tampilkan pesan error
                            } else {
                                barcodeErrorDiv.text('').hide(); // Sembunyikan jika tidak ada error
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX Barcode Check Error:", textStatus, errorThrown);
                            barcodeErrorDiv.text('Error saat cek barcode.').show(); // Tampilkan pesan error umum
                        }
                    });
                }
            });
        
            // Delegated event to clear error when user starts typing again in a barcode field
            $('#produkTable tbody').on('input', 'input[name="barcode[]"]', function() {
                var barcodeInput = $(this);
                if (barcodeInput.hasClass('is-invalid')) {
                    barcodeInput.removeClass('is-invalid');
                    barcodeInput.next('.barcode-error-message').text('').hide();
                }
            });
            
            $('#produkTable tbody').on('click', '.generate-kode-item-icon', function() {
                var icon = $(this); // Ikon yang diklik
                var wrapper = icon.closest('.input-with-icon-wrapper'); // Wrapper div terdekat
                var kodeItemInput = wrapper.find('input[name="kode_item[]"]'); // Input kode_item di baris ini
                var kodeItemFeedback = wrapper.find('.kode-item-error-message'); // Div feedback di baris ini
        
                // Mencegah klik berulang jika sedang loading
                if (icon.hasClass('is-loading')) {
                    return;
                }
        
                // Mengatur UI untuk status loading (mirip dengan btn.prop('disabled', true).text('Generating...'))
                icon.addClass('is-loading'); // Ini akan memicu animasi putar CSS
                kodeItemInput.removeClass('is-invalid'); // Hapus kelas error sebelumnya
                kodeItemFeedback.text('').hide(); // Bersihkan dan sembunyikan feedback sebelumnya
        
                $.ajax({
                    url: 'ajax_generate_unique_kode_item.php', // Pastikan URL ini benar
                    type: 'GET', // Atau 'POST', sesuaikan dengan backend Anda
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.kode_item) {
                            kodeItemInput.val(response.kode_item);
                            // Jika Anda menggunakan localStorage dan ingin menyimpannya saat input berubah:
                            kodeItemInput.trigger('input'); // Memicu event input agar localStorage tersimpan
                        } else {
                            // Menampilkan pesan error (mirip dengan kodeItemFeedback.text(...).addClass('text-red-600'))
                            kodeItemInput.addClass('is-invalid'); // Tambahkan kelas untuk border merah
                            kodeItemFeedback.text(response.message || 'Gagal men-generate kode item.').show();
                            kodeItemInput.val(''); // Kosongkan input jika gagal
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Generate Kode Item Error:", textStatus, errorThrown);
                        // Menampilkan pesan error server (mirip dengan kodeItemFeedback.text(...).addClass('text-red-600'))
                        kodeItemInput.addClass('is-invalid');
                        kodeItemFeedback.text('Terjadi kesalahan saat menghubungi server.').show();
                        kodeItemInput.val('');
                    },
                    complete: function() {
                        // Mengembalikan UI ke status normal (mirip dengan btn.prop('disabled', false).text('Generate'))
                        icon.removeClass('is-loading'); // Hapus kelas loading, menghentikan animasi
                    }
                });
            });
        
            // Opsional: Membersihkan error kode_item ketika pengguna mulai mengetik di field tersebut
            $('#produkTable tbody').on('input', 'input[name="kode_item[]"]', function() {
                var kodeItemInput = $(this);
                if (kodeItemInput.hasClass('is-invalid')) {
                    var wrapper = kodeItemInput.closest('.input-with-icon-wrapper');
                    var kodeItemFeedback = wrapper.find('.kode-item-error-message');
        
                    kodeItemInput.removeClass('is-invalid');
                    kodeItemFeedback.text('').hide();
                }
            });
        });
// User's original tambahBaris and hapusBaris functions (with minor enhancement for specificity)
function tambahBaris() {
    const tbody = document.getElementById("tableBody");
    const templateRow = tbody.querySelector("tr"); // Get the first row as a template

    if (!templateRow) {
        console.error("Template row not found in tableBody for tambahBaris.");
        return;
    }
    const newRow = templateRow.cloneNode(true);

    // Clear values and reset specific fields in the new cloned row
    newRow.querySelectorAll("input, select, textarea").forEach(el => {
        const nameAttr = el.getAttribute('name');
        if (el.tagName === 'SELECT') {
            el.selectedIndex = 0; // Reset select to the first option (e.g., "Pilih")
        } else if (nameAttr === 'reason[]') { // Target reason textarea specifically
            el.value = 'Penambahan produk baru';
        } else {
            el.value = ''; // Clear other input/textarea fields
        }

        // If flatpickr was cloned, destroy the old instance to avoid conflicts
        if (el._flatpickr && nameAttr === 'expire_date[]') {
            el._flatpickr.destroy();
        }
    });

    tbody.appendChild(newRow);
}

function hapusBaris(button) {
    const row = button.closest("tr");
    const tbody = row.parentNode;
    if (tbody.rows.length > 1) {
        tbody.removeChild(row);
    } else {
        alert("Minimal satu baris harus ada.");
    }
}


// Comprehensive localStorage and enhancement script
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('tambahBarangForm');
    const tableBody = document.getElementById('tableBody');
    const localStorageKey = 'tambahBarangFormData';

    // Define the names of the input fields within each row
    // Ensure these match the 'name' attributes (without '[]') in your table rows
    const rowFieldNames = [
        "kode_item", "barcode", "nama_produk", "category_id", "unit",
        "minimum_stock",
        "stock_quantity", // Make sure you have <input name="stock_quantity[]"...> in your rows
        "cost_price", "price", "batch_number", "expire_date",
        "posisi",         // Make sure you have <input name="posisi[]"...> in your rows
        "reason"
    ];
    
    // Fungsi untuk memformat harga pada sebuah row
    function initPriceFormatting(rowElement) {
        $(rowElement).find('input[name="cost_price[]"], input[name="price[]"]').each(function() {
            if (this.value && !this.dataset.numericValue) {
                // Gunakan jQuery untuk memanggil event handler formatRupiah 
                $(this).trigger('input');
            }
        });
    }


    function saveTableData() {
        const rowsData = [];
        const rows = tableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const rowData = {};
            let rowHasMeaningfulData = false;
            rowFieldNames.forEach(fieldName => {
                const input = row.querySelector(`[name="${fieldName}[]"]`);
                if (input) {
                    rowData[fieldName] = input.value;
                    if (input.value.trim() !== '') {
                        if (fieldName === 'reason' && input.value.trim() === 'Penambahan produk baru' && rows.length > 1) {
                            // Don't count default reason as meaningful if other fields are empty and it's not the only row
                        } else {
                            rowHasMeaningfulData = true;
                        }
                    }
                }
            });
            if (rowHasMeaningfulData || (rows.length === 1 && Object.keys(rowData).length > 0)) {
                rowsData.push(rowData);
            }
        });

        if (rowsData.length > 0) {
            localStorage.setItem(localStorageKey, JSON.stringify(rowsData));
        } else {
            localStorage.removeItem(localStorageKey);
        }
    }

    function initFlatpickrForRow(rowElement) {
        const expireInput = rowElement.querySelector('input[name="expire_date1[]"]');
        if (expireInput && typeof flatpickr === "function") {
            if (expireInput._flatpickr) { // Destroy existing instance if any (e.g. from cloning)
                expireInput._flatpickr.destroy();
            }
            flatpickr(expireInput, { dateFormat: "d-m-Y", locale: "id", allowInput: true });
        }
    }

    function loadTableData() {
        const savedData = localStorage.getItem(localStorageKey);
        const firstRow = tableBody.querySelector('tr'); // The template row

        if (!firstRow) {
            console.error("Initial template row is missing from tableBody.");
            return;
        }

        if (!savedData) {
            initFlatpickrForRow(firstRow); // Init for the default first row if no saved data
            return;
        }

        const rowsData = JSON.parse(savedData);
        if (rowsData.length === 0) {
            initFlatpickrForRow(firstRow);
            return;
        }

        // Remove all rows except the template row if it's not part of saved data structure
        // Or, more simply, clear all rows and repopulate from template
        while (tableBody.rows.length > 0) {
            tableBody.deleteRow(0);
        }


        rowsData.forEach((rowData, index) => {
            const newClonedRow = firstRow.cloneNode(true); // Clone the original template row
             newClonedRow.querySelectorAll("input, select, textarea").forEach(el => { // Clear it first
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else if (el.getAttribute('name') === 'reason[]') el.value = 'Penambahan produk baru';
                else el.value = '';
                 if (el._flatpickr) el._flatpickr.destroy(); // Clean up cloned flatpickr
            });

            rowFieldNames.forEach(fieldName => {
                const input = newClonedRow.querySelector(`[name="${fieldName}[]"]`);
                if (input && rowData[fieldName] !== undefined) {
                    input.value = rowData[fieldName];
                }
            });
            tableBody.appendChild(newClonedRow);
            initFlatpickrForRow(newClonedRow);
        });

        // If table is empty after load (e.g. rowsData was empty array), add back one template row
        if (tableBody.rows.length === 0) {
            const freshTemplateRow = firstRow.cloneNode(true);
            freshTemplateRow.querySelectorAll("input, select, textarea").forEach(el => {
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else if (el.getAttribute('name') === 'reason[]') el.value = 'Penambahan produk baru';
                else el.value = '';
                if (el._flatpickr) el._flatpickr.destroy();
            });
            tableBody.appendChild(freshTemplateRow);
            initFlatpickrForRow(freshTemplateRow);
        }
    }

    // Load data when the page loads
    loadTableData();
    Array.from(tableBody.querySelectorAll('tr')).forEach(row => {
        initPriceFormatting(row);
    });

    // Save data whenever an input changes in the table body
    tableBody.addEventListener('input', function(event) {
        if (event.target.closest('tr') && (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA')) {
            saveTableData();
        }
    });
    tableBody.addEventListener('change', function(event) {
        if (event.target.closest('tr') && event.target.tagName === 'SELECT') {
            saveTableData();
        }
    });

    if (form) {
        form.addEventListener('submit', function() {
            localStorage.removeItem(localStorageKey);
        });
    }

    // --- Integration Point: Wrap user's functions ---
    const originalTambahBaris = window.tambahBaris;
    window.tambahBaris = function() {
        if (typeof originalTambahBaris === 'function') {
            originalTambahBaris.apply(this, arguments); // Call user's tambahBaris
            const newRowElement = tableBody.rows[tableBody.rows.length - 1];
            if (newRowElement) {
                initFlatpickrForRow(newRowElement); // Initialize flatpickr for the new row
                initPriceFormatting(newRowElement); // Format harga untuk baris baru
            }
            saveTableData(); // Save state after adding row
        } else {
            console.error("Original tambahBaris function not found.");
        }
    };

    const originalHapusBaris = window.hapusBaris;
    window.hapusBaris = function(button) {
        if (typeof originalHapusBaris === 'function') {
            originalHapusBaris.apply(this, arguments); // Call user's hapusBaris
            saveTableData(); // Save state after deleting row
        } else {
            console.error("Original hapusBaris function not found.");
        }
    };
});
