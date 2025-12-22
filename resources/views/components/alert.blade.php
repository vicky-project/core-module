@if($errors->any())
  <div class="my-2">
    <div class="alert alert-danger d-flex align-items-center" role="alert">
      <i class="fas fa-fw fa-exclamation-triangle"></i>
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
      <i class="fas fa-fw fa-check"></i>
      <div>
        {{ session()->get('success') }}
      </div>
    </div>
  </div>
@endif