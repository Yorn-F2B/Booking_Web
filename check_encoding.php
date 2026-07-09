<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Services Encoding ===\n\n";

try {
    $services = \App\Models\Service::limit(10)->get(['id', 'name', 'description']);
    echo "Total services: " . $services->count() . "\n\n";
    
    foreach ($services as $service) {
        echo "ID: " . $service->id . "\n";
        echo "Name: " . $service->name . "\n";
        echo "Description: " . mb_substr($service->description, 0, 100) . "...\n";
        echo "---\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}