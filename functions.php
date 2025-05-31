<?php

session_start();
date_default_timezone_set('Asia/Jakarta');
/**
 * Functions.php - File kelas utama untuk POS Apotek
 *
 * File ini berisi kelas utama yang mencakup semua fungsi dasar yang digunakan dalam aplikasi POS Apotek
 * termasuk koneksi database dan operasi CRUD dasar.
 *
 * @version 1.0.0
 * @date 2025-04-20
 */

class Farmamedika
{
    private $pdo;
    //private $logout = __DIR__ . "/filtered";
    public $logout = "../logout.php";

    /**
     * Konstruktur untuk menginisialisasi koneksi database
     */
    public function __construct()
    {
        $this->connectDatabase();
    }

    /**
     * Fungsi koneksi ke database
     * @return void
     */
    private function connectDatabase()
    {
        // Konfigurasi database
        $host = 'localhost';
        $dbname = 'farmamedika';
        $username = 'farmamedika';
        $password = 'farmamedika2025';

        try {
            // Membuat koneksi PDO
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error
            error_log("Database Connection Error: " . $e->getMessage());
            $this->pdo = null;
        }
    }

    /**
     * Mendapatkan instance PDO
     * @return PDO|null Koneksi PDO atau null jika gagal
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * Fungsi untuk menangani error
     * @param string $message Pesan error
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function handleError($message, $statusCode = 500)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode([
            "success" => false,
            "message" => $message,
        ]);
        exit();
    }
    
    public function getOutstandingInvoicesBySupplier(string $supplier_name): array {
        $sql = "SELECT 
                    purchase_id, 
                    total_amount, 
                    amount_already_paid, 
                    payment_status, -- Ambil juga status saat ini jika diperlukan untuk logika
                    due_date, 
                    purchase_date
                FROM 
                    purchases
                WHERE 
                    supplier_name = :supplier_name 
                    AND amount_already_paid < total_amount -- Kondisi utama: belum lunas
                ORDER BY 
                    due_date ASC, 
                    purchase_date ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':supplier_name', $supplier_name, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Handle error, misalnya log error atau throw exception lagi
            // error_log("Error fetching outstanding invoices for {$supplier_name}: " . $e->getMessage());
            // Untuk contoh ini, kita kembalikan array kosong jika ada error
            return [];
        }
    }
    
    public function getProductsByPosisiLike($posisiKeyword)
    {
        if (!$this->pdo) {
            error_log("getProductsByPosisiLike Error: No valid PDO connection.");
            return [];
        }
        try {
            $query = "SELECT product_id, kode_item, product_name, posisi, unit, stock_quantity 
                      FROM products 
                      WHERE posisi LIKE :posisi_keyword AND is_active = 1
                      ORDER BY product_name ASC";
            $stmt = $this->pdo->prepare($query);
            $keyword = "%" . trim($posisiKeyword) . "%";
            $stmt->bindParam(':posisi_keyword', $keyword, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error (getProductsByPosisiLike): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengupdate jumlah yang sudah dibayar dan status pembayaran untuk faktur tertentu.
     *
     * @param int $invoice_id ID faktur pembelian yang akan diupdate.
     * @param float $new_total_paid_for_invoice Jumlah total baru yang sudah dibayarkan untuk faktur ini.
     * @param string $new_invoice_status Status pembayaran baru ('hutang', 'cicil', 'lunas').
     * @return bool True jika update berhasil, false jika gagal.
     */
    public function updateInvoicePayment(int $invoice_id, float $new_total_paid_for_invoice, string $new_invoice_status): bool {
        // Validasi status pembayaran (opsional, tapi baik untuk dilakukan)
        $valid_statuses = ['hutang', 'cicil', 'lunas'];
        if (!in_array($new_invoice_status, $valid_statuses)) {
            // error_log("Invalid payment status '{$new_invoice_status}' for invoice ID {$invoice_id}.");
            return false;
        }

        // Sebelum update, kita bisa juga memastikan status baru konsisten dengan jumlah pembayaran
        // (Ini bisa juga dilakukan di logika bisnis sebelum memanggil metode ini)
        // Misalnya, jika new_total_paid_for_invoice >= total_amount, maka new_invoice_status HARUS 'lunas'.
        // Untuk contoh ini, kita percaya status yang diberikan sudah benar.

        $sql = "UPDATE purchases 
                SET 
                    amount_already_paid = :amount_already_paid, 
                    payment_status = :payment_status 
                WHERE 
                    purchase_id = :purchase_id";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':amount_already_paid', $new_total_paid_for_invoice, PDO::PARAM_STR); // PDO::PARAM_STR cocok untuk DECIMAL/FLOAT juga
            $stmt->bindParam(':payment_status', $new_invoice_status, PDO::PARAM_STR);
            $stmt->bindParam(':purchase_id', $invoice_id, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Cek apakah ada baris yang terpengaruh (berhasil diupdate)
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Handle error
            // error_log("Error updating invoice ID {$invoice_id}: " . $e->getMessage());
            return false;
        }
    }

