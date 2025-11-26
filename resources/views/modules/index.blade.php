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
            @if(!$module["is_installed"])
            <span class="badge text-bg-info">
              <svg class="icon me-2">
                <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-cloud-download') }}"></use>
              </svg>
              Available: v{{$module["installed_version"] ?? "1.0.0" }}</span>
            @elseif($module["update_available"])
            <div>
              <span class="badge text-bg-warning">Current: v{{$module["installed_version"]}}</span>
              <span class="badge text-bg-success">Update: v{{$module["latest_version"]}}</span>
            </div>
            @else
              <span class="badge text-bg-success">
                <svg class="icon me-2">
                  <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-check-alt') }}"></use>
                </svg>
                {{$module["installed_version"]}}
              </span>
              @if($module["latest_version"] && $module["installed_version"] === $module["latest_version"])
              <small class="text-muted d-block mt-1">Latest version</small>
              @endif
            @endif
          </div>
          <div>
            <span class="badge text-bg-{{$module['source'] === 'packagist' ? 'primary' : 'secondary'}}">{{ucfirst($module["source"])}}</span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <h5 class="card-title mb-2">{{$module["display_name"]}}</h5>
        <p class="card-text text-muted small">{{ str($module["description"])->limit(120) }}</p>
        <small class="text-muted d-block mb-2">{{$module["name"]}}</small>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="mb-3 text-center">
          @if(!$module['is_installed'])
          <form action="{{ route('cores.install-package', $module['name']) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Install {{ $module['name'] }}?')">
              <svg class="icon me-2">
                <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-cloud-download') }}"></use>
              </svg>
              Install v{{ $modul["latest_version"] ?? "1.0.0" }}</button>
          </form>
          @elseif($module['update_available'])
          <form action="{{ route('cores.update-package', $module['name']) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Update {{ $module['name'] }} from v{{ $module['installed_version'] }} to v{{ $module['latest_version'] }} ?')">
              <svg class="icon me-2">
                <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-arrow-up') }}"></use>
              </svg>
              Update to {{ $module["latest_version"] }}
            </button>
          </form>
          @else
            @if(str($module['name'])->lower()->doesntContain('core'))
              @if($module['status'] === 'enabled')
              <form action="{{ route('cores.disable', $module['display_name']) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">
                  Disable
                </button>
              </form>
              @else
              <form action="{{ route('cores.enable', $module['display_name']) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success btn-sm">
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
              <svg class="icon me-2 text-bg-warning">
                <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-star') }}"></use>
              </svg>
              {{ $module["github_stars"] }}
            </small>
            @endif
            @if($module["downloads"]["monthly"] > 0)
            <small class="me-2">
              <svg class="icon me-2 text-primary">
                <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-cloud-download') }}"></use>
              </svg>
              {{ number_format($module["downloads"]["monthly"]) }}/month
            </small>
            @endif
            @if($module["favers"] > 0)
            <small>
              <svg class="icon me-2 text-bg-danger">
                <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-heart') }}"></use>
              </svg>
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