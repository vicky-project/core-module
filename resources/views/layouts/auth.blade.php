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
  </head>
  <body class="bg-primary">
    <div id="layoutAuthentication">
      <div id="layoutAuthentication_content">
        <main>
          <div class="container">
            @if($errors->any())
            <div class="alert alert-danger d-flex align-items-center" role="alert">
              <i class="fas fa-fw fa-exclamation-triangle flex-shrink-0 me-2"></i>
              @foreach($errors->all() as $error)
              <div>
                {{ $error }}
              </div>
              @endforeach
            </div>
            @endif
        
            @yield('content')
          </div>
        </main>
      </div>
      <div id="layoutAuthentication_footer">
        <footer class="py-4 bg-light mt-auto">
          <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between small">
              <div class="text-muted">Copyright &copy; {{ config('app.name') }} {{ date('Y') }}</div>
              <div>
                <a href="#">Privacy Policy</a>&middot;<a href="#">Terms &amp; Conditions</a>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    @stack('scripts')
  </body>
</html>
