<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Creating Admin User ===\n\n";

$admin = \App\Models\User::create([
    'name' => 'Admin MCuong Hotel',
    'email' => 'admin@mchuong.com',
    'password' => bcrypt('admin123'),
    'role' => 'super_admin',
    'status' => 'active',
]);

echo "✓ Admin user created successfully!\n\n";
echo "Login Information:\n";
echo "Email: admin@mchuong.com\n";
echo "Password: admin123\n";
echo "Role: super_admin\n";
echo "\nAccess: http://127.0.0.1:8000/admin\n";