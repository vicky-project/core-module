@extends('core::layouts.auth')

@section('content')
<main>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5">
        <div class="card shadow-lg border-0 rounded-lg mt-5">
          <div class="card-header"><h3 class="text-center font-weight-light my-4">Login</h3></div>
          <div class="card-body">
            <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate>
              @csrf
              <div class="form-floating mb-3">
                <input class="form-control" id="inputEmail" type="email" name="email" placeholder="name@example.com" autofocus autocomplete />
                <label for="inputEmail">Email address</label>
                @error('email')
                <div class="invalid-feedback">
                  {{ $message }}
                </div>
                @enderror
              </div>
              <div class="form-floating mb-3">
                <input class="form-control" id="inputPassword" type="password" name="password" placeholder="Password" />
                <label for="inputPassword">Password</label>
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input" id="inputRememberPassword" type="checkbox" name="remember" value="" />
                <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
              </div>
              <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                @if(Route::has('password.request'))
                  <a class="small" href="{{ route('password.request') }}">Forgot Password?</a>
                @endif
                <button type="submit" class="btn btn-primary">Login</button>
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
  </div>
</main>
@endsection