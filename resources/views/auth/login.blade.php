@extends('core::layouts.auth')

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
          <div class="form-floating mb-3">
            <input class="form-control @error('password') is-invalid @enderror" id="inputPassword" type="password" name="password" placeholder="Password" />
            <label for="inputPassword">Password</label>
            @error('password')
            <div class="invalid-feedback"> {{ $message }}</div>
            @enderror
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" id="inputRememberPassword" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} value="{{ old('remember') ? '1' : '0'}}" />
            <label class="form-check-label" for="inputRememberPassword">Remember Me</label>
          </div>
          <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
            @if(Route::has('password.request'))
              <a class="small" href="{{ route('password.request') }}">Forgot Password?</a>
            @endif
            <button type="submit" class="btn btn-primary">Login</button>
          </div>
          <div class="d-flex justify-content-center align-items-center mt-4 pt-2 border-top border-primary">
            <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="Vickyserver_bot" data-size="large" data-auth-url="https://vickyserver.my.id/server/dashboard" data-request-access="write"></script>
          </div>
        </form>
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
@endpush