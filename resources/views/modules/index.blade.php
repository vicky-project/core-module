@extends('viewmanager::layouts.app')

@section('page-title', 'Module Available')

@section('content')
<div class="row g-3 pb-2 mb-4 border-bottom border-primary">
  @forelse($modules as $module)
  <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">{{ $module["name"] }}</h5>
      </div>
      <div class="card-body">
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <a class="btn btn-outline-primary"></a>
        @if($module["latest_version"])
        @endif
      </div>
    </div>
  </div>
  @empty
  <div class="col-auto">
    <div class="card">
      <div class="card-body">
        <p class="text-center">No Module available here.</p>
      </div>
    </div>
  </div>
  @endforelse
</div>
@endsection