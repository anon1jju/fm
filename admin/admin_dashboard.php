<?php
require_once '../functions.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../signin.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" class="light" data-header-styles="light" data-menu-styles="light" data-width="fullwidth" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title> Farma Medika - Dashboard</title>
    <meta name="Description" content="Farma Medika">
    <meta name="Author" content="Farma Mediak">
	<meta name="keywords" content="Farma Medika">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/brand-logos/favicon.ico" type="image/x-icon">
    
    <!-- Choices JS -->
    <script src="../assets/libs/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Main Theme Js -->
    <script src="../assets/js/main.js"></script>

    <!-- Style Css -->
    <link href="../assets/css/styles.css" rel="stylesheet" >

    <!-- Node Waves Css -->
    <link href="../assets/libs/node-waves/waves.min.css" rel="stylesheet" > 

    <!-- Simplebar Css -->
    <link href="../assets/libs/simplebar/simplebar.min.css" rel="stylesheet" >
    
    <!-- Color Picker Css -->
    <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="../assets/libs/@simonwep/pickr/themes/nano.min.css">

    <!-- Choices Css -->
    <link rel="stylesheet" href="../assets/libs/choices.js/public/assets/styles/choices.min.css">

    <!-- FlatPickr CSS -->
    <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">

    <!-- Auto Complete CSS -->
    <link rel="stylesheet" href="../assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css">


<!-- FlatPickr CSS -->
<link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">

</head>

<body>

    <!-- ========== Switcher  ========== -->
<div id="hs-overlay-switcher" class="hs-overlay hidden ti-offcanvas ti-offcanvas-right" tabindex="-1">
  <div class="ti-offcanvas-header z-10 relative">
    <h5 class="ti-offcanvas-title">Switcher</h5>
    <button type="button" class="ti-btn flex-shrink-0 p-0 !mb-0  transition-none text-defaulttextcolor dark:text-defaulttextcolor/80 hover:text-gray-700 focus:ring-gray-400 focus:ring-offset-white  dark:hover:text-white/80 dark:focus:ring-white/10 dark:focus:ring-offset-white/10" data-hs-overlay="#hs-overlay-switcher">
      <span class="sr-only">Close modal</span>
      <i class="ri-close-circle-line leading-none text-lg"></i>
    </button>
  </div>
  <div class="ti-offcanvas-body !p-0 !border-b dark:border-white/10 z-10 relative !h-auto">
    <div class="flex rtl:space-x-reverse" aria-label="Tabs" role="tablist">
      <button type="button"
        class="hs-tab-active:bg-danger/20 w-full !py-2 !px-4 hs-tab-active:border-b-transparent text-[0.813rem] border-0 hs-tab-active:text-danger dark:hs-tab-active:bg-danger/20 dark:hs-tab-active:border-b-white/10 dark:hs-tab-active:text-danger -mb-px bg-white font-normal text-center  text-defaulttextcolor dark:text-defaulttextcolor/80 rounded-none hover:text-gray-700 dark:bg-bodybg dark:border-white/10  active"
        id="switcher-item-1" data-hs-tab="#switcher-1" aria-controls="switcher-1" role="tab">
        Theme Style
      </button>
      <button type="button"
        class="hs-tab-active:bg-danger/20 w-full !py-2 !px-4 hs-tab-active:border-b-transparent text-[0.813rem] border-0 hs-tab-active:text-danger dark:hs-tab-active:bg-danger/20 dark:hs-tab-active:border-b-white/10 dark:hs-tab-active:text-danger -mb-px  bg-white font-normal text-center  text-defaulttextcolor dark:text-defaulttextcolor/80 rounded-none hover:text-gray-700 dark:bg-bodybg dark:border-white/10  dark:hover:text-gray-300"
        id="switcher-item-2" data-hs-tab="#switcher-2" aria-controls="switcher-2" role="tab">
        Theme Colors
      </button>
    </div>
  </div>
  <div class="ti-offcanvas-body !p-0 !pb-[20rem] sm:!pb-[10rem]" id="switcher-body">
    <div id="switcher-1" role="tabpanel" aria-labelledby="switcher-item-1" class="">
      <div class="">
        <p class="switcher-style-head">Theme Color Mode:</p>
        <div class="grid grid-cols-3 switcher-style">
          <div class="flex items-center">
            <input type="radio" name="theme-style" class="ti-form-radio" id="switcher-light-theme" checked>
            <label for="switcher-light-theme"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2 font-normal">Light</label>
          </div>
          <div class="flex items-center">
            <input type="radio" name="theme-style" class="ti-form-radio" id="switcher-dark-theme">
            <label for="switcher-dark-theme"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2 font-normal">Dark</label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Directions:</p>
        <div class="grid grid-cols-3  switcher-style">
          <div class="flex items-center">
            <input type="radio" name="direction" class="ti-form-radio" id="switcher-ltr" checked>
            <label for="switcher-ltr" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">LTR</label>
          </div>
          <div class="flex items-center">
            <input type="radio" name="direction" class="ti-form-radio" id="switcher-rtl">
            <label for="switcher-rtl" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">RTL</label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Navigation Styles:</p>
        <div class="grid grid-cols-3  switcher-style">
          <div class="flex items-center">
            <input type="radio" name="navigation-style" class="ti-form-radio" id="switcher-vertical" checked>
            <label for="switcher-vertical"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Vertical</label>
          </div>
          <div class="flex items-center">
            <input type="radio" name="navigation-style" class="ti-form-radio" id="switcher-horizontal">
            <label for="switcher-horizontal"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Horizontal</label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Navigation Menu Style:</p>
        <div class="grid grid-cols-2 gap-2 switcher-style">
          <div class="flex">
            <input type="radio" name="navigation-data-menu-styles" class="ti-form-radio" id="switcher-menu-click"
              checked>
            <label for="switcher-menu-click" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Menu
              Click</label>
          </div>
          <div class="flex">
            <input type="radio" name="navigation-data-menu-styles" class="ti-form-radio" id="switcher-menu-hover">
            <label for="switcher-menu-hover" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Menu
              Hover</label>
          </div>
          <div class="flex">
            <input type="radio" name="navigation-data-menu-styles" class="ti-form-radio" id="switcher-icon-click">
            <label for="switcher-icon-click" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Icon
              Click</label>
          </div>
          <div class="flex">
            <input type="radio" name="navigation-data-menu-styles" class="ti-form-radio" id="switcher-icon-hover">
            <label for="switcher-icon-hover" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Icon
              Hover</label>
          </div>
        </div>
      </div>
      <div class=" sidemenu-layout-styles">
        <p class="switcher-style-head">Sidemenu Layout Syles:</p>
        <div class="grid grid-cols-2 gap-2 switcher-style">
          <div class="flex">
            <input type="radio" name="sidemenu-layout-styles" class="ti-form-radio" id="switcher-default-menu" checked>
            <label for="switcher-default-menu"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal ">Default
              Menu</label>
          </div>
          <div class="flex">
            <input type="radio" name="sidemenu-layout-styles" class="ti-form-radio" id="switcher-closed-menu">
            <label for="switcher-closed-menu" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal ">
              Closed
              Menu</label>
          </div>
          <div class="flex">
            <input type="radio" name="sidemenu-layout-styles" class="ti-form-radio" id="switcher-icontext-menu">
            <label for="switcher-icontext-menu" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal ">Icon
              Text</label>
          </div>
          <div class="flex">
            <input type="radio" name="sidemenu-layout-styles" class="ti-form-radio" id="switcher-icon-overlay">
            <label for="switcher-icon-overlay" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal ">Icon
              Overlay</label>
          </div>
          <div class="flex">
            <input type="radio" name="sidemenu-layout-styles" class="ti-form-radio" id="switcher-detached">
            <label for="switcher-detached"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal ">Detached</label>
          </div>
          <div class="flex">
            <input type="radio" name="sidemenu-layout-styles" class="ti-form-radio" id="switcher-double-menu">
            <label for="switcher-double-menu" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Double
              Menu</label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Page Styles:</p>
        <div class="grid grid-cols-3  switcher-style">
          <div class="flex">
            <input type="radio" name="data-page-styles" class="ti-form-radio" id="switcher-regular" checked>
            <label for="switcher-regular"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Regular</label>
          </div>
          <div class="flex">
            <input type="radio" name="data-page-styles" class="ti-form-radio" id="switcher-classic">
            <label for="switcher-classic"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Classic</label>
          </div>
          <div class="flex">
            <input type="radio" name="data-page-styles" class="ti-form-radio" id="switcher-modern">
            <label for="switcher-modern"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal"> Modern</label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Layout Width Styles:</p>
        <div class="grid grid-cols-3 switcher-style">
          <div class="flex">
            <input type="radio" name="layout-width" class="ti-form-radio" id="switcher-full-width" checked>
            <label for="switcher-full-width"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">FullWidth</label>
          </div>
          <div class="flex">
            <input type="radio" name="layout-width" class="ti-form-radio" id="switcher-boxed">
            <label for="switcher-boxed" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Boxed</label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Menu Positions:</p>
        <div class="grid grid-cols-3  switcher-style">
          <div class="flex">
            <input type="radio" name="data-menu-positions" class="ti-form-radio" id="switcher-menu-fixed" checked>
            <label for="switcher-menu-fixed"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Fixed</label>
          </div>
          <div class="flex">
            <input type="radio" name="data-menu-positions" class="ti-form-radio" id="switcher-menu-scroll">
            <label for="switcher-menu-scroll"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Scrollable </label>
          </div>
        </div>
      </div>
      <div>
        <p class="switcher-style-head">Header Positions:</p>
        <div class="grid grid-cols-3 switcher-style">
          <div class="flex">
            <input type="radio" name="data-header-positions" class="ti-form-radio" id="switcher-header-fixed" checked>
            <label for="switcher-header-fixed" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">
              Fixed</label>
          </div>
          <div class="flex">
            <input type="radio" name="data-header-positions" class="ti-form-radio" id="switcher-header-scroll">
            <label for="switcher-header-scroll"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Scrollable
            </label>
          </div>
        </div>
      </div>
      <div class="">
        <p class="switcher-style-head">Loader:</p>
        <div class="grid grid-cols-3 switcher-style">
          <div class="flex">
            <input type="radio" name="page-loader" class="ti-form-radio" id="switcher-loader-enable" checked>
            <label for="switcher-loader-enable" class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">
              Enable</label>
          </div>
          <div class="flex">
            <input type="radio" name="page-loader" class="ti-form-radio" id="switcher-loader-disable">
            <label for="switcher-loader-disable"
              class="text-[0.813rem] text-defaulttextcolor dark:text-defaulttextcolor/80 ms-2  font-normal">Disable
            </label>
          </div>
        </div>
    </div>
    </div>
    <div id="switcher-2" class="hidden" role="tabpanel" aria-labelledby="switcher-item-2">
      <div class="theme-colors">
        <p class="switcher-style-head">Menu Colors:</p>
        <div class="flex switcher-style space-x-3 rtl:space-x-reverse">
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-white" type="radio" name="menu-colors"
              id="switcher-menu-light" checked>
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Light Menu
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-dark" type="radio" name="menu-colors"
              id="switcher-menu-dark">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Dark Menu
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-primary" type="radio" name="menu-colors"
              id="switcher-menu-primary">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Color Menu
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-gradient" type="radio" name="menu-colors"
              id="switcher-menu-gradient">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Gradient Menu
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-transparent" type="radio" name="menu-colors"
              id="switcher-menu-transparent">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs !font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Transparent Menu
            </span>
          </div>
        </div>
        <div class="px-4 text-textmuted dark:text-textmuted/50 text-[.6875rem]"><b class="me-2 font-normal">Note:</b>If you want to change color Menu
          dynamically
          change from below Theme Primary color picker.</div>
      </div>
      <div class="theme-colors">
        <p class="switcher-style-head">Header Colors:</p>
        <div class="flex switcher-style space-x-3 rtl:space-x-reverse">
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-white !border" type="radio" name="header-colors"
              id="switcher-header-light" checked>
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Light Header
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-dark" type="radio" name="header-colors"
              id="switcher-header-dark">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Dark Header
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-primary" type="radio" name="header-colors"
              id="switcher-header-primary">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Color Header
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-gradient" type="radio" name="header-colors"
              id="switcher-header-gradient">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Gradient Header
            </span>
          </div>
          <div class="hs-tooltip ti-main-tooltip ti-form-radio switch-select ">
            <input class="hs-tooltip-toggle ti-form-radio color-input color-transparent" type="radio"
              name="header-colors" id="switcher-header-transparent">
            <span
              class="hs-tooltip-content ti-main-tooltip-content !py-1 !px-2 !bg-black text-xs font-medium !text-white shadow-sm dark:!bg-black"
              role="tooltip">
              Transparent Header
            </span>
          </div>
        </div>
        <div class="px-4 text-textmuted dark:text-textmuted/50 text-[.6875rem]"><b class="me-2 !font-normal">Note:</b>If you want to change color
          Header dynamically
          change from below Theme Primary color picker.</div>
      </div>
      <div class="theme-colors">
        <p class="switcher-style-head">Theme Primary:</p>
        <div class="flex switcher-style space-x-3 rtl:space-x-reverse">
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-primary-1" type="radio" name="theme-primary"
              id="switcher-primary">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-primary-2" type="radio" name="theme-primary"
              id="switcher-primary1">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-primary-3" type="radio" name="theme-primary"
              id="switcher-primary2">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-primary-4" type="radio" name="theme-primary"
              id="switcher-primary3">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-primary-5" type="radio" name="theme-primary"
              id="switcher-primary4">
          </div>
          <div class="ti-form-radio switch-select ps-0 mt-1 color-primary-light">
            <div class="theme-container-primary"></div>
            <div class="pickr-container-primary"></div>
          </div>
        </div>
      </div>
      <div class="theme-colors">
        <p class="switcher-style-head">Theme Background:</p>
        <div class="flex switcher-style space-x-3 rtl:space-x-reverse">
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-bg-1" type="radio" name="theme-background"
              id="switcher-background">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-bg-2" type="radio" name="theme-background"
              id="switcher-background1">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-bg-3" type="radio" name="theme-background"
              id="switcher-background2">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-bg-4" type="radio" name="theme-background"
              id="switcher-background3">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio color-input color-bg-5" type="radio" name="theme-background"
              id="switcher-background4">
          </div>
          <div class="ti-form-radio switch-select ps-0 mt-1 color-bg-transparent">
            <div class="theme-container-background hidden"></div>
            <div class="pickr-container-background"></div>
          </div>
        </div>
      </div>
      <div class="menu-image theme-colors">
        <p class="switcher-style-head">Menu With Background Image:</p>
        <div class="flex switcher-style space-x-3 rtl:space-x-reverse flex-wrap gap-3">
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio bgimage-input bg-img1" type="radio" name="theme-images" id="switcher-bg-img">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio bgimage-input bg-img2" type="radio" name="theme-images" id="switcher-bg-img1">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio bgimage-input bg-img3" type="radio" name="theme-images" id="switcher-bg-img2">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio bgimage-input bg-img4" type="radio" name="theme-images" id="switcher-bg-img3">
          </div>
          <div class="ti-form-radio switch-select">
            <input class="ti-form-radio bgimage-input bg-img5" type="radio" name="theme-images" id="switcher-bg-img4">
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="ti-offcanvas-footer sm:flex justify-between">
    <a href="javascript:void(0);" id="reset-all" class="ti-btn ti-btn-danger m-1 w-full">Reset</a>
  </div>
