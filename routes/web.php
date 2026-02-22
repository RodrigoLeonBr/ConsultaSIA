<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CboController;
use App\Http\Controllers\PrestadorController;
use App\Http\Controllers\ProcedimentoController;
use App\Http\Controllers\SRubController;
use App\Http\Controllers\SPapController;
use App\Http\Controllers\SApaController;
use App\Http\Controllers\CismetroController;
use App\Http\Controllers\FaturamentoPrestadorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    // Redirect to login if not authenticated, otherwise to dashboard
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});


Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'active', 'verified', 'password.changed'])
    ->name('dashboard');

Route::get('/dashboard/activity', [App\Http\Controllers\DashboardController::class, 'getRecentActivity'])
    ->middleware(['auth', 'active', 'verified', 'password.changed'])
    ->name('dashboard.activity');

Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Admin only routes - SECURITY: Only admins can manage users
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/admin/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::patch('/admin/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('admin.users.toggle-status');
    });
    
    // Admin and Operator routes  
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/operator', function () {
            return view('operator.dashboard');  
        })->name('operator.dashboard');
        
        // CRUD Modules for healthcare system
        Route::resource('cbo', CboController::class);
        Route::resource('prestador', PrestadorController::class);
        Route::resource('procedimento', ProcedimentoController::class);
        Route::resource('srub', SRubController::class);
        Route::resource('cismetro', CismetroController::class);
        
        // APAC Management
        Route::resource('spap', SPapController::class);
        Route::resource('sapa', SApaController::class);
        Route::get('/spap/export', [SPapController::class, 'export'])->name('spap.export');
        Route::get('/sapa/export', [SApaController::class, 'export'])->name('sapa.export');
        
        // Relatórios Produção (s_prd)
        Route::get('/relatorios', [App\Http\Controllers\RelatorioController::class, 'index'])->name('relatorios.index');
        Route::get('/relatorios/fields', [App\Http\Controllers\RelatorioController::class, 'getFields'])->name('relatorios.fields');
        Route::get('/relatorios/lookup', [App\Http\Controllers\RelatorioController::class, 'getLookupData'])->name('relatorios.lookup');
        Route::post('/relatorios/generate', [App\Http\Controllers\RelatorioController::class, 'generate'])->name('relatorios.generate');
        Route::post('/relatorios/generate-matrix', [App\Http\Controllers\RelatorioController::class, 'generateMatrix'])->name('relatorios.generate-matrix');
        Route::post('/relatorios/debug', [App\Http\Controllers\RelatorioController::class, 'debug'])->name('relatorios.debug');
        Route::get('/relatorios/test-excel', [App\Http\Controllers\RelatorioController::class, 'testExcel'])->name('relatorios.test-excel');
        
        // Relatórios APAC/OCI (s_pap/s_apa)
        Route::get('/relatorios/apac', [App\Http\Controllers\RelatorioApacController::class, 'index'])->name('relatorios.apac.index');
        Route::get('/relatorios/apac/fields', [App\Http\Controllers\RelatorioApacController::class, 'getFields'])->name('relatorios.apac.fields');
        Route::get('/relatorios/apac/lookup', [App\Http\Controllers\RelatorioApacController::class, 'getLookupData'])->name('relatorios.apac.lookup');
        Route::post('/relatorios/apac/generate', [App\Http\Controllers\RelatorioApacController::class, 'generate'])->name('relatorios.apac.generate');
        Route::post('/relatorios/apac/generate-matrix', [App\Http\Controllers\RelatorioApacController::class, 'generateMatrix'])->name('relatorios.apac.generate-matrix');
        
        // Relatórios de Faturamento por Prestador
        Route::get('/relatorios/faturamento-prestador', [App\Http\Controllers\FaturamentoPrestadorController::class, 'index'])->name('faturamento-prestador.index');
        Route::post('/relatorios/faturamento-prestador/gerar', [App\Http\Controllers\FaturamentoPrestadorController::class, 'gerar'])->name('faturamento-prestador.gerar');
        Route::post('/relatorios/faturamento-prestador/pdf', [App\Http\Controllers\FaturamentoPrestadorController::class, 'exportarPdf'])->name('faturamento-prestador.pdf');
        
        // Relatórios Standalone (HTML puro)
        Route::get('/relatorios/standalone', function () {
            return view('relatorios.standalone');
        })->name('relatorios.standalone');

        
        // Additional custom routes if needed
        Route::patch('/prestador/{prestador}/toggle-status', [PrestadorController::class, 'toggleStatus'])->name('prestador.toggle-status');
    });
});

require __DIR__.'/auth.php';
