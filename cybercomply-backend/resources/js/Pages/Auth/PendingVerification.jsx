import GuestLayout from '@/Layouts/GuestLayout';
import { Head, router } from '@inertiajs/react';

export default function PendingVerification({ email }) {
    const resend = () => {
        router.post(route('cmd.resend'));
    };

    return (
        <GuestLayout>
            <Head title="Validacao pendente" />

            <div className="space-y-4">
                <h1 className="text-xl font-semibold text-gray-900">Validacao pendente</h1>
                <p className="text-sm text-gray-600">
                    Enviamos um link de verificacao para <strong>{email}</strong>.
                </p>
                <p className="text-sm text-gray-600">O link expira em 10 minutos.</p>
                <button type="button" className="rounded border px-4 py-2 text-sm hover:bg-gray-50" onClick={resend}>
                    Reenviar token
                </button>
            </div>
        </GuestLayout>
    );
}
