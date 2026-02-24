import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

function QuestionCard({ module, question, selectedSiteId, statusOptions }) {
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState('');
    const [uploadSuccess, setUploadSuccess] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        question_id: question.id,
        site_id: selectedSiteId || '',
        status: question.current_response?.status || '',
        comment: '',
    });

    const submitResponse = (e) => {
        e.preventDefault();
        post(route('modules.responses.store', { id: module.id }), {
            preserveScroll: true,
            onSuccess: () => reset('comment'),
        });
    };

    const uploadEvidence = async (file) => {
        if (!question.current_response?.id) {
            setUploadError('Primeiro registre uma resposta para anexar evidência.');
            return;
        }

        setUploadError('');
        setUploadSuccess('');
        setUploading(true);

        try {
            const formData = new FormData();
            formData.append('response_id', String(question.current_response.id));
            formData.append('file', file);

            await window.axios.post('/api/evidences', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            setUploadSuccess('Evidência enviada com sucesso.');
            router.reload({ only: ['questions'] });
        } catch (err) {
            setUploadError(err?.response?.data?.message || 'Falha ao enviar evidência.');
        } finally {
            setUploading(false);
        }
    };

    const downloadEvidence = async (token, filename) => {
        try {
            const { data: blob } = await window.axios.get(`/api/evidences/${token}`, {
                responseType: 'blob',
            });
            const url = window.URL.createObjectURL(new Blob([blob]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch {
            setUploadError('Falha ao baixar evidência.');
        }
    };

    return (
        <div className="cyber-card p-4 space-y-4">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <div className="font-medium text-cyber-text">{question.order_index}. {question.question_text}</div>
                    <div className="text-xs text-cyber-muted">Peso: {question.weight}</div>
                </div>
                {question.current_response && (
                    <div className="text-xs text-cyber-muted text-right">
                        <div>Status atual: <strong>{question.current_response.status}</strong></div>
                        <div>Versão: {question.current_response.version}</div>
                    </div>
                )}
            </div>

            <form onSubmit={submitResponse} className="space-y-2">
                <div>
                    <label className="block text-sm font-medium mb-1">Nova resposta</label>
                    <select
                        className="cyber-input"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                        required
                    >
                        <option value="">Selecione...</option>
                        {statusOptions.map((status) => (
                            <option key={status} value={status}>{status}</option>
                        ))}
                    </select>
                    <InputError message={errors.status} className="mt-1" />
                </div>

                <div>
                    <label className="block text-sm font-medium mb-1">Comentário</label>
                    <textarea
                        className="cyber-input"
                        rows={3}
                        value={data.comment}
                        onChange={(e) => setData('comment', e.target.value)}
                        placeholder="Obrigatório para NAO_CONFORME"
                    />
                    <InputError message={errors.comment} className="mt-1" />
                </div>

                <PrimaryButton disabled={processing}>{processing ? 'A gravar...' : 'Guardar resposta'}</PrimaryButton>
            </form>

            <div className="rounded bg-cyber-secondary p-3 border border-cyber-border">
                <div className="text-sm font-medium mb-2">Evidências</div>
                <div className="flex items-center gap-2">
                    <input
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        onChange={(e) => {
                            const file = e.target.files?.[0];
                            if (file) {
                                uploadEvidence(file);
                                e.target.value = '';
                            }
                        }}
                    />
                    {uploading && <span className="text-xs text-cyber-muted">A enviar...</span>}
                </div>

                {uploadError && <div className="text-xs text-red-600 mt-2">{uploadError}</div>}
                {uploadSuccess && <div className="text-xs text-green-700 mt-2">{uploadSuccess}</div>}

                <div className="mt-3 space-y-1">
                    {(question.current_response?.evidences || []).map((evidence) => (
                        <div key={evidence.id} className="flex items-center justify-between text-sm border border-cyber-border rounded bg-cyber-card p-2">
                            <span className="truncate mr-3">{evidence.original_filename}</span>
                            <button
                                type="button"
                                className="text-cyber-primary hover:underline"
                                onClick={() => downloadEvidence(evidence.internal_token, evidence.original_filename)}
                            >
                                Download
                            </button>
                        </div>
                    ))}
                    {!question.current_response?.evidences?.length && (
                        <div className="text-xs text-cyber-muted">Sem evidências para esta resposta.</div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function ModuleShow({ auth, module, sites, selectedSiteId, questions, statusOptions }) {
    const goToSite = (siteId) => {
        router.get(route('modules.show', { id: module.id }), { site_id: siteId || '' }, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Módulo {module.code}</h2>}
        >
            <Head title={`Módulo ${module.code}`} />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    <div className="cyber-card p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-xl font-semibold">{module.name}</h1>
                                <p className="text-sm text-cyber-muted">{module.description || 'Sem descrição.'}</p>
                            </div>
                            <Link href={route('modules.index')} className="text-sm text-cyber-primary hover:underline">
                                Voltar aos módulos
                            </Link>
                        </div>
                    </div>

                    <div className="cyber-card p-4">
                        <label className="block text-sm font-medium mb-2">Contexto por site</label>
                        <select
                            className="cyber-input w-full md:w-96"
                            value={selectedSiteId || ''}
                            onChange={(e) => goToSite(e.target.value)}
                        >
                            <option value="">Global (sem site)</option>
                            {sites.map((site) => (
                                <option key={site.id} value={site.id}>
                                    {site.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="space-y-3">
                        {questions.map((question) => (
                            <QuestionCard
                                key={question.id}
                                module={module}
                                question={question}
                                selectedSiteId={selectedSiteId}
                                statusOptions={statusOptions}
                            />
                        ))}
                        {questions.length === 0 && <div className="cyber-card p-4 text-sm text-cyber-muted">Sem perguntas ativas neste módulo.</div>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
