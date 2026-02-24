import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function SiteCreate({ auth, clients, isInternal }) {
    const { data, setData, post, processing, errors } = useForm({
        client_id: '',
        name: '',
        address_line1: '',
        address_line2: '',
        city: '',
        postal_code: '',
        country: 'PT',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('sites.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Novo Site</h2>}
        >
            <Head title="Novo Site" />

            <div className="py-8">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                        {isInternal && (
                            <div>
                                <InputLabel htmlFor="client_id" value="Cliente" />
                                <select
                                    id="client_id"
                                    className="mt-1 block w-full rounded border-gray-300"
                                    value={data.client_id}
                                    onChange={(e) => setData('client_id', e.target.value)}
                                >
                                    <option value="">Selecionar cliente</option>
                                    {clients.map((client) => (
                                        <option key={client.id} value={client.id}>
                                            {client.name} ({client.id})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.client_id} className="mt-1" />
                            </div>
                        )}

                        <div>
                            <InputLabel htmlFor="name" value="Nome do site" />
                            <TextInput id="name" className="mt-1 block w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            <InputError message={errors.name} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="address_line1" value="Morada" />
                            <TextInput
                                id="address_line1"
                                className="mt-1 block w-full"
                                value={data.address_line1}
                                onChange={(e) => setData('address_line1', e.target.value)}
                            />
                            <InputError message={errors.address_line1} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="address_line2" value="Complemento" />
                            <TextInput
                                id="address_line2"
                                className="mt-1 block w-full"
                                value={data.address_line2}
                                onChange={(e) => setData('address_line2', e.target.value)}
                            />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="city" value="Cidade" />
                                <TextInput id="city" className="mt-1 block w-full" value={data.city} onChange={(e) => setData('city', e.target.value)} />
                                <InputError message={errors.city} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="postal_code" value="Codigo postal" />
                                <TextInput
                                    id="postal_code"
                                    className="mt-1 block w-full"
                                    value={data.postal_code}
                                    onChange={(e) => setData('postal_code', e.target.value)}
                                />
                                <InputError message={errors.postal_code} className="mt-1" />
                            </div>
                        </div>

                        <PrimaryButton disabled={processing}>{processing ? 'A guardar...' : 'Criar site'}</PrimaryButton>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