</div>
<!-- ========== END Switcher  ========== -->
    <!-- Loader -->
<div id="loader" >
    <img src="../assets/images/media/loader.svg" alt="">
</div>
<!-- Loader -->

    <div class="page">
        <!-- app-header -->
<header class="app-header sticky " id="header">

    <!-- Start::main-header-container -->
    <div class="main-header-container container-fluid">

        <!-- Start::header-content-left -->
        <div class="header-content-left">

            <!-- Start::header-element -->
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="index.html" class="header-logo">
                        <img src="../assets/images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
                        <img src="../assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
                        <img src="../assets/images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
                        <img src="../assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
                        <img src="../assets/images/brand-logos/toggle-dark.png" alt="logo" class="toggle-dark">
                        <img src="../assets/images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
                    </a>
                </div>
            </div>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <div class="header-element lg:mx-0">
                <a aria-label="Hide Sidebar" class="sidemenu-toggle hor-toggle horizontal-navtoggle header-link"
                    data-bs-toggle="sidebar" href="javascript:void(0);">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn" width="32" height="32"
                        fill="#000000" viewBox="0 0 256 256">
                        <path
                            d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z">
                        </path>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn-close" width="32"
                            height="32" fill="#000000" viewBox="0 0 256 256">
                            <path
                                d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z">
                            </path>
                        </svg>
                    </span>
                </a>
            </div>
            <!-- End::header-element -->

        </div>
        <!-- End::header-content-left -->

        <!-- Start::header-content-right -->
        <ul class="header-content-right">

            <!-- Start::header-element -->
            <li class="header-element search-dropdown hs-dropdown ti-dropdown md:!block !hidden [--placement:bottom-right] rtl:[--placement:bottom-left] [--auto-close:inside]">
                <!-- Start::header-link|dropdown-toggle -->
                <a aria-label="anchor" href="javascript:void(0);" class="header-link  hs-dropdown-toggle ti-dropdown-toggle" data-bs-auto-close="outside"
                    data-bs-toggle="dropdown">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" width="32" height="32"
                        fill="#000000" viewBox="0 0 256 256">
                        <path
                            d="M228.24,219.76l-51.38-51.38a86.15,86.15,0,1,0-8.48,8.48l51.38,51.38a6,6,0,0,0,8.48-8.48ZM38,112a74,74,0,1,1,74,74A74.09,74.09,0,0,1,38,112Z">
                        </path>
                    </svg>
                </a>
                <ul class="main-header-dropdown hs-dropdown-menu ti-dropdown-menu hidden overflow-visible"
                    data-popper-placement="none">
                    <li class="px-3 py-2">
                        <div class="header-element header-search md:block hidden my-auto">
                            <!-- Start::header-link -->
                            <input type="text" class="header-search-bar form-control" id="header-search" placeholder="Search for Results.." autocomplete="off" autocapitalize="off">
                            <a aria-label="anchor" href="javascript:void(0);" class="header-search-icon border-0">
                                <i class="bi bi-search header-link-icon"></i>
                            </a>
                            <!-- End::header-link -->
                        </div>
                    </li>
                </ul>
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element md:!hidden block">
                <a aria-label="anchor" href="javascript:void(0);" class="header-link" data-bs-toggle="modal"
                    data-hs-overlay="#header-responsive-search">
                    <!-- Start::header-link-icon -->
                    <i class="bi bi-search header-link-icon"></i>
                    <!-- End::header-link-icon -->
                </a>
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <!-- light and dark theme -->
            <li class="header-element header-theme-mode hidden !items-center sm:block md:!px-[0.5rem] px-2">
                <a aria-label="anchor"
                    class="hs-dark-mode-active:hidden flex hs-dark-mode group flex-shrink-0 justify-center items-center gap-2  rounded-full font-medium transition-all text-xs dark:bg-bgdark dark:hover:bg-black/20 text-textmuted dark:text-textmuted/50 dark:hover:text-white dark:focus:ring-white/10 dark:focus:ring-offset-white/10"
                    href="javascript:void(0);" data-hs-theme-click-value="dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"> <rect width="256" height="256" fill="none"></rect> <path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path> </svg>
                </a>
                <a aria-label="anchor"
                    class="hs-dark-mode-active:flex hidden hs-dark-mode group flex-shrink-0 justify-center items-center gap-2  rounded-full font-medium text-defaulttextcolor  transition-all text-xs dark:bg-bodybg dark:bg-bgdark dark:hover:bg-black/20  dark:hover:text-white dark:focus:ring-white/10 dark:focus:ring-offset-white/10"
                    href="javascript:void(0);" data-hs-theme-click-value="light">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"> <rect width="256" height="256" fill="none"></rect> <line x1="128" y1="40" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <circle cx="128" cy="128" r="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></circle> <line x1="64" y1="64" x2="56" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <line x1="64" y1="192" x2="56" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <line x1="192" y1="64" x2="200" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <line x1="192" y1="192" x2="200" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <line x1="40" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <line x1="128" y1="216" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <line x1="216" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> </svg>
                </a>
            </li>
            <!-- End light and dark theme -->

            <!-- Start::header-element -->
            <li class="header-element notifications-dropdown !block hs-dropdown ti-dropdown [--auto-close:inside]">
                <!-- Start::header-link|dropdown-toggle -->
                <a aria-label="anchor" href="javascript:void(0);" class="header-link hs-dropdown-toggle ti-dropdown-toggle"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" id="messageDropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon animate-bell" viewBox="0 0 256 256"> <rect width="256" height="256" fill="none"></rect> <path d="M96,192a32,32,0,0,0,64,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path> <path d="M184,24a102.71,102.71,0,0,1,36.29,40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path> <path d="M35.71,64A102.71,102.71,0,0,1,72,24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path> <path d="M56,112a72,72,0,0,1,144,0c0,35.82,8.3,56.6,14.9,68A8,8,0,0,1,208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,147.81,56,112Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path> </svg>
                    <span class="header-icon-pulse bg-secondary rounded pulse pulse-secondary"></span>
                    
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <!-- Start::main-header-dropdown -->
                <div class="main-header-dropdown hs-dropdown-menu ti-dropdown-menu hidden" data-popper-placement="none">
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <p class="mb-0 text-[16px] font-medium">Notifications</p>
                            <span class="badge bg-secondary-transparent rounded-sm font-semibold" id="notifiation-data">15 Unread</span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <nav class="-mb-0.5 flex space-x-6 rtl:space-x-reverse px-3" role="tablist">
                        <a class="hs-tab-active:font-semibold hs-tab-active:border-primary hs-tab-active:text-primary py-2 px-1 inline-flex items-center gap-2 border-b-[2px] border-transparent text-sm whitespace-nowrap text-defaulttextcolor  dark:text-[#8c9097] dark:text-white/50 hover:text-primary dark:hover:!text-primary active" href="javascript:void(0);" id="underline-item-1" data-hs-tab="#underline-1" aria-controls="underline-1">
                          Activity
                        </a>
                        <a class="hs-tab-active:font-semibold hs-tab-active:border-primary hs-tab-active:text-primary py-2 px-1 inline-flex items-center gap-2 border-b-[2px] border-transparent text-sm whitespace-nowrap text-defaulttextcolor  dark:text-[#8c9097] dark:text-white/50 hover:text-primary dark:hover:!text-primary" href="javascript:void(0);" id="underline-item-2" data-hs-tab="#underline-2" aria-controls="underline-2">
                          Notes
                        </a>
                        <a class="hs-tab-active:font-semibold hs-tab-active:border-primary hs-tab-active:text-primary py-2 px-1 inline-flex items-center gap-2 border-b-[2px] border-transparent text-sm whitespace-nowrap text-defaulttextcolor  dark:text-[#8c9097] dark:text-white/50 hover:text-primary dark:hover:!text-primary" href="javascript:void(0);" id="underline-item-3" data-hs-tab="#underline-3" aria-controls="underline-3">
                          Alerts
                        </a>
                    </nav>
                    <div class="dropdown-divider"></div>
                    <div>
                        <div id="underline-1" role="tabpanel" >
                            <ul class="list-none mb-0" id="header-notification-scroll">
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded svg-white">
                                                <img src="../assets/images/faces/2.jpg" alt="img"> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold">Way to go Jack Miller ! &#127881;</p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Congratulations on snagging the Monthly Best Seller Gold Badge !</div>
                                                <span class="text-textmuted dark:text-textmuted/50 text-xs">2 Min
                                                        Ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md bg-primary/10 !text-primary avatar-rounded svg-white">
                                               SM </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold">You’ve Got Mail!</p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Natalie has sent you a new message. Click here to view it.</div>
                                                    <span class="text-textmuted dark:text-textmuted/50 text-xs">5 Min
                                                        Ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded svg-white">
                                                <img src="../assets/images/faces/6.jpg" alt="img"> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"> Application Approved By Team &#128640;.</p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Congratulations! Your project application has been approved.</div>
                                                    <span class="text-textmuted dark:text-textmuted/50 text-xs">Yesterday</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md bg-secondary-transparent avatar-rounded svg-white">
                                                TR </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold">New Connection Request</p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Peter has sent you a connection request, please check your connection requests.</div>
                                                    <span class="text-textmuted dark:text-textmuted/50 text-xs">2 Days Ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded svg-white">
                                                <img src="../assets/images/faces/14.jpg" alt="img"> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold">Whoo! Your Order Is On the Way &#128666;.</p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Great news! Your order is now on its way to you.</div>
                                                    <span class="text-textmuted dark:text-textmuted/50 text-xs">1 Hr Ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div id="underline-2" class="hidden" role="tabpanel">
                            <ul class="list-none mb-0" id="header-notification-scroll1">
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-primary">
                                                <i class="ri-file-text-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">This Month Notes
                                                        Prepared</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Your notes for this month are ready and available. Please review them at your convenience.</div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">2 min ago</span>
                                            </div>
                                            <div>
                                            <a aria-label="anchor" href="javascript:void(0);"
                                                class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                    class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-secondary">
                                                <i class="ri-box-3-line text-[16px]"></i></span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Order <span
                                                            class="text-success">#54321</span> has been shipped.</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Order is on its way. You can track your shipment using the tracking number provided.</div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">2 min ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-success">
                                                <i class="ri-mail-open-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">A Email Will be
                                                        sent to customer.</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    An email regarding your recent order will be sent to the customer shortly.</div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">10 Days ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span class="avatar avatar-md avatar-rounded bg-info">
                                                <i class="ri-bank-card-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Payment Form
                                                        Will be Activated.</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                            The payment form will be activated shortly. Please ensure that all necessary details are correctly filled out to proceed with the payment process.</div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">Yesterday</span>
                                            </div>
                                            <div>
                                                <a aria-label="anchor" href="javascript:void(0);" class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1">
                                                    <i class="ri-close-line fs-5"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-warning">
                                                <i class="ri-group-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Meeting will be
                                                        held tomorrow</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    This is a reminder that a meeting will be held tomorrow. </div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">2 days ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div id="underline-3" class="hidden" role="tabpanel">
                            <ul class="list-none mb-0" id="header-notification-scroll2">
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-primary/10 !text-primary">
                                                <i class="ri-mail-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Mail Login with
                                                        Different Device</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Your email account has been accessed from a new device. If this was you, no action is needed. </div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">2 min ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-secondary/10 !text-secondary">
                                                <i class="ri-folder-warning-line text-[16px]"></i></span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Your
                                                        Subscription was expaired.</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Your subscription has expired. Please renew your subscription to continue enjoying uninterrupted access to our services. </div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">15 min ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-success/10 !text-success">
                                                <i class="ri-database-2-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Your storage
                                                        space is running low.</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Your storage space is running low. Please free up some space or upgrade your storage plan to avoid any interruptions. </div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">23 min ago</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50  dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-info/10 !text-info">
                                                <i class="ri-bank-card-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">Your changes
                                                        have been saved.</a></p>
                                               <div class="font-normal text-[13px] header-notification-text truncate">
                                                Your changes have been saved. If you need to make any more adjustments, feel free to do so. </div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">Yesterday</span>
                                            </div>
                                            <div> <a aria-label="anchor" href="javascript:void(0);"
                                                    class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1"><i
                                                        class="ri-close-line fs-5"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="ti-dropdown-item block">
                                    <div class="flex items-start">
                                        <div class="pe-2 leading-none"> <span
                                                class="avatar avatar-md avatar-rounded bg-warning/10 !text-warning">
                                                <i class="ri-stack-line text-[16px]"></i> </span>
                                        </div>
                                        <div class="flex-grow-1 flex items-start justify-between">
                                            <div>
                                                <p class="mb-0 font-semibold"><a href="javascript:void(0);">New updates are
                                                        available soon.</a></p>
                                                <div class="font-normal text-[13px] header-notification-text truncate">
                                                    Exciting new updates are on the way! Stay tuned for enhancements and new features that will be available soon. </div>
                                                <span class="text-[13px] text-textmuted dark:text-textmuted/50">2 days ago</span>
                                            </div>
                                            <div>
                                                <a aria-label="anchor" href="javascript:void(0);" class="min-w-fit-content text-textmuted dark:text-textmuted/50 dropdown-item-close1">
                                                    <i class="ri-close-line fs-5"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="p-4 empty-header-item1 border-t border-t-defaultborder">
                        <div class="grid text-center">
                            <a href="checkout.html" class="text-primary underline">View All  <i class="ri-arrow-right-line rtl:rotate-180"></i></a>
                        </div>
                    </div>
                    <div class="p-[3rem] empty-item1 hidden">
                        <div class="text-center">
                            <span class="avatar avatar-xl avatar-rounded bg-secondary/10 !text-secondary">
                                <i class="ri-notification-off-line text-[2rem]"></i>
                            </span>
                            <h6 class="font-medium mt-3">No New Notifications</h6>
                        </div>
                    </div>
                </div>
                <!-- End::main-header-dropdown -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element header-fullscreen">
                <!-- Start::header-link -->
                <a aria-label="anchor" onclick="openFullscreen();" href="javascript:void(0);" class="header-link">
                    <svg xmlns="http://www.w3.org/2000/svg" class="full-screen-open header-link-icon block" viewBox="0 0 256 256"> <rect width="256" height="256" fill="none"></rect> <polyline points="168 48 208 48 208 88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline> <polyline points="88 208 48 208 48 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline> <polyline points="208 168 208 208 168 208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline> <polyline points="48 88 48 48 88 48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline> </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="full-screen-close header-link-icon hidden" viewBox="0 0 256 256"> <rect width="256" height="256" fill="none"></rect> <polyline points="160 48 208 48 208 96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline> <line x1="144" y1="112" x2="208" y2="48" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> <polyline points="96 208 48 208 48 160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline> <line x1="112" y1="144" x2="48" y2="208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></line> </svg>
                </a>
                <!-- End::header-link -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element ti-dropdown hs-dropdown">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link hs-dropdown-toggle ti-dropdown-toggle dropdown-profile"
                    id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="flex items-center">
                        <div class="xl:me-2 me-0">
                            <img src="../assets/images/faces/2.jpg" alt="img" class="avatar avatar-sm avatar-rounded mb-0">
                        </div>
                        <div class="xl:block hidden leading-none">
                            <span class="font-medium leading-none dark:text-defaulttextcolor/70"><? echo $_SESSION["name"]; ?></span>
                        </div>
                    </div>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown hs-dropdown-menu ti-dropdown-menu pt-0 overflow-hidden header-profile-dropdown hidden"
                    aria-labelledby="mainHeaderProfile">
                    <li>
                        <div
                            class="py-2 px-4 text-center block">
                            <span class="font-semibold">
                                <? echo $_SESSION["name"]; ?>
                            </span>
                            <span class="block text-xs text-textmuted dark:text-textmuted/50"><? echo $_SESSION["username"]; ?></span>
                        </div>
                    </li>
                    <li><a class="ti-dropdown-item flex items-center" href="profile.html"><i
                                class="ti ti-user text-primary me-2 text-[1rem]"></i>Profile</a>
                    </li>
                    <li><a class="ti-dropdown-item flex items-center" href="mail.html"><i
                                class="ti ti-mail text-secondary me-2 text-[1rem]"></i>
                            Inbox</a></li>
                    <li><a class="ti-dropdown-item flex items-center" href="file-manager.html"><i
                                class="ti ti-checklist text-success klist me-2 text-[1rem]"></i>Task
                            Manger</a></li>
                    <li><a class="ti-dropdown-item flex items-center" href="mail-settings.html"><i
                                class="ti ti-settings text-info ings me-2 text-[1rem]"></i>Settings</a>
                    </li>
                    <li class="!border-t-0 "><a
                            class="ti-dropdown-item flex items-center" href="chat.html"><i
                                class="ti ti-headset text-warning set me-2 text-[1rem]"></i>Support</a>
                    </li>
                    <li class="py-2 px-3 ">
                        <a href="<?php echo $farma->logout; ?>" class=" ti-btn ti-btn-primary btn-wave !border-0 me-0 !m-0 waves-effect waves-light w-full" data-bs-toggle="dropdown" aria-expanded="false"> Log Out</a>
                      </li>
                </ul>
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element">
                <!-- Start::header-link|switcher-icon -->
                <a aria-label="anchor" href="javascript:void(0);" class="header-link switcher-icon" data-hs-overlay="#hs-overlay-switcher">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256"> <rect width="256" height="256" fill="none"></rect> <circle cx="128" cy="128" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></circle> <path d="M41.43,178.09A99.14,99.14,0,0,1,31.36,153.8l16.78-21a81.59,81.59,0,0,1,0-9.64l-16.77-21a99.43,99.43,0,0,1,10.05-24.3l26.71-3a81,81,0,0,1,6.81-6.81l3-26.7A99.14,99.14,0,0,1,102.2,31.36l21,16.78a81.59,81.59,0,0,1,9.64,0l21-16.77a99.43,99.43,0,0,1,24.3,10.05l3,26.71a81,81,0,0,1,6.81,6.81l26.7,3a99.14,99.14,0,0,1,10.07,24.29l-16.78,21a81.59,81.59,0,0,1,0,9.64l16.77,21a99.43,99.43,0,0,1-10,24.3l-26.71,3a81,81,0,0,1-6.81,6.81l-3,26.7a99.14,99.14,0,0,1-24.29,10.07l-21-16.78a81.59,81.59,0,0,1-9.64,0l-21,16.77a99.43,99.43,0,0,1-24.3-10l-3-26.71a81,81,0,0,1-6.81-6.81Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path> </svg>
                </a>
                <!-- End::header-link|switcher-icon -->
            </li>
            <!-- End::header-element -->

        </ul>
        <!-- End::header-content-right -->

    </div>
    <!-- End::main-header-container -->

