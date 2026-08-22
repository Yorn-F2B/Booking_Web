@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')

@section('content')
@php
    $workspaceMode = 'main';
@endphp

@include('admin.pages.bookings._workspace', ['workspaceMode' => $workspaceMode])
@endsection
