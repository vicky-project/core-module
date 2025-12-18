<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
  <div class="sb-sidenav-menu">
    <div class="nav">
      <a class="nav-link" href="{{ route('dashboard') }}">
        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>Dashboard
      </a>
      @if(Module::has('MenuManagement') && Module::isEnabled('MenuManagement'))
        @include('menumanagement::menu')
      @endif
    </div>
  </div>
  <div class="sb-sidenav-footer">
    <div class="small">Logged in as:</div>
    @auth
      {{ auth()->user()->name }}
    @endauth
    @guest
      Guest
    @endguest
  </div>
</nav>