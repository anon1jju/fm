<?php
require_once 'functions.php';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

// Periksa apakah sesi masih ada
if ($farma->checkPersistentSession()) {
    // Ambil role pengguna dari sesi
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($role === 'admin') {
        header("Location: admin_dashboard.php");
    } elseif ($role === 'cashier') {
        header("Location: cashier/cashier.php");
    } else {
        header("Location: default_dashboard.php"); // Default halaman jika role tidak ditentukan
    }
    exit;
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr" class="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Farma Medika | Sign In</title>
    <!-- Core CSS -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <!-- App Start -->
    <div id="root">
        <!-- App Layout -->
        <div class="app-layout-blank flex flex-auto flex-col h-[100vh]">
            <main class="h-full">
                <div class="page-container relative h-full flex flex-auto flex-col">
                    <div class="h-full">
                        <div class="container mx-auto flex flex-col flex-auto items-center justify-center min-w-0 h-full">
                            <div class="card min-w-[320px] md:min-w-[450px] card-shadow" role="presentation">
                                <div class="card-body md:p-10">
                                    <div class="text-center">
                                        <div class="logo">
                                            <img class="mx-auto" src="img/logo/logo-light-streamline.png" alt="Farma Medika Logo">
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <h3 class="mb-1">Farma Medika</h3>
                                            <p>Silahkan login dengan akun anda</p>
                                        </div>
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger">
                                                <div class="alert-content">
                                                    <span class="alert-icon">
                                                        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 20 20" aria-hidden="true" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </span>
                                                    <div>Username dan password salah!</div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <form action="prosesdata/process_login.php" method="POST">
                                                <div class="form-container vertical">
                                                    <div class="form-item vertical">
                                                        <label class="form-label mb-2">User Name</label>
                                                        <div>
                                                            <input
                                                                class="input"
                                                                type="text"
                                                                name="userName"
                                                                autocomplete="off"
                                                                placeholder="User Name"
                                                                required
                                                            >
                                                        </div>
                                                    </div>
                                                    <div class="form-item vertical">
                                                        <label class="form-label mb-2">Password</label>
                                                        <div>
                                                            <span class="input-wrapper">
                                                                <input
                                                                    class="input pr-8"
                                                                    type="password"
                                                                    name="password"
                                                                    autocomplete="off"
                                                                    placeholder="Password"
                                                                    required
                                                                >
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-between mb-6">
                                                        <label class="checkbox-label mb-0">
                                                            <input class="checkbox" type="checkbox" name="remember" value="true">
                                                            <span class="ltr:ml-2 rtl:mr-2">Remember Me</span>
                                                        </label>
                                                        <a class="text-primary-600 hover:underline" href="forgot-password-simple.html">Forgot Password?</a>
                                                    </div>
                                                    <button class="btn btn-solid w-full" type="submit">Sign In</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Core Vendors JS -->
    <script src="js/vendors.min.js"></script>

    <!-- Core JS -->
    <script src="js/app.min.js"></script>
</body>
</html>
