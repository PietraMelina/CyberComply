import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function BugReportWidget() {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        severity: 'MEDIUM',
        page_url: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('bug-reports.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const openModal = () => {
        setData('page_url', window.location.href);
        setOpen(true);
    };

    return (
        <>
            <button
                type="button"
                onClick={openModal}
                className="fixed bottom-5 right-5 z-40 rounded-full bg-black px-4 py-3 text-sm font-semibold text-white shadow-lg hover:opacity-90"
            >
                Reportar bug
            </button>

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <div className="w-full max-w-xl rounded-xl border border-cyber-border bg-cyber-card p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="font-display text-lg">Reportar bug</h3>
                            <button type="button" className="text-cyber-muted hover:text-cyber-text" onClick={() => setOpen(false)}>
                                Fechar
                            </button>
                        </div>

                        <form onSubmit={submit} className="space-y-3">
                            <div>
                                <label className="mb-1 block text-xs text-cyber-muted">Título</label>
                                <input
                                    className="cyber-input w-full"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Ex: botão salvar não responde"
                                    required
                                />
                                {errors.title && <div className="mt-1 text-xs text-cyber-danger">{errors.title}</div>}
                            </div>

                            <div>
                                <label className="mb-1 block text-xs text-cyber-muted">Gravidade</label>
                                <select className="cyber-input w-full" value={data.severity} onChange={(e) => setData('severity', e.target.value)}>
                                    <option value="LOW">Baixa</option>
                                    <option value="MEDIUM">Média</option>
                                    <option value="HIGH">Alta</option>
                                    <option value="CRITICAL">Crítica</option>
                                </select>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs text-cyber-muted">Descrição</label>
                                <textarea
                                    className="cyber-input w-full min-h-28"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Explique o que aconteceu e como reproduzir"
                                    required
                                />
                                {errors.description && <div className="mt-1 text-xs text-cyber-danger">{errors.description}</div>}
                            </div>

                            <div>
                                <label className="mb-1 block text-xs text-cyber-muted">Página atual</label>
                                <input className="cyber-input w-full" value={data.page_url} onChange={(e) => setData('page_url', e.target.value)} />
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    className="rounded-lg border border-cyber-border px-3 py-2 text-sm"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancelar
                                </button>
                                <button type="submit" className="rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white" disabled={processing}>
                                    {processing ? 'Enviando...' : 'Enviar bug'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}
