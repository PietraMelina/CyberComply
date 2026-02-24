import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

function json(value) {
    return JSON.stringify(value ?? {}, null, 2);
}

function sourceBadge(source) {
    if (source === 'db1') {
        return 'bg-cyber-info/15 text-cyber-info border-cyber-info/40';
    }
    if (source === 'db2') {
        return 'bg-cyber-success/15 text-cyber-success border-cyber-success/40';
    }
    return 'bg-cyber-secondary text-cyber-muted border-cyber-border';
}

export default function AuditLogsIndex({ auth, filters, logs, summary, selectedLog }) {
    const applyFilters = (event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);

        router.get(
            route('audit-logs.index'),
            {
                source: form.get('source') || 'all',
                user_id: form.get('user_id') || '',
                client_id: form.get('client_id') || '',
                action: form.get('action') || '',
                date_from: form.get('date_from') || '',
                date_to: form.get('date_to') || '',
                per_page: Number(form.get('per_page') || 20),
            },
            { preserveState: true, replace: true }
        );
    };

    const openDetail = (log) => {
        router.get(
            route('audit-logs.index'),
            {
                ...filters,
                page: logs.current_page,
                selected_id: log.id,
                selected_source: log.source,
            },
            { preserveState: true, replace: true }
        );
    };

    const goPage = (page) => {
        router.get(
            route('audit-logs.index'),
            {
                ...filters,
                page,
            },
            { preserveState: true, replace: true }
        );
    };

    const exportUrl = (format) =>
        route('audit-logs.export', {
            ...filters,
            format,
        });

    const topActions = Object.entries(summary.top_actions || {});

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-display font-bold text-xl text-cyber-text">Auditoria de Segurança</h2>}
        >
            <Head title="Audit Logs" />

            <div className="space-y-6">
                <section className="relative overflow-hidden rounded-xl border border-cyber-border bg-gradient-to-r from-cyber-secondary to-cyber-card p-5">
                    <div className="pointer-events-none absolute -right-8 -top-8 h-28 w-28 rounded-full bg-cyber-primary/10 blur-2xl" />
                    <div className="relative">
                        <h1 className="font-display text-2xl text-cyber-text">Audit Trail Console</h1>
                        <p className="mt-1 text-sm text-cyber-muted">
                            Rastreabilidade completa de autenticação, alterações e operações de dados.
                        </p>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <span className="cyber-badge bg-cyber-info/15 text-cyber-info">DB1 Auth/Gestão</span>
                            <span className="cyber-badge bg-cyber-success/15 text-cyber-success">DB2 Tenant Data</span>
                            <span className="cyber-badge bg-cyber-warning/15 text-cyber-warning">Eventos críticos monitorados</span>
                        </div>
                    </div>
                </section>

                <section className="grid grid-cols-1 gap-6 xl:grid-cols-12">
                    <aside className="xl:col-span-4 space-y-4">
                        <div className="cyber-card p-4">
                            <div className="text-[11px] font-mono text-cyber-muted mb-3">PAINEL DE CONTROLO</div>
                            <form onSubmit={applyFilters} className="space-y-3">
                                <select name="source" defaultValue={filters.source} className="cyber-input">
                                    <option value="all">Fonte: todas</option>
                                    <option value="db1">Fonte: db1</option>
                                    <option value="db2">Fonte: db2</option>
                                </select>
                                <input name="user_id" defaultValue={filters.user_id} placeholder="user_id" className="cyber-input" />
                                <input name="client_id" defaultValue={filters.client_id} placeholder="client_id" className="cyber-input" />
                                <input name="action" defaultValue={filters.action} placeholder="action" className="cyber-input" />
                                <div className="grid grid-cols-2 gap-2">
                                    <input type="date" name="date_from" defaultValue={filters.date_from} className="cyber-input" />
                                    <input type="date" name="date_to" defaultValue={filters.date_to} className="cyber-input" />
                                </div>
                                <select name="per_page" defaultValue={String(filters.per_page)} className="cyber-input">
                                    <option value="20">20 / página</option>
                                    <option value="50">50 / página</option>
                                    <option value="100">100 / página</option>
                                </select>
                                <button type="submit" className="cyber-button-primary w-full">Aplicar filtros</button>
                            </form>
                            <div className="mt-3 grid grid-cols-2 gap-2">
                                <a href={exportUrl('csv')} className="cyber-button-secondary text-center">CSV</a>
                                <a href={exportUrl('pdf')} className="cyber-button-secondary text-center">PDF</a>
                            </div>
                        </div>

                        <div className="cyber-card p-4 space-y-3">
                            <div className="text-[11px] font-mono text-cyber-muted">RESUMO ATIVO</div>
                            <div className="rounded-lg border border-cyber-border bg-cyber-secondary p-3">
                                <div className="text-xs text-cyber-muted">Eventos</div>
                                <div className="text-3xl font-display">{summary.total_events}</div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div className="rounded-lg border border-cyber-border bg-cyber-secondary p-3">
                                    <div className="text-xs text-cyber-muted">Users</div>
                                    <div className="text-xl font-display">{summary.unique_users}</div>
                                </div>
                                <div className="rounded-lg border border-cyber-danger/40 bg-cyber-danger/10 p-3">
                                    <div className="text-xs text-cyber-muted">Críticos</div>
                                    <div className="text-xl font-display text-cyber-danger">{summary.critical_actions}</div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-cyber-border bg-cyber-secondary p-3 text-xs text-cyber-muted">
                                Período: <span className="text-cyber-text">{summary.period}</span>
                            </div>
                        </div>

                        <div className="cyber-card p-4">
                            <div className="text-[11px] font-mono text-cyber-muted mb-2">TOP AÇÕES</div>
                            <div className="space-y-2">
                                {topActions.length === 0 && <div className="text-xs text-cyber-muted">Sem dados no período.</div>}
                                {topActions.map(([action, count]) => (
                                    <div key={action} className="flex items-center justify-between rounded-lg border border-cyber-border bg-cyber-secondary px-3 py-2">
                                        <span className="text-xs font-mono text-cyber-primary">{action}</span>
                                        <span className="text-xs text-cyber-text">{count}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </aside>

                    <div className="xl:col-span-8 space-y-4">
                        <section className="cyber-card p-4 overflow-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="border-b border-cyber-border text-left text-cyber-muted">
                                        <th className="p-2">Fonte</th>
                                        <th className="p-2">Action</th>
                                        <th className="p-2">User</th>
                                        <th className="p-2">Entity</th>
                                        <th className="p-2">Data/Hora</th>
                                        <th className="p-2">Abrir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.data.map((log) => (
                                        <tr key={`${log.source}:${log.id}`} className="border-b border-cyber-border/60">
                                            <td className="p-2">
                                                <span className={`inline-flex rounded-full border px-2 py-0.5 text-xs ${sourceBadge(log.source)}`}>
                                                    {log.source}
                                                </span>
                                            </td>
                                            <td className="p-2">
                                                <div className="font-mono text-xs text-cyber-primary">{log.action}</div>
                                                <div className="text-[11px] text-cyber-muted">#{log.id}</div>
                                            </td>
                                            <td className="p-2">
                                                <div>{log.user_id}</div>
                                                <div className="text-[11px] text-cyber-muted">{log.client_id || '-'}</div>
                                            </td>
                                            <td className="p-2">{log.entity_type}</td>
                                            <td className="p-2">{new Date(log.created_at).toLocaleString()}</td>
                                            <td className="p-2">
                                                <button
                                                    onClick={() => openDetail(log)}
                                                    className="rounded-lg border border-cyber-border px-2 py-1 text-cyber-primary hover:bg-cyber-primary/10"
                                                    type="button"
                                                >
                                                    Detalhar
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            <div className="mt-4 flex items-center justify-between text-sm">
                                <span className="text-cyber-muted">
                                    {logs.total} eventos | página {logs.current_page}/{logs.last_page}
                                </span>
                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        className="cyber-button-secondary disabled:opacity-40"
                                        disabled={logs.current_page <= 1}
                                        onClick={() => goPage(logs.current_page - 1)}
                                    >
                                        Anterior
                                    </button>
                                    <button
                                        type="button"
                                        className="cyber-button-secondary disabled:opacity-40"
                                        disabled={logs.current_page >= logs.last_page}
                                        onClick={() => goPage(logs.current_page + 1)}
                                    >
                                        Próxima
                                    </button>
                                </div>
                            </div>
                        </section>

                        {selectedLog && (
                            <section className="cyber-card p-4 space-y-4">
                                <h3 className="font-display text-lg text-cyber-text">
                                    Evento #{selectedLog.id} <span className="text-cyber-muted">({selectedLog.source})</span>
                                </h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <div className="mb-1 text-sm font-semibold text-cyber-muted">Diff</div>
                                        <pre className="max-h-72 overflow-auto rounded-lg border border-cyber-border bg-cyber-secondary p-3 text-xs text-cyber-text">
                                            {json(selectedLog.diff)}
                                        </pre>
                                    </div>
                                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        <div>
                                            <div className="mb-1 text-sm font-semibold text-cyber-muted">Before</div>
                                            <pre className="max-h-72 overflow-auto rounded-lg border border-cyber-border bg-cyber-secondary p-3 text-xs text-cyber-text">
                                                {json(selectedLog.before_state)}
                                            </pre>
                                        </div>
                                        <div>
                                            <div className="mb-1 text-sm font-semibold text-cyber-muted">After</div>
                                            <pre className="max-h-72 overflow-auto rounded-lg border border-cyber-border bg-cyber-secondary p-3 text-xs text-cyber-text">
                                                {json(selectedLog.after_state)}
                                            </pre>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        )}
                    </div>
                </section>

            </div>
        </AuthenticatedLayout>
    );
}
