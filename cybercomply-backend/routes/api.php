<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ClientAnonymizationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\MfaController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/mfa/verify', [MfaController::class, 'verify']);
Route::post('/mfa/email', [MfaController::class, 'email'])->middleware('throttle:3,10');
Route::post('/mfa/verify-email', [MfaController::class, 'verifyEmail']);

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth_login');

    Route::middleware(['auth:api'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware(['auth:api', 'tenant'])->group(function (): void {
    Route::post('/admin/clients/{id}/anonymize', [ClientAnonymizationController::class, 'anonymize'])
        ->middleware('role:MASTER');

    Route::middleware('role:MASTER,GESTOR')->prefix('clients')->group(function (): void {
        Route::get('/', [ClientController::class, 'index']);
        Route::post('/', [ClientController::class, 'store']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
        Route::get('/{id}/sites', [ClientController::class, 'sites']);
        Route::post('/{id}/sites', [ClientController::class, 'storeSite']);
    });

    Route::prefix('users')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::post('/', [UserController::class, 'store'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::put('/{id}/role', [UserController::class, 'changeRole'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::post('/{id}/mfa/enable', [UserController::class, 'enableMfa'])->middleware(['role:MASTER,GESTOR,ADMIN_CLIENTE', 'throttle:mfa']);
        Route::post('/{id}/mfa/disable', [UserController::class, 'disableMfa'])->middleware(['role:MASTER,GESTOR,ADMIN_CLIENTE', 'throttle:mfa']);
    });

    Route::prefix('modules')->group(function (): void {
        Route::get('/', [ModuleController::class, 'index'])->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA');
        Route::post('/', [ModuleController::class, 'store'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::get('/{id}', [ModuleController::class, 'show'])->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA');
        Route::put('/{id}', [ModuleController::class, 'update'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::post('/{id}/questions', [ModuleController::class, 'storeQuestion'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE');
        Route::get('/{id}/responses', [ModuleController::class, 'responses'])->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA');
    });

    Route::prefix('responses')->group(function (): void {
        Route::post('/', [ResponseController::class, 'store'])->middleware('role:MASTER,GESTOR,ADMIN_CLIENTE,TECNICO');
        Route::get('/{id}/history', [ResponseController::class, 'history'])->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA');
        Route::get('/{id}/evidences', [ResponseController::class, 'evidences'])->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA');
    });

    Route::prefix('evidences')->group(function (): void {
        Route::post('/', [EvidenceController::class, 'store'])->middleware(['role:MASTER,GESTOR,ADMIN_CLIENTE,TECNICO', 'throttle:evidence_upload']);
        Route::get('/{token}', [EvidenceController::class, 'download'])->middleware('role:MASTER,GESTOR,AUDITOR,ADMIN_CLIENTE,TECNICO,LEITURA');
    });

    Route::prefix('audit-logs')->middleware('role:MASTER,GESTOR,AUDITOR')->group(function (): void {
        Route::get('/', [AuditLogController::class, 'index']);
        Route::get('/export', [AuditLogController::class, 'export'])->middleware('throttle:audit_export');
        Route::get('/{id}', [AuditLogController::class, 'show']);
    });
});
