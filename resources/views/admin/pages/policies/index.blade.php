@extends('layouts.admin')

@section('title', 'Chính sách khách sạn')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <div class="admin-page-head">
            <div>
                <h2>Chính sách khách sạn</h2>
                <p>Các mốc nghiệp vụ dùng cho booking mới. Booking đã chốt giữ snapshot để không thay đổi dữ liệu lịch sử.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.policies.update') }}">
            @csrf
            @method('PATCH')

            @foreach($groups as $group => $rows)
                <section class="card mb-3">
                    <div class="card-header">
                        <strong>{{ $groupLabels[$group] ?? ucfirst($group) }}</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($rows as $policy)
                                @php
                                    $inputType = $policy->type === 'time'
                                        ? 'time'
                                        : (in_array($policy->type, ['integer', 'decimal'], true) ? 'number' : 'text');
                                    $step = $policy->type === 'decimal' ? '0.01' : '1';
                                @endphp

                                <div class="col-12 col-xl-6">
                                    <label class="form-label fw-semibold" for="policy-{{ $policy->id }}">
                                        {{ $policy->label }}
                                    </label>
                                    <input
                                        id="policy-{{ $policy->id }}"
                                        name="values[{{ $policy->id }}]"
                                        type="{{ $inputType }}"
                                        @if($inputType === 'number') step="{{ $step }}" @endif
                                        class="form-control @error('values.' . $policy->id) is-invalid @enderror"
                                        value="{{ old('values.' . $policy->id, $policy->value) }}"
                                        required
                                    >
                                    @error('values.' . $policy->id)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($policy->description)
                                        <div class="form-text">{{ $policy->description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach

            <div class="d-flex justify-content-end pb-3">
                <button class="btn btn-primary px-4" type="submit">
                    <i class="bx bx-save me-1"></i>
                    Lưu chính sách
                </button>
            </div>
        </form>
    </main>
</div>
@endsection
