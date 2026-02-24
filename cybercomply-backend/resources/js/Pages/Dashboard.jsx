import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

export default function Dashboard({ auth, isInternal, summary, clients }) {
    const [uploading, setUploading] = useState('');

    const canUploadLogoRole = ['MASTER', 'GESTOR', 'ADMIN_CLIENTE'].includes(auth.user?.role);

    const uploadLogo = async (clientId, file) => {
        if (!file) return;
        setUploading(clientId);
        try {
            const data = new FormData();
            data.append('logo', file);
            await window.axios.post(route('clients.logo.update', { clientId }), data, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            window.location.reload();
        } finally {
            setUploading('');
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-display font-bold text-xl text-cyber-text">Dashboard Geral</h2>}
        >
            <Head title="Dashboard" />

            <div className="space-y-6">
                <section className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div className="cyber-card p-4">
                        <div className="text-xs text-cyber-muted">Clientes</div>
                        <div className="text-3xl font-display">{summary.clients_total}</div>
                    </div>
                    <div className="cyber-card p-4">
                        <div className="text-xs text-cyber-muted">Clientes ativos</div>
                        <div className="text-3xl font-display text-cyber-success">{summary.clients_active}</div>
                    </div>
                    <div className="cyber-card p-4">
                        <div className="text-xs text-cyber-muted">Riscos atuais</div>
                        <div className="text-3xl font-display text-cyber-danger">{summary.total_risks}</div>
                    </div>
                    <div className="cyber-card p-4">
                        <div className="text-xs text-cyber-muted">Respostas atuais</div>
                        <div className="text-3xl font-display">{summary.total_responses}</div>
                    </div>
                </section>

                <section className="cyber-card p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-display text-lg">{isInternal ? 'Todos os clientes' : 'Resumo do cliente'}</h3>
                        {['MASTER', 'GESTOR', 'AUDITOR'].includes(auth.user?.role) && (
                            <Link href={route('audit-logs.index')} className="cyber-button-secondary text-xs">
                                Abrir auditoria
                            </Link>
                        )}
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="border-b border-cyber-border text-left text-cyber-muted">
                                    <th className="p-2">Cliente</th>
                                    <th className="p-2">Tipo</th>
                                    <th className="p-2">Estado</th>
                                    <th className="p-2">Utilizadores</th>
                                    <th className="p-2">Sites</th>
                                    <th className="p-2">Módulos</th>
                                    <th className="p-2">Riscos</th>
                                    <th className="p-2">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {clients.map((client) => (
                                    <tr key={client.id} className="border-b border-cyber-border/50">
                                        <td className="p-2">
                                            <div className="font-semibold">{client.name}</div>
                                            <div className="text-xs text-cyber-muted font-mono">{client.id}</div>
                                        </td>
                                        <td className="p-2">{client.type}</td>
                                        <td className="p-2">
                                            {client.is_active ? (
                                                <span className="cyber-badge bg-cyber-success/15 text-cyber-success">Ativo</span>
                                            ) : (
                                                <span className="cyber-badge bg-cyber-danger/15 text-cyber-danger">Inativo</span>
                                            )}
                                        </td>
                                        <td className="p-2">{client.users_count}</td>
                                        <td className="p-2">{client.sites_count}</td>
                                        <td className="p-2">{client.modules_count}</td>
                                        <td className="p-2 text-cyber-danger font-semibold">{client.risk_count}</td>
                                        <td className="p-2">
                                            <div className="mb-2 flex items-center gap-2">
                                                {client.logo_url ? (
                                                    <img
                                                        src={client.logo_url}
                                                        alt={`logo-${client.id}`}
                                                        className="h-8 w-8 rounded object-cover border border-cyber-border"
                                                    />
                                                ) : (
                                                    <span className="inline-flex h-8 w-8 items-center justify-center rounded bg-cyber-secondary text-xs text-cyber-muted">LOGO</span>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                <Link className="cyber-button-secondary text-xs" href={route('sites.index', { client_id: client.id })}>
                                                    Sites
                                                </Link>
                                                <Link className="cyber-button-secondary text-xs" href={route('modules.index', { client_id: client.id })}>
                                                    Módulos
                                                </Link>
                                                <Link className="cyber-button-secondary text-xs" href={route('responses.index', { client_id: client.id })}>
                                                    Respostas
                                                </Link>
                                                {canUploadLogoRole && (isInternal || auth.user?.client_id === client.id) && (
                                                    <label className="cyber-button-secondary text-xs cursor-pointer">
                                                        {uploading === client.id ? 'A carregar...' : 'Enviar logo'}
                                                        <input
                                                            type="file"
                                                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                            className="hidden"
                                                            onChange={(e) => uploadLogo(client.id, e.target.files?.[0])}
                                                            disabled={uploading === client.id}
                                                        />
                                                    </label>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
