<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\CmdAuthController;
use App\Http\Controllers\Auth\SelfRegistrationController;
use App\Http\Controllers\Admin\ClientAnonymizationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\Web\AuditLogPageController;
use App\Http\Controllers\Web\AvatarController;
use App\Http\Controllers\Web\ClientLogoController;
use App\Http\Controllers\Web\BugReportController;
use App\Http\Controllers\Web\BugReportPageController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MfaSetupController;
use App\Http\Controllers\Web\ModulePageController;
use App\Http\Controllers\Web\ResponsePageController;
use App\Http\Controllers\Web\SitePageController;
use App\Http\Controllers\Web\TermsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [SelfRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [SelfRegistrationController::class, 'store'])->name('register.submit');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/auth/web-session', [AuthenticatedSessionController::class, 'sessionFromApiToken'])->name('auth.web-session');

    if ((bool) env('CMD_AUTH_ENABLED', false)) {
        Route::get('/auth/cmd', [CmdAuthController::class, 'redirect'])->name('cmd.redirect');
        Route::get('/auth/cmd/callback', [CmdAuthController::class, 'callback'])->name('cmd.callback');
        Route::get('/registar/novo', [CmdAuthController::class, 'showCompleteRegistration'])->middleware('cmd.validated')->name('register.new');
        Route::post('/registar/novo', [CmdAuthController::class, 'completeRegistration'])->middleware('cmd.validated')->name('register.store');
        Route::get('/registar/pendente', [CmdAuthController::class, 'pending'])->name('register.pending');
        Route::post('/auth/cmd/resend-token', [CmdAuthController::class, 'resendToken'])->middleware('throttle:3,10')->name('cmd.resend');
        Route::get('/verificar-email/{token}', [CmdAuthController::class, 'verifyEmail'])->name('verify.email');
    }
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/terms', [TermsController::class, 'show'])->name('terms.show');
    Route::post('/terms/accept', [TermsController::class, 'accept'])->name('terms.accept');
    Route::get('/media/avatar/{id}', [AvatarController::class, 'show'])->name('profile.avatar.show');
    Route::get('/media/logo/{clientId}', [ClientLogoController::class, 'show'])->name('client.logo.show');
});

Route::middleware(['auth', 'terms.accepted', 'mfa.enrolled'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [AvatarController::class, 'update'])->name('profile.avatar.update');

    Route::get('/sites', [SitePageController::class, 'index'])->name('sites.index');
    Route::post('/clients/{clientId}/logo', [ClientLogoController::class, 'update'])->name('clients.logo.update');
    Route::get('/sites/create', [SitePageController::class, 'create'])->name('sites.create');
    Route::post('/sites', [SitePageController::class, 'store'])->name('sites.store');
    Route::get('/modules', [ModulePageController::class, 'index'])
        ->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA')
        ->name('modules.index');
    Route::get('/modules/{id}', [ModulePageController::class, 'show'])
        ->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA')
        ->name('modules.show');
    Route::post('/modules/{id}/responses', [ModulePageController::class, 'storeResponse'])
        ->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE,TECNICO')
        ->name('modules.responses.store');
    Route::get('/responses', [ResponsePageController::class, 'index'])->name('responses.index');
    Route::post('/bug-reports', [BugReportController::class, 'store'])->name('bug-reports.store');
    Route::get('/bugs', [BugReportPageController::class, 'index'])
        ->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE')
        ->name('bugs.index');
    Route::patch('/bugs/{id}/status', [BugReportPageController::class, 'updateStatus'])
        ->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE')
        ->name('bugs.status.update');
    Route::get('/security/mfa', [MfaSetupController::class, 'show'])->name('security.mfa.show');
    Route::get('/security/mfa/setup-pdf', [MfaSetupController::class, 'downloadSetupPdf'])->name('security.mfa.setup-pdf');

    Route::get('/audit-logs', [AuditLogPageController::class, 'index'])
        ->middleware('role:MASTER,GESTOR,AUDITOR')
        ->name('audit-logs.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])
        ->middleware(['role:MASTER,GESTOR,AUDITOR', 'throttle:audit_export'])
        ->name('audit-logs.export');

    Route::post('/admin/clients/{id}/anonymize', [ClientAnonymizationController::class, 'anonymize'])
        ->middleware('role:MASTER')
        ->name('admin.clients.anonymize');
});

Route::middleware(['auth', 'terms.accepted'])->prefix('api/mfa')->group(function (): void {
    Route::get('/status', [MfaController::class, 'status'])->name('api.mfa.status');
    Route::post('/setup', [MfaController::class, 'setup'])->name('api.mfa.setup');
    Route::post('/confirm', [MfaController::class, 'confirm'])->name('api.mfa.confirm');
    Route::post('/disable', [MfaController::class, 'disable'])->name('api.mfa.disable');
});
