@extends('core::layouts.main')

@section('content')
<div class="main-container my-4">
  
  <!-- Logo Lingkaran -->
  <div class="app-logo d-flex justify-content-center align-items-center">
    <img src="{{ config('core.logo_url') }}" alt="Logo Aplikasi" class="img-fluid rounded-circle">
  </div>

  <!-- Nama Aplikasi -->
  <div class="app-name h4 fw-bold text-center">
    {{ config('app.name') }} App
  </div>

  <!-- Deskripsi -->
  <div class="app-description text-center">
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
</div>
@endsection