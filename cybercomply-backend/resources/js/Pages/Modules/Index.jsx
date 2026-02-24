import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function ModulesIndex({ auth, modules }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Módulos</h2>}
        >
            <Head title="Módulos" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="cyber-card p-4 overflow-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="text-left border-b border-cyber-border text-cyber-muted">
                                    <th className="p-2">Código</th>
                                    <th className="p-2">Nome</th>
                                    <th className="p-2">Versão</th>
                                    <th className="p-2">Estado</th>
                                    <th className="p-2">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                {modules.data.map((module) => (
                                    <tr key={module.id} className="border-b border-cyber-border/60">
                                        <td className="p-2">{module.code}</td>
                                        <td className="p-2">{module.name}</td>
                                        <td className="p-2">{module.version}</td>
                                        <td className="p-2">{module.is_active ? 'Ativo' : 'Inativo'}</td>
                                        <td className="p-2">
                                            <Link href={route('modules.show', { id: module.id })} className="text-cyber-primary hover:underline">
                                                Abrir
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {modules.data.length === 0 && <p className="text-sm text-cyber-muted p-2">Nenhum módulo disponível.</p>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
