<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fixing Admin User Name ===\n\n";

$admin = \App\Models\User::where('email', 'admin@mchuong.com')->first();

if ($admin) {
    $admin->name = 'Admin MCuong Hotel';
    $admin->save();
    
    echo "✓ Admin name updated successfully!\n";
    echo "Name: " . $admin->name . "\n";
    echo "Email: " . $admin->email . "\n";
    echo "Role: " . $admin->role . "\n";
} else {
    echo "✗ Admin user not found\n";
}

echo "\n=== Testing Vietnamese Display ===\n";
echo "Tên admin: " . $admin->name . "\n";
echo "Đăng nhập thành công\n";
echo "Bạn có thể đăng nhập ngay bây giờ\n";