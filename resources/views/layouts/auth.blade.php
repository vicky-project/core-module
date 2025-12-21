<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Web application server." />
    <meta name="author" content="Vicky Rahman" />
    <title>Login - {{ config('app.name', 'VickyServer') }}</title>
    <link href="https://vickyserver.my.id/server/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
  </body>
</html>
