<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\CounterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\RestartQueueController;
use App\Http\Controllers\Admin\SessionController as AdminSessionController;

// Landing redirect to kiosk
Route::get('/', [KioskController::class, 'index'])->name('home');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Kiosk flow
Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.index');
Route::post('/kiosk/service', [KioskController::class, 'chooseService'])->name('kiosk.service');
Route::get('/kiosk/registrar/programs', [KioskController::class, 'showRegistrarPrograms'])->name('kiosk.registrarPrograms');
// Gracefully handle accidental GETs to POST-only routes
Route::get('/kiosk/service', function () {
    return redirect()->route('kiosk.index');
});
Route::post('/kiosk/priority', [KioskController::class, 'choosePriority'])->name('kiosk.priority');
Route::post('/kiosk/issue', [KioskController::class, 'issueTicket'])->name('kiosk.issue');
Route::get('/kiosk/issue', function () {
    return redirect()->route('kiosk.index');
});
Route::get('/kiosk/ticket/{ticket}', [KioskController::class, 'showTicket'])->name('kiosk.ticket');

// Monitor (TV)
Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');
Route::get('/monitor/media', [MonitorController::class, 'mediaFragment'])->name('monitor.media');
Route::get('/monitor/marquee', [MonitorController::class, 'marquee'])->name('monitor.marquee');

// Counter (Registrar/Cashier) - Requires authentication
Route::middleware(['auth'])->group(function () {
    // Redirect /counter to user's assigned counter
    Route::get('/counter', function () {
        $user = Auth::user();
        if ($user && $user->counter_id) {
            return redirect()->route('counter.show', $user->counter_id);
        }
        return redirect()->route('login')->withErrors(['error' => 'No counter assigned to your account.']);
    })->name('counter.index');

    Route::get('/counter/{counter}', [CounterController::class, 'show'])->name('counter.show');

    // Counter actions
    Route::post('/counter/{counter}/next', [CounterController::class, 'next'])->name('counter.next');
    Route::post('/counter/{counter}/hold/{ticket}', [CounterController::class, 'hold'])->name('counter.hold');
    Route::post('/counter/{counter}/call-again/{ticket}', [CounterController::class, 'callAgain'])->name('counter.callAgain');
    Route::delete('/counter/{counter}/hold/{ticket}', [CounterController::class, 'removeHold'])->name('counter.removeHold');

    // Daily queue restart
    Route::get('/queue/restart', [RestartQueueController::class, 'index'])->name('queue.restart.index');
    Route::post('/queue/restart', [RestartQueueController::class, 'restart'])->name('queue.restart.run');

    // Media management routes
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::post('/media/marquee', [MediaController::class, 'updateMarquee'])->name('media.updateMarquee');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::patch('/media/{id}/toggle', [MediaController::class, 'toggleActive'])->name('media.toggleActive');
    Route::post('/media/order', [MediaController::class, 'updateOrder'])->name('media.updateOrder');

    // Cutoff - on

    Route::post('/counter/cutoff', [CounterController::class, 'cutoff'])
        ->name('counter.cutoff');

    Route::post('/counter/reopen', [CounterController::class, 'reopen'])
        ->name('counter.reopen');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{sessionId}', [AdminSessionController::class, 'destroy'])->name('sessions.destroy');
    Route::delete('/users/{user}/sessions', [AdminSessionController::class, 'destroyUserSessions'])->name('sessions.destroyUserSessions');

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::post('/media/marquee', [MediaController::class, 'updateMarquee'])->name('media.updateMarquee');
    Route::delete('/media/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::patch('/media/{id}/toggle', [MediaController::class, 'toggleActive'])->name('media.toggleActive');
    Route::post('/media/order', [MediaController::class, 'updateOrder'])->name('media.updateOrder');
});


