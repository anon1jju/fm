<?php
require_once 'functions.php';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

// Periksa apakah sesi masih ada
if ($farma->checkPersistentSession()) {
    // Ambil role pengguna dari sesi
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($role === 'admin') {
        header("Location: admin/admin_dashboard.php");
    } elseif ($role === 'cashier') {
        header("Location: cashier/cashier.php");
    } else {
        header("Location: signin.php"); // Default halaman jika role tidak ditentukan
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-vertical-style="overlay" class="light" data-header-styles="light" data-menu-styles="light" data-toggled="close">
    <head>
        <!-- Meta Data -->
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Farma Medika - Signin</title>
        <meta name="Description" content="Farma Medika" />
        <meta name="Author" content="Farma Medika" />
        <meta name="keywords" content="Farma Medika" />

        <!-- Favicon -->
        <link rel="icon" href="../assets/images/brand-logos/favicon.ico" type="image/x-icon" />

        <!-- Main Theme Js -->
        <script src="../assets/js/authentication-main.js"></script>

        <!-- Style Css -->
        <link href="../assets/css/styles.css" rel="stylesheet" />
    </head>

    <body class="authentication-background authenticationcover-background relative" id="particles-js">
        <!-- End Switcher -->

        <div class="container">
            <div class="grid grid-cols-12 justify-center authentication authentication-basic items-center h-full">
                <div class="xxl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-3 sm:col-span-2 col-span-12"></div>
                <div class="xxl:col-span-4 xl:col-span-4 lg:col-span-6 md:col-span-6 sm:col-span-8 col-span-12 px-3">
                    <div class="box my-4 border z-10 relative border-defaultborder dark:border-defaultborder/10">
                        <div class="box-body p-0">
                            <div class="p-[3rem]">
                                <div class="flex items-center justify-center mb-3">
                                    <span class="auth-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" id="password">
                                            <path fill="#6446fe" d="M59,8H5A1,1,0,0,0,4,9V55a1,1,0,0,0,1,1H59a1,1,0,0,0,1-1V9A1,1,0,0,0,59,8ZM58,54H6V10H58Z" class="color1d1f47 svgShape"></path>
                                            <path
                                                fill="#6446fe"
                                                d="M36,35H28a3,3,0,0,1-3-3V27a3,3,0,0,1,3-3h8a3,3,0,0,1,3,3v5A3,3,0,0,1,36,35Zm-8-9a1,1,0,0,0-1,1v5a1,1,0,0,0,1,1h8a1,1,0,0,0,1-1V27a1,1,0,0,0-1-1Z"
                                                class="color0055ff svgShape"
                                            ></path>
                                            <path
                                                fill="#6446fe"
                                                d="M36 26H28a1 1 0 0 1-1-1V24a5 5 0 0 1 10 0v1A1 1 0 0 1 36 26zm-7-2h6a3 3 0 0 0-6 0zM32 31a1 1 0 0 1-1-1V29a1 1 0 0 1 2 0v1A1 1 0 0 1 32 31z"
                                                class="color0055ff svgShape"
                                            ></path>
                                            <path
                                                fill="#6446fe"
                                                d="M59 8H5A1 1 0 0 0 4 9v8a1 1 0 0 0 1 1H20.08a1 1 0 0 0 .63-.22L25.36 14H59a1 1 0 0 0 1-1V9A1 1 0 0 0 59 8zm-1 4H25l-.21 0a1.09 1.09 0 0 0-.42.2L19.73 16H6V10H58zM50 49H14a1 1 0 0 1-1-1V39a1 1 0 0 1 1-1H50a1 1 0 0 1 1 1v9A1 1 0 0 1 50 49zM15 47H49V40H15z"
                                                class="color1d1f47 svgShape"
                                            ></path>
                                            <circle cx="19.5" cy="43.5" r="1.5" fill="#6446fe" class="color0055ff svgShape"></circle>
                                            <circle cx="24.5" cy="43.5" r="1.5" fill="#6446fe" class="color0055ff svgShape"></circle>
                                            <circle cx="29.5" cy="43.5" r="1.5" fill="#6446fe" class="color0055ff svgShape"></circle>
                                            <circle cx="34.5" cy="43.5" r="1.5" fill="#6446fe" class="color0055ff svgShape"></circle>
                                            <circle cx="39.5" cy="43.5" r="1.5" fill="#6446fe" class="color0055ff svgShape"></circle>
                                            <circle cx="44.5" cy="43.5" r="1.5" fill="#6446fe" class="color0055ff svgShape"></circle>
                                            <path fill="#6446fe" d="M60 9a1 1 0 0 0-1-1H28.81l2.37-2.37A19.22 19.22 0 0 1 60 31zM35.19 56l-2.37 2.37A19.22 19.22 0 0 1 4 33V55a1 1 0 0 0 1 1z" opacity=".3" class="color0055ff svgShape"></path>
                                        </svg>
                                    </span>
                                </div>
                                <p class="h4 !font-semibold !mb-0 text-center">Farma Medika</p>
                                <p class="!mb-3 text-gray-500 dark:text-textmuted/50 font-semibold text-center">Silakan masuk sesuai akun anda</p>
                                <?php if ($error): ?>
                                <div class="alert alert-danger flex items-center" role="alert">
                                    <svg class="sm:flex-shrink-0 me-2 fill-danger" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="1.5rem" viewBox="0 0 24 24" width="1.5rem" fill="#000000">
                                        <g>
                                            <rect fill="none" height="24" width="24" />
                                        </g>
                                        <g>
                                            <g>
                                                <g>
                                                    <path d="M15.73,3H8.27L3,8.27v7.46L8.27,21h7.46L21,15.73V8.27L15.73,3z M19,14.9L14.9,19H9.1L5,14.9V9.1L9.1,5h5.8L19,9.1V14.9z" />
                                                    <rect height="6" width="2" x="11" y="7" />
                                                    <rect height="2" width="2" x="11" y="15" />
                                                </g>
                                            </g>
                                        </g>
                                    </svg>
                                    <div>
                                        Username dan Password anda tidak valid!
                                    </div>
                                </div>
                                <?php endif; ?>
                                <hr class="mb-3" />
                                <form method="POST" action="prosesdata/process_login.php">
                                    <div class="grid grid-cols-12 gap-y-3">
                                        <div class="xl:col-span-12 col-span-12">
                                            <label for="signup-firstname" class="ti-form-label text-dark">User Name</label>
                                            <div class="relative">
                                                <input type="text" name="userName" class="form-control form-control-lg" id="signup-firstname" placeholder="Enter User Name" required />
                                            </div>
                                        </div>
                                        <div class="xl:col-span-12 col-span-12 mb-2">
                                            <label for="signin-password" class="ti-form-label text-dark block">Password</label>
                                            <div class="relative">
                                                <input type="password" name="password" class="form-control form-control-lg" id="signin-password" placeholder="Password" required />
                                                <a href="javascript:void(0);" class="show-password-button text-textmuted dark:text-textmuted/50" onclick="createpassword('signin-password',this)" id="button-addon2">
                                                    <i class="ri-eye-off-line align-middle"></i>
                                                </a>
                                            </div>
                                            <div class="mt-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="defaultCheck1" />
                                                    <label class="form-check-label text-gray-600 dark:text-textmuted/50 font-se text-sm" for="defaultCheck1">
                                                        Remember password?
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="grid mt-4">
                                                <button type="submit" class="ti-btn ti-btn-lg ti-btn-primary w-full">Sign In</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="xxl:col-span-4 xl:col-span-4 lg:col-span-3 md:col-span-3 sm:col-span-2 col-span-12"></div>
            </div>
        </div>

        <!-- Particles JS -->
        <script src="../assets/libs/particles.js/particles.js"></script>

        <script src="../assets/js/basic-password.js"></script>

        <!-- Show Password JS -->
        <script src="../assets/js/show-password.js"></script>
    </body>
</html>
