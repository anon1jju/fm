<?php
/**
 * Functions.php - File fungsi-fungsi utama untuk POS Apotek
 * 
 * File ini berisi fungsi-fungsi dasar yang digunakan dalam aplikasi POS Apotek
 * termasuk koneksi database dan operasi CRUD dasar
 * 
 * @version 1.0.0
 * @date 2025-04-17
 */

/**
 * Fungsi koneksi ke database
 * @return PDO Object koneksi PDO
 */
function connectDatabase() {
    // Konfigurasi database
    $host = 'localhost';
    $dbname = 'farmamedika';
    $username = 'farmamedika';
    $password = 'farmamedika2025';
    
    try {
        // Membuat koneksi PDO
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        
        // Set error mode ke exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set default fetch mode ke associative array
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch(PDOException $e) {
        // Log error
        error_log("Database Connection Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fungsi untuk menangani error
 * @param string $message Pesan error
 * @param int $statusCode HTTP status code
 * @return void
 */
function handleError($message, $statusCode = 500) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode([
        "success" => false,
        "message" => $message
    ]);
    exit;
}

/**
 * Fungsi untuk membuat nomor invoice
 * @return string Format invoice APT-YYYYMMDD-XXXX
 */
function generateInvoiceNumber() {
    return 'APT-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * Fungsi untuk memformat tanggal
 * @param string $date Tanggal dalam format MySQL
 * @param string $format Format output yang diinginkan
 * @return string Tanggal yang sudah diformat
 */
function formatDate($date, $format = 'd M Y') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/****************************
 * FUNGSI MANAJEMEN PRODUK *
 ****************************/

/**
 * Mendapatkan semua data produk
 * @param PDO $pdo Koneksi database
 * @return array Daftar produk
 */
function getAllProducts($pdo) {
    try {
        $query = "SELECT p.*, c.category_name, mt.type_name
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.category_id
                  LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                  WHERE p.is_active = 1
                  ORDER BY p.product_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getAllProducts): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan produk berdasarkan kategori
 * @param PDO $pdo Koneksi database
 * @param int $categoryId ID kategori
 * @return array Daftar produk dalam kategori tersebut
 */
function getProductsByCategory($pdo, $categoryId) {
    try {
        $query = "SELECT p.*, c.category_name, mt.type_name
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.category_id
                  LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                  WHERE p.is_active = 1 AND p.category_id = :category_id
                  ORDER BY p.product_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getProductsByCategory): " . $e->getMessage());
        return [];
    }
}

/**
 * Mencari produk berdasarkan keyword
 * @param PDO $pdo Koneksi database
 * @param string $keyword Kata kunci pencarian
 * @return array Hasil pencarian produk
 */
function searchProducts($pdo, $keyword) {
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
        
        $stmt = $pdo->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (searchProducts): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan detail produk berdasarkan ID
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk
 * @return array|bool Detail produk atau false jika tidak ditemukan
 */
function getProductById($pdo, $productId) {
    try {
        $query = "SELECT p.*, c.category_name, mt.type_name
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.category_id
                  LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                  WHERE p.product_id = :product_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch(PDOException $e) {
        error_log("Database Error (getProductById): " . $e->getMessage());
        return false;
    }
}

/**
 * Mendapatkan produk yang akan segera kadaluarsa
 * @param PDO $pdo Koneksi database
 * @param int $daysThreshold Jumlah hari sebagai batas
 * @return array Daftar produk yang akan kadaluarsa
 */
function getExpiringProducts($pdo, $daysThreshold = 90) {
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':days_threshold', $daysThreshold, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getExpiringProducts): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan produk dengan stok menipis
 * @param PDO $pdo Koneksi database
 * @return array Daftar produk dengan stok di bawah minimum
 */
function getLowStockProducts($pdo) {
    try {
        $query = "SELECT p.*, c.category_name, mt.type_name
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.category_id
                  LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                  WHERE p.is_active = 1 AND p.stock_quantity <= p.minimum_stock
                  ORDER BY p.stock_quantity ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getLowStockProducts): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan batch tersedia untuk produk
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk
 * @return array Daftar batch yang tersedia
 */
function getAvailableBatches($pdo, $productId) {
    try {
        $query = "SELECT pb.batch_id, pb.batch_number, pb.expiry_date, 
                         pb.remaining_quantity, s.supplier_name
                  FROM product_batches pb
                  LEFT JOIN suppliers s ON pb.supplier_id = s.supplier_id
                  WHERE pb.product_id = :product_id 
                    AND pb.remaining_quantity > 0
                    AND pb.expiry_date > CURDATE()
                  ORDER BY pb.expiry_date ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getAvailableBatches): " . $e->getMessage());
        return [];
    }
}

/******************************
 * FUNGSI MANAJEMEN TRANSAKSI *
 ******************************/

/**
 * Menyimpan transaksi penjualan
 * @param PDO $pdo Koneksi database
 * @param array $data Data transaksi
 * @return array Hasil operasi
 */
function savePharmacyTransaction($pdo, $data) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Generate invoice number
        $invoiceNumber = generateInvoiceNumber($pdo);
        
        // Insert sale header
        $stmtHeader = $pdo->prepare("
            INSERT INTO sales (
                invoice_number, 
                customer_name, 
                doctor_id, 
                prescription_number, 
                user_id, 
                subtotal, 
                tax_amount, 
                discount_amount, 
                total_amount, 
                payment_method_id, 
                payment_status, 
                notes,
                sale_date
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $stmtHeader->execute([
            $invoiceNumber,
            $data['customer_name'],
            $data['doctor_id'],
            $data['prescription_number'],
            $data['user_id'] ?? 1, // Default ke user ID 1 jika tidak diisi
            $data['subtotal'],
            $data['tax_amount'],
            $data['discount_amount'],
            $data['total_amount'],
            $data['payment_method_id'],
            $data['payment_status'],
            $data['notes']
        ]);
        
        $saleId = $pdo->lastInsertId();
        
        // Process each item
        foreach ($data['items'] as $item) {
            // Check if item exists and has sufficient stock
            $stmtProduct = $pdo->prepare("
                SELECT product_id, stock_quantity, price 
                FROM products 
                WHERE product_id = ?
            ");
            $stmtProduct->execute([$item['product_id']]);
            $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                throw new Exception("Produk dengan ID {$item['product_id']} tidak ditemukan");
            }
            
            if ($product['stock_quantity'] < $item['quantity']) {
                throw new Exception("Stok tidak mencukupi untuk produk ID: {$item['product_id']}");
            }
            
            // Solusi untuk masalah batch_id: gunakan default NULL atau ambil dari product_batches
            $batch_id = null; // Default ke NULL jika tidak ada batch
            
            // SOLUSI 1: Set batch_id ke NULL dan skip pengecekan
            
            // SOLUSI 2 (Opsional): Jika ingin coba menggunakan batch yang ada
            /*
            $stmtBatch = $pdo->prepare("
                SELECT batch_id 
                FROM product_batches 
                WHERE product_id = ? 
                AND remaining_quantity > 0 
                ORDER BY expiry_date ASC
                LIMIT 1
            ");
            $stmtBatch->execute([$item['product_id']]);
            $batchResult = $stmtBatch->fetch(PDO::FETCH_ASSOC);
            
            if ($batchResult) {
                $batch_id = $batchResult['batch_id'];
                
                // Update batch remaining_quantity jika ingin
                $stmtUpdateBatch = $pdo->prepare("
                    UPDATE product_batches 
                    SET remaining_quantity = remaining_quantity - ? 
                    WHERE batch_id = ?
                ");
                $stmtUpdateBatch->execute([$item['quantity'], $batch_id]);
            }
            */
            
            // Hitung total harga untuk item ini
            $discountPercent = $item['discount_percent'] ?? 0;
            $itemTotal = $item['quantity'] * $item['unit_price'] * (1 - $discountPercent / 100);
            
            // Insert sale item dengan item_total (bukan total_price)
            $stmtItem = $pdo->prepare("
                INSERT INTO sale_items (
                    sale_id, 
                    product_id, 
                    batch_id, 
                    quantity, 
                    unit_price, 
                    discount_percent, 
                    item_total
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmtItem->execute([
                $saleId,
                $item['product_id'],
                $batch_id, // NULL jika tidak ada batch
                $item['quantity'],
                $item['unit_price'],
                $discountPercent,
                $itemTotal
            ]);
            
            // Update product stock
            $stmtUpdateStock = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE product_id = ?
            ");
            $stmtUpdateStock->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'sale_id' => $saleId,
            'invoice_number' => $invoiceNumber,
            'message' => 'Transaksi berhasil disimpan'
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log error untuk debugging
        error_log("Transaction Error: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Mendapatkan daftar transaksi penjualan
 * @param PDO $pdo Koneksi database
 * @param string $startDate Tanggal awal (YYYY-MM-DD)
 * @param string $endDate Tanggal akhir (YYYY-MM-DD)
 * @return array Daftar transaksi
 */
function getSalesTransactions($pdo, $startDate = null, $endDate = null) {
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
        
        $stmt = $pdo->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getSalesTransactions): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan detail transaksi penjualan
 * @param PDO $pdo Koneksi database
 * @param int $saleId ID transaksi
 * @return array Detail transaksi
 */
function getSaleDetails($pdo, $saleId) {
    try {
        // Ambil data header transaksi
        $query = "SELECT s.*, u.name as cashier_name, pm.method_name as payment_method,
                         d.doctor_name
                  FROM sales s
                  LEFT JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN payment_methods pm ON s.payment_method_id = pm.payment_method_id
                  LEFT JOIN doctors d ON s.doctor_id = d.doctor_id
                  WHERE s.sale_id = :sale_id";
        
        $stmt = $pdo->prepare($query);
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
        $stmt->execute();
        
        $saleItems = $stmt->fetchAll();
        
        return [
            'header' => $saleHeader,
            'items' => $saleItems
        ];
    } catch(PDOException $e) {
        error_log("Database Error (getSaleDetails): " . $e->getMessage());
        return false;
    }
}

/************************
 * FUNGSI AUTENTIKASI *
 ************************/

/**
 * Fungsi login user
 * @param PDO $pdo Koneksi database
 * @param string $username Username
 * @param string $password Password
 * @return array|bool Data user jika berhasil, false jika gagal
 */
function loginUser($pdo, $username, $password) {
    try {
        $query = "SELECT * FROM users WHERE username = :username AND is_active = 1";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->bindParam(':user_id', $user['user_id']);
            $updateStmt->execute();
            
            // Hapus password dari result untuk keamanan
            unset($user['password']);
            
            return $user;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log("Database Error (loginUser): " . $e->getMessage());
        return false;
    }
}

/**
 * Mendaftarkan user baru
 * @param PDO $pdo Koneksi database
 * @param array $userData Data user
 * @return bool True jika berhasil, false jika gagal
 */
function registerUser($pdo, $userData) {
    try {
        // Cek apakah username sudah ada
        $query = "SELECT COUNT(*) as count FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $userData['username']);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Username sudah digunakan'
            ];
        }
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user baru
        $query = "INSERT INTO users (username, password, name, role) 
                  VALUES (:username, :password, :name, :role)";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':username', $userData['username']);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':name', $userData['name']);
        $stmt->bindParam(':role', $userData['role']);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'User berhasil didaftarkan',
            'user_id' => $pdo->lastInsertId()
        ];
    } catch(PDOException $e) {
        error_log("Database Error (registerUser): " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/********************
 * FUNGSI REPORTING *
 ********************/

/**
 * Laporan penjualan harian
 * @param PDO $pdo Koneksi database
 * @param string $date Tanggal (YYYY-MM-DD)
 * @return array Data laporan
 */
function getDailySalesReport($pdo, $date) {
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getDailySalesReport): " . $e->getMessage());
        return [];
    }
}

/**
 * Laporan penjualan per produk
 * @param PDO $pdo Koneksi database
 * @param string $startDate Tanggal awal (YYYY-MM-DD)
 * @param string $endDate Tanggal akhir (YYYY-MM-DD)
 * @return array Data laporan
 */
function getProductSalesReport($pdo, $startDate, $endDate) {
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
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
function getAuditLog($pdo, $startDate, $endDate) {
    try {
        $query = "SELECT al.*, u.name as user_name
                  FROM audit_log al
                  LEFT JOIN users u ON al.user_id = u.user_id
                  WHERE DATE(al.action_date) BETWEEN :start_date AND :end_date
                  ORDER BY al.action_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getAuditLog): " . $e->getMessage());
        return [];
    }
}

/**
 * Log aktivitas user
 * Catatan: Tabel audit_log perlu dibuat jika ingin menggunakan fungsi ini
 * @param PDO $pdo Koneksi database
 * @param int $userId ID user
 * @param string $action Jenis aksi
 * @param string $details Detail aksi
 * @return bool True jika berhasil, false jika gagal
 */
function logActivity($pdo, $userId, $action, $details) {
    try {
        $query = "INSERT INTO audit_log (user_id, action, details)
                  VALUES (:user_id, :action, :details)";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':details', $details);
        return $stmt->execute();
    } catch(PDOException $e) {
        error_log("Database Error (logActivity): " . $e->getMessage());
        return false;
    }
}

/**
 * Mendapatkan semua kategori produk
 * @param PDO $pdo Koneksi database
 * @return array Daftar kategori
 */
function getAllCategories($pdo) {
    try {
        $query = "SELECT * FROM product_categories ORDER BY category_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getAllCategories): " . $e->getMessage());
        return [];
    }
}


/**
 * Mencari produk dengan cepat untuk halaman kasir
 * @param PDO $pdo Koneksi database
 * @param string $keyword Kata kunci pencarian
 * @return array Hasil pencarian produk dengan flag khusus untuk barcode
 */
function searchProductsForCashier($pdo, $keyword) {
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
        
        $stmtBarcode = $pdo->prepare($barcodeQuery);
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
        
        $stmt = $pdo->prepare($query);
        $keywordLike = "%$keyword%";
        $stmt->bindParam(':keyword', $keywordLike);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (searchProductsForCashier): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan produk berdasarkan kategori untuk kasir
 * @param PDO $pdo Koneksi database
 * @param int $categoryId ID kategori
 * @param int $limit Batasan jumlah hasil
 * @return array Daftar produk
 */
function getProductsByCategoryForCashier($pdo, $categoryId, $limit = 50) {
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getProductsByCategoryForCashier): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan produk populer untuk kasir
 * @param PDO $pdo Koneksi database
 * @param int $limit Batasan jumlah hasil
 * @return array Daftar produk populer
 */
function getPopularProductsForCashier($pdo, $limit = 12) {
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getPopularProductsForCashier): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan detail produk dengan info batch untuk kasir
 * @param PDO $pdo Koneksi database
 * @param int $productId ID produk
 * @return array|bool Detail produk atau false jika tidak ditemukan
 */
function getProductDetailForCashier($pdo, $productId) {
    try {
        // Dapatkan informasi produk
        $query = "SELECT p.*, c.category_name, mt.type_name
                  FROM products p
                  LEFT JOIN product_categories c ON p.category_id = c.category_id
                  LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                  WHERE p.product_id = :product_id AND p.is_active = 1";
        
        $stmt = $pdo->prepare($query);
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        $batches = $stmt->fetchAll();
        
        $product['batches'] = $batches;
        
        return $product;
    } catch(PDOException $e) {
        error_log("Database Error (getProductDetailForCashier): " . $e->getMessage());
        return false;
    }
}


/**
 * Mendapatkan metode pembayaran aktif
 * @param PDO $pdo Koneksi database
 * @return array Daftar metode pembayaran
 */
function getActivePaymentMethods($pdo) {
    try {
        $query = "SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY method_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getActivePaymentMethods): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan dokter untuk dropdown
 * @param PDO $pdo Koneksi database
 * @return array Daftar dokter
 */
function getDoctorsForDropdown($pdo) {
    try {
        $query = "SELECT doctor_id, doctor_name, specialization 
                  FROM doctors 
                  ORDER BY doctor_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Database Error (getDoctorsForDropdown): " . $e->getMessage());
        return [];
    }
}

/**
 * Mendapatkan informasi transaksi untuk cetak struk/nota
 * @param PDO $pdo Koneksi database
 * @param int $saleId ID transaksi
 * @return array|bool Informasi transaksi atau false jika tidak ditemukan
 */
function getReceiptData($pdo, $saleId) {
    try {
        // Dapatkan informasi header transaksi
        $query = "SELECT s.*, u.name as cashier_name, pm.method_name as payment_method,
                         d.doctor_name
                  FROM sales s
                  LEFT JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN payment_methods pm ON s.payment_method_id = pm.payment_method_id
                  LEFT JOIN doctors d ON s.doctor_id = d.doctor_id
                  WHERE s.sale_id = :sale_id";
        
        $stmt = $pdo->prepare($query);
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
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':sale_id', $saleId, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll();
        
        // Dapatkan informasi apotek
        // Catatan: Ini bisa diganti dengan pengambilan data dari tabel settings jika ada
        $pharmacy = [
            'name' => 'Farma Medika',
            'address' => 'Jl. Sultan Iskandar Muda, Punge Jurong, Kec. Meuraxa, Kota Banda Aceh, Aceh',
            'phone' => '021-12345678',
            'email' => 'info@apoteksehat.com',
            'footer_note' => 'Terima kasih atas kunjungan Anda. Semoga lekas sembuh.'
        ];
        
        return [
            'pharmacy' => $pharmacy,
            'sale' => $sale,
            'items' => $items
        ];
    } catch(PDOException $e) {
        error_log("Database Error (getReceiptData): " . $e->getMessage());
        return false;
    }
}

/**
 * Menghasilkan HTML untuk cetak struk
 * @param array $receiptData Data struk
 * @return string HTML struk
 */
function generateReceiptHTML($receiptData) {
    $pharmacy = $receiptData['pharmacy'];
    $sale = $receiptData['sale'];
    $items = $receiptData['items'];
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Struk Pembelian - ' . $sale['invoice_number'] . '</title>
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
                <div class="pharmacy-name">' . $pharmacy['name'] . '</div>
                <div class="pharmacy-info">' . $pharmacy['address'] . '</div>
                <div class="pharmacy-info">Telp: ' . $pharmacy['phone'] . '</div>
            </div>
            
            <div class="invoice-info">
                <div><span>No. Invoice:</span> <span>' . $sale['invoice_number'] . '</span></div>
                <div><span>Tanggal:</span> <span>' . date('d/m/Y H:i', strtotime($sale['sale_date'])) . '</span></div>
                <div><span>Kasir:</span> <span>' . $sale['cashier_name'] . '</span></div>';
    
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
        $html .= '
                <tr>
                    <td>
                        ' . $item['product_name'] . '<br>
                        <span class="item-detail">' . $item['kode_item'] . '</span>
                    </td>
                    <td>' . $item['quantity'] . ' ' . $item['unit'] . '</td>
                    <td style="text-align: right;">Rp ' . number_format($item['unit_price'], 0, ',', '.') . '</td>
                    <td style="text-align: right;">Rp ' . number_format($item['item_total'], 0, ',', '.') . '</td>
                </tr>';
    }
    
    $html .= '
            </table>
            
            <div class="totals">
                <div><span>Subtotal:</span> <span>Rp ' . number_format($sale['subtotal'], 0, ',', '.') . '</span></div>';
    
    if ($sale['tax_amount'] > 0) {
        $html .= '<div><span>Pajak:</span> <span>Rp ' . number_format($sale['tax_amount'], 0, ',', '.') . '</span></div>';
    }
    
    if ($sale['discount_amount'] > 0) {
        $html .= '<div><span>Diskon:</span> <span>Rp ' . number_format($sale['discount_amount'], 0, ',', '.') . '</span></div>';
    }
    
    $html .= '
                <div class="grand-total"><span>Total:</span> <span>Rp ' . number_format($sale['total_amount'], 0, ',', '.') . '</span></div>
                <div><span>Metode Pembayaran:</span> <span>' . $sale['payment_method'] . '</span></div>
            </div>
            
            <div class="footer">
                ' . $pharmacy['footer_note'] . '<br>
                ' . date('d/m/Y H:i:s') . '
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
?>
