@extends('layouts.admin')

@section('title', 'Cập nhật đặt phòng')

@section('content')

    <script>
        window.location.href = "{{ route('admin.bookings.show', $booking->id) }}";
    </script>

@endsection