$(document).ready(function() {
    
    // Fungsi untuk memformat angka menjadi format ribuan dengan titik dan langsung menambahkan ",00"
    function formatRupiah(input) {
        var originalCursorPos = input.selectionStart; // Simpan posisi kursor awal
        var originalValue = input.value;

        // 1. Ambil bagian integer: hapus format ribuan lama dan bagian desimal apa pun
        var numericString = originalValue.split(',')[0].replace(/\./g, '').replace(/[^\d]/g, '');

        // Jika setelah dibersihkan jadi string kosong, dan nilai awal tidak kosong (misal input "abc"),
        // atau jika nilai awal hanya ",", set numericString jadi kosong agar input.value jadi ""
        if (numericString === "" && originalValue.trim() !== "" && !/^\d/.test(originalValue.trim())) {
            // Contoh: input "abc", atau ",123" atau "."
            input.value = "";
            input.dataset.numericValue = "";
            return;
        }
        
        input.dataset.numericValue = numericString; // Simpan nilai integer murni, misal "10000"

        // 2. Format bagian integer dengan titik ribuan
        var formattedInteger = "";
        if (numericString.length > 0) {
            var remainder = numericString.length % 3;
            var rupiah = numericString.substr(0, remainder);
            var thousands = numericString.substr(remainder).match(/\d{3}/g);
            
            if (rupiah.length > 0) {
                formattedInteger += rupiah + (thousands ? '.' : '');
            }
            if (thousands) {
                formattedInteger += thousands.join('.');
            }
        } else { 
            // Jika numericString kosong (misal pengguna menghapus semua angka)
            input.value = ""; // Tampilkan input kosong
            return;
        }

        // 3. Tambahkan ',00'
        var finalValue = formattedInteger + ',00';
        input.value = finalValue;

        // 4. Atur posisi kursor: tepat sebelum ',00'
        if (document.activeElement === input) {
            var newCursorPos = formattedInteger.length;
            
            // Jika input menjadi kosong setelah semua proses (seharusnya sudah dihandle di atas)
            if (finalValue === ",00" && numericString === "") { // Kasus dari input kosong jadi ",00"
                input.value = "";
                newCursorPos = 0;
            }
            input.setSelectionRange(newCursorPos, newCursorPos);
        }
    }
    
    
    // Event delegation untuk format harga (memanggil formatRupiah yang baru)
    $('#produkTable tbody').on('input', 'input[name="cost_price[]"], input[name="price[]"]', function() {
        formatRupiah(this);
    });

    // HAPUS BLOK LISTENER 'blur' UNTUK HARGA DI SINI, KARENA TIDAK DIPERLUKAN LAGI
    // $('#produkTable tbody').on('blur', 'input[name="cost_price[]"], input[name="price[]"]', function() { ... });
    
    // Reset nilai ke numerik saat form disubmit (TETAP SAMA)
    $('#tambahBarangForm').on('submit', function(event) {
        event.preventDefault(); 

        const form = this;
        const submitButton = $(form).find('button[type="submit"]');
        const originalButtonText = submitButton.html();

        Swal.fire({
            title: 'Konfirmasi Penyimpanan',
            text: "Apakah Anda yakin ingin menyimpan data produk ini?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Simpan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $(form).find('input[name="cost_price[]"], input[name="price[]"]').each(function() {
                    var numericVal = this.dataset.numericValue;
                    if (numericVal && numericVal.trim() !== "") {
                        this.value = numericVal + ".00";
                    } else if (numericVal === "0") {
                        this.value = "0.00";
                    } else {
                        this.value = "0.00";
                    }
                });

                const formData = new FormData(form);
                submitButton.html('Menyimpan... <i class="ri-loader-4-line animate-spin"></i>').prop('disabled', true);

                $.ajax({
                    url: '../prosesdata/process_tambah_barang.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: response.message || 'Produk berhasil ditambahkan.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => { // Setelah notifikasi sukses ditutup
                                localStorage.removeItem('tambahBarangFormData');
                                if (response.redirect_url) {
                                    window.location.href = response.redirect_url; // Arahkan ke beli.php
                                } else {
                                window.location.reload(); // <<< BARIS INI AKAN ME-RELOAD HALAMAN
                                }
                            });
                        } else { 
                            Swal.fire({
                                title: 'Gagal!',
                                html: response.message || 'Terjadi kesalahan saat menambahkan produk.',
                                icon: 'error',
                                confirmButtonText: 'Coba Lagi'
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Submit Error:", textStatus, errorThrown);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Tidak dapat terhubung ke server atau terjadi kesalahan lainnya saat mengirim data.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function() {
                        // Tombol akan kembali normal setelah reload, jadi baris ini mungkin tidak
                        // terlihat efeknya jika reload terjadi cepat. Tapi tidak masalah untuk tetap ada.
                        submitButton.html(originalButtonText).prop('disabled', false);
                    }
                });
            }
        });
    });
    
    // MODIFIED: Fungsi untuk memformat semua input harga dalam sebuah row (saat load/tambah baris)
    // Sekarang hanya memanggil formatRupiah yang sudah menangani semuanya.
    function applyPriceFormatting(row) {
        $(row).find('input[name="cost_price[]"], input[name="price[]"]').each(function() {
            let currentValue = this.value.trim();
            if (currentValue !== "") {
                // Nilai awal bisa "10000.00" (server), "10.000,00" (localStorage), atau "10000"
                // formatRupiah yang baru akan menangani ini untuk menghasilkan "XX.XXX,00"
                
                // Kita perlu memastikan formatRupiah menerima nilai yang bisa diparsingnya dengan benar
                // untuk mengambil bagian integer.
                let valueForFormatter = currentValue;
                if (currentValue.includes('.') && !currentValue.includes(',')) { // Cek jika format "10000.00"
                    // Jika ada titik tapi tidak ada koma, bisa jadi format server XXXXX.XX
                    // formatRupiah akan mengambil bagian sebelum koma (jika ada) atau seluruh string jika tidak ada koma
                    // lalu membersihkan non-digit.
                    // Tidak perlu pra-proses khusus jika formatRupiah sudah robust.
                }
                this.value = valueForFormatter; // Set nilai agar formatRupiah bisa memprosesnya
                formatRupiah(this); // Panggil formatRupiah yang sudah dimodifikasi
            } else {
                this.value = ''; 
                delete this.dataset.numericValue;
            }
        });
    }
    
    // Format harga untuk semua baris yang ada saat halaman dimuat (TETAP SAMA)
    $('#tableBody tr').each(function() {
        applyPriceFormatting(this); 
    });

    // ... (SISA KODE ANDA: cleanToNumericString, validasi barcode, generate kode item, dll. TETAP SAMA) ...
            function cleanToNumericString(value) {
                if (value === null || value === undefined) return '';
                let str = String(value);
                str = str.replace(/[^\d,]/g, "");
                let parts = str.split(',');
                if (parts.length > 2) { 
                    str = parts[0] + ',' + parts.slice(1).join(''); 
                }
                return str; 
            }

            $('#produkTable tbody').on('blur', 'input[name="barcode[]"]', function() {
                var barcodeInput = $(this); 
                var barcodeVal = barcodeInput.val().trim();
                var barcodeErrorDiv = barcodeInput.next('.barcode-error-message');
                barcodeInput.removeClass('is-invalid');
                barcodeErrorDiv.text('').hide();
                if (barcodeVal !== '') {
                    $.ajax({
                        url: 'ajax_check_barcode.php', 
                        type: 'POST',
                        data: { barcode: barcodeVal },
                        dataType: 'json',
                        success: function(response) {
                            if (response.exists) {
                                barcodeInput.addClass('is-invalid'); 
                                barcodeErrorDiv.text('Barcode ini sudah terdaftar.').show(); 
                            } else {
                                barcodeErrorDiv.text('').hide(); 
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("AJAX Barcode Check Error:", textStatus, errorThrown);
                            barcodeErrorDiv.text('Error saat cek barcode.').show(); 
                        }
                    });
                }
            });
        
            $('#produkTable tbody').on('input', 'input[name="barcode[]"]', function() {
                var barcodeInput = $(this);
                if (barcodeInput.hasClass('is-invalid')) {
                    barcodeInput.removeClass('is-invalid');
                    barcodeInput.next('.barcode-error-message').text('').hide();
                }
            });
            
            $('#produkTable tbody').on('click', '.generate-kode-item-icon', function() {
                var icon = $(this); 
                var wrapper = icon.closest('.input-with-icon-wrapper'); 
                var kodeItemInput = wrapper.find('input[name="kode_item[]"]'); 
                var kodeItemFeedback = wrapper.find('.kode-item-error-message'); 
                if (icon.hasClass('is-loading')) {
                    return;
                }
                icon.addClass('is-loading'); 
                kodeItemInput.removeClass('is-invalid'); 
                kodeItemFeedback.text('').hide(); 
                $.ajax({
                    url: 'ajax_generate_unique_kode_item.php', 
                    type: 'GET', 
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.kode_item) {
                            kodeItemInput.val(response.kode_item);
                            kodeItemInput.trigger('input'); 
                        } else {
                            kodeItemInput.addClass('is-invalid'); 
                            kodeItemFeedback.text(response.message || 'Gagal men-generate kode item.').show();
                            kodeItemInput.val(''); 
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX Generate Kode Item Error:", textStatus, errorThrown);
                        kodeItemInput.addClass('is-invalid');
                        kodeItemFeedback.text('Terjadi kesalahan saat menghubungi server.').show();
                        kodeItemInput.val('');
                    },
                    complete: function() {
                        icon.removeClass('is-loading'); 
                    }
                });
            });
        
            $('#produkTable tbody').on('input', 'input[name="kode_item[]"]', function() {
                var kodeItemInput = $(this);
                if (kodeItemInput.hasClass('is-invalid')) {
                    var wrapper = kodeItemInput.closest('.input-with-icon-wrapper');
                    var kodeItemFeedback = wrapper.find('.kode-item-error-message');
                    kodeItemInput.removeClass('is-invalid');
                    kodeItemFeedback.text('').hide();
                }
            });
}); // Akhir $(document).ready


