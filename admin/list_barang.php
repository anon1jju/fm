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
                            <span class="font-medium leading-none dark:text-defaulttextcolor/70"><? echo $_SESSION["username"]; ?></span>
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
                                Mr.Jack Miller
                            </span>
                            <span class="block text-xs text-textmuted dark:text-textmuted/50">UI/UX Designer</span>
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

                <!-- Start::row-2 -->
                <div class="grid grid-cols-12 gap-x-6">
                    <div class="xl:col-span-12 col-span-12">
                        <div class="box">
                            <div class="box-header justify-between">
                                <div class="box-title"><i class="ri-box-3-fill text-2xl"></i>
                                    Daftar Barang
                                </div>
                                <div class="flex gap-2">
                                    <input class="ti-form-control" type="text" placeholder="Text atau Scan">
                                    <button type="button" class="hs-dropdown-toggle ti-btn ti-btn-sm !m-0 ti-btn-primary text-nowrap" data-hs-overlay="#hs-focus-management-modal"><i class="ri-add-fill"></i>Tambah Item</button>
                                    <div id="hs-focus-management-modal" class="hs-overlay hidden ti-modal shadow-md">
                                        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out">
                                            <div class="ti-modal-content">
                                                <div class="ti-modal-header">
                                                    <h4 class="ti-modal-title"><i class="ri-add-fill"></i></i>Tambah Produk</h4>
                                                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#hs-focus-management-modal">
                                                            <span class="sr-only">Close</span>
                                                            <svg class="w-3.5 h-3.5" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path
                                                                    d="M0.258206 1.00652C0.351976 0.912791 0.479126 0.860131 0.611706 0.860131C0.744296 0.860131 0.871447 0.912791 0.965207 1.00652L3.61171 3.65302L6.25822 1.00652C6.30432 0.958771 6.35952 0.920671 6.42052
                                                                    0.894471C6.48152 0.868271 6.54712 0.854471 6.61352 0.853901C6.67992 0.853321 6.74572 0.865971 6.80722 0.891111C6.86862 0.916251 6.92442 0.953381 6.97142 1.00032C7.01832 1.04727 7.05552 1.1031 7.08062
                                                                    1.16454C7.10572 1.22599 7.11842 1.29183 7.11782 1.35822C7.11722 1.42461 7.10342 1.49022 7.07722 1.55122C7.05102 1.61222 7.01292 1.6674 6.96522 1.71352L4.31871 4.36002L6.96522 7.00648C7.05632 7.10078 7.10672
                                                                    7.22708 7.10552 7.35818C7.10442 7.48928 7.05182 7.61468 6.95912 7.70738C6.86642 7.80018 6.74102 7.85268 6.60992 7.85388C6.47882 7.85498 6.35252 7.80458 6.25822 7.71348L3.61171 5.06702L0.965207 7.71348C0.870907
                                                                    7.80458 0.744606 7.85498 0.613506 7.85388C0.482406 7.85268 0.357007 7.80018 0.264297 7.70738C0.171597 7.61468 0.119017 7.48928 0.117877 7.35818C0.116737 7.22708 0.167126 7.10078 0.258206 7.00648L2.90471
                                                                    4.36002L0.258206 1.71352C0.164476 1.61976 0.111816 1.4926 0.111816 1.36002C0.111816 1.22744 0.164476 1.10028 0.258206 1.00652Z"
                                                                    fill="currentColor"
                                                                ></path>
                                                            </svg>
                                                    </button>
                                                </div>
                                                <form method="POST" action="../prosesdata/process_tambah.php">
                                                      <div class="ti-modal-body">
                                                        <label for="nama_produk" class="ti-form-label text-sm">Nama Produk</label>
                                                        <input type="text" name="nama_produk" id="nama_produk" class="ti-form-input text-sm py-1" required/>
                                                      </div>
                                                      <div class="ti-modal-body">
                                                        <label for="kode_item" class="ti-form-label text-sm">Kode Item</label>
                                                        <input type="text" name="kode_item" id="kode_item" class="ti-form-input text-sm py-1" />
                                                      </div>
                                                      <div class="ti-modal-body">
                                                        <label for="barcode" class="ti-form-label text-sm mb-1">Barcode</label>
                                                        <input type="text" name="barcode" id="barcode" class="ti-form-input text-sm py-1" />
                                                      </div>
                                                    <div class="ti-modal-body">
                                                        <label for="kategori" class="ti-form-label text-sm mb-1">Kategori</label>
                                                        <input type="text" name="kategori" id="kategori" class="ti-form-input text-sm py-1" />
                                                    </div>
                                                    <div class="ti-modal-footer">
                                                        <button type="button" class="hs-dropdown-toggle ti-btn btn-wave ti-btn-danger" data-hs-overlay="#hs-focus-management-modal">Tutup</button>
                                                        <button type="submit" class="ti-btn btn-wave ti-btn-success">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table border border-defaultborder dark:border-defaultborder/10 text-nowrap">
                                        <thead>
                                            <tr class="border-b border-defaultborder dark:border-defaultborder/10">
                                                <th scope="col">ID</th>
                                                <th scope="col">Product</th>
                                                <th scope="col">Kode Item</th>
                                                <th scope="col">Barcode</th>
                                                <th scope="col">Kategori</th>
                                                <th scope="col">Modal</th>
                                                <th scope="col">Jual</th>
                                                <th scope="col">Unit</th>
                                                <th scope="col">Stok</th>
                                                <th scope="col">Stok Minimum</th>
                                                <th scope="col">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="product-list border-b border-defaultborder dark:border-defaultborder/10">
                                                
                                                <td>
                                                    <div class="flex">
                                                        
                                                        <div class="ms-2">
                                                            <p class="text-sm font-semibold mb-0 flex items-center">Wooden Sofa</p>
                                                            <p class="text-xs text-textmuted font-semibold dark:text-textmuted/50 mb-0">Accusam Brand</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>24234</td>
                                                <td>24234</td>
                                                <td>3190050181</td>
                                                <td>283</td>
                                                <td>25000</td>
                                                <td>25000</td>
                                                <td>34</td>
                                                <td>345</td>
                                                <td>67</td>
                                                <td>
                                                    <div class="hstack gap-2 text-[15px]">
                                                        <a aria-label="anchor" href="edit-products.html"
                                                            class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-primary"><i
                                                                class="ri-edit-line"></i></a>
                                                        <a aria-label="anchor" href="javascript:void(0);"
                                                            class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-danger product-btn"><i
                                                                class="ri-delete-bin-line"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="product-list border-b border-defaultborder dark:border-defaultborder/10">
                                                
                                                <td>
                                                    <div class="flex">
                                                        
                                                        <div class="ms-2">
                                                            <p class="font-semibold mb-0 flex items-center"><a
                                                                    href="javascript:void(0);">Wooden Sofa</a></p>
                                                            <p class="text-xs text-textmuted dark:text-textmuted/50 mb-0">Accusam Brand</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span>Electronic</span>
                                                </td>
                                                <td>24234</td>
                                                <td>$1,299</td>
                                                <td>283</td>
                                                <td>345</td>
                                                <td>$1,299</td>
                                                <td>567</td>
                                                <td>567</td>
                                                <td>657</td>
                                                <td>
                                                    <div class="hstack gap-2 text-[15px]">
                                                        <a aria-label="anchor" href="edit-products.html"
                                                            class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-primary"><i
                                                                class="ri-edit-line"></i></a>
                                                        <a aria-label="anchor" href="javascript:void(0);"
                                                            class="ti-btn ti-btn-icon ti-btn-md ti-btn-soft-danger product-btn"><i
                                                                class="ri-delete-bin-line"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="box-footer">
                                <div class="flex items-center flex-wrap overflow-auto">
                                    
                                    <div class="ms-auto">
                                        <nav aria-label="..." class="me-4 sm:mb-0 mb-2">
                                            <ul class="ti-pagination">
                                                <li class="page-item disabled">
                                                    <a class="page-link px-3 py-[0.375rem]">Previous</a>
                                                </li>
                                                <li class="page-item"><a class="page-link active px-3 py-[0.375rem]"
                                                        href="javascript:void(0);">1</a></li>
                                                <li class="page-item " aria-current="page"><a class="page-link px-3 py-[0.375rem]" href="javascript:void(0);">2</a></li>
                                                
                                                    <a class="page-link px-3 py-[0.375rem]" href="javascript:void(0);">Next</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--End::row-2 -->

            </div>
        </div>
        <!-- End::app-content -->


        
<footer class="mt-auto py-4 bg-white dark:bg-bodybg text-center border-t border-defaultborder dark:border-defaultborder/10">
    <div class="container">
        <span class="text-textmuted dark:text-textmuted/50">
            Copyright © <span id="year"></span> 
            <a href="javascript:void(0);" class="text-dark font-medium dark:text-defaulttextcolor/80">Zynix</a>.
            Designed with <span class="bi bi-heart-fill text-danger"></span> by 
            <a href="https://spruko.com/" target="_blank">
                <span class="font-medium text-primary">Spruko</span>
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
