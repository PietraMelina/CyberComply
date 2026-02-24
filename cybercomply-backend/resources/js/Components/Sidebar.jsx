import { Link, usePage } from '@inertiajs/react';
import { ComplianceIcon, FileLockIcon, LayersIcon, ShieldCheckIcon } from '@/Components/Icons/CyberIcons';

export default function Sidebar({ isAuditReader }) {
    const page = usePage();
    const url = page.url || '';
    const role = page.props?.auth?.user?.role;
    const canReadBugs = ['MASTER', 'GESTOR', 'AUDITOR', 'ADMIN_CLIENTE'].includes(role);

    const navItems = [
        { href: route('dashboard'), label: 'Dashboard', icon: ComplianceIcon, match: '/dashboard' },
        { href: route('sites.index'), label: 'Sites', icon: ShieldCheckIcon, match: '/sites' },
        { href: route('modules.index'), label: 'Módulos', icon: LayersIcon, match: '/modules' },
        { href: route('responses.index'), label: 'Respostas', icon: FileLockIcon, match: '/responses' },
        ...(canReadBugs ? [{ href: route('bugs.index'), label: 'Bugs', icon: FileLockIcon, match: '/bugs' }] : []),
        ...(isAuditReader ? [{ href: route('audit-logs.index'), label: 'Auditoria', icon: ShieldCheckIcon, match: '/audit-logs' }] : []),
        { href: route('security.mfa.show'), label: 'MFA', icon: ShieldCheckIcon, match: '/security/mfa' },
        { href: route('profile.edit'), label: 'Perfil', icon: ShieldCheckIcon, match: '/profile' },
    ];

    return (
        <aside className="hidden md:flex md:w-72 md:flex-col md:fixed md:inset-y-0 md:border-r md:border-cyber-border md:bg-cyber-secondary">
            <div className="px-6 pt-6">
                <div className="cyber-card p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-cyber-primary to-cyber-secondaryAccent text-cyber-bg shadow-cyber">
                            <ComplianceIcon className="h-6 w-6" />
                        </div>
                        <div>
                            <div className="text-lg font-display font-bold text-cyber-text">CyberComply</div>
                            <div className="text-[10px] font-mono uppercase tracking-[0.2em] text-cyber-primary">Dark Edition</div>
                        </div>
                    </div>
                </div>
            </div>

            <nav className="mt-6 flex-1 px-4 space-y-1">
                {navItems.map((item) => {
                    const Icon = item.icon;
                    const isActive = url.startsWith(item.match);
                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`group flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition ${
                                isActive
                                    ? 'bg-cyber-primary/10 text-cyber-primary border border-cyber-primary/30'
                                    : 'text-cyber-muted hover:text-cyber-text hover:bg-cyber-hover'
                            }`}
                        >
                            <Icon className={`h-5 w-5 ${isActive ? 'text-cyber-primary' : 'text-cyber-soft group-hover:text-cyber-primary'}`} />
                            <span className="font-medium">{item.label}</span>
                            {isActive && <span className="ml-auto h-2 w-2 rounded-full bg-cyber-primary animate-pulse-slow" />}
                        </Link>
                    );
                })}
            </nav>

            <div className="p-4">
                <div className="cyber-card p-3 text-xs text-cyber-muted">
                    <div className="font-mono">v2.4.0-dark</div>
                    <div className="mt-1 text-cyber-primary">● Sistema operacional</div>
                </div>
            </div>
        </aside>
    );
}
