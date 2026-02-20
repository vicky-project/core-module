@extends('core::layouts.app')

@section('title', 'Dashboard')

@section('content')
  <!-- Grid Aplikasi -->
  <div class="app-grid">
    @hasHook('dashboard-widgets')
      @hook('dashboard-widgets')
    @endHasHook
     {{-- <a href="#" class="app-item" onclick="navigateTo('akun')">
      <i class="bi bi-person-circle"></i>
      <span>Akun</span>
    </a> --}}
  </div>
@endsection