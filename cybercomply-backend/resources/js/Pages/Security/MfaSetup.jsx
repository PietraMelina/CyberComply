import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useMfa } from '@/hooks/useMfa';
import QRCode from 'qrcode';
import { jsPDF } from 'jspdf';

export default function MfaSetup({ auth, status }) {
    const mfa = useMfa();
    const [loading, setLoading] = useState(true);
    const [enabled, setEnabled] = useState(false);
    const [remaining, setRemaining] = useState(0);
    const [setupData, setSetupData] = useState(null);
    const [confirmCode, setConfirmCode] = useState('');
    const [disableCode, setDisableCode] = useState('');
    const [qrDataUrl, setQrDataUrl] = useState('');
    const [success, setSuccess] = useState(status || '');
    const [error, setError] = useState('');

    const loadStatus = async () => {
        setLoading(true);
        setError('');
        try {
            const data = await mfa.status();
            setEnabled(Boolean(data.enabled));
            setRemaining(Number(data.backup_codes_remaining || 0));
        } catch (e) {
            setError(e?.response?.data?.error || 'Falha ao carregar status MFA.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadStatus();
    }, []);

    const startSetup = async () => {
        setError('');
        setSuccess('');
        try {
            const data = await mfa.setup();
            setSetupData(data);
            if (data?.otpauth_uri) {
                const dataUrl = await QRCode.toDataURL(data.otpauth_uri, { margin: 1, width: 280 });
                setQrDataUrl(dataUrl);
            }
            setSuccess('QR gerado. Escaneie no app autenticador e confirme com o primeiro código.');
        } catch (e) {
            setError(e?.response?.data?.error || 'Falha ao iniciar setup.');
        }
    };

    const confirmSetup = async () => {
        setError('');
        setSuccess('');
        try {
            const data = await mfa.confirm(confirmCode);
            setSetupData(null);
            setConfirmCode('');
            setSuccess(data.message || 'MFA ativado com sucesso.');
            await loadStatus();
        } catch (e) {
            setError(e?.response?.data?.error || 'Falha ao confirmar MFA.');
        }
    };

    const disableMfa = async () => {
        setError('');
        setSuccess('');
        try {
            const data = await mfa.disable(disableCode);
            setDisableCode('');
            setSetupData(null);
            setSuccess(data.message || 'MFA desativado.');
            await loadStatus();
        } catch (e) {
            setError(e?.response?.data?.error || 'Falha ao desativar MFA.');
        }
    };

    const downloadSetupPdfLocal = () => {
        if (!setupData?.otpauth_uri || !setupData?.backup_codes?.length || !qrDataUrl) {
            setError('Inicie o setup para gerar o PDF completo com QR e backup codes.');
            return;
        }

        const doc = new jsPDF();
        doc.setFontSize(16);
        doc.text('CyberComply - MFA Setup', 14, 16);
        doc.setFontSize(11);
        doc.text(`Utilizador: ${auth.user?.display_name || auth.user?.email}`, 14, 25);
        doc.text(`Email: ${auth.user?.email}`, 14, 31);
        doc.text(`User ID: ${auth.user?.id}`, 14, 37);
        doc.addImage(qrDataUrl, 'PNG', 14, 44, 55, 55);
        doc.setFontSize(9);
        doc.text('QR Code para app autenticador', 14, 103);
        doc.text(`URI: ${setupData.otpauth_uri}`, 14, 112, { maxWidth: 182 });
        doc.setFontSize(11);
        doc.text('Backup codes:', 14, 134);
        setupData.backup_codes.forEach((code, index) => {
            const x = 14 + (index % 3) * 55;
            const y = 142 + Math.floor(index / 3) * 10;
            doc.text(code, x, y);
        });
        doc.save(`mfa-setup-${auth.user?.id}.pdf`);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-black leading-tight">Configurar MFA</h2>}
        >
            <Head title="MFA" />

            <div className="py-8">
                <div className="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    {success && <div className="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800">{success}</div>}
                    {error && <div className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</div>}

                    <div className="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        {loading ? (
                            <div className="text-sm text-black">Carregando status MFA...</div>
                        ) : (
                            <div className="text-sm text-black">
                                Estado atual: <strong>{enabled ? 'Ativado' : 'Desativado'}</strong>
                                {enabled && <span className="ml-2 text-black">backup codes restantes: {remaining}</span>}
                            </div>
                        )}

                        {!loading && !enabled && (
                            <div className="space-y-4">
                                {!setupData && (
                                    <PrimaryButton className="bg-black text-white hover:bg-black" onClick={startSetup}>Iniciar setup MFA</PrimaryButton>
                                )}

                                {setupData && (
                                    <div className="space-y-4">
                                        <p className="text-sm text-black">
                                            Escaneie o QR code no Google Authenticator/Authy e depois confirme com o primeiro código de 6 dígitos.
                                        </p>
                                        {qrDataUrl ? (
                                            <img src={qrDataUrl} alt="QR Code MFA" className="h-56 w-56 rounded border" />
                                        ) : (
                                            <img src={setupData.qr_code_url} alt="QR Code MFA" className="h-56 w-56 rounded border" />
                                        )}
                                        <div>
                                            <button
                                                type="button"
                                                onClick={downloadSetupPdfLocal}
                                                className="inline-flex rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white"
                                            >
                                                Download PDF do setup (QR + códigos)
                                            </button>
                                        </div>

                                        <div className="rounded border border-yellow-300 bg-yellow-50 p-3">
                                            <div className="font-semibold text-sm mb-2">Backup codes (guarde agora)</div>
                                            <div className="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs font-mono">
                                                {(setupData.backup_codes || []).map((code) => (
                                                    <div key={code} className="rounded bg-white border p-2 text-center">
                                                        {code}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="max-w-sm">
                                            <InputLabel htmlFor="confirm_code" value="Código do app autenticador" />
                                            <TextInput
                                                id="confirm_code"
                                                value={confirmCode}
                                                onChange={(e) => setConfirmCode(e.target.value)}
                                                className="mt-1 block w-full"
                                            />
                                            <PrimaryButton className="mt-2 bg-black text-white hover:bg-black" onClick={confirmSetup}>
                                                Confirmar e ativar MFA
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {!loading && enabled && (
                            <div className="space-y-2 max-w-sm">
                                <a href={route('security.mfa.setup-pdf')} className="inline-flex rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white">
                                    Download PDF do MFA
                                </a>
                                <InputLabel htmlFor="disable_code" value="Código TOTP atual para desativar" />
                                <TextInput
                                    id="disable_code"
                                    value={disableCode}
                                    onChange={(e) => setDisableCode(e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <PrimaryButton className="bg-black text-white hover:bg-black" onClick={disableMfa}>Desativar MFA</PrimaryButton>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
