@php
$sidebarServerMenus = $sidebarServerMenus ?? null;
$sidebarApplicationMenus = $sidebarApplicationMenus ?? null;
@endphp

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Dashboard Applucation" />
    <meta name="author" content="Vicky Rahman" />
    
    <link rel="icon" type="image/x-icon" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/icons/laptop.svg">
    <title>@yield('title', config('core.title', 'Vicky Server')) - {{ config('app.name') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Simple DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link href="https://vickyserver.my.id/server/css/styles.css" rel="stylesheet">
    <link href="https://vickyserver.my.id/server/css/app.css" rel="stylesheet">
    
    <!-- Dark Mode CSS -->
    <style>
      :root {
        --bs-body-bg: #f8f9fa;
        --bs-body-color: #212529;
        --bs-border-color: #dee2e6;
        --bs-card-bg: #ffffff;
        --bs-card-border-color: rgba(0,0,0,.125);
        --bs-sidebar-bg: #212529;
        --bs-sidebar-color: #adb5bd;
        --bs-navbar-bg: #ffffff;
        --bs-primary: #0d6efd;
        --bs-primary-rgb: 13, 110, 253;
      }
      
      [data-bs-theme="dark"] {
        --bs-body-bg: #121416;
        --bs-body-color: #e9ecef;
        --bs-border-color: #495057;
        --bs-card-bg: #1e2125;
        --bs-card-border-color: #495057;
        --bs-sidebar-bg: #1a1d20;
        --bs-sidebar-color: #adb5bd;
        --bs-navbar-bg: #1e2125;
        --bs-primary: #6ea8fe;
        --bs-primary-rgb: 110, 168, 254;
      }
      
      body {
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
        transition: background-color 0.3s ease, color 0.3s ease;
      }
      
      .card {
        background-color: var(--bs-card-bg);
        border-color: var(--bs-card-border-color);
        transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
      }
      
      .card-header.bg-primary {
        background: linear-gradient(135deg, var(--bs-primary), #0a58ca) !important;
      }
      
      .sb-sidenav {
        background-color: var(--bs-sidebar-bg);
        color: var(--bs-sidebar-color);
        transition: background-color 0.3s ease;
      }
      
      .sb-sidenav .nav-link, .sb-sidenav .sb-sidenav-menu-heading {
        color: var(--bs-sidebar-color);
      }
      
      .sb-sidenav .nav-link:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
      }
      
      .sb-sidenav .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.2);
      }
      
      .form-control, .form-select {
        background-color: var(--bs-card-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
        transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
      }
      
      .form-control:focus, .form-select:focus {
        background-color: var(--bs-card-bg);
        border-color: var(--bs-primary);
        color: var(--bs-body-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
      }
      
      .btn-outline-secondary {
        color: var(--bs-body-color);
        border-color: var(--bs-border-color);
      }
      
      .btn-outline-secondary:hover {
        background-color: var(--bs-border-color);
        border-color: var(--bs-border-color);
        color: var(--bs-body-bg);
      }
      
      .toast {
        background-color: var(--bs-card-bg);
        border-color: var(--bs-border-color);
      }
      
      .toast-header {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-bottom-color: var(--bs-border-color);
        color: var(--bs-body-color);
      }
      
      .breadcrumb {
        background-color: transparent;
        padding: 0;
      }
      
      .breadcrumb-item a {
        color: var(--bs-primary);
        text-decoration: none;
      }
      
      .breadcrumb-item.active {
        color: var(--bs-body-color);
      }
    </style>
    
    @stack('styles')
  </head>
  <body class="sb-nav-fixed">
    <x-core-navbar />
    <div id="layoutSidenav">
      <div id="layoutSidenav_nav">
        <x-core-sidebar :sidebarServerMenus=$sidebarServerMenus :sidebarApplicationMenus=$sidebarApplicationMenus />
      </div>
      <div id="layoutSidenav_content">
        <main class="my-4">
          <div class="container-fluid px-4">
            <x-core-breadcrumb />
            <x-core-alert />

            @yield('content')
            
          </div>
        </main>
        <x-core-footer />
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    
    @stack('scripts')
  </body>
</html>