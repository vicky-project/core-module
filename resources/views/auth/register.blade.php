@extends('core::layouts.auth')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-lg border-0 rounded-lg mt-5">
      <div class="card-header"><h3 class="text-center font-weight-light my-4">Create Account</h3></div>
      <div class="card-body">
        <form method="POST" action="{{ route('register') }}">
          @csrf
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputName" type="text" name="name" placeholder="Enter your name" />
                <label for="inputName">User name</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating">
                <input class="form-control" id="inputEmail" type="email" name="email" placeholder="name@example.com" />
                <label for="inputEmail">Email address</label>
              </div>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputPassword" type="password" name="password" placeholder="Create a password" />
                <label for="inputPassword">Password</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-floating mb-3 mb-md-0">
                <input class="form-control" id="inputPasswordConfirm" type="password" name="password_confirmation" placeholder="Confirm password" />
                <label for="inputPasswordConfirm">Confirm Password</label>
              </div>
            </div>
          </div>
          <div class="mt-4 mb-0">
            <div class="d-grid"><button type="submit" class="btn btn-primary btn-block">Create Account</button></div>
          </div>
          <div class="mt-4 mb-2 pt-2 border-top border-primary">
            <div class="d-grid">
              <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('core.telegram.username') }}" data-size="large" data-auth-url="https://vickyserver.my.id/server/telegram/callback" data-request-access="write"></script>
            </div>
          </div>
        </form>
      </div>
      <div class="card-footer text-center py-3">
        <div class="small"><a href="{{ route('login') }}">Have an account? Go to login</a></div>
      </div>
    </div>
  </div>
</div>
@endsection