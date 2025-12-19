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
    <li class="nav-item dropdown bd-mode-toggle">
      <a class="nav-link dropdown-toggle theme-icon-active" id="bd-theme" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-fw fa-circle-half-stroke"></i>
        <span class="visually-hidden" id="bd-theme-text">Auto</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bd-theme-text">
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
            <i class="fas fa-fw fa-sun me-2"></i>
            Light
            <i class="fas fa-fw fa-check ms-auto"></i>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
            <i class="fas fa-fw fa-moon me-2"></i>
            Dark
            <i class="fas fa-fw fa-check ms-auto"></i>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="auto">
            <i class="fas fa-fw fa-circle-half-stroke me-2"></i>
            Auto
            <i class="fas fa-fw fa-check ms-auto"></i>
          </button>
        </li>
      </ul>
    </li>
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
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
              <a class="dropdown-item" href="#" onclick="this.preventDefault();return logout();"><i class="fas fa-fw fa-sign-out"></i> Logout</a>
            </form>
          </li>
        @endif
      </ul>
    </li>
  </ul>
</nav>

<script>
  function logout(){
    if(!confirm('Are you sure to log out this session ?')) return;
    
    document.getElementById('logout-form').submit();
  }
  
window.addEventListener("DOMContentLoaded", event => {
	// Toggle the side navigation
	const sidebarToggle = document.body.querySelector("#sidebarToggle");
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

	const getStoredTheme = () => localStorage.getItem("theme");
	const setStoredTheme = theme => localStorage.setItem("theme", theme);

	const getPreferredTheme = () => {
		const storedTheme = getStoredTheme();
		if (storedTheme) {
			return storedTheme;
		}

		return window.matchMedia("(prefers-color-scheme: dark)").matches
			? "dark"
			: "light";
	};

	const setTheme = theme => {
		if (theme === "auto") {
			document.documentElement.setAttribute(
				"data-bs-theme",
				window.matchMedia("(prefers-color-scheme: dark)").matches
					? "dark"
					: "light"
			);
		} else {
			document.documentElement.setAttribute("data-bs-theme", theme);
		}
	};

	const setThemeToDb = theme => {
		fetch("{{ route('api.v1.cores.theme.update') }}", {
			method: "POST",
			data: {
				theme: theme,
				_token: "{{ csrf_token() }}"
			}
		})
			.then(res => res.json())
			.then(data => {
				alert(data.message);
			});
	};

	const showActiveTheme = (theme, focus = false) => {
		const themeSwitcher = document.querySelector("#bd-theme");

		if (!themeSwitcher) {
			return;
		}

		const themeSwitcherText = document.querySelector("#bd-theme-text");
		const activeThemeIcon = document.querySelector(".theme-icon-active i");
		const btnToActive = document.querySelector(
			`[data-bs-theme-value="${theme}"]`
		);
		const iconOfActiveBtn = btnToActive.querySelector("i").classList;

		document.querySelectorAll("[data-bs-theme-value]").forEach(element => {
			element.classList.remove("active");
			element.setAttribute("aria-pressed", "false");
		});

		btnToActive.classList.add("active");
		btnToActive.setAttribute("aria-pressed", "true");
		activeThemeIcon.className = iconOfActiveBtn;
		const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`;
		themeSwitcher.setAttribute("aria-label", themeSwitcherLabel);

		if (focus) {
			themeSwitcher.focus();
		}
	};

	showActiveTheme(getPreferredTheme());

	document.querySelectorAll("[data-bs-theme-value]").forEach(toggle => {
		toggle.addEventListener("click", () => {
			const theme = toggle.getAttribute("data-bs-theme-value");
			setStoredTheme(theme);
			setTheme(theme);
			showActiveTheme(theme, true);
			setThemeToDb(theme);
		});
	});
});

</script>