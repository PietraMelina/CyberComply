import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        company_name: '',
        company_type: 'PRIV',
        company_vat: '',
        email: '',
        password: '',
        password_confirmation: '',
        billing_address: {
            line1: '',
            line2: '',
            city: '',
            postal_code: '',
            country: 'PT',
        },
        accepted_terms: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('register.submit'));
    };

    return (
        <GuestLayout>
            <Head title="Registar-se" />

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="company_name" value="Nome da empresa" />
                    <TextInput
                        id="company_name"
                        value={data.company_name}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('company_name', e.target.value)}
                        required
                    />
                    <InputError message={errors.company_name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="company_type" value="Tipo de cliente" />
                    <select
                        id="company_type"
                        className="cyber-input mt-1 block w-full"
                        value={data.company_type}
                        onChange={(e) => setData('company_type', e.target.value)}
                    >
                        <option value="PRIV">Privado (PRIV)</option>
                        <option value="PUBL">Público (PUBL)</option>
                    </select>
                    <InputError message={errors.company_type} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="company_vat" value="NIF da empresa (opcional)" />
                    <TextInput
                        id="company_vat"
                        value={data.company_vat}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('company_vat', e.target.value)}
                    />
                    <InputError message={errors.company_vat} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email corporativo" />
                    <TextInput
                        id="email"
                        type="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation" value="Confirmar password" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />
                </div>

                <div className="rounded border border-cyber-border bg-cyber-secondary p-3">
                    <div className="text-sm font-semibold text-cyber-text">Morada de faturação</div>

                    <div className="mt-3">
                        <InputLabel htmlFor="line1" value="Morada (linha 1)" />
                        <TextInput
                            id="line1"
                            value={data.billing_address.line1}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('billing_address', { ...data.billing_address, line1: e.target.value })
                            }
                            required
                        />
                        <InputError message={errors['billing_address.line1']} className="mt-2" />
                    </div>

                    <div className="mt-3">
                        <InputLabel htmlFor="line2" value="Morada (linha 2, opcional)" />
                        <TextInput
                            id="line2"
                            value={data.billing_address.line2}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('billing_address', { ...data.billing_address, line2: e.target.value })
                            }
                        />
                    </div>

                    <div className="mt-3">
                        <InputLabel htmlFor="city" value="Cidade" />
                        <TextInput
                            id="city"
                            value={data.billing_address.city}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('billing_address', { ...data.billing_address, city: e.target.value })
                            }
                            required
                        />
                        <InputError message={errors['billing_address.city']} className="mt-2" />
                    </div>

                    <div className="mt-3">
                        <InputLabel htmlFor="postal_code" value="Código postal" />
                        <TextInput
                            id="postal_code"
                            value={data.billing_address.postal_code}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('billing_address', { ...data.billing_address, postal_code: e.target.value })
                            }
                            required
                        />
                        <InputError message={errors['billing_address.postal_code']} className="mt-2" />
                    </div>

                    <div className="mt-3">
                        <InputLabel htmlFor="country" value="País (2 letras)" />
                        <TextInput
                            id="country"
                            value={data.billing_address.country}
                            className="mt-1 block w-full"
                            onChange={(e) =>
                                setData('billing_address', { ...data.billing_address, country: e.target.value.toUpperCase() })
                            }
                            required
                        />
                        <InputError message={errors['billing_address.country']} className="mt-2" />
                    </div>
                </div>

                <label className="flex items-center gap-2 text-sm text-cyber-muted">
                    <input
                        type="checkbox"
                        checked={data.accepted_terms}
                        onChange={(e) => setData('accepted_terms', e.target.checked)}
                    />
                    Aceito os termos de uso e política de privacidade.
                </label>
                <InputError message={errors.accepted_terms} className="mt-2" />

                <div className="flex items-center justify-end gap-3 pt-2">
                    <Link href={route('login')} className="text-sm text-cyber-muted underline hover:text-cyber-text">
                        Já tenho conta
                    </Link>
                    <PrimaryButton disabled={processing}>{processing ? 'A registar...' : 'Registar-se'}</PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
