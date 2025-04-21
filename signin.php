<!DOCTYPE html>
<html lang="en" dir="ltr" class="light">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>Farma Medika</title>

		<!-- Core CSS -->
		<link rel="stylesheet" type="text/css" href="css/style.css">
	</head>
	<body>
		<!-- App Start-->
		<div id="root">
			<!-- App Layout-->
			<div class="app-layout-blank flex flex-auto flex-col h-[100vh]">
				<main class="h-full">
                    <div class="page-container relative h-full flex flex-auto flex-col">
                        <div class="h-full">
                            <div class="container mx-auto flex flex-col flex-auto items-center justify-center min-w-0 h-full">
                                <div class="card min-w-[320px] md:min-w-[450px] card-shadow" role="presentation">
                                    <div class="card-body md:p-10">
                                        <div class="text-center">
                                            <div class="logo">
                                                <img class="mx-auto" src="img/logo/logo-light-streamline.png" alt="Elstar logo">
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="mb-4">
                                                <h3 class="mb-1">Welcome back!</h3>
                                                <p>Please enter your credentials to sign in!</p>
                                            </div>
                                            <div>
                                                <form action="#">
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
                                                                    value=""
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
                                                                        value=""
                                                                    >
                                                                    <div class="input-suffix-end">
                                                                        <span class="cursor-pointer text-xl">
                                                                            <svg
                                                                                stroke="currentColor"
                                                                                fill="none"
                                                                                stroke-width="2"
                                                                                viewBox="0 0 24 24"
                                                                                aria-hidden="true"
                                                                                height="1em"
                                                                                width="1em"
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                            >
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                                                            </svg>
                                                                        </span>
                                                                    </div>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex justify-between mb-6">
                                                            <label class="checkbox-label mb-0">
                                                                <input class="checkbox" type="checkbox" value="true" checked>
                                                                <span class="ltr:ml-2 rtl:mr-2">Remember Me</span>
                                                            </label>
                                                            <a class="text-primary-600 hover:underline" href="forgot-password-simple.html">Forgot Password?</a>
                                                        </div>
                                                        <button class="btn btn-solid w-full" type="submit">Sign In</button>
                                                        <div class="mt-4 text-center">
                                                            <span>Don't have an account yet?</span>
                                                            <a class="text-primary-600 hover:underline" href="signup-simple.html">Sign up</a>
                                                        </div>
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

		<!-- Other Vendors JS -->

		<!-- Page js -->

		<!-- Core JS -->
		<script src="js/app.min.js"></script>
	</body>

</html>
