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
    
    /**
     * Fungsi untuk membuat nomor invoice
     * @return string Format invoice APT-YYYYMMDD-XXXX
     */
    public function generateInvoiceNumber($user)
    {
        return 'FM-'.$user.'-'. date('Ymd') . '-' . rand(1000, 9999);
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

    /**
     * Mendapatkan semua data produk
     * @return array Daftar produk
     */
    
    
    /*public function getAllProducts()
    {
        try {
            $query = "SELECT p.*, c.category_name, mt.type_name, pb.expiry_date
                      FROM products p
                      LEFT JOIN product_categories c ON p.category_id = c.category_id
                      LEFT JOIN medicine_types mt ON p.medicine_type_id = mt.medicine_type_id
                      LEFT JOIN product_batches pb ON p.product_id = pb.product_id
                      WHERE p.is_active = 1
                      ORDER BY p.product_name";
    
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
    
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database Error (getAllProducts): " . $e->getMessage());
            return [];
        }
    }*/
    
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
                    $item['quantity'] .
                    ' ' .
                    $item['unit'] .
                    '</td>
                        <td style="text-align: right;">Rp ' .
                    number_format($item['unit_price'], 0, ',', '.') .
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
