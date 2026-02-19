@extends('core::layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="header">
  <!-- Tombol Kembali ke halaman utama -->
  <button class="back-button" onclick="goBack()">
    <i class="bi bi-arrow-left"></i>
  </button>

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
      <li><a class="dropdown-item" href="#" onclick="logout()"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
    </ul>
  </div>
</div>

<div class="content container-custom">
  <!-- Grid Aplikasi -->
  <div class="app-grid">
    @hasHook('dashboard-widgets')
      @hook('dashboard-widgets')
    @endHasHook
    <a href="#" class="app-item" onclick="navigateTo('akun')">
      <i class="bi bi-person-circle"></i>
      <span>Akun</span>
    </a>
  </div>
</div>
@endsection