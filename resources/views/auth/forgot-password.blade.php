<x-guest-layout>
    <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg p-6">

        <h2 class="text-2xl font-bold text-center mb-4">
            Quên mật khẩu
        </h2>

        <p class="text-gray-600 text-sm mb-4 text-center">
            Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.
        </p>

        <x-auth-session-status
            class="mb-4 text-green-600"
            :status="session('status')"
        />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block mb-2 font-medium">
                    Email
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="w-full border rounded-lg px-4 py-2"
                    required
                    autofocus
                >

                @error('email')
                    <span class="text-red-500 text-sm">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700"
            >
                Gửi liên kết đặt lại mật khẩu
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}"
               class="text-blue-600 hover:underline">
                Quay lại đăng nhập
            </a>
        </div>

    </div>
</x-guest-layout>
