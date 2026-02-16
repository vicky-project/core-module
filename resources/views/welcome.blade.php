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
  <div class="container text-center mt-4 p-3">
    <div class="row">
      @hasHook('main-apps')
        @hook('main-apps')
      @endHasHook
      <div class="col-4 col-md-2 mb-2">
        <a href="{{ route('settings.index') }}" class="menu-item rounded-4 p-2">
          <i class="bi bi-gear"></i>
          <span>Pengaturan</span>
        </a>
      </div>
    </div>
  </div>
</div>
@endsection