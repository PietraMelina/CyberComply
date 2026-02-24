<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\TotpService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class MfaSetupController extends Controller
{
    public function __construct(private readonly TotpService $totpService)
    {
    }

    public function show(Request $request): Response
    {
        return Inertia::render('Security/MfaSetup', [
            'status' => session('status'),
        ]);
    }

    public function downloadSetupPdf(Request $request)
    {
        $user = $request->user();
        $cacheKey = "mfa_setup_{$user->id}";
        $setup = Cache::get($cacheKey);

        $secret = null;
        $backupCodes = [];
        if (is_array($setup)) {
            $secret = isset($setup['secret']) ? (string) $setup['secret'] : null;
            $backupCodes = is_array($setup['backup_codes_plain'] ?? null) ? $setup['backup_codes_plain'] : [];
        }

        if (!$secret) {
            $secret = $user->mfa_secret ? (string) $user->mfa_secret : null;
        }

        if (!$secret) {
            return back()->with('status', 'Inicie o setup do MFA para gerar o PDF.');
        }

        $otpauthUri = $this->totpService->makeOtpAuthUri(
            (string) config('app.name', 'CyberComply'),
            (string) $user->email,
            $secret
        );
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='.rawurlencode($otpauthUri);

        $pdf = Pdf::loadView('pdf.mfa-setup', [
            'user' => $user,
            'secret' => $secret,
            'backupCodes' => $backupCodes,
            'otpauthUri' => $otpauthUri,
            'qrCodeUrl' => $qrCodeUrl,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download('mfa-setup-'.$user->id.'.pdf');
    }
}
