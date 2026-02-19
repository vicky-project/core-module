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
          {{ $user ? strtoupper(substr($user['first_name'], 0, 1)) : 'U' }}
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
  <div class="section-title">Aplikasi</div>
  <div class="app-grid">
    @hasHook('dashboard-widgets')
      @hook('dashboard-widgets')
    @endHasHook
    <a href="#" class="app-item">
      <i class="bi bi-person-circle"></i>
      <span>Akun</span>
    </a>
  </div>
</div>
@endsection

@push('styles')
<style>
  .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background-color: var(--tg-theme-secondary-bg-color, #f8f9fa);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .back-button {
            font-size: 1.5rem;
            color: var(--tg-theme-button-color, #40a7e3);
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }
        .user-dropdown {
            cursor: pointer;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--tg-theme-button-color, #40a7e3);
        }
        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--tg-theme-button-color, #40a7e3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .dropdown-menu {
            background-color: var(--tg-theme-secondary-bg-color, #fff);
            border: 1px solid var(--tg-theme-hint-color, #ddd);
        }
        .dropdown-item {
            color: var(--tg-theme-text-color, #000);
        }
        .dropdown-item:hover {
            background-color: var(--tg-theme-button-color, #40a7e3);
            color: var(--tg-theme-button-text-color, white);
        }
        .content {
            padding: 20px;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--tg-theme-text-color, #000);
        }
        .app-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .app-item {
            background-color: var(--tg-theme-secondary-bg-color, #f8f9fa);
            border-radius: 16px;
            padding: 20px 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            color: var(--tg-theme-text-color, #000);
            text-decoration: none;
            display: block;
        }
        .app-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .app-item i {
            font-size: 2.5rem;
            color: var(--tg-theme-button-color, #40a7e3);
            margin-bottom: 10px;
            display: block;
        }
        .app-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        .admin-section {
            margin-top: 20px;
            border-top: 1px solid var(--tg-theme-hint-color, #ddd);
            padding-top: 20px;
        }
        .container-custom {
            max-width: 500px;
            margin: 0 auto;
        }
</style>
@endpush