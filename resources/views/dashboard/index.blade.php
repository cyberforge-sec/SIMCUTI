@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    @if(session('user_role') === 'admin')
        @include('dashboard.admin')
    @elseif(session('user_role') === 'manager')
        @include('dashboard.manager')
    @else
        @include('dashboard.karyawan')
    @endif
@endsection
 