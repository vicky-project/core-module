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
      <div class="col-4 col-md-2 mb-2">
        <a href="{{ route('cores.dashboard') }}" class="menu-item rounded-4 p-2" id="dashboard">
          <i class="bi bi-app"></i>
          <span>Application</span>
        </a>
      </div>
      @hasHook('main-apps')
        @hook('main-apps')
      @endHasHook
    </div>
  </div>
  <div class="row mt-4 py-3 fixed-bottom">
    <div class="col-12">
      @hasHook('main-footer')
      <div class="d-inline gap-2">
        @hook('main-footer')
      </div>
      @endHasHook
    </div>
  </div>
</div>
@endsection