</header>
<!-- /app-header -->
        <!-- Start::app-sidebar -->
<aside class="app-sidebar" id="sidebar">

    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        <a href="index.html" class="header-logo">
            <img src="../assets/images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
            <img src="../assets/images/brand-logos/toggle-dark.png" alt="logo" class="toggle-dark">
            <img src="../assets/images/brand-logos/desktop-dark.png" alt="logo" class="desktop-dark">
            <img src="../assets/images/brand-logos/desktop-white.png" alt="logo" class="desktop-white">
            <img src="../assets/images/brand-logos/toggle-logo.png" alt="logo" class="toggle-logo">
            <img src="../assets/images/brand-logos/toggle-white.png" alt="logo" class="toggle-white">
        </a>
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll">

        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-col sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
            </div>
            <ul class="main-menu">
                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Dashboards</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                        <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M104,216V152h48v64h64V120a8,8,0,0,0-2.34-5.66l-80-80a8,8,0,0,0-11.32,0l-80,80A8,8,0,0,0,40,120v96Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                        <span class="side-menu__label">Dashboards</span>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Dashboards</a>
                        </li>
                        <li class="slide">
                            <a href="admin_dashboard.php" class="side-menu__item">Sales</a>
                        </li>
                        <li class="slide">
                            <a href="notifikasi.php" class="side-menu__item">Notifikasi</a>
                        </li>
                        <li class="slide">
                            <a href="activity.php" class="side-menu__item">Aktifitas Log</a>
                        </li>
                        
                    </ul>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Stok Barang</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><rect x="48" y="48" width="64" height="64" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><rect x="144" y="48" width="64" height="64" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><rect x="48" y="144" width="64" height="64" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><rect x="144" y="144" width="64" height="64" rx="8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                        <span class="side-menu__label">Stocklist</span>
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Stocklist</a>
                        </li>
                        <li class="slide">
                            <a href="list_barang.php" class="side-menu__item">Daftar Barang</a>
                        </li>
                        <li class="slide">
                            <a href="kategori_barang.php" class="side-menu__item">Kategori</a>
                        </li>
                        <li class="slide">
                            <a href="stok_minimum.php" class="side-menu__item">Stok Minimum</a>
                        </li>
                        <li class="slide">
                            <a href="exp_return.php" class="side-menu__item">Kadaluarsa & Return</a>
                        </li>
                        <li class="slide">
                            <a href="stok_in_out.php" class="side-menu__item">Stok Masuk/Keluar</a>
                        </li>
                    </ul>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Laporan</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <svg class="h-5 w-5 text-gray-400 mr-2"  width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">  <path stroke="none" d="M0 0h24v24H0z"/>  <rect x="4" y="3" width="16" height="18" rx="2" />  <rect x="8" y="7" width="8" height="3" rx="1" />  <line x1="8" y1="14" x2="8" y2="14.01" />  <line x1="12" y1="14" x2="12" y2="14.01" />  <line x1="16" y1="14" x2="16" y2="14.01" />  <line x1="8" y1="17" x2="8" y2="17.01" />  <line x1="12" y1="17" x2="12" y2="17.01" />  <line x1="16" y1="17" x2="16" y2="17.01" /></svg>
                        <span class="side-menu__label">Transaksi</span>
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Transaksi</a>
                        </li>
                        <li class="slide">
                            <a href="penjualan_kasir.php" class="side-menu__item">Penjualan (Kasir)</a>
                        </li>
                        <li class="slide">
                            <a href="daftar_penjualan.php" class="side-menu__item">Daftar Penjualan</a>
                        </li>
                        <li class="slide">
                            <a href="return_penjualan.php" class="side-menu__item">Retur Penjualan</a>
                        </li>
                    </ul>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <svg class="h-5 w-5 text-gray-400 mr-2"  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="side-menu__label">Keuangan</span>
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Keuangan</a>
                        </li>
                        <li class="slide">
                            <a href="pemasukan.php" class="side-menu__item">Pemasukan</a>
                        </li>
                        <li class="slide">
                            <a href="pengeluaran.php" class="side-menu__item">Pengeluaran</a>
                        </li>
                        <li class="slide">
                            <a href="hutang_pelanggan.php" class="side-menu__item">Hutang</a>
                        </li>
                    </ul>
                </li>
                <!-- End::slide -->

                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Supplier</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <svg class="h-5 w-5 text-gray-400 mr-2"  width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">  <path stroke="none" d="M0 0h24v24H0z"/>  <circle cx="7" cy="17" r="2" />  <circle cx="17" cy="17" r="2" />  <path d="M5 17h-2v-11a1 1 0 0 1 1 -1h9v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5" /></svg>
                        <span class="side-menu__label">Supplier</span>
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Supplier</a>
                        </li>
                        <li class="slide">
                            <a href="daftar_supplier.php" class="side-menu__item">Daftar Supplier</a>
                        </li>
                        <li class="slide">
                            <a href="pembelian_supplier.php" class="side-menu__item">Pembelian per Supplier</a>
                        </li>
                        <li class="slide">
                            <a href="hutang_supplier.php" class="side-menu__item">Hutang (Supplier)</a>
                        </li>
                    
                    </ul>
                </li>
                <!-- End::slide -->

                
                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Users</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide has-sub">
                    <a href="javascript:void(0);" class="side-menu__item">
                        <svg class="h-5 w-5 text-gray-400 mr-2"  fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>

                        <span class="side-menu__label">Users</span>
                        <i class="ri-arrow-right-s-line side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide side-menu__label1">
                            <a href="javascript:void(0)">Users</a>
                        </li>
                        <li class="slide">
                            <a href="list_users.php" class="side-menu__item">Daftar Staf</a>
                        </li>
                        <li class="slide">
                            <a href="aktifitas_users.php" class="side-menu__item">Aktivitas Pengguna</a>
                        </li>
                    </ul>
                </li>
                <!-- End::slide -->

            </ul>
            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
        </nav>
        <!-- End::nav -->

    </div>
    <!-- End::main-sidebar -->

