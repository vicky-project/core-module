@if($errors->any())
  <div class="my-2">
    <div class="alert alert-danger d-flex align-items-center" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>
      <ul>
        @foreach($errors->all() as $error)
          <li>
            {{ $error }}
          </li>
        @endforeach
      </ul>
    </div>
  </div>
@endif
@if(session()->has('success'))
  <div class="my-2">
    <div class="alert alert-success d-flex align-items-center" role="alert">
      <i class="bi bi-check me-2"></i>
      <div>
        {{ session()->get('success') }}
      </div>
    </div>
  </div>
@endif