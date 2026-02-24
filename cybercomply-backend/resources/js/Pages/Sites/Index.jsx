import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function SitesIndex({ auth, sites, canCreate }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-black leading-tight">Locais Auditaveis</h2>}
        >
            <Head title="Sites" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    <div className="bg-white p-4 shadow-sm sm:rounded-lg">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-black">Gestao dos locais auditaveis do cliente.</p>
                            {canCreate && (
                                <Link href={route('sites.create')} className="text-xs rounded bg-black px-3 py-2 text-white">
                                    Novo site
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="bg-white p-4 shadow-sm sm:rounded-lg overflow-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="text-left border-b text-black">
                                    <th className="p-2 font-semibold">Nome</th>
                                    <th className="p-2 font-semibold">Cidade</th>
                                    <th className="p-2 font-semibold">Estado</th>
                                    <th className="p-2 font-semibold">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sites.map((site) => (
                                    <tr key={site.id} className="border-b text-black">
                                        <td className="p-2">{site.name}</td>
                                        <td className="p-2">{site.address?.city || '-'}</td>
                                        <td className="p-2">{site.is_active ? 'Ativo' : 'Inativo'}</td>
                                        <td className="p-2">
                                            <Link
                                                href={route('responses.index', { site_id: site.id })}
                                                className="text-black underline"
                                            >
                                                Ver respostas
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {sites.length === 0 && <p className="text-sm text-black p-2">Sem sites cadastrados.</p>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
