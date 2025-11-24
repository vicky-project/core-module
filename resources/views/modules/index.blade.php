@extends('viewmanager::layouts.app')

@section('page-title', 'Module Available')

@section('content')
<div class="row g-3 pb-2 mb-4 border-bottom border-primary">
  @forelse($allModules as $module)
  <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            @if($module["is_installed"])
            <span class="badge badge-info">Latest: v{{$module["latest_version"]}}</span>
            @elseif($module["update_available"])
            <div>
              <span class="badge badge-warning">Current: v{{$module["installed_version"]}}</span>
              <span class="badge badge-success">Update: v{{$module["latest_version"]}}</span>
            </div>
            @else
            <span class="badge badge-success">v{{$module["installed_version"]}}</span>
            @endif
          </div>
          <div>
            <span class="badge badge-{{$module['source'] === 'packagist' ? 'primary' : 'secondary'}}">{{ucfirst($module["source"])}}</span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <h5 class="card-title mb-2">{{$module["display_name"]}}</h5>
        <p class="card-text text-muted small">{{ str($module["description"])->limit(120) }}</p>
        <small class="text-muted d-block mb-2">{{$module["name"]}}</small>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
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