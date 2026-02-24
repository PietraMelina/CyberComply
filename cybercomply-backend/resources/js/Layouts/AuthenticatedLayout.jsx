import { Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import Sidebar from '@/Components/Sidebar';
import BugReportWidget from '@/Components/BugReportWidget';

export default function Authenticated({ user, header, children }) {
    const { auth } = usePage().props;
    const isAuditReader = ['MASTER', 'GESTOR', 'AUDITOR'].includes(user?.role);
    const canReadBugs = ['MASTER', 'GESTOR', 'AUDITOR', 'ADMIN_CLIENTE'].includes(user?.role);
    const [menuOpen, setMenuOpen] = useState(false);

    const availableClients = auth?.available_clients || [];
    const selectedClientId = auth?.selected_client_id || '';
    const effectiveSelectedClientId = selectedClientId || (availableClients[0]?.id ?? '');
    const isInternal = !user?.client_id;

    const mobileLinks = [
        { href: route('dashboard'), label: 'Dashboard' },
        { href: route('sites.index'), label: 'Sites' },
        { href: route('modules.index'), label: 'Módulos' },
        { href: route('responses.index'), label: 'Respostas' },
        ...(canReadBugs ? [{ href: route('bugs.index'), label: 'Bugs' }] : []),
        ...(isAuditReader ? [{ href: route('audit-logs.index'), label: 'Auditoria' }] : []),
        { href: route('security.mfa.show'), label: 'MFA' },
    ];

    const avatarLabel = useMemo(() => {
        const name = user?.display_name || user?.name || user?.email || 'U';
        return String(name).trim().charAt(0).toUpperCase() || 'U';
    }, [user]);

    const switchClient = (clientId) => {
        const params = new URLSearchParams(window.location.search);
        if (clientId) {
            params.set('client_id', clientId);
        } else {
            params.delete('client_id');
        }
        const target = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}`;
        router.get(target, {}, { preserveScroll: true });
    };

    return (
        <div className="min-h-screen bg-cyber-bg text-cyber-text">
            <Sidebar isAuditReader={isAuditReader} />

            <div className="md:pl-72">
                <header className="sticky top-0 z-20 border-b border-cyber-border bg-cyber-secondary/95 backdrop-blur">
                    <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                        <div className="min-w-0">
                            {header || <h2 className="text-lg font-semibold">CyberComply</h2>}
                            <p className="text-xs text-cyber-muted font-mono">Usuário: {user?.id}</p>
                        </div>

                        <div className="flex items-center gap-3">
                            {isInternal && availableClients.length > 0 && (
                                <select
                                    className="cyber-input h-10 py-1 text-xs"
                                    value={effectiveSelectedClientId}
                                    onChange={(e) => switchClient(e.target.value)}
                                >
                                    {availableClients.map((client) => (
                                        <option key={client.id} value={client.id}>
                                            {client.name} ({client.type})
                                        </option>
                                    ))}
                                </select>
                            )}

                            <div className="relative">
                                <button
                                    type="button"
                                    onClick={() => setMenuOpen((v) => !v)}
                                    className="flex items-center gap-2 rounded-full border border-cyber-border bg-cyber-card px-2 py-1 hover:border-cyber-primary"
                                >
                                    {user?.avatar_url ? (
                                        <img src={user.avatar_url} alt="avatar" className="h-8 w-8 rounded-full object-cover" />
                                    ) : (
                                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-cyber-primary text-cyber-bg font-bold">
                                            {avatarLabel}
                                        </span>
                                    )}
                                    <span className="hidden sm:block text-left">
                                        <span className="block text-sm leading-4">{user?.display_name || user?.name || user?.email}</span>
                                        <span className="block text-[11px] text-cyber-muted">{user?.role}</span>
                                    </span>
                                </button>

                                {menuOpen && (
                                    <div className="absolute right-0 mt-2 w-56 rounded-lg border border-cyber-border bg-cyber-card p-2 shadow-cyber-lg">
                                        <div className="px-3 py-2 border-b border-cyber-border mb-1">
                                            <div className="text-sm">{user?.email}</div>
                                            <div className="text-xs text-cyber-muted">Perfil: {user?.role}</div>
                                        </div>
                                        <Link href={route('profile.edit')} className="block rounded px-3 py-2 text-sm hover:bg-cyber-hover" onClick={() => setMenuOpen(false)}>
                                            Meu perfil
                                        </Link>
                                        <Link href={route('terms.show')} className="block rounded px-3 py-2 text-sm hover:bg-cyber-hover" onClick={() => setMenuOpen(false)}>
                                            Termos e condições
                                        </Link>
                                        <Link href={route('security.mfa.show')} className="block rounded px-3 py-2 text-sm hover:bg-cyber-hover" onClick={() => setMenuOpen(false)}>
                                            Segurança (MFA)
                                        </Link>
                                        <Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="mt-1 w-full rounded px-3 py-2 text-left text-sm text-cyber-danger hover:bg-cyber-danger/10"
                                            onClick={() => setMenuOpen(false)}
                                        >
                                            Terminar sessão
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="md:hidden border-t border-cyber-border/80 px-4 py-2">
                        <div className="flex gap-2 overflow-x-auto">
                            {mobileLinks.map((item) => (
                                <Link key={item.href} href={item.href} className="cyber-button-secondary whitespace-nowrap text-[11px] px-3 py-1.5">
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>
                </header>

                <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>
            <BugReportWidget />
        </div>
    );
}