</aside>
<!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">

                <!-- Start::page-header -->
                <div class="flex items-center justify-between page-header-breadcrumb flex-wrap gap-2">
                    <div>
                        <p class="font-semibold text-xl !mb-0">Selamat Datang, <? echo $_SESSION["username"]; ?></p>
                        <p class="text-[13px] text-textmuted dark:text-textmuted/50 !mb-0">Ringkasan Aktifitas Farma Medika</p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <div class="form-group">
                            <div class="input-group boder border-defaultborder dark:border-defaultborder/10">
                                <div class="input-group-text bg-white dark:bg-bodybg border-0 pe-0"> <i class="ri-calendar-line"></i> </div>
                                <input type="text" class="form-control breadcrumb-input border-0" id="daterange" placeholder="Search By Date Range">
                            </div>
                        </div>
                        <div class="ti-btn-list">
                            <button type="button" class="ti-btn ti-btn-primary btn-wave !border-0 me-0 !m-0">
                                <i class="ri-upload-2-line me-2"></i> Export report
                            </button>
                        </div>
                    </div>
                </div>
                <!-- End::page-header -->

                <!-- Start:: row-1 -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xxl:col-span-9 col-span-12">
                        <div class="grid grid-cols-12 gap-x-6">
                            <div class="xl:col-span-3 col-span-12">
                                <div class="box main-card-item primary">
                                    <div class="box-body">
                                        <div class="flex items-start justify-between mb-3 flex-wrap">
                                            <div>
                                                <span class="block mb-4 font-medium text-[17px]">Pemasukan</span>
                                                <h3 class="!font-semibold leading-none mb-0">32,981</h3>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-6">
                                                    <span class="avatar avatar-md bg-primary svg-white avatar-rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.0004 16C14.2095 16 16.0004 14.2091 16.0004 12 16.0004 9.79086 14.2095 8 12.0004 8 9.79123 8 8.00037 9.79086 8.00037 12 8.00037 14.2091 9.79123 16 12.0004 16ZM21.0049 4.00293H3.00488C2.4526 4.00293 2.00488 4.45064 2.00488 5.00293V19.0029C2.00488 19.5552 2.4526 20.0029 3.00488 20.0029H21.0049C21.5572 20.0029 22.0049 19.5552 22.0049 19.0029V5.00293C22.0049 4.45064 21.5572 4.00293 21.0049 4.00293ZM4.00488 15.6463V8.35371C5.13065 8.017 6.01836 7.12892 6.35455 6.00293H17.6462C17.9833 7.13193 18.8748 8.02175 20.0049 8.3564V15.6436C18.8729 15.9788 17.9802 16.8711 17.6444 18.0029H6.3563C6.02144 16.8742 5.13261 15.9836 4.00488 15.6463Z"></path></svg>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <a href="javascript:void(0);" class="text-textmuted dark:text-textmuted/50 underline font-medium text-[13px]">View all sales</a>
                                            <span class="text-success font-semibold"><i class="ti ti-arrow-narrow-up"></i>0.29%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xl:col-span-3 col-span-12">
                                <div class="box main-card-item">
                                    <div class="box-body">
                                        <div class="flex items-start justify-between mb-3 flex-wrap">
                                            <div>
                                                <span class="block mb-4 font-medium text-[17px]">Transaksi</span>
                                                <h3 class="!font-semibold leading-none mb-0">$14,32,145</h3>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-6">
                                                    <span class="avatar avatar-md bg-secondary svg-white avatar-rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 2H20C20.5523 2 21 2.44772 21 3V21C21 21.5523 20.5523 22 20 22H4C3.44772 22 3 21.5523 3 21V3C3 2.44772 3.44772 2 4 2ZM5 4V20H19V4H5ZM7 6H17V10H7V6ZM7 12H9V14H7V12ZM7 16H9V18H7V16ZM11 12H13V14H11V12ZM11 16H13V18H11V16ZM15 12H17V18H15V12Z"></path></svg>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <a href="javascript:void(0);" class="text-textmuted dark:text-textmuted/50 underline font-medium text-[13px]">complete revenue</a>
                                            <span class="text-danger font-semibold"><i class="ti ti-arrow-narrow-down"></i>3.45%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xl:col-span-3 col-span-12">
                                <div class="box main-card-item">
                                    <div class="box-body">
                                        <div class="flex items-start justify-between mb-3 flex-wrap">
                                            <div>
                                                <span class="block mb-4 font-medium text-[17px]">Profit</span>
                                                <h3 class="!font-semibold leading-none mb-0">4,678</h3>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-6">
                                                    <span class="avatar avatar-md bg-success svg-white avatar-rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><line x1="128" y1="24" x2="128" y2="232" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M184,88a40,40,0,0,0-40-40H112a40,40,0,0,0,0,80h40a40,40,0,0,1,0,80H104a40,40,0,0,1-40-40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <a href="javascript:void(0);" class="text-textmuted dark:text-textmuted/50 underline font-medium text-[13px]">Total page views</a>
                                            <span class="text-success font-semibold"><i class="ti ti-arrow-narrow-up"></i>11.54%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xl:col-span-3 col-span-12">
                                <div class="box main-card-item">
                                    <div class="box-body">
                                        <div class="flex items-start justify-between mb-3 flex-wrap">
                                            <div>
                                                <span class="block mb-4 font-medium text-[17px]">Stok Barang</span>
                                                <h3 class="!font-semibold leading-none mb-0">$645</h3>
                                            </div>
                                            <div class="text-end">
                                                <div class="mb-6">
                                                    <span class="avatar avatar-md bg-info svg-white avatar-rounded">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L21.5 6.5V17.5L12 23L2.5 17.5V6.5L12 1ZM5.49388 7.0777L12.0001 10.8444L18.5062 7.07774L12 3.311L5.49388 7.0777ZM4.5 8.81329V16.3469L11.0001 20.1101V12.5765L4.5 8.81329ZM13.0001 20.11L19.5 16.3469V8.81337L13.0001 12.5765V20.11Z"></path></svg>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <a href="javascript:void(0);" class="text-textmuted dark:text-textmuted/50 underline font-medium text-[13px]">Total profit earned</a>
                                            <span class="text-success font-semibold"><i class="ti ti-arrow-narrow-up"></i>0.18%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xl:col-span-4 col-span-12">
                                <div class="box">
                                    <div class="box-header justify-between">
                                        <div class="box-title">
                                            Visitors Report
                                        </div>
                                        <div class="ti-dropdown hs-dropdown">
                                            <a href="javascript:void(0);" class="p-2 text-xs text-textmuted dark:text-textmuted/50 hs-dropdown-toggle ti-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> Sort By <i class="ri-arrow-down-s-line align-middle ms-1 inline-block"></i> </a>
                                            <ul class="ti-dropdown-menu hs-dropdown-menu hidden" role="menu">
                                                <li><a class="ti-dropdown-item" href="javascript:void(0);">This Week</a></li>
                                                 <li><a class="ti-dropdown-item" href="javascript:void(0);">Last Week</a></li>
                                                 <li><a class="ti-dropdown-item" href="javascript:void(0);">This Month</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <div class="grid grid-cols-12 gap-y-4 sm:gap-x-4">
                                            <div class="xl:col-span-6 col-span-12">
                                                <div class="p-4 bg-light text-default rounded-sm border border-dashed border-defaultborder dark:border-defaultborder/10">
                                                    <span class="block mb-1">This Week</span>
                                                    <h5 class="!font-semibold leading-none mb-0 flex flex-wrap items-end">14,642<span class="text-success font-semibold text-[13px] ms-2 inline-flex items-center">0.64%<i class="ri-arrow-up-s-line ms-1"></i></span></h5>
                                                </div>
                                            </div>
                                            <div class="xl:col-span-6 col-span-12">
                                                <div class="p-4 bg-light text-default rounded-sm border border-dashed border-defaultborder dark:border-defaultborder/10">
                                                    <span class="block mb-1">Last Week</span>
                                                    <h5 class="!font-semibold leading-none mb-0 flex flex-wrap items-end">12,326<span class="text-danger font-semibold text-[13px] ms-2 inline-flex items-center">5.31%<i class="ri-arrow-down-s-line ms-1"></i></span></h5>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="visitors-report"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="xl:col-span-8 col-span-12">
                                <div class="box">
                                    <div class="box-header justify-between">
                                        <div class="box-title">
                                            Order Statistics
                                        </div>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="inline-flex rounded-sm">
                                                <button type="button"
                                                    class="ti-btn-group !border-0 !text-[0.8rem] !py-[0.25rem] !px-[0.8rem] bg-primary btn-wave text-white waves-effect waves-light">Day</button>
                                                <button type="button"
                                                    class="ti-btn-group !border-0 !text-[0.8rem] !py-[0.25rem] !px-[0.8rem] btn-wave ti-btn-soft-primary waves-effect waves-light">Week</button>
                                                <button type="button"
                                                    class="ti-btn-group !border-0 !text-[0.8rem] !py-[0.25rem] !px-[0.8rem] btn-wave ti-btn-soft-primary waves-effect waves-light">Month</button>
                                                <button type="button"
                                                    class="ti-btn-group !border-0 !text-[0.8rem] !py-[0.25rem] !px-[0.8rem] btn-wave ti-btn-soft-primary !rounded-s-none waves-effect waves-light">Year</button>
                                            </div>
                                            <div>
                                                <button type="button" class="ti-btn ti-btn-light border ti-btn-sm">Export<i class="ri-share-forward-line ms-1"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-body pb-0">
                                        <div id="ordered-statistics"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="xxl:col-span-3 col-span-12">
                        <div class="box overflow-hidden">
                            <div class="box-header justify-between">
                                <div class="box-title">
                                    Top Selling categories
                                </div><div class="ti-dropdown hs-dropdown">
                                    <a href="javascript:void(0);" class="p-0 text-xs text-textmuted dark:text-textmuted/50 hs-dropdown-toggle ti-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> Sort By <i class="ri-arrow-down-s-line align-middle ms-1 inline-block"></i> </a>
                                    <ul class="ti-dropdown-menu hs-dropdown-menu hidden" role="menu">
                                        <li><a class="ti-dropdown-item" href="javascript:void(0);">This Week</a></li>
                                         <li><a class="ti-dropdown-item" href="javascript:void(0);">Last Week</a></li>
                                         <li><a class="ti-dropdown-item" href="javascript:void(0);">This Month</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="box-body p-0">
                                <div id="top-categories" class="p-4 pb-3"></div>
                                <div class="border-t border-defaultborder dark:border-defaultborder/10">
                                    <ul class="ti-list-group list-group-flush top-categories border-0 rounded-none">
                                        <li class="ti-list-group-item">
                                            <div class="flex items-center justify-between">
                                                <div class="leading-none">
                                                    <div class="font-semibold mb-1">Electronics</div>
                                                    <div><span class="text-textmuted dark:text-textmuted/50 text-[13px]">Increased by <span class="text-success font-medium ms-1 inline-flex items-center">0.64%<i class="ti ti-trending-up ms-1"></i></span></span></div>
                                                </div>
                                                <div class="leading-none text-end">
                                                    <span class="block font-semibold h6 mb-1">1,754</span>
                                                    <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Sales</span>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item">
                                            <div class="flex items-center justify-between">
                                                <div class="leading-none">
                                                    <div class="font-semibold mb-1">Accessories</div>
                                                    <div><span class="text-textmuted dark:text-textmuted/50 text-[13px]">Decreased by <span class="text-danger font-medium ms-1 inline-flex items-center">2.75%<i class="ti ti-trending-down ms-1"></i></span></span></div>
                                                </div>
                                                <div class="leading-none text-end">
                                                    <span class="block font-semibold h6 mb-1">1,234</span>
                                                    <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Sales</span>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item">
                                            <div class="flex items-center justify-between">
                                                <div class="leading-none">
                                                    <div class="font-semibold mb-1">Home Appliances</div>
                                                    <div><span class="text-textmuted dark:text-textmuted/50 text-[13px]">Increased by <span class="text-success font-medium ms-1 inline-flex items-center">1.54%<i class="ti ti-trending-up ms-1"></i></span></span></div>
                                                </div>
                                                <div class="leading-none text-end">
                                                    <span class="block font-semibold h6 mb-1">878</span>
                                                    <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Sales</span>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item">
                                            <div class="flex items-center justify-between">
                                                <div class="leading-none">
                                                    <div class="font-semibold mb-1">Beauty Products</div>
                                                    <div><span class="text-textmuted dark:text-textmuted/50 text-[13px]">Increased by <span class="text-success font-medium ms-1 inline-flex items-center">1.54%<i class="ti ti-trending-up ms-1"></i></span></span></div>
                                                </div>
                                                <div class="leading-none text-end">
                                                    <span class="block font-semibold h6 mb-1">270</span>
                                                    <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Sales</span>
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item">
                                            <div class="flex items-center justify-between">
                                                <div class="leading-none">
                                                    <div class="font-semibold mb-1">Furniture</div>
                                                    <div><span class="text-textmuted dark:text-textmuted/50 text-[13px]">Decreased by <span class="text-danger font-medium ms-1 inline-flex items-center">0.12%<i class="ti ti-trending-down ms-1"></i></span></span></div>
                                                </div>
                                                <div class="leading-none text-end">
                                                    <span class="block font-semibold h6 mb-1">456</span>
                                                    <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Sales</span>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End:: row-1 -->

                <!-- Start:: row-2 -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xxl:col-span-3 col-span-12">
                        <div class="box">
                            <div class="box-header">
                                <div class="box-title">
                                    Country Wise Sales
                                </div>
                            </div>
                            <div class="box-body">
                                <ul class="list-unstyled mb-0 top-country-sales">
                                    <li>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="leading-none">
                                                <span class="avatar avatar-md p-2 bg-light border dark:border-defaultborder/10 avatar-rounded">
                                                    <img src="../assets/images/flags/us_flag.jpg" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto leading-none">
                                                <span class="font-semibold mb-2 block">United States</span>
                                                <span class="block text-textmuted dark:text-textmuted/50 text-[13px]">32,190 Sales</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-default h6 font-semibold mb-0">$32,190</span>
                                                <span class="text-success font-medium block"><i class="ti ti-arrow-narrow-up"></i>0.27%</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="leading-none">
                                                <span class="avatar avatar-md p-2 bg-light border dark:border-defaultborder/10 avatar-rounded">
                                                    <img src="../assets/images/flags/germany_flag.jpg" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto leading-none">
                                                <span class="font-semibold mb-2 block">Germany</span>
                                                <span class="block text-textmuted dark:text-textmuted/50 text-[13px]">8,798 Sales</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-default h6 font-semibold mb-0">$29,234</span>
                                                <span class="text-success font-medium block"><i class="ti ti-arrow-narrow-up"></i>0.12%</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="leading-none">
                                                <span class="avatar avatar-md p-2 bg-light border dark:border-defaultborder/10 avatar-rounded">
                                                    <img src="../assets/images/flags/mexico_flag.jpg" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto leading-none">
                                                <span class="font-semibold mb-2 block">Mexico</span>
                                                <span class="block text-textmuted dark:text-textmuted/50 text-[13px]">16,885 Sales</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-default h6 font-semibold mb-0">$26,166</span>
                                                <span class="text-danger font-medium block"><i class="ti ti-arrow-narrow-down"></i>0.75%</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="leading-none">
                                                <span class="avatar avatar-md p-2 bg-light border dark:border-defaultborder/10 avatar-rounded">
                                                    <img src="../assets/images/flags/uae_flag.jpg" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto leading-none">
                                                <span class="font-semibold mb-2 block">Uae</span>
                                                <span class="block text-textmuted dark:text-textmuted/50 text-[13px]">14,885 Sales</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-default h6 font-semibold mb-0">$24,263</span>
                                                <span class="text-success font-medium block"><i class="ti ti-arrow-narrow-up"></i>1.45%</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="leading-none">
                                                <span class="avatar avatar-md p-2 bg-light border dark:border-defaultborder/10 avatar-rounded">
                                                    <img src="../assets/images/flags/argentina_flag.jpg" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto leading-none">
                                                <span class="font-semibold mb-2 block">Argentina</span>
                                                <span class="block text-textmuted dark:text-textmuted/50 text-[13px]">17,578 Sales</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-default h6 font-semibold mb-0">$23,897</span>
                                                <span class="text-success font-medium block"><i class="ti ti-arrow-narrow-up"></i>0.36%</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center flex-wrap gap-2">
                                            <div class="leading-none">
                                                <span class="avatar avatar-md p-2 bg-light border dark:border-defaultborder/10 avatar-rounded">
                                                    <img src="../assets/images/flags/russia_flag.jpg" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto leading-none">
                                                <span class="font-semibold mb-2 block">Russia</span>
                                                <span class="block text-textmuted dark:text-textmuted/50 text-[13px]">10,118 Sales</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-default h6 font-semibold mb-0">$20,212</span>
                                                <span class="text-danger font-medium block"><i class="ti ti-arrow-narrow-down"></i>0.68%</span>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="xxl:col-span-3 col-span-12">
                        <div class="box">
                            <div class="box-header">
                                <div class="box-title">
                                    Visitors By Gender
                                </div>
                            </div>
                            <div class="box-body">
                                <div id="segmentation"></div>
                                <div>
                                    <ul class="ti-list-group segmentation-list">
                                        <li class="ti-list-group-item male">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <span class="block font-semibold">Male<span class="text-success font-medium ms-1 inline-flex items-center"><i class="ti ti-arrow-narrow-up"></i>0.78%</span></span>
                                                </div>
                                                <div class="h6 mb-0 font-semibold">
                                                    18,235
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item female">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <span class="block font-semibold">Female<span class="text-danger font-medium ms-1 inline-flex"><i class="ti ti-arrow-narrow-down"></i>1.57%</span></span>
                                                </div>
                                                <div class="h6 mb-0 font-semibold">
                                                    12,743
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item others">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <span class="block font-semibold">Others<span class="text-success font-medium ms-1 inline-flex items-center"><i class="ti ti-arrow-narrow-up"></i>0.32%</span></span>
                                                </div>
                                                <div class="h6 mb-0 font-semibold">
                                                    5,369
                                                </div>
                                            </div>
                                        </li>
                                        <li class="ti-list-group-item not-mentioned">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <span class="block font-semibold">Not Mentioned<span class="text-success font-medium ms-1 inline-flex items-center"><i class="ti ti-arrow-narrow-up"></i>19.45%</span></span>
                                                </div>
                                                <div class="h6 mb-0 font-semibold">
                                                    16,458
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="xxl:col-span-3 col-span-12">
                        <div class="box">
                            <div class="box-header">
                                <div class="box-title">
                                    Recent Activity
                                </div>
                            </div>
                            <div class="box-body">
                                <ul class="list-unstyled recent-activity-list">
                                    <li>
                                        <div class="flex justify-between items-start gap-2 pe-4">
                                            <div>
                                                <span class="block">Jane Smith ordered 5 new units of <span class="text-primary font-semibold">Product Y.</span></span>
                                            </div>
                                            <div class="recent-activity-time text-[13px]">
                                                <span class="text-textmuted dark:text-textmuted/50">12:45 Am</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex justify-between items-start gap-2 pe-4">
                                            <div>
                                                <span class="block">Scheduled demo with potential client DEF for next Tuesday</span>
                                            </div>
                                            <div class="recent-activity-time text-[13px]">
                                                <span class="text-textmuted dark:text-textmuted/50">03:26 Pm</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex justify-between items-start gap-2 pe-4">
                                            <div>
                                                <span class="block">Product X price updated to <span class="text-success font-semibold">$XX.XX</span> per every unit</span>
                                            </div>
                                            <div class="recent-activity-time text-[13px]">
                                                <span class="text-textmuted dark:text-textmuted/50">08:52 Pm</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex justify-between items-start gap-2 pe-4">
                                            <div>
                                                <span class="block">Database backup completed successfully</span>
                                            </div>
                                            <div class="recent-activity-time text-[13px]">
                                                <span class="text-textmuted dark:text-textmuted/50">02:54 Am</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex justify-between items-start gap-2 pe-4">
                                            <div>
                                                <span class="block">Generated <span class="text-warning font-semibold">$10,000</span> in revenue</span>
                                            </div>
                                            <div class="recent-activity-time text-[13px]">
                                                <span class="text-textmuted dark:text-textmuted/50">11:38 Am</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex justify-between items-start gap-2 pe-4">
                                            <div>
                                                <span class="block">Processed refund for Order <span class="text-danger font-semibold">#13579</span> due to defective item</span>
                                            </div>
                                            <div class="recent-activity-time text-[13px]">
                                                <span class="text-textmuted dark:text-textmuted/50">01:42 Pm</span>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="xxl:col-span-3 col-span-12">
                        <div class="box overflow-hidden">
                            <div class="box-header justify-between">
                                <div class="box-title">
                                    Recent Transactions
                                </div>
                                <a href="javascript:void(0);" class="text-[13px] text-textmuted dark:text-textmuted/50"> View All<i class="ti ti-arrow-narrow-right ms-1"></i> </a>
                            </div>
                            <div class="box-body p-0">
                                <div class="table-responsive">
                                    <table class="table text-nowrap">
                                        <thead>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <th scope="col" class="font-semibold text-[0.9rem]">Payment Mode</th>
                                                <th scope="col" class="!text-end font-semibold text-[0.9rem]">Amount Paid</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td>
                                                    <div class="flex items-start gap-2">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                                                                <i class="ri-paypal-line text-lg"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="block font-semibold mb-1">Paypal ****2783</span>
                                                            <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Online Transaction</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="!text-end">
                                                    <div>
                                                        <span class="block font-semibold mb-1 h6">$1,234.78</span>
                                                        <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Nov 22,2024</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td>
                                                    <div class="flex items-start gap-2">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-secondary-transparent">
                                                                <i class="ri-wallet-3-line text-lg"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="block font-semibold mb-1">Digital Wallet</span>
                                                            <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Online Transaction</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="!text-end">
                                                    <div>
                                                        <span class="block font-semibold mb-1 h6">$623.99</span>
                                                        <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Nov 22,2024</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td>
                                                    <div class="flex items-start gap-2">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                                                                <i class="ri-mastercard-line text-lg"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="block font-semibold mb-1">Mastro Card ****7893</span>
                                                            <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Card Payment</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="!text-end">
                                                    <div>
                                                        <span class="block font-semibold mb-1 h6">$1,324</span>
                                                        <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Nov 21,2024</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td>
                                                    <div class="flex items-start gap-2">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                                                                <i class="ti ti-currency-dollar text-lg"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="block font-semibold mb-1">Cash On Delivery</span>
                                                            <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Pay On Delivery</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="!text-end">
                                                    <div>
                                                        <span class="block font-semibold mb-1 h6">$1,123.49</span>
                                                        <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Nov 20,2024</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="border-bottom-0">
                                                    <div class="flex items-start gap-2">
                                                        <div>
                                                            <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                                                                <i class="ri-visa-line text-lg"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <span class="block font-semibold mb-1">Visa Card ****2563</span>
                                                            <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Card Payment</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border-bottom-0 !text-end">
                                                    <div>
                                                        <span class="block font-semibold mb-1 h6">$1,289</span>
                                                        <span class="block text-[13px] text-textmuted dark:text-textmuted/50">Nov 18,2024</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End:: row-2 -->

                <!-- Start:: row-3 -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xxl:col-span-9 col-span-12">
                        <div class="box">
                            <div class="box-header justify-between">
                                <div class="box-title">
                                    Recent Orders
                                </div>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <div>
                                        <input class="form-control" type="text" placeholder="Search Here" aria-label=".form-control-sm example">
                                    </div>
                                    <div class="ti-dropdown hs-dropdown">
                                        <a href="javascript:void(0);" class="ti-btn ti-btn-primary ti-btn-sm btn-wave hs-dropdown-toggle ti-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> Sort By<i class="ri-arrow-down-s-line align-middle ms-1 inline-block"></i>
                                        </a>
                                        <ul class="ti-dropdown-menu hs-dropdown-menu hidden" role="menu">
                                            <li><a class="ti-dropdown-item" href="javascript:void(0);">New</a></li>
                                            <li><a class="ti-dropdown-item" href="javascript:void(0);">Popular</a></li>
                                            <li><a class="ti-dropdown-item" href="javascript:void(0);">Relevant</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="box-body p-0">
                                <div class="table-responsive">
                                    <table class="table text-nowrap">
                                        <thead>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <th scope="row" class="ps-4"><input class="form-check-input" type="checkbox" id="checkboxNoLabeljob1" value="" aria-label="..."></th>
                                                <th scope="col">Product</th>
                                                <th scope="col">Category</th>
                                                <th scope="col">Quantity</th>
                                                <th scope="col">Customer</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Price</th>
                                                <th scope="col">Ordered Date</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td class="ps-4"><input class="form-check-input" type="checkbox" id="checkboxNoLabeljob2" value="" aria-label="..."></td>
                                                <td>
                                                    <div class="flex">
                                                        <span class="avatar avatar-md bg-light"><img src="../assets/images/ecommerce/png/1.png" class="" alt="..."></span>
                                                        <div class="ms-2">
                                                            <p class="font-semibold !mb-0 flex items-center"><a href="javascript:void(0);">Classic tufted leather sofa</a></p>
                                                            <p class="text-[13px] text-textmuted dark:text-textmuted/50 !mb-0">Pixel</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                   Furniture
                                                </td>
                                                <td class="text-center"> 1 </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="leading-none">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="../assets/images/faces/1.jpg" alt="">
                                                            </span>
                                                        </div>
                                                        <div>
                                                            Lucas Hayes
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-primary-transparent">Shipped</span></td>
                                                <td class="font-semibold">$1200.00</td>
                                                <td>2024-05-18</td>
                                                <td>
                                                    <div class="ti-btn-list flex gap-2">
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-primary btn-wave">
                                                            <i class="ri-eye-line"></i>
                                                        </button>
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-secondary btn-wave">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td class="ps-4"><input class="form-check-input" type="checkbox" id="checkboxNoLabeljob3" value="" aria-label="..." checked></td>
                                                <td>
                                                    <div class="flex">
                                                        <span class="avatar avatar-md bg-light"><img src="../assets/images/ecommerce/png/36.png" class="" alt="..."></span>
                                                        <div class="ms-2">
                                                            <p class="font-semibold !mb-0 flex items-center"><a href="javascript:void(0);">Rose Flower Pot</a></p>
                                                            <p class="text-[13px] text-textmuted dark:text-textmuted/50 !mb-0">Sonic</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    Decoration
                                                </td>
                                                <td class="text-center">
                                                    2
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="leading-none">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="../assets/images/faces/2.jpg" alt="">
                                                            </span>
                                                        </div>
                                                        <div>
                                                            Abigail Scott
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success-transparent">Delivered</span>
                                                </td>
                                                <td class="font-semibold">$250.00</td>
                                                <td>2024-05-19</td>
                                                <td>
                                                    <div class="ti-btn-list flex gap-2">
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-primary btn-wave">
                                                            <i class="ri-eye-line"></i>
                                                        </button>
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-secondary btn-wave">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td class="ps-4"><input class="form-check-input" type="checkbox" id="checkboxNoLabeljob4" value="" aria-label="..." checked></td>
                                                <td>
                                                    <div class="flex">
                                                        <span class="avatar avatar-md bg-light"><img src="../assets/images/ecommerce/png/31.png" class="" alt="..."></span>
                                                        <div class="ms-2">
                                                            <p class="font-semibold !mb-0 flex items-center"><a href="javascript:void(0);">Leather Handbag</a></p>
                                                            <p class="text-[13px] text-textmuted dark:text-textmuted/50 !mb-0">Elite</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    Fashion
                                                </td>
                                                <td class="text-center">
                                                    1
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="leading-none">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="../assets/images/faces/10.jpg" alt="">
                                                            </span>
                                                        </div>
                                                        <div>
                                                            Mason Wallace
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary-transparent">Processing</span>
                                                </td>
                                                <td class="font-semibold">$800.00</td>
                                                <td>2024-05-20</td>
                                                <td>
                                                    <div class="ti-btn-list flex gap-2">
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-primary btn-wave">
                                                            <i class="ri-eye-line"></i>
                                                        </button>
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-secondary btn-wave">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <td class="ps-4"><input class="form-check-input" type="checkbox" id="checkboxNoLabeljob5" value="" aria-label="..."></td>
                                                <td>
                                                    <div class="flex">
                                                        <span class="avatar avatar-md bg-light"><img src="../assets/images/ecommerce/png/14.png" class="" alt="..."></span>
                                                        <div class="ms-2">
                                                            <p class="font-semibold !mb-0 flex items-center"><a href="javascript:void(0);">Polaroid Medium Camera</a></p>
                                                            <p class="text-[13px] text-textmuted dark:text-textmuted/50 !mb-0">Bright</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                  Electronics
                                                </td>
                                                <td class="text-center">
                                                    3
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="leading-none">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="../assets/images/faces/3.jpg" alt="">
                                                            </span>
                                                        </div>
                                                        <div>
                                                            Chloe Lewis
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning-transparent">Pending</span>
                                                </td>
                                                <td class="font-semibold">$50.00</td>
                                                <td>2024-05-20</td>
                                                <td>
                                                    <div class="ti-btn-list flex gap-2">
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-primary btn-wave">
                                                            <i class="ri-eye-line"></i>
                                                        </button>
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-secondary btn-wave">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="ps-4 border-bottom-0"><input class="form-check-input" type="checkbox" id="checkboxNoLabeljob7" value="" aria-label="..."></td>
                                                <td class="border-bottom-0">
                                                    <div class="flex">
                                                        <span class="avatar avatar-md bg-light"><img src="../assets/images/ecommerce/png/13.png" class="" alt="..."></span>
                                                        <div class="ms-2">
                                                            <p class="font-semibold !mb-0 flex items-center"><a href="javascript:void(0);">Digital Watch</a></p>
                                                            <p class="text-[13px] text-textmuted dark:text-textmuted/50 !mb-0">Nova</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border-bottom-0">
                                                    Fashion
                                                </td>
                                                <td class="border-bottom-0 text-center">
                                                    2
                                                </td>
                                                <td class="border-bottom-0">
                                                    <div class="flex items-center gap-2">
                                                        <div class="leading-none">
                                                            <span class="avatar avatar-xs avatar-rounded">
                                                                <img src="../assets/images/faces/11.jpg" alt="">
                                                            </span>
                                                        </div>
                                                        <div>
                                                            Henry Morgan
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="border-bottom-0">
                                                    <span class="badge bg-primary-transparent">Shipped</span>
                                                </td>
                                                <td class="font-semibold border-bottom-0">$100.00</td>
                                                <td class="border-bottom-0">2024-05-21</td>
                                                <td class="border-bottom-0">
                                                    <div class="ti-btn-list flex gap-2">
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-primary btn-wave">
                                                            <i class="ri-eye-line"></i>
                                                        </button>
                                                        <button type="button" aria-label="button" class="ti-btn ti-btn-sm ti-btn-icon ti-btn-soft-secondary btn-wave">
                                                            <i class="ri-edit-line"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="flex items-center">
                                    <div> Showing 5 Entries <i class="bi bi-arrow-right ms-2 font-semibold"></i> </div>
                                    <div class="ms-auto">
                                        <nav aria-label="Page navigation" class="pagination-style-2">
                                            <ul class="ti-pagination mb-0 flex-wrap">
                                                <li class="page-item disabled">
                                                    <a class="page-link px-3 py-[0.375rem] !border-0"
                                                        href="javascript:void(0);">
                                                        Prev
                                                    </a>
                                                </li>
                                                <li class="page-item"><a class="page-link active px-3 py-[0.375rem] !border-0"
                                                        href="javascript:void(0);">1</a></li>
                                                <li class="page-item"><a class="page-link px-3 py-[0.375rem] !border-0"
                                                        href="javascript:void(0);">2</a></li>
                                                <li class="page-item">
                                                    <a class="page-link px-3 py-[0.375rem] !text-primary !border-0"
                                                        href="javascript:void(0);">
                                                        next
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="xxl:col-span-3 col-span-12">
                        <div class="box">
                            <div class="box-header">
                                <div class="box-title">
                                    Visitors By Browser
                                </div>
                            </div>
                            <div class="box-body">
                                <ul class="list-unstyled visitors-browser-list">
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/chrome.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">Chrome<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1">(Google LLC)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-success me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-up"></i>3.26%</span>13,546</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 70%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/edge.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">Edge<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1 font-normal">(Microsoft Corp)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-danger me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-down"></i>0.96%</span>11,322</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" style="width: 60%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/firefox.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">Firefox<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1 font-normal">(Mozilla Corp)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-success me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-up"></i>1.64%</span>6,236</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 30%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/safari.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">Safari<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1 font-normal">(Apple Inc)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-success me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-up"></i>6.38%</span>10,235</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width: 50%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/uc.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">UCBrowser<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1 font-normal">(UCWeb)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-success me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-up"></i>5.18%</span>14,965</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 80%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/opera.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">Opera<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1 font-normal">(Opera Software AS)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-danger me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-down"></i>1.65%</span>8,432</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 40%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <div>
                                                <span class="avatar avatar-md avatar-rounded bg-light p-2 border dark:border-defaultborder/10">
                                                    <img src="../assets/images/browsers/samsung-internet.png" alt="">
                                                </span>
                                            </div>
                                            <div class="flex-auto">
                                                <div class="flex items-center justify-between mb-2 flex-wrap">
                                                    <span class="font-semibold inline-flex">Samsung Internet<span class="text-textmuted dark:text-textmuted/50 text-[13px] ms-1 rtl:me-1 font-normal">(Samsung)</span></span>
                                                    <span class="block h6 mb-0 font-semibold"><span class="text-success me-2 rtl:ms-2 text-[13px] font-medium inline-flex"><i class="ti ti-arrow-narrow-up"></i>0.99%</span>4,134</span>
                                                </div>
                                                <div class="progress progress-xs" role="progressbar" aria-valuenow="36" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-orangemain" style="width: 36%">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End:: row-3 -->

            </div>
        </div>
        <!-- End::app-content -->

