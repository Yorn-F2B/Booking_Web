<header class="sticky top-0 z-30 flex items-center justify-between px-6 py-4 bg-white border-b shadow-sm">
    <div class="flex items-center">
        <button @click="sidebarOpen = true" class="text-gray-500 focus:outline-none lg:hidden mr-4">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <h2 class="text-xl font-semibold text-gray-800">
            <?php echo $__env->yieldContent('header_title', 'Bảng điều khiển'); ?>
        </h2>
    </div>

    <div class="flex items-center">
        <!-- User Menu Dropdown -->
        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center space-x-2 text-sm focus:outline-none">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">
                    <?php echo e(Auth::user() ? substr(Auth::user()->name, 0, 1) : 'A'); ?>

                </div>
                <span class="hidden md:block font-medium text-gray-700"><?php echo e(Auth::user() ? Auth::user()->name : 'Admin User'); ?></span>
                <svg class="w-4 h-4 text-gray-500 hidden md:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="dropdownOpen" @click.away="dropdownOpen = false" class="absolute right-0 z-20 w-48 py-2 mt-2 bg-white rounded-md shadow-xl"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-90"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90" style="display: none;">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hồ sơ</a>
                <a href="<?php echo e(url('/')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" target="_blank">Xem trang Web</a>
                <div class="border-t border-gray-100 my-1"></div>
                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\layouts\test\header.blade.php ENDPATH**/ ?>