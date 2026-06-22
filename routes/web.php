<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CboController;
use App\Http\Controllers\PrestadorController;
use App\Http\Controllers\PrestadorImportController;
use App\Http\Controllers\ProcedimentoController;
use App\Http\Controllers\ProcedimentoImportController;
use App\Http\Controllers\SRubController;
use App\Http\Controllers\SPapController;
use App\Http\Controllers\SApaController;
use App\Http\Controllers\CismetroController;
use App\Http\Controllers\FaturamentoPrestadorController;
use App\Http\Controllers\AihImportController;
use App\Http\Controllers\RelatorioAihController;
use App\Http\Controllers\RelatorioAihPaController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [HomeController::class, 'index'])
    ->middleware(['auth', 'active', 'verified', 'password.changed'])
    ->name('dashboard');

Route::get('/painel', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'active', 'verified', 'password.changed'])
    ->name('painel');

Route::get('/painel/activity', [App\Http\Controllers\DashboardController::class, 'getRecentActivity'])
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
        Route::get('/prestador-import', [PrestadorImportController::class, 'create'])->name('prestador.import');
        Route::post('/prestador-import', [PrestadorImportController::class, 'store'])->name('prestador.import.store');
        Route::get('/prestador-import/preview', [PrestadorImportController::class, 'preview'])->name('prestador.import.preview');
        Route::post('/prestador-import/apply', [PrestadorImportController::class, 'apply'])->name('prestador.import.apply');
        Route::resource('procedimento', ProcedimentoController::class);
        Route::get('/procedimento-import', [ProcedimentoImportController::class, 'create'])->name('procedimento.import');
        Route::post('/procedimento-import', [ProcedimentoImportController::class, 'store'])->name('procedimento.import.store');
        Route::get('/procedimento-import/preview', [ProcedimentoImportController::class, 'preview'])->name('procedimento.import.preview');
        Route::post('/procedimento-import/apply', [ProcedimentoImportController::class, 'apply'])->name('procedimento.import.apply');
        Route::post('/procedimento-import/tu', [ProcedimentoImportController::class, 'storeTu'])->name('procedimento.import.tu.store');
        Route::get('/procedimento-import/tu/preview', [ProcedimentoImportController::class, 'previewTu'])->name('procedimento.import.tu.preview');
        Route::post('/procedimento-import/tu/apply', [ProcedimentoImportController::class, 'applyTu'])->name('procedimento.import.tu.apply');
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
        
        // Relatórios Produção Individualizada (s_bpi)
        Route::get('/relatorios/bpi', [App\Http\Controllers\RelatorioBpiController::class, 'index'])->name('relatorios.bpi.index');
        Route::get('/relatorios/bpi/fields', [App\Http\Controllers\RelatorioBpiController::class, 'getFields'])->name('relatorios.bpi.fields');
        Route::get('/relatorios/bpi/lookup', [App\Http\Controllers\RelatorioBpiController::class, 'getLookupData'])->name('relatorios.bpi.lookup');
        Route::post('/relatorios/bpi/generate', [App\Http\Controllers\RelatorioBpiController::class, 'generate'])->name('relatorios.bpi.generate');
        Route::post('/relatorios/bpi/generate-matrix', [App\Http\Controllers\RelatorioBpiController::class, 'generateMatrix'])->name('relatorios.bpi.generate-matrix');
        Route::post('/relatorios/bpi/debug', [App\Http\Controllers\RelatorioBpiController::class, 'debug'])->name('relatorios.bpi.debug');

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

        
        // Importação AIH (SIHD)
        Route::get('/aih-import', [AihImportController::class, 'create'])->name('aih.import');
        Route::post('/aih-import', [AihImportController::class, 'store'])->name('aih.import.store');
        Route::get('/aih-import/preview', [AihImportController::class, 'preview'])->name('aih.import.preview');
        Route::post('/aih-import/apply', [AihImportController::class, 'apply'])->name('aih.import.apply');

        // Relatórios AIH — Internações (s_aih)
        Route::get('/relatorios/aih', [RelatorioAihController::class, 'index'])->name('relatorios.aih.index');
        Route::get('/relatorios/aih/fields', [RelatorioAihController::class, 'getFields'])->name('relatorios.aih.fields');
        Route::get('/relatorios/aih/lookup', [RelatorioAihController::class, 'getLookupData'])->name('relatorios.aih.lookup');
        Route::post('/relatorios/aih/generate', [RelatorioAihController::class, 'generate'])->name('relatorios.aih.generate');
        Route::post('/relatorios/aih/generate-matrix', [RelatorioAihController::class, 'generateMatrix'])->name('relatorios.aih.generate-matrix');

        // Relatórios AIH — Procedimentos (s_aih_pa)
        Route::get('/relatorios/aih-pa', [RelatorioAihPaController::class, 'index'])->name('relatorios.aih-pa.index');
        Route::get('/relatorios/aih-pa/fields', [RelatorioAihPaController::class, 'getFields'])->name('relatorios.aih-pa.fields');
        Route::get('/relatorios/aih-pa/lookup', [RelatorioAihPaController::class, 'getLookupData'])->name('relatorios.aih-pa.lookup');
        Route::post('/relatorios/aih-pa/generate', [RelatorioAihPaController::class, 'generate'])->name('relatorios.aih-pa.generate');
        Route::post('/relatorios/aih-pa/generate-matrix', [RelatorioAihPaController::class, 'generateMatrix'])->name('relatorios.aih-pa.generate-matrix');

        // Additional custom routes if needed
        Route::patch('/prestador/{prestador}/toggle-status', [PrestadorController::class, 'toggleStatus'])->name('prestador.toggle-status');
    });
});

require __DIR__.'/auth.php';