<footer class="mt-auto py-4 bg-white dark:bg-bodybg text-center border-t border-defaultborder dark:border-defaultborder/10">
            <div class="container">
                <span class="text-textmuted dark:text-textmuted/50">
                    <span id="year"></span> Developed by
                    <a href="javascript:void(0);" class="text-dark font-medium dark:text-defaulttextcolor/80">Afra</a>.
                    <a href="#" target="_blank">
                        <span class="font-medium text-primary"> Design by Spruko</span>
                    </a>
                    All rights reserved
                </span>
            </div>
        </footer>

        <div class="hs-overlay ti-modal hidden" id="header-responsive-search" tabindex="-1" aria-labelledby="header-responsive-search" aria-hidden="true">
    <div class="ti-modal-box">
        <div class="ti-modal-dialog">
            <div class="ti-modal-content">
                <div class="ti-modal-body">
                    <div class="input-group">
                        <input type="text" class="form-control border-end-0 !border-s" placeholder="Search Anything ..."
                            aria-label="Search Anything ..." aria-describedby="button-addon2">
                        <button class="ti-btn ti-btn-primary !m-0" type="button"
                            id="button-addon2"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>

    
<!-- Scroll To Top -->
<div class="scrollToTop">
    <span class="arrow"><i class="ti ti-arrow-big-up !text-[1rem]"></i></span>
