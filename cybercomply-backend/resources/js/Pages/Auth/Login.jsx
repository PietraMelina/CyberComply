import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { useMfa } from '@/hooks/useMfa';

export default function Login({ status }) {
    const mfa = useMfa();
    const [step, setStep] = useState('credentials');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [mfaCode, setMfaCode] = useState('');
    const [emailToken, setEmailToken] = useState('');
    const [tempToken, setTempToken] = useState('');
    const [mfaType, setMfaType] = useState('totp');
    const [processing, setProcessing] = useState(false);
    const [message, setMessage] = useState(status || '');
    const [errors, setErrors] = useState({});

    const setError = (field, msg) => setErrors((prev) => ({ ...prev, [field]: msg }));
    const clearErrors = () => setErrors({});

    const establishWebSession = async (accessToken) => {
        await window.axios.post(route('auth.web-session'), { access_token: accessToken });
        window.location.href = route('dashboard');
    };

    const submitCredentials = async (e) => {
        e.preventDefault();
        clearErrors();
        setMessage('');
        setProcessing(true);

        try {
            const { data } = await window.axios.post('/api/auth/login', { email, password });

            if (data.mfa_required && data.temp_token) {
                setTempToken(data.temp_token);
                setStep('mfa');
                setMessage('Validação 2FA necessária para concluir o acesso.');
                return;
            }

            if (data.access_token) {
                await establishWebSession(data.access_token);
                return;
            }

            setError('email', 'Resposta inesperada no login.');
        } catch (err) {
            setError('email', err?.response?.data?.message || 'Falha no login.');
        } finally {
            setProcessing(false);
        }
    };

    const verifyMfa = async (e) => {
        e.preventDefault();
        clearErrors();
        setMessage('');
        setProcessing(true);

        try {
            const code = mfaType === 'email' ? emailToken : mfaCode;
            if (!code) {
                setError('mfa_code', 'Informe o código.');
                return;
            }

            const data =
                mfaType === 'email'
                    ? await mfa.verifyEmailToken(tempToken, code)
                    : await mfa.verify(tempToken, code, mfaType);

            await establishWebSession(data.access_token);
        } catch (err) {
            setError('mfa_code', err?.response?.data?.error || 'Código inválido.');
        } finally {
            setProcessing(false);
        }
    };

    const sendEmailToken = async () => {
        clearErrors();
        setMessage('');
        setProcessing(true);
        try {
            const data = await mfa.sendEmailToken(tempToken);
            setMfaType('email');
            setMessage(data.message || 'Código enviado por email.');
        } catch (err) {
            setError('email_token', err?.response?.data?.error || 'Falha ao enviar token.');
        } finally {
            setProcessing(false);
        }
    };

    return (
        <GuestLayout>
            <Head title="Login" />

            <div className="space-y-6">
                <div className="text-center">
                    <h1 className="text-3xl font-display font-bold text-cyber-text">Acesso Seguro</h1>
                    <p className="mt-1 text-sm text-cyber-muted font-mono tracking-wide">COMPLIANCE PLATFORM</p>
                </div>

                {message && (
                    <div className="rounded-lg border border-cyber-primary/30 bg-cyber-primary/10 px-3 py-2 text-sm text-cyber-primary">
                        {message}
                    </div>
                )}

                {step === 'credentials' && (
                    <form onSubmit={submitCredentials} className="space-y-4">
                        <div>
                            <InputLabel htmlFor="email" value="EMAIL_CORPORATIVO" />
                            <TextInput
                                id="email"
                                type="email"
                                value={email}
                                className="mt-1 block w-full"
                                onChange={(e) => setEmail(e.target.value)}
                                autoComplete="username"
                            />
                            <InputError message={errors.email} className="mt-1 text-cyber-danger" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="SENHA_DE_ACESSO" />
                            <TextInput
                                id="password"
                                type="password"
                                value={password}
                                className="mt-1 block w-full"
                                onChange={(e) => setPassword(e.target.value)}
                                autoComplete="current-password"
                            />
                            <InputError message={errors.password} className="mt-1 text-cyber-danger" />
                        </div>

                        <PrimaryButton className="w-full justify-center py-3" disabled={processing}>
                            {processing ? 'AUTENTICANDO...' : 'ENTRAR_NO_SISTEMA'}
                        </PrimaryButton>

                        <div className="text-center text-xs text-cyber-muted">
                            Não tem conta?{' '}
                            <Link href={route('register')} className="text-cyber-primary underline">
                                Registar-se
                            </Link>
                        </div>
                    </form>
                )}

                {step === 'mfa' && (
                    <form onSubmit={verifyMfa} className="space-y-4">
                        <div className="rounded-lg border border-cyber-border bg-cyber-secondary p-3">
                            <div className="text-sm font-medium text-cyber-text">Verificação 2FA</div>
                            <p className="mt-1 text-xs text-cyber-muted">Escolha o método para validar o login.</p>
                        </div>

                        <div>
                            <InputLabel htmlFor="mfa_type" value="METODO" />
                            <select
                                id="mfa_type"
                                className="cyber-input mt-1"
                                value={mfaType}
                                onChange={(e) => setMfaType(e.target.value)}
                            >
                                <option value="totp">App autenticador</option>
                                <option value="backup">Backup code</option>
                                <option value="email">Token por email</option>
                            </select>
                        </div>

                        {mfaType !== 'email' && (
                            <div>
                                <InputLabel htmlFor="mfa_code" value={mfaType === 'backup' ? 'BACKUP_CODE' : 'CODIGO_APP'} />
                                <TextInput
                                    id="mfa_code"
                                    value={mfaCode}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setMfaCode(e.target.value)}
                                />
                            </div>
                        )}

                        {mfaType === 'email' && (
                            <div className="space-y-2">
                                <button type="button" className="cyber-button-secondary w-full text-sm" onClick={sendEmailToken} disabled={processing}>
                                    Enviar token por email
                                </button>
                                <div>
                                    <InputLabel htmlFor="email_token" value="TOKEN_EMAIL" />
                                    <TextInput
                                        id="email_token"
                                        value={emailToken}
                                        className="mt-1 block w-full"
                                        onChange={(e) => setEmailToken(e.target.value)}
                                    />
                                </div>
                            </div>
                        )}

                        <InputError message={errors.mfa_code || errors.email_token} className="mt-1 text-cyber-danger" />

                        <div className="flex gap-2">
                            <PrimaryButton className="flex-1 justify-center" disabled={processing}>
                                {processing ? 'VALIDANDO...' : 'Validar 2FA'}
                            </PrimaryButton>
                            <button
                                type="button"
                                className="cyber-button-secondary"
                                onClick={() => {
                                    setStep('credentials');
                                    setTempToken('');
                                    setMfaCode('');
                                    setEmailToken('');
                                    setMessage('');
                                    clearErrors();
                                }}
                            >
                                Voltar
                            </button>
                        </div>
                    </form>
                )}

                <div className="flex justify-center gap-2 pt-1">
                    <span className="cyber-badge bg-cyber-success/10 text-cyber-success">● TLS 1.3</span>
                    <span className="cyber-badge bg-cyber-primary/10 text-cyber-primary">● 2FA</span>
                </div>
            </div>
        </GuestLayout>
    );
}

