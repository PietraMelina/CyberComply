import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function ResponsesIndex({ auth, responses, sites, selectedSite }) {
    const onSiteChange = (siteId) => {
        router.get(route('responses.index'), { site_id: siteId || '' }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Respostas</h2>}
        >
            <Head title="Respostas" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    <div className="cyber-card p-4">
                        <label className="block text-sm font-medium mb-2">Filtrar por local auditavel</label>
                        <select
                            value={selectedSite || ''}
                            onChange={(e) => onSiteChange(e.target.value)}
                            className="cyber-input w-full md:w-96"
                        >
                            <option value="">Todos os sites (inclui global)</option>
                            {sites.map((site) => (
                                <option key={site.id} value={site.id}>
                                    {site.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="cyber-card p-4 overflow-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="text-left border-b border-cyber-border text-cyber-muted">
                                    <th className="p-2">Site</th>
                                    <th className="p-2">Modulo</th>
                                    <th className="p-2">Pergunta</th>
                                    <th className="p-2">Status</th>
                                    <th className="p-2">Versao</th>
                                    <th className="p-2">Respondido em</th>
                                </tr>
                            </thead>
                            <tbody>
                                {responses.data.map((item) => (
                                    <tr key={item.id} className="border-b border-cyber-border/60">
                                        <td className="p-2">{item.site_name}</td>
                                        <td className="p-2">{item.module_code || '-'}</td>
                                        <td className="p-2 max-w-[400px] truncate">{item.question_text || '-'}</td>
                                        <td className="p-2">{item.status}</td>
                                        <td className="p-2">{item.version}</td>
                                        <td className="p-2">{item.answered_at ? new Date(item.answered_at).toLocaleString() : '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {responses.data.length === 0 && <p className="text-sm text-cyber-muted p-2">Sem respostas para o filtro selecionado.</p>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
