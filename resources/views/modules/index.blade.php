@extends('viewmanager::layouts.app')

@section('page-title', 'Module Available')

@section('content')
<div class="row g-3 pb-2 mb-4 border-bottom border-primary">
  @forelse($modules as $module)
  <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
    <div class="card overflow-hidden">
      <div class="card-body p-0 d-flex justify-content-between align-items-start">
        <div>
          <div class="bg-primary text-white py-4 px-5 me-3">
            <svg class="icon icon-xl">
              <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-swap-horizontal') }}"></use>
            </svg>
          </div>
          <div>
            <div class="fs-6 fw-semibold text-primary">{{ $module["name"] }}</div>
            <div class="text-body-secondary text-uppercase fw-semibold small">{{ $modul["description"] }}</div>
          </div>
        </div>
        <div class="dropdown">
          <button class="btn btn-transparent text-white p-0" type="button" data-coreui-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <svg class="icon">
              <use xlink:href="{{ asset('vendors/@coreui/icons/svg/free.svg#cil-options') }}"></use>
            </svg>
          </button>
          <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="{{ route('cores.show',['core' => $module['name']]) }}">Detail</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  @empty
  @endforelse
</div>
@endsection