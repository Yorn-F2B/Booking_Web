<?php

use App\Http\Controllers\Admin\ChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('chats')->name('admin.chats.')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');

    Route::post('/presence/heartbeat', [ChatController::class, 'heartbeat'])->name('presence.heartbeat');
    Route::post('/presence', [ChatController::class, 'updatePresence'])->name('presence.update');
    Route::post('/handoff', [ChatController::class, 'bulkHandoff'])->name('handoff');

    Route::get('/{conversation}/messages', [ChatController::class, 'messages'])->name('messages');
    Route::post('/{conversation}/take', [ChatController::class, 'take'])->name('take');
    Route::post('/{conversation}/send', [ChatController::class, 'send'])->name('send');
    Route::post('/{conversation}/read', [ChatController::class, 'markRead'])->name('read');
    Route::post('/{conversation}/close', [ChatController::class, 'close'])->name('close');
    Route::post('/{conversation}/reopen', [ChatController::class, 'reopen'])->name('reopen');
    Route::post('/{conversation}/transfer', [ChatController::class, 'transfer'])->name('transfer');
});
