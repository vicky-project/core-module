<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Dashboard Applucation" />
    <meta name="author" content="Vicky Rahman" />
    <title>@yield('title', config('viewmanager.title', 'Vicky Server')) - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="https://vickyserver.my.id/server/css/styles.css" rel="stylesheet">
    <link href="https://vickyserver.my.id/server/css/app.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    
    @stack('styles')
  </head>
  <body class="sb-nav-fixed">
    <x-core-navbar />
    <div id="layoutSidenav">
      <div id="layoutSidenav_nav">
        <x-core-sidebar :sidebarServerMenus=$sidebarServerMenus :sidebarApplicationMenus=$sidebarApplicationMenus />
      </div>
      <div id="layoutSidenav_content">
        <main>
          <div class="container-fluid px-4">
            <x-core-breadcrumb />

            @yield('content')
            
          </div>
        </main>
        <x-core-footer />
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    
    @stack('scripts')
  </body>
</html>