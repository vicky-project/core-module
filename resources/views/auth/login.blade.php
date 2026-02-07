@extends('core::layouts.auth')

@section('title', 'Login')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="card shadow-lg border-0 rounded-lg mt-5">
      <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
      <div class="card-body">
        <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
              @csrf
          <div class="form-floating mb-3">
            <input class="form-control" id="inputEmail" type="email" name="email" placeholder="name@example.com" value="{{ old('email') }}" autofocus autocomplete />
            <label for="inputEmail">Email address</label>
            @error('email')
              <div class="invalid-feedback">
                {{ $message }}
              </div>
            @enderror
          </div>
          <div class="input-group mb-3">
            <div class="form-floating">
              <input class="form-control @error('password') is-invalid @enderror" id="inputPassword" type="password" name="password" placeholder="Password" />
              <label for="inputPassword">Password</label>
            </div>
            <span class="input-group-text" onclick="showPassword()" id="btn-show-password"><i class="bi bi-eye"></i></span>
            @error('password')
            <div class="invalid-feedback"> {{ $message }}</div>
            @enderror
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" id="inputRememberPassword" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} value="1" @checked(old('remember')) />
            <label class="form-check-label" for="inputRememberPassword">Remember Me</label>
          </div>
          <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
            @if(Route::has('password.request'))
              <a class="small" href="{{ route('password.request') }}">Forgot Password?</a>
            @endif
            <button type="submit" class="btn btn-primary">Login</button>
          </div>
        </form>
        @hasHook('auth.socials')
        <div class="d-flex justify-content-center align-items-center pt-3 mt-4 border-top border-primary">
          <div class="row">
            <div class="col-md-12">
              @hook('auth.socials')
            </div>
          </div>
        </div>
        @endHasHook
      </div>
      <div class="card-footer text-center py-3">
        @if(Route::has('register'))
        <div class="small"><a href="{{ route('register') }}">Need an account? Sign up!</a></div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function showPassword() {
    const btnShowPassword = document.getElementById('btn-show-password');
    const inputPassword = document.getElementById('inputPassword');
    const passwordType = inputPassword.getAttribute('type') != 'text';
    
    btnShowPassword.innerHTML = passwordType ? '<i class="bi bi-eye-slahs"></i>' : '<i class="bi bi-eye"></i>';
    inputPassword.setAttribute('type', passwordType ? 'text' : 'password');
  }
</script>
@endpush