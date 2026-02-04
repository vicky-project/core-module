<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
  <!-- Navbar Brand-->
  <a class="navbar-brand ps-3" href="{{ config('app.url') }}">{{ config('app.name') }}</a>
  <!-- Sidebar Toggle-->
  <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
  <!-- Navbar Search-->
  <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
    <div class="input-group">
      <input class="form-control" type="text" placeholder="Search for..." aria-label="Search for..." aria-describedby="btnNavbarSearch" />
      <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
    </div>
  </form>
  <!-- Navbar-->
  <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        @if(Module::has('UserManagement') && Module::isEnabled('UserManagement'))
        <img class="rounded-circle" width="32" height="32" src="{{ \Auth::user()->profile()->image() }}"alt="{{ \Auth::user()->name }}">
        @else
        <i class="fas fa-user fa-fw"></i>
        @endif
      </a>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
        <li><a class="dropdown-item" href="#!">Activity Log</a></li>
        @if(Route::has("settings"))
        <li><a class="dropdown-item" href="{{ route('settings') }}"><i class="fas fa-fw fa-cog"></i> Settings</a></li>
        @endif
        <li><hr class="dropdown-divider" /></li>
        @if(Route::has("logout"))
          <li>
            <form method="POST" action="{{ route('logout') }}" id="logout-form">
              @csrf
              <button type="submit" class="dropdown-item" onclick="return confirm('Are you sure to log out this session?');"><i class="fas fa-sign-out"></i> Logout</button>
            </form>
          </li>
        @endif
      </ul>
    </li>
    <li class="nav-item">
      <button class="btn btn-primary btn-sm rounded-circle shadow-lg" id="themeToggle" style="width: 44px; height: 44px;">
        <i class="bi bi-moon-stars" id="themeIcon"></i>
      </button>
    </li>
  </ul>
</nav>

<script>
window.addEventListener("DOMContentLoaded", event => {
	// Toggle the side navigation
	const sidebarToggle = document.body.querySelector("#sidebarToggle");
	const themeToggle = document.body.querySelector("#themeToggle");
	const themeIcon = document.body.querySelector("#themeIcon");
	const htmlElement = document.documentElement;
	
	if (sidebarToggle) {
		// Uncomment Below to persist sidebar toggle between refreshes
		// if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
		//     document.body.classList.toggle('sb-sidenav-toggled');
		// }
		sidebarToggle.addEventListener("click", event => {
			event.preventDefault();
			document.body.classList.toggle("sb-sidenav-toggled");
			localStorage.setItem(
				"sb|sidebar-toggle",
				document.body.classList.contains("sb-sidenav-toggled")
			);
		});
	}
	
	const getPreferedTheme = () => {
	  const storedTheme = localStorage.getItem('theme');
	  if(storedTheme) {
	    return storedTheme;
	  }
	  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	}
	
	const setTheme = (theme) => {
	  htmlElement.setAttribute('data-bs-theme', theme);
	  localStorage.setItem('theme', theme);
	  
	  if(theme === 'dark') {
	    themeIcon.className = 'bi bi-sun';
	    themeToggle.setAttribute('title', 'Switch to light mode');
	  } else {
	    themeIcon.className = 'bi bi-moon-stars';
	    themeToggle.setAttribute('title', 'Switch to dark mode');
	  }
	}
	
	const currentTheme = getPreferedTheme();
	setTheme(currentTheme);
	
	themeToggle.addEventListener('click', function() {
	  const newTheme = htmlElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
	  setTheme(newTheme);
	});
	
	window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
	  if(!localStorage.getItem('theme')) {
	    setTheme(e.matches ? 'dark' : 'light');
	  }
	});
});

</script>