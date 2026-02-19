@extends('core::layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row">
  @hasHook('dashboard-widgets')
    @hook('dashboard-widgets')
  @endHasHook
</div>
@endsection

