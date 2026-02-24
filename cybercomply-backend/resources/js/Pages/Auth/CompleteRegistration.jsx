import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function CompleteRegistration({ nif, nome }) {
    const { data, setData, post, processing, errors } = useForm({
        company_name: '',
        company_type: 'PRIV',
        company_vat: '',
        email_corporate: '',
        billing_address: {
            line1: '',
            line2: '',
            city: '',
            postal_code: '',
            country: 'PT',
        },
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register.store'));
    };

    return (
        <GuestLayout>
            <Head title="Completar Registo" />

            <form onSubmit={submit} className="space-y-4">
                <div className="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                    Identidade CMD validada: {nome} ({nif})
                </div>

                <div>
                    <InputLabel htmlFor="company_name" value="Razao social" />
                    <TextInput
                        id="company_name"
                        value={data.company_name}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('company_name', e.target.value)}
                    />
                    <InputError message={errors.company_name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="company_type" value="Tipo" />
                    <select
                        id="company_type"
                        className="mt-1 block w-full rounded border-gray-300"
                        value={data.company_type}
                        onChange={(e) => setData('company_type', e.target.value)}
                    >
                        <option value="PRIV">Privado</option>
                        <option value="PUBL">Publico</option>
                    </select>
                </div>

                <div>
                    <InputLabel htmlFor="company_vat" value="NIF empresa" />
                    <TextInput
                        id="company_vat"
                        value={data.company_vat}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('company_vat', e.target.value)}
                    />
                    <InputError message={errors.company_vat} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="email_corporate" value="Email corporativo" />
                    <TextInput
                        id="email_corporate"
                        type="email"
                        value={data.email_corporate}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('email_corporate', e.target.value)}
                    />
                    <InputError message={errors.email_corporate} className="mt-2" />
                </div>

                <div className="grid grid-cols-1 gap-3">
                    <TextInput
                        placeholder="Morada"
                        value={data.billing_address.line1}
                        onChange={(e) =>
                            setData('billing_address', { ...data.billing_address, line1: e.target.value })
                        }
                    />
                    <TextInput
                        placeholder="Complemento"
                        value={data.billing_address.line2}
                        onChange={(e) =>
                            setData('billing_address', { ...data.billing_address, line2: e.target.value })
                        }
                    />
                    <TextInput
                        placeholder="Cidade"
                        value={data.billing_address.city}
                        onChange={(e) =>
                            setData('billing_address', { ...data.billing_address, city: e.target.value })
                        }
                    />
                    <TextInput
                        placeholder="Codigo postal"
                        value={data.billing_address.postal_code}
                        onChange={(e) =>
                            setData('billing_address', { ...data.billing_address, postal_code: e.target.value })
                        }
                    />
                </div>

                <PrimaryButton disabled={processing}>
                    {processing ? 'A criar...' : 'Finalizar registo'}
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}

