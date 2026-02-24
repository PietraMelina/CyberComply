import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';

function statusBadge(status) {
    if (status === 'OPEN') return 'bg-cyber-danger/15 text-cyber-danger';
    if (status === 'IN_PROGRESS') return 'bg-cyber-warning/15 text-cyber-warning';
    return 'bg-cyber-success/15 text-cyber-success';
}

function severityBadge(severity) {
    if (severity === 'CRITICAL') return 'bg-cyber-danger/20 text-cyber-danger';
    if (severity === 'HIGH') return 'bg-cyber-warning/20 text-cyber-warning';
    if (severity === 'MEDIUM') return 'bg-cyber-info/20 text-cyber-info';
    return 'bg-cyber-secondary text-cyber-muted';
}

function StatusForm({ bugId, currentStatus, canUpdateStatus }) {
    const form = useForm({ status: currentStatus });

    const submit = (e) => {
        e.preventDefault();
        form.patch(route('bugs.status.update', { id: bugId }), {
            preserveScroll: true,
        });
    };

    if (!canUpdateStatus) {
        return <span className={`cyber-badge ${statusBadge(currentStatus)}`}>{currentStatus}</span>;
    }

    return (
        <form onSubmit={submit} className="flex items-center gap-2">
            <select className="cyber-input py-1 text-xs" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                <option value="OPEN">OPEN</option>
                <option value="IN_PROGRESS">IN_PROGRESS</option>
                <option value="RESOLVED">RESOLVED</option>
            </select>
            <button type="submit" className="rounded bg-black px-2 py-1 text-xs text-white" disabled={form.processing}>
                {form.processing ? '...' : 'Salvar'}
            </button>
        </form>
    );
}

export default function BugReportsIndex({ auth, bugs, filters, canUpdateStatus }) {
    const applyFilters = (event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);

        router.get(
            route('bugs.index'),
            {
                q: form.get('q') || '',
                status: form.get('status') || '',
                severity: form.get('severity') || '',
                per_page: Number(form.get('per_page') || 20),
            },
            { preserveState: true, replace: true }
        );
    };

    const changePage = (page) => {
        router.get(
            route('bugs.index'),
            {
                ...filters,
                page,
            },
            { preserveState: true, replace: true }
        );
    };

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-display text-xl">Gestão de Bugs</h2>}>
            <Head title="Bugs" />

            <div className="space-y-4">
                <section className="cyber-card p-4">
                    <form onSubmit={applyFilters} className="grid grid-cols-1 gap-2 md:grid-cols-5">
                        <input className="cyber-input" name="q" placeholder="buscar..." defaultValue={filters.q} />
                        <select className="cyber-input" name="status" defaultValue={filters.status}>
                            <option value="">Status: todos</option>
                            <option value="OPEN">OPEN</option>
                            <option value="IN_PROGRESS">IN_PROGRESS</option>
                            <option value="RESOLVED">RESOLVED</option>
                        </select>
                        <select className="cyber-input" name="severity" defaultValue={filters.severity}>
                            <option value="">Severidade: todas</option>
                            <option value="LOW">LOW</option>
                            <option value="MEDIUM">MEDIUM</option>
                            <option value="HIGH">HIGH</option>
                            <option value="CRITICAL">CRITICAL</option>
                        </select>
                        <select className="cyber-input" name="per_page" defaultValue={String(filters.per_page || 20)}>
                            <option value="20">20/página</option>
                            <option value="50">50/página</option>
                            <option value="100">100/página</option>
                        </select>
                        <button type="submit" className="rounded bg-black px-4 py-2 text-sm font-semibold text-white">Aplicar</button>
                    </form>
                </section>

                <section className="cyber-card p-4 overflow-auto">
                    <table className="min-w-full text-sm">
                        <thead>
                            <tr className="border-b border-cyber-border text-left text-cyber-muted">
                                <th className="p-2">ID</th>
                                <th className="p-2">Título</th>
                                <th className="p-2">Severidade</th>
                                <th className="p-2">Status</th>
                                <th className="p-2">Utilizador</th>
                                <th className="p-2">Página</th>
                                <th className="p-2">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            {bugs.data.map((bug) => (
                                <tr key={bug.id} className="border-b border-cyber-border/50 align-top">
                                    <td className="p-2 font-mono text-xs">#{bug.id}</td>
                                    <td className="p-2">
                                        <div className="font-semibold">{bug.title}</div>
                                        <div className="text-xs text-cyber-muted max-w-md">{bug.description}</div>
                                    </td>
                                    <td className="p-2">
                                        <span className={`cyber-badge ${severityBadge(bug.severity)}`}>{bug.severity}</span>
                                    </td>
                                    <td className="p-2">
                                        <StatusForm bugId={bug.id} currentStatus={bug.status} canUpdateStatus={canUpdateStatus} />
                                    </td>
                                    <td className="p-2">
                                        <div>{bug.reporter_user_id}</div>
                                        <div className="text-xs text-cyber-muted">{bug.reporter_email}</div>
                                    </td>
                                    <td className="p-2 text-xs text-cyber-muted max-w-[220px] break-all">{bug.page_url || '-'}</td>
                                    <td className="p-2 text-xs">{new Date(bug.created_at).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {bugs.data.length === 0 && <p className="p-3 text-sm text-cyber-muted">Nenhum bug encontrado.</p>}

                    <div className="mt-3 flex items-center justify-between text-sm">
                        <span className="text-cyber-muted">{bugs.total} bugs | página {bugs.current_page}/{bugs.last_page}</span>
                        <div className="flex gap-2">
                            <button
                                type="button"
                                className="cyber-button-secondary disabled:opacity-40"
                                disabled={bugs.current_page <= 1}
                                onClick={() => changePage(bugs.current_page - 1)}
                            >
                                Anterior
                            </button>
                            <button
                                type="button"
                                className="cyber-button-secondary disabled:opacity-40"
                                disabled={bugs.current_page >= bugs.last_page}
                                onClick={() => changePage(bugs.current_page + 1)}
                            >
                                Próxima
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
