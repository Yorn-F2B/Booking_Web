<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Invoice Module ===\n\n";

// Test 1: Check if Invoice model exists
echo "1. Invoice Model: " . (class_exists('App\Models\Invoice') ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";

// Test 2: Check if invoices table exists
try {
    $hasTable = Schema::hasTable('invoices');
    echo "2. Invoices Table: " . ($hasTable ? "✓ EXISTS" : "✗ NOT FOUND") . "\n";
} catch (Exception $e) {
    echo "2. Invoices Table: ✗ ERROR - " . $e->getMessage() . "\n";
}

// Test 3: Check for checked_out bookings
try {
    $checkedOutBookings = \App\Models\Booking::where('status', 'checked_out')->count();
    echo "3. Checked_out Bookings: " . $checkedOutBookings . " found\n";
    
    if ($checkedOutBookings > 0) {
        $sampleBooking = \App\Models\Booking::where('status', 'checked_out')->first();
        echo "   Sample booking code: " . $sampleBooking->booking_code . "\n";
    }
} catch (Exception $e) {
    echo "3. Checked_out Bookings: ✗ ERROR - " . $e->getMessage() . "\n";
}

// Test 4: Check if any invoices exist
try {
    $invoiceCount = \App\Models\Invoice::count();
    echo "4. Existing Invoices: " . $invoiceCount . " found\n";
} catch (Exception $e) {
    echo "4. Existing Invoices: ✗ ERROR - " . $e->getMessage() . "\n";
}

// Test 5: Check routes
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $invoiceRoutes = 0;
    foreach ($routes as $route) {
        if (strpos($route->uri, 'invoice') !== false) {
            $invoiceRoutes++;
        }
    }
    echo "5. Invoice Routes: " . $invoiceRoutes . " found\n";
} catch (Exception $e) {
    echo "5. Invoice Routes: ✗ ERROR - " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";