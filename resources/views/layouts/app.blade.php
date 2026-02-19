<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('core.title', 'Vicky Server')) - config('app.name')</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @include('core::partials.app-styles')
    @stack('styles')
</head>
<body>
  <div class="header">
    <!-- Tombol Kembali ke halaman utama -->
    <button class="back-button" onclick="goBack()">
      <i class="bi bi-arrow-left"></i>
    </button>
      
    <div class="d-flex align-items-center ms-auto ms-md-0 me-3 me-lg-4">
      <div class="theme-indicator" id="themeIndicator" title="Toggle Theme">
        <i class="bi" id="themeIcon"></i>
      </div>

      <!-- Dropdown User -->
      <div class="dropdown">
        <div class="user-dropdown" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        @if(Module::has('UserManagement') && Module::isEnabled('UserManagement'))
          <img class="rounded-circle user-avatar" width="32" height="32" src="{{ \Auth::user()->profile()->image() }}"alt="{{ \Auth::user()->name }}">
        @elseif(request()->has('photo_url'))
          <img src="{{ request()->get('photo_url') }}" class="user-avatar" alt="User">
        @else
          <div class="user-avatar-placeholder">
          {{ \Auth::user()->name ? strtoupper(substr(\Auth::user()->name, 0, 1)) : 'U' }}
          </div>
        @endif
        </div>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
          @if(Route::has('settings.index'))
          <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bi bi-person me-2"></i>Profile</i></li>
          @endif
          <li><hr class="dropdown-divider"></li>
          @if(Route::has("logout"))
            <li>
              <form method="POST" action="{{ route('logout') }}" id="logout-form">
                @csrf
                <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure to log out this session?');"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
              </form>
            </li>
          @endif
        </ul>
      </div>
    </div>
  </div>
  @yield('content')
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Telegram WebApp SDK -->
  <script src="https://telegram.org/js/telegram-web-app.js?59"></script>
  <script>
    // Inisialisasi Telegram WebApp
    const tg = window.Telegram.WebApp;
    tg.expand();
    
    // Ambil elemen
    const themeIndicator = document.getElementById('themeIndicator');
    const themeIcon = document.getElementById('themeIcon');

    // --- Fungsi Tema Internal ---
    // Warna untuk light mode (default Bootstrap)
    const lightTheme = {
            bg: '#ffffff',
            text: '#000000',
            hint: '#999999',
            button: '#40a7e3',
            buttonText: '#ffffff',
            secondaryBg: '#f8f9fa'
    };
    // Warna untuk dark mode (custom)
    const darkTheme = {
            bg: '#1f1f1f',
            text: '#ffffff',
            hint: '#aaaaaa',
            button: '#8774e1',
            buttonText: '#ffffff',
            secondaryBg: '#2f2f2f'
    };

    // Ambil preferensi dari localStorage, default ke colorScheme Telegram
    let currentTheme = localStorage.getItem('app_theme');
    if (!currentTheme) {
      currentTheme = tg.colorScheme || 'light'; // 'light' atau 'dark'
    }

    // Fungsi untuk menerapkan tema
    function applyTheme(theme) {
            const colors = theme === 'dark' ? darkTheme : lightTheme;
            document.body.style.setProperty('--tg-theme-bg-color', colors.bg);
            document.body.style.setProperty('--tg-theme-text-color', colors.text);
            document.body.style.setProperty('--tg-theme-hint-color', colors.hint);
            document.body.style.setProperty('--tg-theme-button-color', colors.button);
            document.body.style.setProperty('--tg-theme-button-text-color', colors.buttonText);
            document.body.style.setProperty('--tg-theme-secondary-bg-color', colors.secondaryBg);

            // Update ikon dan tooltip
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-moon-stars';
                themeIndicator.setAttribute('title', 'Mode Gelap (klik untuk toggle)');
            } else {
                themeIcon.className = 'bi bi-brightness-high';
                themeIndicator.setAttribute('title', 'Mode Terang (klik untuk toggle)');
            }

      localStorage.setItem('app_theme', theme);
    }

    // Terapkan tema awal
    applyTheme(currentTheme);

    // Event klik untuk toggle tema
    themeIndicator.addEventListener('click', function() {
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      currentTheme = newTheme;
      applyTheme(newTheme);
    });

    // Fungsi navigasi sementara
    function navigateTo(page) {
      showToast('Fitur ' + page + ' sedang dikembangkan', 'info');
      // Nanti bisa diganti dengan window.location.href
    }

    // Fungsi kembali
    function goBack() {
      window.location.href = "{{ url()->previous() }}?initData=" + encodeURIComponent(tg.initData);
    }

    // Fungsi logout
    function logout() {
      fetch('/logout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ initData: tg.initData })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            window.location.href = "/";
          } else {
            showToast('Logout gagal', 'danger');
          }
      })
        .catch(error => {
          showToast('Terjadi kesalahan', 'danger');
        });
    }

    // Fungsi toast
    function showToast(message, type = 'success') {
      let toastContainer = document.querySelector('.toast-container');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
                
        const toastEl = document.createElement('div');
        toastEl.id = 'liveToast';
        toastEl.className = 'toast';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
          <div class="toast-header">
            <strong class="me-auto">Notifikasi</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
          <div class="toast-body"></div>
        `;
        toastContainer.appendChild(toastEl);
      }

      const toastEl = document.getElementById('liveToast');
      const toastBody = toastEl.querySelector('.toast-body');
      toastBody.textContent = message;

      toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');
      if (type === 'success') {
        toastEl.classList.add('bg-success', 'text-white');
      } else if (type === 'danger') {
        toastEl.classList.add('bg-danger', 'text-white');
      } else {
        toastEl.classList.add('bg-info', 'text-white');
      }

      const toast = new bootstrap.Toast(toastEl);
      toast.show();
    }

    tg.ready();
  </script>
    
  @stack('scripts')
</body>
</html>