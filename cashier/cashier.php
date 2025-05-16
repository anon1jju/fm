<?php
/**
 * cashier.php - Halaman utama kasir
 * 
 * Halaman untuk operasi POS Apotek oleh kasir
 * 
 * @version 1.0.3
 * @date 2025-05-15
 */

// Include file fungsi cashier
require_once '../functions.php';

if (!$farma->checkPersistentSession()) {
    header("Location: ../signin.php");
    exit;
}

$products = $farma->getAllProducts(); 
$paymentMethods = $farma->getActivePaymentMethods();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farma Medika</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto p-4">
        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
            <div class="font-bold text-xl text-gray-800">Farma Medika</div>
            <div class="text-sm text-gray-600">
                <div>Kasir: <? echo $_SESSION["username"]?></div>
                <div>Tanggal: 15 Mei 2025</div>
            </div>
        </div>

        <!-- Payment Total Card -->
        <div class="bg-indigo-700 rounded-lg p-4 mb-4 text-white flex justify-between items-center">
            <div>
                <div class="text-sm">Total Pembayaran</div>
                <div class="text-4xl font-bold">Rp 0</div>
            </div>
            <button class="bg-white bg-opacity-20 p-3 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </button>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-4 gap-2 mb-4">
            <button class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">Bayar</button>
            <button class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">Cetak Struk</button>
            <button class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded">Tahan</button>
            <button class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded">Batal</button>
        </div>

        <!-- Navigation Dots -->
        <div class="flex justify-center mb-4">
            <div class="h-2 w-2 rounded-full bg-blue-500 mx-1"></div>
            <div class="h-2 w-2 rounded-full bg-gray-300 mx-1"></div>
            <div class="h-2 w-2 rounded-full bg-gray-300 mx-1"></div>
        </div>

        <!-- Main Content Area -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <!-- Search Bar - Top Left -->
            <div class="mb-4">
                <div class="flex">
                    <div class="relative w-full">
                        <input type="text" placeholder="Cari produk, kode item atau scan barcode" class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="px-4 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-gray-200 rounded-md flex items-center justify-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">Kopi Hitam</div>
                                        <div class="text-sm text-gray-500">Rp 25.000</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end">
                                    <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                        </svg>
                                    </button>
                                    <span class="mx-2 text-gray-700">1</span>
                                    <button class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium">Rp 25.000</td>
                            <td class="px-4 py-3 text-center">
                                <button class="text-red-500 hover:text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-gray-200 rounded-md flex items-center justify-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01
