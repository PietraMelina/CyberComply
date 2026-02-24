import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';

export default function TermsShow({ auth, currentVersion, alreadyAccepted }) {
    const form = useForm({ accepted: false });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('terms.accept'));
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-display text-xl">Termos e Condições</h2>}>
            <Head title="Termos e Condições" />

            <div className="cyber-card p-6 space-y-4">
                <div className="text-sm text-cyber-muted">Versão atual: {currentVersion}</div>
                <div className="rounded border border-cyber-border bg-cyber-secondary p-4 text-sm leading-6 text-cyber-text">
                    <p>1. O uso desta plataforma é restrito a utilizadores autorizados.</p>
                    <p>2. Todas as ações são auditadas e registradas para rastreabilidade.</p>
                    <p>3. É proibido compartilhar credenciais de acesso com terceiros.</p>
                    <p>4. Evidências e respostas devem refletir o estado real de compliance.</p>
                    <p>5. O não cumprimento destas regras pode resultar em suspensão de acesso.</p>
                </div>

                {alreadyAccepted ? (
                    <div className="rounded border border-cyber-success/40 bg-cyber-success/10 p-3 text-sm text-cyber-success">
                        Você já aceitou os termos desta versão.
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-3">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.accepted}
                                onChange={(e) => form.setData('accepted', e.target.checked)}
                            />
                            Li e aceito os Termos e Condições.
                        </label>
                        {form.errors.accepted && <div className="text-sm text-cyber-danger">{form.errors.accepted}</div>}

                        <PrimaryButton className="bg-black text-white hover:bg-black" disabled={form.processing}>
                            {form.processing ? 'A processar...' : 'Aceitar termos'}
                        </PrimaryButton>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