// --- BAGIAN localStorage dan fungsi global Anda ---
// User's original tambahBaris and hapusBaris functions (TETAP SAMA)
function tambahBaris() {
    const tbody = document.getElementById("tableBody");
    const templateRow = tbody.querySelector("tr"); 
    if (!templateRow) {
        console.error("Template row not found in tableBody for tambahBaris.");
        return;
    }
    const newRow = templateRow.cloneNode(true);
    newRow.querySelectorAll("input, select, textarea").forEach(el => {
        const nameAttr = el.getAttribute('name');
        if (el.tagName === 'SELECT') {
            el.selectedIndex = 0; 
        } else if (nameAttr === 'reason[]') { 
            el.value = 'Penambahan produk baru';
        } else {
            el.value = ''; 
        }
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

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('tambahBarangForm');
    const tableBody = document.getElementById('tableBody');
    const localStorageKey = 'tambahBarangFormData';

    const rowFieldNames = [
        "kode_item", "barcode", "nama_produk", "category_id", "unit",
        "minimum_stock", "stock_quantity", 
        "cost_price", "price", "batch_number", "expire_date",
        "posisi", "reason"
    ];
    
    // MODIFIED: initPriceFormatting sekarang hanya trigger 'input'
    function initPriceFormatting(rowElement) {
        $(rowElement).find('input[name="cost_price[]"], input[name="price[]"]').each(function() {
            let currentValue = this.value.trim();
            if (currentValue !== "") {
                // Trigger 'input' akan memanggil formatRupiah yang baru,
                // yang akan menghasilkan format "XX.XXX,00"
                $(this).trigger('input'); 
            } else {
                this.value = '';
                delete this.dataset.numericValue;
            }
        });
    }

    function saveTableData() { // (TETAP SAMA)
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
                            //
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

    function initFlatpickrForRow(rowElement) { // (TETAP SAMA, pastikan selector 'expire_date1[]' benar)
        const expireInput = rowElement.querySelector('input[name="expire_date1[]"]'); 
        if (expireInput && typeof flatpickr === "function") {
            if (expireInput._flatpickr) { 
                expireInput._flatpickr.destroy();
            }
            flatpickr(expireInput, { dateFormat: "d-m-Y", locale: "id", allowInput: true });
        }
    }

    function loadTableData() { // (Logika load tetap sama, tapi initPriceFormatting akan berperilaku baru)
        const savedData = localStorage.getItem(localStorageKey);
        const firstRow = tableBody.querySelector('tr'); 

        if (!firstRow && tableBody.rows.length === 0) {
             console.error("Initial template row is missing and table is empty.");
             return;
        }
        
        if (!savedData && tableBody.rows.length > 0) {
            Array.from(tableBody.rows).forEach(existingRow => {
                initFlatpickrForRow(existingRow);
                initPriceFormatting(existingRow); 
            });
            return; 
        }
        
        if (!savedData) return; 

        const rowsData = JSON.parse(savedData);
        if (rowsData.length === 0 && tableBody.rows.length > 0) { 
             Array.from(tableBody.rows).forEach(existingRow => {
                initFlatpickrForRow(existingRow);
                initPriceFormatting(existingRow);
            });
            return;
        }
        if (rowsData.length === 0) return;

        while (tableBody.rows.length > 0) {
            tableBody.deleteRow(0);
        }

        rowsData.forEach((rowData) => {
            const newClonedRow = firstRow.cloneNode(true); 
             newClonedRow.querySelectorAll("input, select, textarea").forEach(el => { 
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else if (el.getAttribute('name') === 'reason[]') el.value = 'Penambahan produk baru';
                else el.value = '';
                 if (el._flatpickr) el._flatpickr.destroy(); 
            });

            rowFieldNames.forEach(fieldName => {
                const input = newClonedRow.querySelector(`[name="${fieldName}[]"]`);
                if (input && rowData[fieldName] !== undefined) {
                    input.value = rowData[fieldName]; 
                }
            });
            tableBody.appendChild(newClonedRow);
            initFlatpickrForRow(newClonedRow);
            initPriceFormatting(newClonedRow); // Panggil initPriceFormatting yang baru
        });

        if (tableBody.rows.length === 0 && firstRow) { 
            const freshTemplateRow = firstRow.cloneNode(true);
            freshTemplateRow.querySelectorAll("input, select, textarea").forEach(el => { /* clear */ });
            tableBody.appendChild(freshTemplateRow);
            initFlatpickrForRow(freshTemplateRow);
            initPriceFormatting(freshTemplateRow);
        }
    }

    if (tableBody) { 
      loadTableData();
    }

    if (tableBody) { 
        tableBody.addEventListener('input', function(event) {
            if (event.target.closest('tr') && (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA')) {
                if (!$(event.target).is('input[name="cost_price[]"], input[name="price[]"]')) {
                     // Hanya panggil saveTableData jika bukan input harga,
                     // karena formatRupiah pada input harga sudah memicu perubahan yang mungkin disimpan.
                     // Namun, untuk konsistensi, bisa tetap dipanggil.
                }
                saveTableData();
            }
        });
        tableBody.addEventListener('change', function(event) { 
            if (event.target.closest('tr') && event.target.tagName === 'SELECT') {
                saveTableData();
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function() {
            localStorage.removeItem(localStorageKey);
        });
    }

    const originalTambahBaris = window.tambahBaris;
    window.tambahBaris = function() {
        if (typeof originalTambahBaris === 'function') {
            originalTambahBaris.apply(this, arguments); 
            const newRowElement = tableBody.rows[tableBody.rows.length - 1];
            if (newRowElement) {
                initFlatpickrForRow(newRowElement); 
                initPriceFormatting(newRowElement); // Panggil initPriceFormatting yang baru
            }
            saveTableData(); 
        } else {
            console.error("Original tambahBaris function not found.");
        }
    };

    const originalHapusBaris = window.hapusBaris;
    window.hapusBaris = function(button) {
        if (typeof originalHapusBaris === 'function') {
            originalHapusBaris.apply(this, arguments); 
            saveTableData(); 
        } else {
            console.error("Original hapusBaris function not found.");
        }
    };
});
