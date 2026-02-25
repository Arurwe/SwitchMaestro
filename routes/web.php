<?php


use App\Http\Controllers\CommandController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SettingsManager;
use App\Http\Controllers\TopologyMapController;
use App\Http\Controllers\VlanTopologyController;
use App\Livewire\PortDetails;
use App\Livewire\ActionManager;
use App\Livewire\AiTranslationHistory;
use App\Livewire\AuditLogViewer;
use App\Livewire\BackupDetail;
use App\Livewire\BackupViewer;
use App\Livewire\BulkActionRunner;
use App\Livewire\CredentialsManager;
use App\Livewire\Dashboard;
use App\Livewire\DeviceConsole;
use App\Livewire\DeviceDetail;
use App\Livewire\DeviceForm;
use App\Livewire\DeviceGroupManager;
use App\Livewire\DeviceManager;
use App\Livewire\DeviceTypeManager;
use App\Livewire\JobMonitor;
use App\Livewire\RunActionOnDevice;
use App\Livewire\TopologyMap;
use App\Livewire\UserManager;
use App\Livewire\VlanExplorer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/topology/map', [TopologyMapController::class, 'index'])
    ->middleware(['auth'])
    ->name('topology.map');

Route::get('/topology/map-full', [TopologyMapController::class, 'indexWithUnmanaged'])->name('topology.map.full');

Route::get('/topology/vlan', [VlanTopologyController::class, 'index'])
    ->middleware(['auth'])
    ->name('topology.vlan.index');

    Route::get('/topology/vlan/{vlan}', [VlanTopologyController::class, 'show'])
    ->middleware(['auth'])
    ->name('topology.vlan.show');


Route::get('/devices/{device}/ports/{port}', PortDetails::class)
     ->name('ports.show');

Route::get('/', Dashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/lo')->name('profile.edit');
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('/users', UserManager::class)->name('users.index');

    Route::get('/device-types', DeviceTypeManager::class)->name('device-types.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
});


Route::middleware(['auth', 'permission:credentials:manage'])->group(function () {
    Route::get('/credentials', CredentialsManager::class)->name('credentials.index');
});



Route::middleware(['auth'])->group(function () { 


Route::get('/ai-translations', AiTranslationHistory::class)->name('ai-translations.index');

   Route::get('/vlans-explorer', VlanExplorer::class)->name('vlans.explorer');

    Route::prefix('devices')->name('devices.')->group(function () {

        Route::get('/create', DeviceForm::class)
             ->name('create')
             ->middleware('permission:devices:manage'); 

        Route::get('/', DeviceManager::class)
             ->name('index')
             ->middleware('permission:devices:view');
                     Route::get('/bulk-action', BulkActionRunner::class)
        ->name('bulk-action')
        ->middleware('permission:commands:run:config');

        Route::get('/{device}', DeviceDetail::class)
             ->name('show')
             ->middleware('permission:devices:view');

        Route::get('/{device}/edit', DeviceForm::class)
             ->name('edit')
             ->middleware('permission:devices:manage');

        Route::get('/{device}/run-action', RunActionOnDevice::class)
                 ->name('run-action')
                 ->middleware('permission:commands:run:config');

         
        Route::get('/{device}/console', DeviceConsole::class)
             ->name('console')
             ->middleware('permission:devices:manage');


    });

});

Route::middleware(['auth', 'permission:groups:view'])->group(function () {
    Route::get('/device-groups', DeviceGroupManager::class)->name('device-groups.index');
});


// LOGI
Route::middleware(['auth', 'permission:auditlog:view'])->group(function () {
    Route::get('/audit-log', AuditLogViewer::class)->name('audit-log.index');
    Route::get('/jobs', JobMonitor::class)->name('jobs.index');

});

// Backupy configu
Route::middleware(['auth', 'permission:backups:view'])->group(function () {
    Route::get('/backups', BackupViewer::class)->name('backups.index');
    Route::get('/backups/{backup}', BackupDetail::class)->name('backups.show');
});

// actions and commands

Route::get('/actions', ActionManager::class)->name('actions.index');
Route::prefix('commands')->name('commands.')->group(function () {
    Route::get('/{command}/edit', [CommandController::class, 'edit'])->name('edit');
    Route::put('/{command}', [CommandController::class, 'update'])->name('update');
});


require __DIR__.'/auth.php';
