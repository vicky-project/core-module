@extends('core::layouts.main')

@section('content')
<div class="main-container">
  
  <!-- Logo Lingkaran -->
  <div class="app-logo d-flex justify-content-center align-items-center text-center p-4">
    <img src="{{ config('core.logo_url') }}" alt="Logo Aplikasi" class="img-fluid rounded-circle" style="width: 100px; height: 100px;">
  </div>

  <!-- Nama Aplikasi -->
  <div class="app-name h4 fw-bold text-center">
    {{ config('app.name') }} App
  </div>

  <!-- Deskripsi -->
  <div class="app-description text-center pb-4">
    <small>
      Satu aplikasi untuk semua fitur tersedia.
    </small>
  </div>
        
  <!-- Menu Utama -->
  @hasHook('main-apps')
  <div class="container text-center mt-4 p-3">
    <div class="row">
      @hook('main-apps')
      <div class="col-4 col-md-2 mb-2">
        <a onclick="handleMenuClick('pengaturan');" class="menu-item rounded-4 p-2">
          <i class="bi bi-gear"></i>
          <span>Pengaturan</span>
        </a>
      </div>
    </div>
  </div>
  @endHasHook
  
  <nav class="navbar fixed-bottom bg-body-tertiary">
    <div class="container-fluid">
      <div class="navbar-brand float-end ms-auto p-2" id="auth-button"></div>
    </div>
  </nav>
</div>
@endsection