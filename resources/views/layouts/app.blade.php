<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Telegram Mini App</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @include('core::partials.app-styles')
</head>
<body>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Telegram WebApp SDK -->
  <script src="https://telegram.org/js/telegram-web-app.js"></script>
  <script>
    // Inisialisasi Telegram WebApp
    const tg = window.Telegram.WebApp;
    tg.expand();

    // Terapkan tema Telegram
    const theme = tg.themeParams;
    document.body.style.setProperty('--tg-theme-bg-color', theme.bg_color || '#ffffff');
    document.body.style.setProperty('--tg-theme-text-color', theme.text_color || '#000000');
    document.body.style.setProperty('--tg-theme-hint-color', theme.hint_color || '#999999');
    document.body.style.setProperty('--tg-theme-button-color', theme.button_color || '#40a7e3');
    document.body.style.setProperty('--tg-theme-button-text-color', theme.button_text_color || '#ffffff');
    document.body.style.setProperty('--tg-theme-secondary-bg-color', theme.secondary_bg_color || '#f8f9fa');

    // Fungsi navigasi sementara
    function navigateTo(page) {
      showToast('Fitur ' + page + ' sedang dikembangkan', 'info');
            // Nanti bisa diganti dengan window.location.href
    }

    // Fungsi kembali
    function goBack() {
      window.location.href = "{{ route('telegram.mini.app') }}?initData=" + encodeURIComponent(tg.initData);
    }

    // Fungsi logout
    function logout() {
      fetch('/telegram-logout', {
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
            window.location.href = "{{ route('telegram.mini.app') }}";
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
</body>