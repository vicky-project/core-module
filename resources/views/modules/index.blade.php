@extends('core::layouts.app')

@use('Modules\Core\Constants\Permissions')

@section('title', 'Module Available')

@section('content')
<div class="row g-3 pb-2 mb-4 border-bottom border-primary">
  @forelse($allModules as $module)
  <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
    <div class="card">
      <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            @if($module["is_installed"] && $module["latest_version"] && $module["installed_version"] === $module["latest_version"])
            <small class="text-muted d-block mt-1">Latest version</small>
            @endif
          </div>
          <div>
            <span class="badge text-bg-{{$module['source'] === 'packagist' ? 'primary' : 'secondary'}}">{{ucfirst($module["source"])}}</span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <h5 class="card-title mb-2">{{$module["display_name"]}}<span class="small ms-2 text-muted">{{$module["is_installed"] ? $module['installed_version'] : ($module["latest_version"] ?? "1.0.0")}}</span></h5>
        <p class="card-text text-muted small">{{ str($module["description"])->limit(120) }}</p>
        <small class="text-muted d-block mb-2">{{$module["name"]}}</small>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="mb-3 text-center">
          @if(!$module['is_installed'])
          <form action="{{ route('cores.modules.install-package') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="module" value="{{$module['name']}}">
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Install {{ $module['name'] }}?')" @disabled(auth()->user()->canNot(Permissions::MANAGE_MODULES))>
              <i class="bi bi-download me-2"></i>
              Install v{{ $modul["latest_version"] ?? "1.0.0" }}</button>
          </form>
          @elseif($module['update_available'])
          <form action="{{ route('cores.modules.update-package') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="module" value="{{$module['name']}}">
            <button type="submit" class="btn btn-primary position-relative" onclick="return confirm('Update {{ $module['name'] }} from v{{ $module['installed_version'] }} to v{{ $module['latest_version'] }} ?')" @disabled(auth()->user()->canNot(Permissions::MANAGE_MODULES))>
              {{ $module["installed_version"] }}
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-warning">
                <i class="bi bi-download me-2"></i>
                {{$module["latest_version"]}}
                <span class="visually-hidden">Update available</span>
              </span>
            </button>
          </form>
          @else
            @if(str($module['name'])->lower()->doesntContain('core'))
              @if($module['status'] === 'enabled')
              <form action="{{ route('cores.modules.disable') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="module" value="{{$module['display_name']}}">
                <button type="submit" class="btn btn-outline-warning btn-sm" @disabled(auth()->user()->canNot(Permissions::MANAGE_MODULES))>
                  Disable
                </button>
              </form>
              @else
              <form action="{{ route('cores.modules.enable') }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="module" value="{{$module['display_name']}}">
                <button type="submit" class="btn btn-outline-success btn-sm" @disabled(Permissions::MANAGE_MODULES)>
                   Enable
                </button>
              </form>
              @endif
            @endif
          @endif
        </div>
        <div class="d-flex justify-content-between align-items-center text-muted">
          <div class="meta-stats">
            @if($module["github_stars"] > 0)
            <small class="me-1">
              <i class="bi bi-star text-warning me-2"></i>
              {{ $module["github_stars"] }}
            </small>
            @endif
            @if($module["downloads"]["monthly"] > 0)
            <small class="me-2">
              <i class="bi bi-download text-primary me-2"></i>
              {{ number_format($module["downloads"]["monthly"]) }}/month
            </small>
            @endif
            @if($module["favers"] > 0)
            <small>
              <i class="fas fa-fw fa-heart text-danger"></i>
              {{ $module["favers"] }}
            </small>
            @endif
          </div>
        </div>
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