    // Anda mungkin memerlukan metode untuk mendapatkan total_amount faktur jika ingin
    // menghitung status secara dinamis di dalam updateInvoicePayment
    public function getInvoiceTotalAmount(int $invoice_id): ?float {
        $sql = "SELECT total_amount FROM purchases WHERE purchase_id = :purchase_id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':purchase_id', $invoice_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (float)$result['total_amount'] : null;
        } catch (PDOException $e) {
            // error_log("Error fetching total amount for invoice ID {$invoice_id}: " . $e->getMessage());
            return null;
        }
    }
    
        /**
     * Melakukan stok opname untuk satu produk tertentu
     * 
     * @param int $product_id ID produk yang akan di-opname
     * @param float $actual_stock Jumlah stok aktual setelah penghitungan fisik
     * @param int $user_id ID pengguna yang melakukan stok opname
     * @param string $reason Alasan dilakukannya penyesuaian stok (opsional)
     * @param string $related_transaction_id ID transaksi terkait (opsional)
     * @return array Hasil operasi (success, message, dll)
     */
    public function performStockOpname($product_id, $actual_stock, $user_id, $reason = "Stok Opname Rutin", $related_transaction_id = null)
    {
        if (!$this->pdo) {
            error_log("performStockOpname Error: No valid PDO connection.");
            return ['success' => false, 'message' => 'Koneksi database gagal.'];
        }
    
        try {
            // Begin transaction
            $this->pdo->beginTransaction();
    
            // Get current stock from products table
            $stmt = $this->pdo->prepare("SELECT product_id, product_name, stock_quantity FROM products WHERE product_id = :product_id FOR UPDATE");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$product) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Produk tidak ditemukan.'];
            }
    
            $current_stock = (float)$product['stock_quantity'];
            $difference = (float)$actual_stock - $current_stock;
    
            // Jika tidak ada perbedaan, tidak perlu penyesuaian
            if ($difference == 0) {
                $this->pdo->rollBack();
                return [
                    'success' => true, 
                    'message' => 'Stok fisik sesuai dengan stok sistem, tidak ada penyesuaian yang diperlukan.',
                    'product_name' => $product['product_name'],
                    'system_stock' => $current_stock,
                    'actual_stock' => $actual_stock,
                    'difference' => $difference
                ];
            }
    
            // Tentukan jenis pergerakan berdasarkan perbedaan
            $movement_type = $difference > 0 ? 'penyesuaian_stok' : 'penyesuaian_stok';
            $abs_difference = abs($difference);
            
            // Catat pergerakan stok
            $stmt = $this->pdo->prepare("INSERT INTO stock_movements 
                                        (product_id, current_stock_before_movement, movement_type, 
                                         quantity_changed, current_stock_after_movement, user_id, 
                                         reason, related_transaction_id)
                                        VALUES 
                                        (:product_id, :current_stock_before, :movement_type, 
                                         :quantity_changed, :current_stock_after, :user_id, 
                                         :reason, :related_transaction_id)");
            
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':current_stock_before', $current_stock);
            $stmt->bindParam(':movement_type', $movement_type);
            $stmt->bindParam(':quantity_changed', $difference);
            $stmt->bindParam(':current_stock_after', $actual_stock);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':related_transaction_id', $related_transaction_id);
            $stmt->execute();
    
            // Update stok produk
            $stmt = $this->pdo->prepare("UPDATE products SET stock_quantity = :new_stock WHERE product_id = :product_id");
            $stmt->bindParam(':new_stock', $actual_stock);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
    
            // Log aktivitas
            $adjustmentType = $difference > 0 ? "penambahan" : "pengurangan";
            $logMessage = "Stok opname: {$adjustmentType} {$abs_difference} untuk produk {$product['product_name']} (ID: {$product_id}). Stok sistem: {$current_stock}, stok aktual: {$actual_stock}";
            $this->logActivity($user_id, 'STOCK_OPNAME', $logMessage);
    
            $this->pdo->commit();
    
            return [
                'success' => true,
                'message' => "Stok opname berhasil. {$adjustmentType} stok sebanyak {$abs_difference}.",
                'product_name' => $product['product_name'],
                'system_stock' => $current_stock,
                'actual_stock' => $actual_stock,
                'difference' => $difference
            ];
    
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("PDO Error (performStockOpname): " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal melakukan stok opname: ' . $e->getMessage()];
        }
    }
    
    /**
     * Melakukan stok opname secara batch untuk multiple produk
     * 
     * @param array $stockOpnameData Array berisi data stok opname: [['product_id' => X, 'actual_stock' => Y], ...]
     * @param int $user_id ID pengguna yang melakukan stok opname
     * @param string $reason Alasan dilakukannya penyesuaian stok (opsional)
     * @return array Hasil operasi (success, results)
     */
    public function performBatchStockOpname($stockOpnameData, $user_id, $reason = "Stok Opname Rutin")
    {
        if (!$this->pdo) {
            error_log("performBatchStockOpname Error: No valid PDO connection.");
            return ['success' => false, 'message' => 'Koneksi database gagal.'];
        }
        
        $results = [
            'success' => true,
            'total_items' => count($stockOpnameData),
            'adjusted_items' => 0,
            'accurate_items' => 0,
            'failed_items' => 0,
            'details' => []
        ];
    
        try {
            // Begin transaction for all operations
            $this->pdo->beginTransaction();
            
            $opnameDate = date('Y-m-d H:i:s');
            $related_transaction_id = "BATCH_OPNAME_" . date('YmdHis');
            
            foreach ($stockOpnameData as $item) {
                if (!isset($item['product_id']) || !isset($item['actual_stock'])) {
                    $results['failed_items']++;
                    $results['details'][] = [
                        'product_id' => $item['product_id'] ?? 'unknown',
                        'success' => false,
                        'message' => 'Data produk tidak lengkap.'
                    ];
                    continue;
                }
                
                $itemResult = $this->performStockOpname(
                    $item['product_id'],
                    $item['actual_stock'],
                    $user_id,
                    $reason,
                    $related_transaction_id
                );
                
                if ($itemResult['success']) {
                    if (isset($itemResult['difference']) && $itemResult['difference'] != 0) {
                        $results['adjusted_items']++;
                    } else {
                        $results['accurate_items']++;
                    }
                } else {
                    $results['failed_items']++;
                }
                
                $results['details'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $itemResult['product_name'] ?? null,
                    'system_stock' => $itemResult['system_stock'] ?? null,
                    'actual_stock' => $itemResult['actual_stock'] ?? null,
                    'difference' => $itemResult['difference'] ?? null,
                    'success' => $itemResult['success'],
                    'message' => $itemResult['message']
                ];
            }
            
            $this->pdo->commit();
            
            // Log aktivitas batch
            $logMessage = "Stok opname batch: {$results['adjusted_items']} item disesuaikan, {$results['accurate_items']} item akurat, {$results['failed_items']} item gagal.";
            $this->logActivity($user_id, 'BATCH_STOCK_OPNAME', $logMessage);
            
            $results['message'] = "Stok opname batch selesai. {$results['adjusted_items']} item disesuaikan, {$results['accurate_items']} item akurat, {$results['failed_items']} item gagal.";
            return $results;
            
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("PDO Error (performBatchStockOpname): " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal melakukan stok opname batch: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mendapatkan riwayat stok opname dalam periode tertentu
     * 
     * @param string $startDate Tanggal mulai (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir (YYYY-MM-DD)
     * @return array Riwayat stok opname
     */
    public function getStockOpnameHistory($startDate = null, $endDate = null)
    {
        if (!$this->pdo) {
            error_log("getStockOpnameHistory Error: No valid PDO connection.");
            return [];
        }
        
        try {
            $query = "SELECT sm.*, p.product_name, p.kode_item, u.name as user_name
                      FROM stock_movements sm
                      JOIN products p ON sm.product_id = p.product_id
                      LEFT JOIN users u ON sm.user_id = u.user_id
                      WHERE sm.movement_type = 'penyesuaian_stok'";
            
            $params = [];
            
            if ($startDate && $endDate) {
                $query .= " AND DATE(sm.movement_date) BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date'] = $endDate;
            } elseif ($startDate) {
                $query .= " AND DATE(sm.movement_date) >= :start_date";
                $params[':start_date'] = $startDate;
            } elseif ($endDate) {
                $query .= " AND DATE(sm.movement_date) <= :end_date";
                $params[':end_date'] = $endDate;
            }
            
            $query .= " ORDER BY sm.movement_date DESC";
            
            $stmt = $this->pdo->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Database Error (getStockOpnameHistory): " . $e->getMessage());
            return [];
        }
    }


    /**
     * Mengupdate jumlah yang sudah dibayar untuk faktur tertentu.
     * Status pembayaran akan dihitung ulang berdasarkan jumlah pembayaran baru dan total faktur.
     *
     * @param int $invoice_id ID faktur pembelian yang akan diupdate.
     * @param float $new_total_paid_for_invoice Jumlah total baru yang sudah dibayarkan untuk faktur ini.
     * @return bool True jika update berhasil, false jika gagal.
     */
    public function updateInvoicePaymentAndRecalculateStatus(int $invoice_id, float $new_total_paid_for_invoice): bool {
        $total_amount = $this->getInvoiceTotalAmount($invoice_id);

        if ($total_amount === null) {
            // error_log("Could not retrieve total amount for invoice ID {$invoice_id} to recalculate status.");
            return false; // Gagal mendapatkan total amount, tidak bisa lanjut
        }

        $new_invoice_status = '';
        if ($new_total_paid_for_invoice >= $total_amount) {
            $new_invoice_status = 'lunas';
            // Pastikan amount_already_paid tidak melebihi total_amount jika ada kebijakan seperti itu
            // $new_total_paid_for_invoice = $total_amount; // Opsional: batasi pembayaran maks = total tagihan
        } elseif ($new_total_paid_for_invoice > 0 && $new_total_paid_for_invoice < $total_amount) {
            $new_invoice_status = 'cicil';
        } elseif ($new_total_paid_for_invoice <= 0) { // <= 0 karena bisa saja ada koreksi negatif
            $new_invoice_status = 'hutang';
            $new_total_paid_for_invoice = 0; // Pastikan tidak negatif
        } else {
             // Seharusnya tidak sampai sini jika logika di atas benar
            // error_log("Unexpected condition when recalculating status for invoice ID {$invoice_id}.");
            return false;
        }

        // Panggil metode update yang sudah ada
        return $this->updateInvoicePayment($invoice_id, $new_total_paid_for_invoice, $new_invoice_status);
    }


    
    /**
     * Fungsi untuk membuat nomor invoice
     * @return string Format invoice APT-YYYYMMDD-XXXX
     */
    public function generateInvoiceNumber($user)
    {
        return 'FM-'.$user.'-'. date('Ymd') . '-' . rand(1000, 9999);
    }
    
    public function getSuppliersByProductId($productId)
    {
        // Periksa apakah koneksi PDO valid
        if (!$this->pdo) {
            error_log("getSuppliersByProductId Error: No valid PDO connection.");
            return []; // Kembalikan array kosong jika tidak ada koneksi
        }
    
        // Query untuk mendapatkan supplier berdasarkan product_id
        $sql = "SELECT 
                    s.supplier_id, 
                    s.supplier_name, 
                    s.contact_person, 
                    s.phone, 
                    s.is_active 
                FROM suppliers s
                INNER JOIN product_batches pb ON s.supplier_id = pb.supplier_id
                WHERE pb.product_id = :product_id
                ORDER BY s.supplier_name ASC"; // Urutkan berdasarkan nama supplier
    
        try {
            // Siapkan dan eksekusi statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT); // Bind parameter product_id
            $stmt->execute();
    
            // Ambil semua hasil sebagai associative array
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            return $suppliers; // Kembalikan array hasil
    
        } catch (PDOException $e) {
            // Log error jika query gagal
            error_log("PDO Error fetching suppliers by product_id: " . $e->getMessage());
            return []; // Kembalikan array kosong jika terjadi error
        }
    }
    
    public function getAllProductsForDropdown() 
    {
        $stmt = $this->pdo->query("SELECT product_id, product_name, stock_quantity FROM products WHERE is_active = 1 ORDER BY product_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllProductsPaginated($offset = 0, $limit = 10) {
    try {
        $pdo = $this->getPDO();
        
        $query = "
            SELECT p.*, c.category_name, s.supplier_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
            ORDER BY p.product_name ASC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error
        error_log("Error getting paginated products: " . $e->getMessage());
        return [];
    }
}
    
    

/**
 * Menambahkan catatan pembayaran untuk sebuah pembelian dan memperbarui status pembelian.
 * @param int $purchase_id ID pembelian
 * @param array $data Data pembayaran (payment_date, amount_paid, payment_method, reference, proof_document_path)
 * @param int $userId ID user yang melakukan aksi (opsional, untuk logging)
 * @return array Hasil operasi (success, message)
 */
/*public function addPurchasePayment($purchase_id, $data, $userId = null)
{
    if (!$this->pdo) {
        error_log("addPurchasePayment Error: No valid PDO connection.");
        return ['success' => false, 'message' => 'Koneksi database gagal.'];
    }

    // Validasi data input
    if (empty($data['payment_date']) || !isset($data['amount_paid']) || !is_numeric($data['amount_paid']) || $data['amount_paid'] <= 0 || empty($data['payment_method'])) {
        return ['success' => false, 'message' => 'Tanggal bayar, jumlah bayar (harus angka > 0), dan metode bayar wajib diisi.'];
    }
    // Sanitasi sederhana untuk path, idealnya ada validasi/pembersihan lebih lanjut
    $proof_path = isset($data['proof_document_path']) ? filter_var(trim($data['proof_document_path']), FILTER_SANITIZE_STRING) : null;
    $reference = isset($data['reference']) ? filter_var(trim($data['reference']), FILTER_SANITIZE_STRING) : null;


    try {
        $this->pdo->beginTransaction();

        // 1. Insert ke tabel purchase_payments
        $sql_insert_payment = "INSERT INTO purchase_payments (purchase_id, payment_date, amount_paid, proof_document_path, payment_method, reference, created_at, updated_at)
                               VALUES (:purchase_id, :payment_date, :amount_paid, :proof_document_path, :payment_method, :reference, NOW(), NOW())";
        $stmt_insert = $this->pdo->prepare($sql_insert_payment);
        $stmt_insert->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':payment_date', $data['payment_date']);
        $stmt_insert->bindParam(':amount_paid', $data['amount_paid']);
        $stmt_insert->bindParam(':proof_document_path', $proof_path);
        $stmt_insert->bindParam(':payment_method', $data['payment_method']);
        $stmt_insert->bindParam(':reference', $reference);
        $stmt_insert->execute();

        // 2. Dapatkan total tagihan pembelian
        $stmt_total_due = $this->pdo->prepare("SELECT total_amount FROM purchases WHERE purchase_id = :purchase_id");
        $stmt_total_due->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_total_due->execute();
        $purchase = $stmt_total_due->fetch(PDO::FETCH_ASSOC);

        if (!$purchase) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Data pembelian tidak ditemukan.'];
        }
        $total_due_amount = (float)$purchase['total_amount'];

        // 3. Hitung total yang sudah dibayar untuk pembelian ini
        $stmt_total_paid = $this->pdo->prepare("SELECT SUM(amount_paid) as total_paid_sum FROM purchase_payments WHERE purchase_id = :purchase_id");
        $stmt_total_paid->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_total_paid->execute();
        $paid_summary = $stmt_total_paid->fetch(PDO::FETCH_ASSOC);
        $current_total_paid = (float)($paid_summary['total_paid_sum'] ?? 0);

        // 4. Tentukan status pembayaran baru
        $new_payment_status = 'hutang';
        if ($current_total_paid >= $total_due_amount) {
            $new_payment_status = 'lunas';
        } elseif ($current_total_paid > 0 && $current_total_paid < $total_due_amount) {
            $new_payment_status = 'cicil';
        }
        // Jika $current_total_paid <= 0, tetap 'pending' (kecuali jika total_due_amount juga 0)
        if ($total_due_amount == 0 && $current_total_paid == 0) { // Kasus khusus jika total tagihan 0
             $new_payment_status = 'lunas';
        }


        // 5. Update status pembayaran di tabel purchases
        $sql_update_status = "UPDATE purchases SET payment_status = :payment_status, updated_at = NOW() WHERE purchase_id = :purchase_id";
        $stmt_update_purchase = $this->pdo->prepare($sql_update_status);
        $stmt_update_purchase->bindParam(':payment_status', $new_payment_status);
        $stmt_update_purchase->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_update_purchase->execute();

        $this->pdo->commit();

        // Opsional: Log aktivitas
        if ($userId) {
            $this->logActivity($userId, 'ADD_PURCHASE_PAYMENT', "Pembayaran Rp {$data['amount_paid']} untuk pembelian #{$purchase_id}. Status: {$new_payment_status}");
        }

        return ['success' => true, 'message' => 'Pembayaran berhasil ditambahkan. Status pembelian telah diperbarui.'];

    } catch (PDOException $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        error_log("PDO Error (addPurchasePayment): " . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal menambahkan pembayaran: ' . $e->getMessage()];
    }
}*/

// In functions.php (Farma class) - MODIFIED
public function addPurchasePayment($purchase_id, $payment_data, $user_id) {
    // No $this->pdo->beginTransaction(), commit(), or rollBack() here.
    // It will operate within the transaction started by beli.php.

    try {
        // 1. Insert into purchase_payments table
        $sql_payment = "INSERT INTO purchase_payments (purchase_id, payment_date, amount_paid, payment_method, reference, proof_document_path, created_at) 
                        VALUES (:purchase_id, :payment_date, :amount_paid, :payment_method, :reference, :proof_document_path, NOW())";
        $stmt_payment = $this->pdo->prepare($sql_payment);
        // Bind all necessary parameters, including $user_id
        $stmt_payment->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
       // $stmt_payment->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Ensure $user_id is available and bound
        $stmt_payment->bindParam(':payment_date', $payment_data['payment_date']);
        $stmt_payment->bindParam(':amount_paid', $payment_data['amount_paid']);
        $stmt_payment->bindParam(':payment_method', $payment_data['payment_method']);
        $stmt_payment->bindParam(':reference', $payment_data['reference']);
        $stmt_payment->bindParam(':proof_document_path', $payment_data['proof_document_path']);
        $stmt_payment->execute();

        // 2. Update payment_status and total_amount_paid in purchases table
        // (This logic should already be in your addPurchasePayment or a related function)
        $sql_sum = "SELECT SUM(amount_paid) as total_paid FROM purchase_payments WHERE purchase_id = :purchase_id";
        $stmt_sum = $this->pdo->prepare($sql_sum);
        $stmt_sum->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_sum->execute();
        $total_paid = $stmt_sum->fetchColumn();
        if ($total_paid === null) $total_paid = 0;


        $sql_purchase_info = "SELECT total_amount FROM purchases WHERE purchase_id = :purchase_id";
        $stmt_purchase_info = $this->pdo->prepare($sql_purchase_info);
        $stmt_purchase_info->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_purchase_info->execute();
        $purchase = $stmt_purchase_info->fetch(PDO::FETCH_ASSOC);
        $total_amount = $purchase ? $purchase['total_amount'] : 0;

        $new_payment_status = 'hutang'; 
        if ((float)$total_paid >= (float)$total_amount) {
            $new_payment_status = 'lunas';
        } elseif ((float)$total_paid > 0) {
            $new_payment_status = 'cicil';
        }

        $sql_update_purchase = "UPDATE purchases SET amount_already_paid = :total_paid, payment_status = :payment_status, updated_at = NOW() 
                                WHERE purchase_id = :purchase_id";
        $stmt_update_purchase = $this->pdo->prepare($sql_update_purchase);
        $stmt_update_purchase->bindParam(':total_paid', $total_paid);
        $stmt_update_purchase->bindParam(':payment_status', $new_payment_status);
        $stmt_update_purchase->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt_update_purchase->execute();
        
        // If successful, just return true or a success indicator.
        // The message can be constructed in beli.php
        return true; 

    } catch (Exception $e) {
        // IMPORTANT: Re-throw the exception.
        // This allows the outer try-catch block in beli.php to catch it and roll back the main transaction.
        throw $e;
    }
}

/**
 * Mengambil semua catatan pembayaran untuk sebuah pembelian.
 * @param int $purchase_id ID pembelian
 * @return array Daftar pembayaran
 */
public function getPurchasePayments($purchase_id)
{
    if (!$this->pdo) {
        error_log("getPurchasePayments Error: No valid PDO connection.");
        return [];
    }
    try {
        $stmt = $this->pdo->prepare("SELECT * FROM purchase_payments WHERE purchase_id = :purchase_id ORDER BY payment_date DESC, created_at DESC");
        $stmt->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("PDO Error (getPurchasePayments): " . $e->getMessage());
        return [];
    }
}

/**
 * Mengambil detail pembelian beserta ringkasan total yang sudah dibayar.
 * @param int $purchase_id ID pembelian
 * @return array|null Detail pembelian atau null jika tidak ditemukan
 */
public function getPurchaseWithPaymentSummary($purchase_id) {
    if (!$this->pdo) {
        error_log("getPurchaseWithPaymentSummary Error: No valid PDO connection.");
        return null;
    }
    try {
        // Query ini mengambil header pembelian dan subquery untuk total yang sudah dibayar
        $sql = "SELECT p.*, s.supplier_name, u.name as user_name, 
                       COALESCE((SELECT SUM(pp.amount_paid) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id), 0) as total_amount_paid
                FROM purchases p
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                LEFT JOIN users u ON p.user_id = u.user_id
                WHERE p.purchase_id = :purchase_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':purchase_id', $purchase_id, PDO::PARAM_INT);
        $stmt->execute();
        $purchase_header = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $purchase_header; // Akan null jika purchase_id tidak ditemukan

    } catch (PDOException $e) {
        error_log("PDO Error (getPurchaseWithPaymentSummary): " . $e->getMessage());
        return null;
    }
}
    
    public function getSuppliers()
    {
        // Periksa apakah koneksi PDO valid
        if (!$this->pdo) {
            error_log("getSuppliers Error: No valid PDO connection.");
            return []; // Kembalikan array kosong jika tidak ada koneksi
        }

        // Kolom yang akan diambil (sesuai struktur setelah menghapus email/address)
        $sql = "SELECT supplier_id, supplier_name, contact_person, phone, is_active 
                FROM suppliers 
                ORDER BY supplier_name ASC"; // Urutkan berdasarkan nama supplier

        try {
            // Siapkan dan eksekusi statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            // Ambil semua hasil sebagai associative array
            $suppliers = $stmt->fetchAll(); 
            
            return $suppliers; // Kembalikan array hasil

        } catch (PDOException $e) {
            // Log error jika query gagal
            error_log("PDO Error fetching suppliers: " . $e->getMessage());
            return []; // Kembalikan array kosong jika terjadi error
        }
    }

    
    public function getPurchases($startDate = null, $endDate = null) {
        try {
            $query = "SELECT p.*, s.supplier_name, u.name as user_name 
                      FROM purchases p
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      LEFT JOIN users u ON p.user_id = u.user_id";

            $params = [];

            if ($startDate && $endDate) {
                $query .= " WHERE DATE(p.purchase_date) BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date'] = $endDate;
            } elseif ($startDate) {
                $query .= " WHERE DATE(p.purchase_date) >= :start_date";
                $params[':start_date'] = $startDate;
            } elseif ($endDate) {
                $query .= " WHERE DATE(p.purchase_date) <= :end_date";
                $params[':end_date'] = $endDate;
            }

            $query .= " ORDER BY p.purchase_date DESC";

            $stmt = $this->pdo->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error (getPurchases): " . $e->getMessage());
            return [];
        }
    }

    public function getPurchaseDetails($purchaseId) {
        try {
            // Get purchase header
            $query = "SELECT p.*, s.supplier_name, u.name as user_name 
                      FROM purchases p
                      LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                      LEFT JOIN users u ON p.user_id = u.user_id
                      WHERE p.purchase_id = :purchase_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
            $stmt->execute();

            $purchaseHeader = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$purchaseHeader) {
                return false;
            }

            // Get purchase items
            $query = "SELECT pi.*, p.product_name, p.kode_item, p.unit
                      FROM purchase_items pi
                      JOIN products p ON pi.product_id = p.product_id
                      WHERE pi.purchase_id = :purchase_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':purchase_id', $purchaseId, PDO::PARAM_INT);
            $stmt->execute();

            $purchaseItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'header' => $purchaseHeader,
                'items' => $purchaseItems,
            ];
        } catch (PDOException $e) {
            error_log("Database Error (getPurchaseDetails): " . $e->getMessage());
            return false;
        }
    }
    
    // Di dalam class Farma Anda (atau sebagai fungsi global jika tidak menggunakan class)
public function getProductsForPurchaseForm()
{
    try {
        // Query ini mengambil produk beserta batch number dan expiry date
        // dari batch yang paling cepat kedaluwarsa untuk setiap produk aktif.
        // Jika ada beberapa batch dengan tanggal kedaluwarsa paling awal yang sama,
        // maka akan dipilih berdasarkan created_at batch (yang paling baru dibuat).
        // Jika tidak ada batch yang cocok, default_batch_number dan default_expiry_date akan NULL.
        $query = "SELECT
                    p.*,
                    c.category_name,
                    mt.type_name,
                    (SELECT s_sub.supplier_name
                     FROM product_batches pb_sub_supplier
                     JOIN suppliers s_sub ON pb_sub_supplier.supplier_id = s_sub.supplier_id
                     WHERE pb_sub_supplier.product_id = p.product_id
                     -- AND pb_sub_supplier.remaining_quantity > 0 -- Opsional: hanya batch dengan stok
                     ORDER BY pb_sub_supplier.expiry_date ASC, pb_sub_supplier.created_at DESC
                     LIMIT 1) AS supplier_name_from_batch, -- Supplier dari batch spesifik tersebut
                    (SELECT pb_sub.expiry_date
                     FROM product_batches pb_sub
                     WHERE pb_sub.product_id = p.product_id
                     -- AND pb_sub.remaining_quantity > 0 -- Opsional: hanya batch dengan stok
                     ORDER BY pb_sub.expiry_date ASC, pb_sub.created_at DESC
                     LIMIT 1) AS default_expiry_date,
                    (SELECT pb_sub.batch_number
                     FROM product_batches pb_sub
                     WHERE pb_sub.product_id = p.product_id
                     -- AND pb_sub.remaining_quantity > 0 -- Opsional: hanya batch dengan stok
                     ORDER BY pb_sub.expiry_date ASC, pb_sub.created_at DESC
                     LIMIT 1) AS default_batch_number
                  FROM
                    products p
                  LEFT JOIN
                    product_categories c ON p.category_id = c.category_id
                  LEFT JOIN
                    medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                  WHERE
                    p.is_active = 1
                  ORDER BY
                    p.product_name ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database Error (getProductsForPurchaseForm): " . $e->getMessage());
        return []; // Kembalikan array kosong jika ada error
    }
}
    
    public function getAllProducts()
    {
        try {
            $query = "SELECT 
                        p.*, 
                        c.category_name, 
                        mt.type_name,
                        pb.expiry_date,
                        pb.batch_number,  -- Menambahkan batch_number dari tabel product_batches
                        s.supplier_name
                      FROM 
                        products p
                      LEFT JOIN 
                        product_categories c ON p.category_id = c.category_id
                      LEFT JOIN 
                        medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      LEFT JOIN 
                        product_batches pb ON p.product_id = pb.product_id
                      LEFT JOIN 
                        suppliers s ON pb.supplier_id = s.supplier_id
                      WHERE 
                        p.is_active = 1
                      GROUP BY 
                        p.product_id
                      ORDER BY 
                        p.product_name";
    
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
    
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAllProducts): " . $e->getMessage());
            return [];
        }
    }
    
    // Asumsikan class dan koneksi PDO ($this->pdo) sudah ada
    
    /*public function getAllProducts()
    {
        try {
            // Query dimodifikasi untuk mengambil nama unit dasar dan sub-unit penjualan
            $query = "SELECT 
                        p.*,  -- Ini akan menyertakan p.price, p.stock_quantity, 
                              -- p.base_unit_id, p.sub_sales_unit_id, 
                              -- dan p.sub_sales_unit_conversion_factor
                        c.category_name, 
                        mt.type_name,
                        pb.expiry_date,
                        pb.batch_number,
                        s.supplier_name,
                        bu.unit_name AS base_unit_name,          -- Nama untuk unit dasar
                        ssu.unit_name AS sub_sales_unit_name     -- Nama untuk sub-unit penjualan (bisa NULL)
                      FROM 
                        products p
                      LEFT JOIN 
                        product_categories c ON p.category_id = c.category_id
                      LEFT JOIN 
                        medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      LEFT JOIN 
                        product_batches pb ON p.product_id = pb.product_id 
                      LEFT JOIN 
                        suppliers s ON pb.supplier_id = s.supplier_id
                      JOIN                                       -- JOIN karena base_unit_id wajib ada
                        units bu ON p.base_unit_id = bu.unit_id
                      LEFT JOIN                                  -- LEFT JOIN karena sub_sales_unit_id bisa NULL
                        units ssu ON p.sub_sales_unit_id = ssu.unit_id
                      WHERE 
                        p.is_active = 1
                      GROUP BY 
                        p.product_id 
                      ORDER BY 
                        p.product_name";
    
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
    
            // Data yang dikembalikan akan mencakup field-field baru:
            // base_unit_name
            // sub_sales_unit_name (bisa null)
            // p.sub_sales_unit_conversion_factor (sudah termasuk dalam p.*)
            // p.price (adalah harga per base_unit)
            // p.stock_quantity (adalah stok dalam base_unit)
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAllProducts): " . $e->getMessage());
            return [];
        }
    }*/


    public function getProductsByCategory($categoryId)
    {
        try {
            $query = "SELECT p.*, c.category_name, mt.type_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.is_active = 1 AND p.category_id = :category_id
                      ORDER BY p.product_name";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getProductsByCategory): " . $e->getMessage());
            return [];
        }
    }

    public function searchProducts($keyword)
    {
        try {
            $query = "SELECT p.*, c.category_name, mt.type_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.is_active = 1 AND 
                            (p.product_name LIKE :keyword OR 
                             p.kode_item LIKE :keyword OR 
                             p.barcode LIKE :keyword OR 
                             p.posisi LIKE :keyword)
                      ORDER BY p.product_name";

            $stmt = $this->pdo->prepare($query);
            $keyword = "%$keyword%";
            $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (searchProducts): " . $e->getMessage());
            return [];
        }
    }

    public function getProductById($productId)
    {
        try {
            $query = "SELECT p.*, c.category_name, mt.type_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.product_id = :product_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database Error (getProductById): " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers()
    {
        try {
            $query = "SELECT name, username, role, last_login, created_at FROM users ORDER BY created_at DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
    
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAllUsers): " . $e->getMessage());
            return [];
        }
    }
    public function deleteSale($saleId) 
    {
        try {
            // Mulai transaksi
            $this->pdo->beginTransaction();
            
            // Hapus item penjualan terlebih dahulu (child records)
            $query = "DELETE FROM sale_items WHERE sale_id = :sale_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Hapus data penjualan (parent record)
            $query = "DELETE FROM sales WHERE sale_id = :sale_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Commit transaksi
            $this->pdo->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback jika terjadi error
            $this->pdo->rollBack();
            error_log("Database Error (deleteSale): " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteUser($username)
    {
        try {
            // Query untuk menghapus pengguna berdasarkan username
            $query = "DELETE FROM users WHERE username = :username";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
    
            // Periksa apakah ada baris yang terpengaruh (berarti pengguna ditemukan dan dihapus)
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Pengguna berhasil dihapus.'];
            } else {
                return ['success' => false, 'message' => 'Pengguna tidak ditemukan.'];
            }
        } catch (PDOException $e) {
            // Log kesalahan jika terjadi error pada database
            error_log("Database Error (deleteUser): " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan pada database.'];
        }
    }
    
    public function updateUser($original_username, $data, $updated_by)
    {
        try {
            // Awal query update
            $query = "UPDATE users SET username = :new_username, name = :name, role = :role, updated_by = :updated_by, updated_at = NOW()";
    
            // Tambahkan password ke query jika ada
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }
    
            $query .= " WHERE username = :original_username";
    
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':new_username', $data['username']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':updated_by', $updated_by); // ID admin/user yang memperbarui
            $stmt->bindParam(':original_username', $original_username);
    
            // Hash dan bind password jika ada
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $hashedPassword);
            }
    
            $stmt->execute();
    
            // Cek apakah ada perubahan
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Tidak ada perubahan yang terjadi. Username mungkin tidak ditemukan.'];
            }
    
            return ['success' => true, 'message' => 'Pengguna berhasil diperbarui.'];
        } catch (PDOException $e) {
            error_log("Database Error (updateUser): " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan saat memperbarui pengguna.'];
        }
    }
    
    public function getAllCategoriesWithProductCount()
    {
        try {
            $query = "
                SELECT 
                    c.category_id, 
                    c.category_name, 
                    c.description, 
                    COUNT(p.product_id) AS product_count
                FROM product_categories c
                LEFT JOIN products p ON c.category_id = p.category_id
                GROUP BY c.category_id
                ORDER BY c.category_name
            ";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
    
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAllCategoriesWithProductCount): " . $e->getMessage());
            return [];
        }
    }

    public function getExpiringProducts($daysThreshold = 90)
    {
        try {
            $query = "SELECT pb.batch_id, p.product_id, p.product_name, p.kode_item, 
                             pb.batch_number, pb.expiry_date, pb.remaining_quantity,
                             DATEDIFF(pb.expiry_date, CURDATE()) as days_remaining,
                             c.category_name, mt.type_name, p.unit
                      FROM product_batches pb
                      JOIN products p ON pb.product_id = p.product_id
                      JOIN product_categories c ON p.category_id = c.category_id
                      JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE pb.remaining_quantity > 0 
                        AND DATEDIFF(pb.expiry_date, CURDATE()) <= :days_threshold
                      ORDER BY days_remaining ASC";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':days_threshold', $daysThreshold, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getExpiringProducts): " . $e->getMessage());
            return [];
        }
    }

    public function getLowStockProducts()
    {
        try {
            $query = "SELECT p.*, c.category_name, mt.type_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.is_active = 1 AND p.stock_quantity <= p.minimum_stock
                      ORDER BY p.stock_quantity ASC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getLowStockProducts): " . $e->getMessage());
            return [];
        }
    }

    public function getAvailableBatches($productId)
    {
        try {
            $query = "SELECT pb.batch_id, pb.batch_number, pb.expiry_date, 
                             pb.remaining_quantity, s.supplier_name
                      FROM product_batches pb
                      LEFT JOIN suppliers s ON pb.supplier_id = s.supplier_id
                      WHERE pb.product_id = :product_id 
                        AND pb.remaining_quantity > 0
                        AND pb.expiry_date > CURDATE()
                      ORDER BY pb.expiry_date ASC";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAvailableBatches): " . $e->getMessage());
            return [];
        }
    }
    
    public function tambahBarang($product_name, $kode_item, $barcode, $posisi, $category_id, $modal, $jual, $unit, $expire, $stock_quantity, $minimum_stock)
    {
        try {
            $query = "INSERT INTO products (product_name, kode_item, barcode, posisi, category_id, modal, jual, unit, expire, stock_quantity, minimum_stock, is_active) 
                      VALUES (:product_name, :kode_item, :barcode, :posisi, :category_id, :modal, :jual, :unit, :expire, :stock_quantity, :minimum_stock, 1)";
            $stmt = $this->pdo->prepare($query);
    
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':kode_item', $kode_item);
            $stmt->bindParam(':barcode', $barcode);
            $stmt->bindParam(':posisi', $posisi);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':modal', $modal);
            $stmt->bindParam(':jual', $jual);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':expire', $expire);
            $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':minimum_stock', $minimum_stock, PDO::PARAM_INT);
    
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database Error (tambahBarang): " . $e->getMessage());
            return false;
        }
    }

    /*public function savePharmacyTransaction($data)
    {
        try {
    
            // Ambil user ID dari sesi
            $userId = $_SESSION['user_id'] ?? null;
    
            if (!$userId) {
                throw new Exception("User tidak terautentikasi.");
            }
    
            $this->pdo->beginTransaction();
    
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($userId);
    
            // Insert sale header
            $stmtHeader = $this->pdo->prepare("
                INSERT INTO sales (
                    invoice_number, customer_name, doctor_id, prescription_number, user_id, 
                    subtotal, tax_amount, discount_amount, total_amount, payment_method_id, 
                    payment_status, notes, sale_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
    
            $stmtHeader->execute([
                $invoiceNumber,
                $data['customer_name'],
                $data['doctor_id'],
                $data['prescription_number'],
                $userId, // Simpan user ID dari sesi
                $data['subtotal'],
                $data['tax_amount'],
                $data['discount_amount'],
                $data['total_amount'],
                $data['payment_method_id'],
                $data['payment_status'],
                $data['notes'],
            ]);
    
            $saleId = $this->pdo->lastInsertId();
    
            foreach ($data['items'] as $item) {
                $stmtProduct = $this->pdo->prepare("
                    SELECT product_id, stock_quantity, price 
                    FROM products 
                    WHERE product_id = ? 
                    FOR UPDATE
                ");
                $stmtProduct->execute([$item['product_id']]);
                $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
    
                if (!$product) {
                    throw new Exception("Produk dengan ID {$item['product_id']} tidak ditemukan");
                }
    
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Stok tidak mencukupi untuk produk ID: {$item['product_id']}");
                }
    
                $batch_id = null;
    
                $discountPercent = $item['discount_percent'] ?? 0;
                $itemTotal = $item['quantity'] * $item['unit_price'] * (1 - $discountPercent / 100);
    
                $stmtItem = $this->pdo->prepare("
                    INSERT INTO sale_items (
                        sale_id, product_id, batch_id, quantity, unit_price, 
                        discount_percent, item_total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
    
                $stmtItem->execute([$saleId, $item['product_id'], $batch_id, $item['quantity'], $item['unit_price'], $discountPercent, $itemTotal]);
    
                $stmtUpdateStock = $this->pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE product_id = ?
                ");
                $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
            }
    
            $this->pdo->commit();
    
            return [
                'success' => true,
                'sale_id' => $saleId,
                'invoice_number' => $invoiceNumber,
                'message' => 'Transaksi berhasil disimpan',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Transaction Error: " . $e->getMessage());
    
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }*/
    
    public function savePharmacyTransaction($data)
    {
        try {
    
            // Ambil user ID dari sesi
            $userId = $_SESSION['user_id'] ?? null;
    
            if (!$userId) {
                throw new Exception("User tidak terautentikasi.");
            }
    
            $this->pdo->beginTransaction();
    
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($userId);
    
            // Insert sale header
            $stmtHeader = $this->pdo->prepare("
                INSERT INTO sales (
                    invoice_number, customer_name, doctor_id, prescription_number, user_id, 
                    subtotal, tax_amount, discount_amount, total_amount, payment_method_id, 
                    payment_status, notes, sale_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
    
            $stmtHeader->execute([
                $invoiceNumber,
                $data['customer_name'],
                $data['doctor_id'],
                $data['prescription_number'],
                $userId, // Simpan user ID dari sesi
                $data['subtotal'],
                $data['tax_amount'],
                $data['discount_amount'],
                $data['total_amount'],
                $data['payment_method_id'],
                $data['payment_status'],
                $data['notes'],
            ]);
    
            $saleId = $this->pdo->lastInsertId();
    
            foreach ($data['items'] as $item) {
                $stmtProduct = $this->pdo->prepare("
                    SELECT product_id, stock_quantity, price 
                    FROM products 
                    WHERE product_id = ? 
                    FOR UPDATE
                ");
                $stmtProduct->execute([$item['product_id']]);
                $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
    
                if (!$product) {
                    throw new Exception("Produk dengan ID {$item['product_id']} tidak ditemukan");
                }
    
                // $item['quantity'] is the actual_quantity in base units from cashier.js
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Stok tidak mencukupi untuk produk ID: {$item['product_id']} (Stok: {$product['stock_quantity']}, Diminta: {$item['quantity']})");
                }
    
                $batch_id = null; // Assuming batch_id is handled or null for now
    
                $discountPercent = $item['discount_percent'] ?? 0;
                
                // Use total_price_rounded from frontend as it includes rounding logic
                // If not provided, you might fall back to recalculating, but ensure rounding matches.
                $itemTotal = $item['total_price_rounded'] ?? ($item['quantity'] * $item['unit_price'] * (1 - $discountPercent / 100));
    
                // Get the new fields from the item data
                $selected_unit = $item['selected_unit'] ?? null;
                $display_quantity = $item['display_quantity'] ?? $item['quantity']; // Fallback display_quantity to actual_quantity if not sent
    
                $stmtItem = $this->pdo->prepare("
                    INSERT INTO sale_items (
                        sale_id, product_id, batch_id, 
                        quantity, display_quantity, selected_unit, -- Added display_quantity and selected_unit
                        unit_price, discount_percent, item_total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) -- Adjusted placeholder count
                ");
    
                $stmtItem->execute([
                    $saleId, 
                    $item['product_id'], 
                    $batch_id, 
                    $item['quantity'],         // Actual quantity in base units
                    $display_quantity,       // Quantity as seen by user
                    $selected_unit,          // Unit selected by user
                    $item['unit_price'],       // Price per base unit
                    $discountPercent,
                    $itemTotal                 // Total for this item line (rounded from frontend)
                ]);
    
                $stmtUpdateStock = $this->pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE product_id = ?
                ");
                // $item['quantity'] is already the actual quantity in base units for stock deduction
                $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
            }
    
            $this->pdo->commit();
    
            return [
                'success' => true,
                'sale_id' => $saleId,
                'invoice_number' => $invoiceNumber,
                'message' => 'Transaksi berhasil disimpan',
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Transaction Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getSalesTransactions($startDate = null, $endDate = null)
    {
        try {
            $query = "SELECT s.*, u.name as cashier_name, pm.method_name as payment_method
                      FROM sales s
                      LEFT JOIN users u ON s.user_id = u.user_id
                      LEFT JOIN payment_methods pm ON s.payment_method_id = pm.payment_method_id";

            $params = [];

            if ($startDate && $endDate) {
                $query .= " WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date'] = $endDate;
            } elseif ($startDate) {
                $query .= " WHERE DATE(s.sale_date) >= :start_date";
                $params[':start_date'] = $startDate;
            } elseif ($endDate) {
                $query .= " WHERE DATE(s.sale_date) <= :end_date";
                $params[':end_date'] = $endDate;
            }

            $query .= " ORDER BY s.sale_date DESC";

            $stmt = $this->pdo->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getSalesTransactions): " . $e->getMessage());
            return [];
        }
    }

    public function getSaleDetails($saleId)
    {
        try {
            // Ambil data header transaksi
            $query = "SELECT s.*, u.name as cashier_name, pm.method_name as payment_method,
                             d.doctor_name
                      FROM sales s
                      LEFT JOIN users u ON s.user_id = u.user_id
                      LEFT JOIN payment_methods pm ON s.payment_method_id = pm.payment_method_id
                      LEFT JOIN doctors d ON s.doctor_id = d.doctor_id
                      WHERE s.sale_id = :sale_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $stmt->execute();

            $saleHeader = $stmt->fetch();

            if (!$saleHeader) {
                return false;
            }

            // Ambil detail item transaksi
            $query = "SELECT si.*, p.product_name, p.kode_item, p.unit,
                             pb.batch_number, pb.expiry_date
                      FROM sale_items si
                      JOIN products p ON si.product_id = p.product_id
                      LEFT JOIN product_batches pb ON si.batch_id = pb.batch_id
                      WHERE si.sale_id = :sale_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $stmt->execute();

            $saleItems = $stmt->fetchAll();

            return [
                'header' => $saleHeader,
                'items' => $saleItems,
            ];
        } catch (PDOException $e) {
            error_log("Database Error (getSaleDetails): " . $e->getMessage());
            return false;
        }
    }
    
    public function loginUser($username, $password)
    {
        try {
            $query = "SELECT * FROM users WHERE username = :username AND is_active = 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
    
            $user = $stmt->fetch();
    
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
                $updateStmt = $this->pdo->prepare($updateQuery);
                $updateStmt->bindParam(':user_id', $user['user_id']);
                $updateStmt->execute();
    
                // Hapus password dari result untuk keamanan
                unset($user['password']);

                // Simpan informasi pengguna ke session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
    
                // Buat cookie untuk sesi persisten
                $sessionData = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'name' => $user['name'],
                ];
    
                // Enkripsi data sebelum disimpan di cookie
                $encryptedData = base64_encode(json_encode($sessionData));
    
                // Set cookie dengan durasi 12 jam
                setcookie('persistent_session', $encryptedData, time() + (6 * 60 * 60), "/", "", true, true); // HttpOnly dan Secure
    
                return $user;
            }
    
            return false;
        } catch (PDOException $e) {
            error_log("Database Error (loginUser): " . $e->getMessage());
            return false;
        }
    }
    
        public function checkPersistentSession()
        {
            // Periksa apakah sudah ada session aktif
            if (isset($_SESSION['user_id'])) {
                return true; // Pengguna sudah login
            }
    
            // Periksa cookie persistent_session
            if (isset($_COOKIE['persistent_session'])) {
                try {
                    $encryptedData = $_COOKIE['persistent_session'];
                    // Penting: Tangani potensi error saat decoding
                    $decodedData = base64_decode($encryptedData, true); 
                    if ($decodedData === false) {
                         // Tangani error base64 decode, mungkin hapus cookie
                         setcookie('persistent_session', '', time() - 3600, "/", "", true, true); // Hapus cookie tidak valid
                         return false;
                    }
    
                    $sessionData = json_decode($decodedData, true);
                    // Periksa error JSON decode
                    if (json_last_error() !== JSON_ERROR_NONE) {
                         // Tangani error JSON decode, mungkin hapus cookie
                         setcookie('persistent_session', '', time() - 3600, "/", "", true, true); // Hapus cookie tidak valid
                         return false;
                    }
    
                    // Periksa apakah data yang diperlukan ada setelah decoding berhasil
                    if ($sessionData && isset($sessionData['user_id'])) {
                        
                        // --- PERBAIKAN ---
                        // Gunakan data dari $sessionData, bukan $user
                        $_SESSION['user_id'] = $sessionData['user_id']; 
    
                        // Pastikan data ini ADA di dalam cookie Anda saat login
                        // (Fungsi loginUser Anda sudah menyimpannya)
                        if (isset($sessionData['username'])) {
                            $_SESSION['username'] = $sessionData['username'];
                        }
                        if (isset($sessionData['role'])) {
                            $_SESSION['role'] = $sessionData['role'];
                        }
                         if (isset($sessionData['name'])) {
                            $_SESSION['name'] = $sessionData['name'];
                        }
                        // --- AKHIR PERBAIKAN ---
    
                        // Penting: Regenerate session ID setelah login (dari cookie atau form)
                        // untuk mencegah session fixation attacks
                        session_regenerate_id(true); 
    
                        return true; // Pengguna berhasil login dari cookie
                    } else {
                        // Data cookie tidak valid atau tidak lengkap
                        setcookie('persistent_session', '', time() - 3600, "/", "", true, true); // Hapus cookie tidak valid
                    }
                } catch (Exception $e) {
                    // Log error jika terjadi exception saat proses cookie
                    error_log("Error processing persistent session cookie: " . $e->getMessage());
                    setcookie('persistent_session', '', time() - 3600, "/", "", true, true); // Hapus cookie bermasalah
                }
            }
    
            return false; // Tidak ada sesi aktif atau cookie valid
        }
        
    /*public function checkPersistentSession()
    {
        // Periksa apakah sudah ada session aktif
        if (isset($_SESSION['user_id'])) {
            return true; // Pengguna sudah login
        }
    
        // Periksa cookie persistent_session
        if (isset($_COOKIE['persistent_session'])) {
            try {
                $encryptedData = $_COOKIE['persistent_session'];
                $decodedData = base64_decode($encryptedData, true);
                if ($decodedData === false) {
                    // Tangani error base64 decode, hapus cookie
                    $this->destroyPersistentSessionCookie();
                    return false;
                }
    
                $sessionData = json_decode($decodedData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Tangani error JSON decode, hapus cookie
                    $this->destroyPersistentSessionCookie();
                    return false;
                }
    
                // Periksa apakah data yang diperlukan dan IP address ada setelah decoding berhasil
                if ($sessionData && isset($sessionData['user_id']) && isset($sessionData['ip_address'])) {
                    // Verifikasi alamat IP
                    if ($sessionData['ip_address'] === $this->getUserIP()) {
                        // Gunakan data dari $sessionData untuk mengisi $_SESSION
                        $_SESSION['user_id'] = $sessionData['user_id'];
                        if (isset($sessionData['username'])) {
                            $_SESSION['username'] = $sessionData['username'];
                        }
                        if (isset($sessionData['role'])) {
                            $_SESSION['role'] = $sessionData['role'];
                        }
                        if (isset($sessionData['name'])) {
                            $_SESSION['name'] = $sessionData['name'];
                        }
    
                        // Regenerate session ID setelah login dari cookie
                        session_regenerate_id(true);
                        return true; // Pengguna berhasil login dari cookie
                    } else {
                        // Alamat IP tidak cocok, hapus cookie
                        $this->destroyPersistentSessionCookie();
                        return false;
                    }
                } else {
                    // Data cookie tidak valid atau tidak lengkap
                    $this->destroyPersistentSessionCookie();
                }
            } catch (Exception $e) {
                // Log error jika terjadi exception saat proses cookie
                error_log("Error processing persistent session cookie: " . $e->getMessage());
                $this->destroyPersistentSessionCookie();
            }
        }
    
        return false; // Tidak ada sesi aktif atau cookie valid
    }*/
    
    private function getUserIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    private function destroyPersistentSessionCookie()
    {
        setcookie('persistent_session', '', time() - 3600, "/", "", true, true); // Hapus cookie
    }
    
    public function logoutUser()
    {
        // Hapus session
        session_unset();
        session_destroy();
    
        // Hapus cookie
        if (isset($_COOKIE['persistent_session'])) {
            setcookie('persistent_session', '', time() - 3600, "/", "", true, true); // Menghapus cookie
        }
    
        header("Location: signin.php");
        exit;
    }

    public function registerUser($userData, $adminId)
    {
        try {
            // Cek apakah username sudah ada
            $query = "SELECT COUNT(*) as count FROM users WHERE username = :username";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->execute();
            $result = $stmt->fetch();
    
            if ($result['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Username sudah digunakan',
                ];
            }
    
            // Hash password
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
    
            // Insert user baru
            $query = "INSERT INTO users (username, password, name, role, is_active, created_by) 
                      VALUES (:username, :password, :name, :role, 1, :created_by)";
    
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':username', $userData['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':name', $userData['name']);
            $stmt->bindParam(':role', $userData['role']);
            $stmt->bindParam(':created_by', $adminId); // Admin ID yang membuat user
            $stmt->execute();
    
            return [
                'success' => true,
                'message' => 'User berhasil didaftarkan',
                'user_id' => $this->pdo->lastInsertId(),
            ];
        } catch (PDOException $e) {
            error_log("Database Error (registerUser): " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function getDailySalesReport($date)
    {
        try {
            $query = "SELECT DATE(s.sale_date) as sale_date, 
                             COUNT(s.sale_id) as total_transactions,
                             SUM(s.subtotal) as subtotal,
                             SUM(s.tax_amount) as tax_amount,
                             SUM(s.discount_amount) as discount_amount,
                             SUM(s.total_amount) as total_amount,
                             pm.method_name,
                             COUNT(CASE WHEN s.payment_method_id = pm.payment_method_id THEN 1 END) as payment_count,
                             SUM(CASE WHEN s.payment_method_id = pm.payment_method_id THEN s.total_amount ELSE 0 END) as payment_amount
                      FROM sales s
                      LEFT JOIN payment_methods pm ON 1=1
                      WHERE DATE(s.sale_date) = :date
                      GROUP BY DATE(s.sale_date), pm.method_name
                      ORDER BY DATE(s.sale_date), pm.method_name";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':date', $date);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getDailySalesReport): " . $e->getMessage());
            return [];
        }
    }

    public function getProductSalesReport($startDate, $endDate)
    {
        try {
            $query = "SELECT p.product_id, p.product_name, p.kode_item, 
                             c.category_name, mt.type_name,
                             SUM(si.quantity) as total_quantity,
                             SUM(si.item_total) as total_sales
                      FROM sale_items si
                      JOIN sales s ON si.sale_id = s.sale_id
                      JOIN products p ON si.product_id = p.product_id
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date
                      GROUP BY p.product_id
                      ORDER BY total_sales DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getProductSalesReport): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mendapatkan log audit sistem
     * Catatan: Tabel audit_log perlu dibuat jika ingin menggunakan fungsi ini
     * @param PDO $pdo Koneksi database
     * @param string $startDate Tanggal awal (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir (YYYY-MM-DD)
     * @return array Data log
     */
    public function getAuditLog($startDate, $endDate)
    {
        try {
            $query = "SELECT al.*, u.name as user_name
                      FROM audit_log al
                      LEFT JOIN users u ON al.user_id = u.user_id
                      WHERE DATE(al.action_date) BETWEEN :start_date AND :end_date
                      ORDER BY al.action_date DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAuditLog): " . $e->getMessage());
            return [];
        }
    }

    public function logActivity($userId, $action, $details)
    {
        try {
            $query = "INSERT INTO audit_log (user_id, action, details)
                      VALUES (:user_id, :action, :details)";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':details', $details);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database Error (logActivity): " . $e->getMessage());
            return false;
        }
    }

    public function getAllCategories()
    {
        try {
            $query = "SELECT * FROM product_categories ORDER BY category_name";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAllCategories): " . $e->getMessage());
            return [];
        }
    }

    public function searchProductsForCashier($keyword)
    {
        try {
            // Pertama, cek apakah ini adalah barcode yang tepat
            $barcodeQuery = "SELECT p.product_id, p.product_name, p.kode_item, 
                             p.price, p.posisi, p.stock_quantity, p.minimum_stock, p.unit, p.requires_prescription,
                             c.category_name, mt.type_name, 1 as is_barcode
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.is_active = 1 AND p.barcode = :barcode
                      LIMIT 1";

            $stmtBarcode = $this->pdo->prepare($barcodeQuery);
            $stmtBarcode->bindParam(':barcode', $keyword);
            $stmtBarcode->execute();

            $barcodeMatch = $stmtBarcode->fetch();

            if ($barcodeMatch) {
                return [$barcodeMatch]; // Return single product with barcode flag
            }

            // Jika bukan barcode, lakukan pencarian biasa
            $query = "SELECT p.product_id, p.product_name, p.kode_item, 
                             p.price, p.posisi, p.stock_quantity, p.minimum_stock, p.unit, p.requires_prescription,
                             c.category_name, mt.type_name, 0 as is_barcode
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.is_active = 1 AND
                            (p.product_name LIKE :keyword OR 
                             p.kode_item LIKE :keyword)
                      ORDER BY p.product_name
                      LIMIT 20";

            $stmt = $this->pdo->prepare($query);
            $keywordLike = "%$keyword%";
            $stmt->bindParam(':keyword', $keywordLike);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (searchProductsForCashier): " . $e->getMessage());
            return [];
        }
    }

    public function getProductsByCategoryForCashier($categoryId, $limit = 50)
    {
        try {
            $query = "SELECT p.product_id, p.product_name, p.kode_item, 
                             p.price, p.posisi, p.stock_quantity, p.minimum_stock, p.unit, p.requires_prescription,
                             c.category_name, mt.type_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.is_active = 1 AND p.category_id = :category_id
                      ORDER BY p.product_name
                      LIMIT :limit";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getProductsByCategoryForCashier): " . $e->getMessage());
            return [];
        }
    }

    public function getPopularProductsForCashier($limit = 12)
    {
        try {
            $query = "SELECT p.product_id, p.product_name, p.kode_item, 
                             p.price, p.posisi, p.stock_quantity, p.minimum_stock, p.unit, p.requires_prescription,
                             c.category_name, mt.type_name,
                             COUNT(si.product_id) as sales_count
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      LEFT JOIN sale_items si ON p.product_id = si.product_id
                      LEFT JOIN sales s ON si.sale_id = s.sale_id AND s.sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                      WHERE p.is_active = 1
                      GROUP BY p.product_id
                      ORDER BY sales_count DESC, p.product_name
                      LIMIT :limit";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getPopularProductsForCashier): " . $e->getMessage());
            return [];
        }
    }

    public function getProductDetailForCashier($productId)
    {
        try {
            // Dapatkan informasi produk
            $query = "SELECT p.*, c.category_name, mt.type_name
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      WHERE p.product_id = :product_id AND p.is_active = 1";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            $product = $stmt->fetch();

            if (!$product) {
                return false;
            }

            // Dapatkan informasi batch yang tersedia
            $query = "SELECT batch_id, batch_number, expiry_date, remaining_quantity
                      FROM product_batches
                      WHERE product_id = :product_id AND remaining_quantity > 0 AND expiry_date > CURDATE()
                      ORDER BY expiry_date ASC";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();

            $batches = $stmt->fetchAll();

            $product['batches'] = $batches;

            return $product;
        } catch (PDOException $e) {
            error_log("Database Error (getProductDetailForCashier): " . $e->getMessage());
            return false;
        }
    }
    
    public function daysUntilExpire($expire_date) 
    {
        $current_date = date('d-m-Y'); // Tanggal hari ini

        // Menghitung selisih antara tanggal sekarang dan tanggal expired
        $expire_timestamp = strtotime($expire_date);
        $current_timestamp = strtotime($current_date);

        // Menghitung jumlah hari
        $diff = $expire_timestamp - $current_timestamp;
        $days_left = floor($diff / (60 * 60 * 24));

        if ($days_left < 0) {
            return json_encode([
                'status' => 'expired',
                'message' => 'Tanggal expired sudah lewat.'
            ]);
        } else {
            return json_encode([
                'status' => 'active',
                'days_left' => $days_left,
                'message' => "Tinggal $days_left hari lagi sampai expired."
            ]);
        }
    }

    public function getActivePaymentMethods()
    {
        try {
            $query = "SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY method_name";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getActivePaymentMethods): " . $e->getMessage());
            return [];
        }
    }

    public function getDoctorsForDropdown()
    {
        try {
            $query = "SELECT doctor_id, doctor_name, specialization 
                  FROM doctors 
                  ORDER BY doctor_name";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getDoctorsForDropdown): " . $e->getMessage());
            return [];
        }
    }
    
    // Di dalam kelas Farmamedika

    /**
     * Mengambil ringkasan penjualan harian termasuk HPP dan laba bersih.
     * @param string $date Tanggal dengan format YYYY-MM-DD
     * @return array Ringkasan penjualan
     */
    public function getDailyFinancialReport($date)
    {
        if (!$this->pdo) {
            error_log("getDailyFinancialReport Error: No valid PDO connection.");
            return [
                'total_revenue' => 0,
                'revenue_by_method' => [],
                'total_cogs' => 0,
                'net_profit' => 0,
                'total_purchase_payments' => 0,
                'error' => 'Koneksi database gagal.'
            ];
        }
    
        $report = [
            'total_revenue' => 0,
            'revenue_by_method' => [],
            'total_cogs' => 0,
            'net_profit' => 0,
            'total_purchase_payments' => 0,
            'error' => null
        ];
    
        try {
            // 1. Total Pemasukan (Revenue) dan Pemasukan per Metode Bayar
            $sql_revenue = "SELECT 
                                SUM(s.total_amount) as daily_total_revenue,
                                pm.method_name, 
                                SUM(s.total_amount) as amount_by_method
                            FROM sales s
                            LEFT JOIN payment_methods pm ON s.payment_method_id = pm.payment_method_id
                            WHERE DATE(s.sale_date) = :sale_date
                            GROUP BY pm.payment_method_id, pm.method_name";
            $stmt_revenue = $this->pdo->prepare($sql_revenue);
            $stmt_revenue->bindParam(':sale_date', $date);
            $stmt_revenue->execute();
            $revenue_data = $stmt_revenue->fetchAll(PDO::FETCH_ASSOC);
    
            $total_revenue_sum = 0;
            foreach ($revenue_data as $row) {
                $method = $row['method_name'] ?? 'Tidak Diketahui';
                $report['revenue_by_method'][$method] = (float)$row['amount_by_method'];
                $total_revenue_sum += (float)$row['amount_by_method'];
            }
            $report['total_revenue'] = $total_revenue_sum;
    
    
            // 2. Total Harga Pokok Penjualan (COGS)
            // Asumsi: tabel 'products' memiliki kolom 'modal' (harga pokok/beli produk)
            // Asumsi: tabel 'sale_items' memiliki 'product_id' dan 'quantity'
            $sql_cogs = "SELECT SUM(si.quantity * p.cost_price) as total_cogs
                 FROM sale_items si
                 JOIN sales s ON si.sale_id = s.sale_id
                 JOIN products p ON si.product_id = p.product_id
                 WHERE DATE(s.sale_date) = :sale_date";
            $stmt_cogs = $this->pdo->prepare($sql_cogs);
            $stmt_cogs->bindParam(':sale_date', $date);
            $stmt_cogs->execute();
            $cogs_result = $stmt_cogs->fetch(PDO::FETCH_ASSOC);
            $report['total_cogs'] = (float)($cogs_result['total_cogs'] ?? 0);
    
            // 3. Laba Bersih
            $report['net_profit'] = $report['total_revenue'] - $report['total_cogs'];
    
            // 4. Total Pengeluaran (Pembayaran Pembelian Hari Ini)
            $sql_expenses = "SELECT SUM(amount_paid) as daily_total_payments
                             FROM purchase_payments
                             WHERE DATE(payment_date) = :payment_date";
            $stmt_expenses = $this->pdo->prepare($sql_expenses);
            $stmt_expenses->bindParam(':payment_date', $date);
            $stmt_expenses->execute();
            $expenses_result = $stmt_expenses->fetch(PDO::FETCH_ASSOC);
            $report['total_purchase_payments'] = (float)($expenses_result['daily_total_payments'] ?? 0);
    
        } catch (PDOException $e) {
            error_log("PDO Error (getDailyFinancialReport for date {$date}): " . $e->getMessage());
            $report['error'] = 'Gagal mengambil data laporan: ' . $e->getMessage();
        }
        return $report;
    }
    
    // Di dalam kelas Farmamedika

    /**
     * Menambahkan catatan pengeluaran operasional baru.
     * @param array $data Data pengeluaran (expense_date, description, category, amount, user_id)
     * @return array Hasil operasi (success, message, expense_id)
     */
    public function addOperationalExpense($data)
    {
        if (!$this->pdo) {
            error_log("addOperationalExpense Error: No valid PDO connection.");
            return ['success' => false, 'message' => 'Koneksi database gagal.'];
        }
    
        // Validasi data dasar
        if (empty($data['expense_date']) || empty($data['description']) || !isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            return ['success' => false, 'message' => 'Tanggal, deskripsi, dan jumlah (harus angka > 0) wajib diisi.'];
        }
    
        try {
            $sql = "INSERT INTO operational_expenses (expense_date, description, category, amount, user_id, created_at, updated_at)
                    VALUES (:expense_date, :description, :category, :amount, :user_id, NOW(), NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':expense_date', $data['expense_date']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':category', $data['category']); // Bisa null jika tidak diisi
            $stmt->bindParam(':amount', $data['amount']);
            $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT); // Bisa null
            
            $stmt->execute();
            $expense_id = $this->pdo->lastInsertId();
    
            if ($data['user_id']) {
                 $this->logActivity($data['user_id'], 'ADD_OPERATIONAL_EXPENSE', "Pengeluaran operasional '{$data['description']}' Rp {$data['amount']} ditambahkan.");
            }
    
            return ['success' => true, 'message' => 'Pengeluaran operasional berhasil ditambahkan.', 'expense_id' => $expense_id];
    
        } catch (PDOException $e) {
            error_log("PDO Error (addOperationalExpense): " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menambahkan pengeluaran operasional: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mengambil total pengeluaran operasional per hari dalam rentang tanggal.
     * @param string $startDate Tanggal mulai (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir (YYYY-MM-DD)
     * @return array Daftar total pengeluaran operasional per hari
     */
    public function getAggregatedOperationalExpenses($startDate, $endDate)
    {
        if (!$this->pdo) {
            error_log("getAggregatedOperationalExpenses Error: No valid PDO connection.");
            return [];
        }
        try {
            $sql = "SELECT 
                        expense_date, 
                        SUM(amount) as total_daily_operational_expense
                    FROM operational_expenses
                    WHERE expense_date BETWEEN :startDate AND :endDate
                    GROUP BY expense_date
                    ORDER BY expense_date ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':startDate', $startDate);
            $stmt->bindParam(':endDate', $endDate);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Kunci adalah tanggal, nilai adalah total
        } catch (PDOException $e) {
            error_log("PDO Error (getAggregatedOperationalExpenses): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil total pembayaran pembelian per hari dalam rentang tanggal.
     * @param string $startDate Tanggal mulai (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir (YYYY-MM-DD)
     * @return array Daftar total pembayaran pembelian per hari
     */
    public function getAggregatedPurchasePayments($startDate, $endDate)
    {
        if (!$this->pdo) {
            error_log("getAggregatedPurchasePayments Error: No valid PDO connection.");
            return [];
        }
        try {
            $sql = "SELECT 
                        DATE(payment_date) as payment_day, 
                        SUM(amount_paid) as total_daily_purchase_payment
                    FROM purchase_payments
                    WHERE DATE(payment_date) BETWEEN :startDate AND :endDate
                    GROUP BY DATE(payment_date)
                    ORDER BY DATE(payment_date) ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':startDate', $startDate);
            $stmt->bindParam(':endDate', $endDate);
            $stmt->execute();
            // Menggunakan FETCH_KEY_PAIR agar mudah di-lookup berdasarkan tanggal
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("PDO Error (getAggregatedPurchasePayments): " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mengambil rincian semua pengeluaran operasional dalam rentang tanggal tertentu.
     * @param string $startDate Tanggal mulai (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir (YYYY-MM-DD)
     * @return array Daftar detail pengeluaran operasional
     */
    public function getOperationalExpenseDetailsInRange($startDate, $endDate)
    {
        if (!$this->pdo) {
            error_log("getOperationalExpenseDetailsInRange Error: No valid PDO connection.");
            return ['data' => [], 'error' => 'Koneksi database gagal.'];
        }
    
        $result = ['data' => [], 'error' => null];
    
        try {
            $sql = "SELECT 
                        expense_id,
                        expense_date, 
                        description, 
                        category, 
                        amount,
                        u.name as recorded_by_user -- Mengambil nama user yang mencatat
                    FROM operational_expenses oe
                    LEFT JOIN users u ON oe.user_id = u.user_id -- Join dengan tabel users
                    WHERE oe.expense_date BETWEEN :startDate AND :endDate
                    ORDER BY oe.expense_date DESC, oe.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':startDate', $startDate);
            $stmt->bindParam(':endDate', $endDate);
            $stmt->execute();
            $result['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (PDOException $e) {
            error_log("PDO Error (getOperationalExpenseDetailsInRange from {$startDate} to {$endDate}): " . $e->getMessage());
            $result['error'] = 'Gagal mengambil rincian pengeluaran operasional: ' . $e->getMessage();
        }
        return $result;
    }

    /**
     * Mengambil ringkasan pendapatan dan pengeluaran historis yang diagregasi per hari.
     * @param string $startDate Tanggal mulai (YYYY-MM-DD)
     * @param string $endDate Tanggal akhir (YYYY-MM-DD)
     * @return array Daftar ringkasan per hari
     */
    /*public function getHistoricalFinancialSummary($startDate, $endDate) // Nama fungsi diubah agar lebih deskriptif
    {
        if (!$this->pdo) {
            error_log("getHistoricalFinancialSummary Error: No valid PDO connection.");
            return ['data' => [], 'error' => 'Koneksi database gagal.'];
        }
    
        $summary = ['data' => [], 'error' => null];
    
        try {
            // CTE untuk mendapatkan semua tanggal unik dari semua sumber
            // dan kemudian LEFT JOIN ke data yang diagregasi.
            $sql = "
                WITH RelevantDates AS (
                    SELECT DISTINCT DATE(s.sale_date) as event_date FROM sales s WHERE DATE(s.sale_date) BETWEEN :startDate AND :endDate
                    UNION
                    SELECT DISTINCT DATE(pp.payment_date) as event_date FROM purchase_payments pp WHERE DATE(pp.payment_date) BETWEEN :startDate AND :endDate
                    UNION
                    SELECT DISTINCT oe.expense_date as event_date FROM operational_expenses oe WHERE oe.expense_date BETWEEN :startDate AND :endDate
                ),
                DailySales AS (
                    SELECT
                        DATE(s.sale_date) as sale_day,
                        SUM(s.total_amount) as daily_revenue,
                        SUM(si.quantity * p.cost_price) as daily_cogs
                    FROM
                        sales s
                    JOIN
                        sale_items si ON s.sale_id = si.sale_id
                    JOIN
                        products p ON si.product_id = p.product_id
                    WHERE
                        DATE(s.sale_date) BETWEEN :startDate AND :endDate
                    GROUP BY
                        DATE(s.sale_date)
                ),
                DailyPurchasePayments AS (
                    SELECT
                        DATE(payment_date) as payment_day,
                        SUM(amount_paid) as daily_purchase_outflow
                    FROM
                        purchase_payments
                    WHERE
                        DATE(payment_date) BETWEEN :startDate AND :endDate
                    GROUP BY
                        DATE(payment_date)
                ),
                DailyOperationalExpenses AS (
                    SELECT
                        expense_date,
                        SUM(amount) as daily_operational_outflow
                    FROM
                        operational_expenses
                    WHERE
                        expense_date BETWEEN :startDate AND :endDate
                    GROUP BY
                        expense_date
                )
                SELECT
                    rd.event_date,
                    COALESCE(ds.daily_revenue, 0) as daily_revenue,
                    COALESCE(ds.daily_cogs, 0) as daily_cogs,
                    COALESCE(dpp.daily_purchase_outflow, 0) as daily_purchase_payments,
                    COALESCE(doe.daily_operational_outflow, 0) as daily_operational_expenses,
                    (COALESCE(ds.daily_revenue, 0) - COALESCE(ds.daily_cogs, 0) - COALESCE(doe.daily_operational_outflow, 0)) as daily_net_profit
                FROM
                    RelevantDates rd
                LEFT JOIN DailySales ds ON rd.event_date = ds.sale_day
                LEFT JOIN DailyPurchasePayments dpp ON rd.event_date = dpp.payment_day
                LEFT JOIN DailyOperationalExpenses doe ON rd.event_date = doe.expense_date
                WHERE rd.event_date IS NOT NULL -- Memastikan hanya tanggal yang ada data yang muncul
                ORDER BY
                    rd.event_date DESC";
    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':startDate', $startDate);
            $stmt->bindParam(':endDate', $endDate);
            // Bind :startDate dan :endDate untuk setiap CTE juga jika diperlukan oleh driver DB tertentu,
            // namun untuk MySQL, parameter yang sama bisa digunakan berulang kali.
            // Jika ada masalah, Anda mungkin perlu bind parameter dengan nama unik untuk setiap penggunaan.
            
            $stmt->execute();
            $summary['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (PDOException $e) {
            error_log("PDO Error (getHistoricalFinancialSummary from {$startDate} to {$endDate}): " . $e->getMessage());
            $summary['error'] = 'Gagal mengambil data laporan historis: ' . $e->getMessage();
        }
        return $summary;
    }*/
    
    public function getHistoricalFinancialSummary($startDate, $endDate)
    {
        if (!$this->pdo) {
            error_log("getHistoricalFinancialSummary Error: No valid PDO connection.");
            return ['data' => [], 'error' => 'Koneksi database gagal.'];
        }
    
        $summary = ['data' => [], 'error' => null];
    
        try {
            // Patch: Pisahkan DailySales dan DailyCOGS agar SUM total_amount tidak ikut join ke sale_items
            $sql = "
                WITH RelevantDates AS (
                    SELECT DISTINCT DATE(s.sale_date) as event_date FROM sales s WHERE DATE(s.sale_date) BETWEEN :startDate AND :endDate
                    UNION
                    SELECT DISTINCT DATE(pp.payment_date) as event_date FROM purchase_payments pp WHERE DATE(pp.payment_date) BETWEEN :startDate AND :endDate
                    UNION
                    SELECT DISTINCT oe.expense_date as event_date FROM operational_expenses oe WHERE oe.expense_date BETWEEN :startDate AND :endDate
                ),
                DailySales AS (
                    SELECT
                        DATE(s.sale_date) as sale_day,
                        SUM(s.total_amount) as daily_revenue
                    FROM
                        sales s
                    WHERE
                        DATE(s.sale_date) BETWEEN :startDate AND :endDate
                    GROUP BY
                        DATE(s.sale_date)
                ),
                DailyCOGS AS (
                    SELECT
                        DATE(s.sale_date) as sale_day,
                        SUM(si.display_quantity * p.cost_price / 10) as daily_cogs
                    FROM
                        sales s
                    JOIN
                        sale_items si ON s.sale_id = si.sale_id
                    JOIN
                        products p ON si.product_id = p.product_id
                    WHERE
                        DATE(s.sale_date) BETWEEN :startDate AND :endDate
                    GROUP BY
                        DATE(s.sale_date)
                ),
                DailyPurchasePayments AS (
                    SELECT
                        DATE(payment_date) as payment_day,
                        SUM(amount_paid) as daily_purchase_outflow
                    FROM
                        purchase_payments
                    WHERE
                        DATE(payment_date) BETWEEN :startDate AND :endDate
                    GROUP BY
                        DATE(payment_date)
                ),
                DailyOperationalExpenses AS (
                    SELECT
                        expense_date,
                        SUM(amount) as daily_operational_outflow
                    FROM
                        operational_expenses
                    WHERE
                        expense_date BETWEEN :startDate AND :endDate
                    GROUP BY
                        expense_date
                )
                SELECT
                    rd.event_date,
                    COALESCE(ds.daily_revenue, 0) as daily_revenue,
                    COALESCE(dc.daily_cogs, 0) as daily_cogs,
                    COALESCE(dpp.daily_purchase_outflow, 0) as daily_purchase_payments,
                    COALESCE(doe.daily_operational_outflow, 0) as daily_operational_expenses,
                    (COALESCE(ds.daily_revenue, 0) - COALESCE(dc.daily_cogs, 0) - COALESCE(doe.daily_operational_outflow, 0)) as daily_net_profit
                FROM
                    RelevantDates rd
                LEFT JOIN DailySales ds ON rd.event_date = ds.sale_day
                LEFT JOIN DailyCOGS dc ON rd.event_date = dc.sale_day
                LEFT JOIN DailyPurchasePayments dpp ON rd.event_date = dpp.payment_day
                LEFT JOIN DailyOperationalExpenses doe ON rd.event_date = doe.expense_date
                WHERE rd.event_date IS NOT NULL
                ORDER BY
                    rd.event_date DESC
            ";
    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':startDate', $startDate);
            $stmt->bindParam(':endDate', $endDate);
    
            $stmt->execute();
            $summary['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        } catch (PDOException $e) {
            error_log("PDO Error (getHistoricalFinancialSummary from {$startDate} to {$endDate}): " . $e->getMessage());
            $summary['error'] = 'Gagal mengambil data laporan historis: ' . $e->getMessage();
        }
        return $summary;
    }
    
    /**
     * Mendapatkan tanggal penjualan paling awal dari database.
     * @return string|null Tanggal dalam format YYYY-MM-DD atau null jika tidak ada penjualan.
     */
    public function getEarliestSaleDate()
    {
        if (!$this->pdo) {
            return null;
        }
        try {
            $stmt = $this->pdo->query("SELECT MIN(DATE(sale_date)) as earliest_date FROM sales");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['earliest_date'] ?? null;
        } catch (PDOException $e) {
            error_log("PDO Error (getEarliestSaleDate): " . $e->getMessage());
            return null;
        }
    }

    public function getReceiptData($saleId)
    {
        try {
            
            $query = "SELECT s.*, u.username as cashier_name, pm.method_name as payment_method,
                         d.doctor_name
                  FROM sales s
                  LEFT JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN payment_methods pm ON s.payment_method_id = pm.payment_method_id
                  LEFT JOIN doctors d ON s.doctor_id = d.doctor_id
                  WHERE s.sale_id = :sale_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $stmt->execute();

            $sale = $stmt->fetch();

            if (!$sale) {
                return false;
            }

            // Dapatkan item transaksi
            $query = "SELECT si.*, p.product_name, p.kode_item, p.unit
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.product_id
                  WHERE si.sale_id = :sale_id";

            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
            $stmt->execute();

            $items = $stmt->fetchAll();

            // Dapatkan informasi apotek
            // Catatan: Ini bisa diganti dengan pengambilan data dari tabel settings jika ada
            $pharmacy = [
                'name' => 'Farma Medika',
                'address' => 'Jl. Sultan Iskandar Muda, Punge Jurong, Kec. Meuraxa, Kota Banda Aceh, Aceh',
                'phone' => '021-12345678',
                'email' => 'info@farmamedika.com',
                'footer_note' => 'Terima kasih atas kunjungan Anda.',
            ];

            return [
                'pharmacy' => $pharmacy,
                'sale' => $sale,
                'items' => $items,
            ];
        } catch (PDOException $e) {
            error_log("Database Error (getReceiptData): " . $e->getMessage());
            return false;
        }
    }
    

    public function generateReceiptHTML($receiptData)
    {
        $pharmacy = $receiptData['pharmacy'];
        $sale = $receiptData['sale'];
        $items = $receiptData['items'];

        $html =
            '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Struk Pembelian - ' .
                $sale['invoice_number'] .
                '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    margin: 0;
                    padding: 10px;
                }
                .receipt {
                    width: 80mm;
                    margin: 0 auto;
                }
                .header {
                    text-align: center;
                    margin-bottom: 10px;
                }
                .pharmacy-name {
                    font-size: 16px;
                    font-weight: bold;
                }
                .pharmacy-info {
                    font-size: 10px;
                }
                .invoice-info {
                    margin: 10px 0;
                    border-top: 1px dashed #000;
                    border-bottom: 1px dashed #000;
                    padding: 5px 0;
                }
                .invoice-info div {
                    display: flex;
                    justify-content: space-between;
                }
                .items {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                .items th {
                    text-align: left;
                    font-size: 10px;
                }
                .items td {
                    font-size: 10px;
                    padding: 2px 0;
                }
                .item-detail {
                    font-size: 9px;
                    color: #555;
                }
                .totals {
                    margin: 10px 0;
                    text-align: right;
                }
                .totals div {
                    display: flex;
                    justify-content: space-between;
                }
                .grand-total {
                    font-weight: bold;
                    font-size: 14px;
                    border-top: 1px solid #000;
                    padding-top: 5px;
                }
                .footer {
                    text-align: center;
                    margin-top: 10px;
                    font-size: 10px;
                    border-top: 1px dashed #000;
                    padding-top: 5px;
                }
                @media print {
                    body {
                        width: 80mm;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt">
                <div class="header">
                    <div class="pharmacy-name">' .
                $pharmacy['name'] .
                '</div>
                    <div class="pharmacy-info">' .
                $pharmacy['address'] .
                '</div>
                    <div class="pharmacy-info">Telp: ' .
                $pharmacy['phone'] .
                '</div>
                </div>
                
                <div class="invoice-info">
                    <div><span>No. Invoice:</span> <span>' .
                $sale['invoice_number'] .
                '</span></div>
                    <div><span>Tanggal:</span> <span>' .
                date('d/m/Y H:i', strtotime($sale['sale_date'])) .
                '</span></div>
                    <div><span>Kasir:</span> <span>' .
                $sale['cashier_name'] .
                '</span></div>';
    
            if ($sale['customer_name']) {
                $html .= '<div><span>Pelanggan:</span> <span>' . $sale['customer_name'] . '</span></div>';
            }
    
            if ($sale['doctor_name']) {
                $html .= '<div><span>Dokter:</span> <span>' . $sale['doctor_name'] . '</span></div>';
                $html .= '<div><span>No. Resep:</span> <span>' . $sale['prescription_number'] . '</span></div>';
            }
    
            $html .= '
                </div>
                
                <table class="items">
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th style="text-align: right;">Harga</th>
                        <th style="text-align: right;">Total</th>
                    </tr>';
    
            foreach ($items as $item) {
                $html .=
                    '
                    <tr>
                        <td>
                            ' .
                    $item['product_name'] .
                    '<br>
                            <span class="item-detail">' .
                    $item['kode_item'] .
                    '</span>
                        </td>
                        <td>' .
                    $item['display_quantity'] .
                    ' ' .
                    $item['selected_unit'] .
                    '</td>
                        <td style="text-align: right;">Rp ' .
                    number_format($item['item_total']/$item['display_quantity'], 0, ',', '.') .
                    '</td>
                        <td style="text-align: right;">Rp ' .
                    number_format($item['item_total'], 0, ',', '.') .
                    '</td>
                    </tr>';
            }
    
            $html .=
                '
                </table>
                
                <div class="totals">
                    <div><span>Subtotal:</span> <span>Rp ' .
                number_format($sale['subtotal'], 0, ',', '.') .
                '</span></div>';
    
            if ($sale['tax_amount'] > 0) {
                $html .= '<div><span>Pajak:</span> <span>Rp ' . number_format($sale['tax_amount'], 0, ',', '.') . '</span></div>';
            }
    
            if ($sale['discount_amount'] > 0) {
                $html .= '<div><span>Diskon:</span> <span>Rp ' . number_format($sale['discount_amount'], 0, ',', '.') . '</span></div>';
            }
    
            $html .=
                '
                    <div class="grand-total"><span>Total:</span> <span>Rp ' .
                number_format($sale['total_amount'], 0, ',', '.') .
                '</span></div>
                    <div><span>Metode Pembayaran:</span> <span>' .
                $sale['payment_method'] .
                '</span></div>
                </div>
                
                <div class="footer">
                    ' .
                $pharmacy['footer_note'] .
                '<br>
                    ' .
                date('d/m/Y H:i:s') .
                '
                </div>
                
                <div class="no-print" style="text-align: center; margin-top: 20px;">
                    <button onclick="window.print()">Cetak Struk</button>
                    <button onclick="window.close()">Tutup</button>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }
}

$farma = new Farmamedika();

?>
