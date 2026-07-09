<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Users in Database ===\n\n";

$users = \App\Models\User::all();

if ($users->count() == 0) {
    echo "No users found in database.\n";
    echo "Creating admin user...\n";
    
    $admin = \App\Models\User::create([
        'name' => 'Admin User',
        'email' => 'admin@hotel.com',
        'password' => bcrypt('admin123'),
        'role' => 'admin',
        'status' => 'active',
    ]);
    
    echo "✓ Admin user created successfully!\n";
    echo "Email: admin@hotel.com\n";
    echo "Password: admin123\n";
} else {
    echo "Found " . $users->count() . " users:\n\n";
    
    foreach ($users as $user) {
        echo "Name: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Role: " . $user->role . "\n";
        echo "Status: " . $user->status . "\n";
        echo "---\n";
    }
}

echo "\n=== You can login with these credentials ===\n";