</div>
<div id="responsive-overlay"></div>
<!-- Scroll To Top -->

<!-- Switch JS -->
<script src="../assets/js/switch.js"></script>

<!-- Popper JS -->
<script src="../assets/libs/@popperjs/core/umd/popper.min.js"></script>

<!-- Preline JS -->
<script src="../assets/libs/preline/preline.js"></script>

<!-- Defaultmenu JS -->
<script src="../assets/js/defaultmenu.min.js"></script>

<!-- Node Waves JS-->
<script src="../assets/libs/node-waves/waves.min.js"></script>

<!-- Sticky JS -->
<script src="../assets/js/sticky.js"></script>

<!-- Simplebar JS -->
<script src="../assets/libs/simplebar/simplebar.min.js"></script>
<script src="../assets/js/simplebar.js"></script>

<!-- Auto Complete JS -->
<script src="../assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js"></script>

<!-- Color Picker JS -->
<script src="../assets/libs/@simonwep/pickr/pickr.es5.min.js"></script>

<!-- Date & Time Picker JS -->
<script src="../assets/libs/flatpickr/flatpickr.min.js"></script>


    <!-- Apex Charts JS -->
    <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>

    <!-- Sales Dashboard -->
    <script src="../assets/js/sales-dashboard.js"></script>

    <!-- Custom JS -->
    <script src="../assets/js/custom.js"></script>

    
<!-- Custom-Switcher JS -->
<script src="../assets/js/custom-switcher.min.js"></script>

</body>

</html> 
