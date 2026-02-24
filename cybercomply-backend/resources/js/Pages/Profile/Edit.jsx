import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Edit({ auth, status }) {
    const [avatarFile, setAvatarFile] = useState(null);

    const profileForm = useForm({
        display_name: auth.user?.display_name || auth.user?.name || '',
        email: auth.user?.email || '',
    });

    const avatarForm = useForm({
        avatar: null,
    });

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.patch(route('profile.update'));
    };

    const submitAvatar = (e) => {
        e.preventDefault();
        if (!avatarFile) return;

        avatarForm.setData('avatar', avatarFile);
        avatarForm.post(route('profile.avatar.update'), {
            forceFormData: true,
            onSuccess: () => setAvatarFile(null),
        });
    };

    const initials = (auth.user?.display_name || auth.user?.name || auth.user?.email || 'U')
        .trim()
        .charAt(0)
        .toUpperCase();

    return (
        <AuthenticatedLayout user={auth.user} header={<h2 className="font-display text-xl">Meu Perfil</h2>}>
            <Head title="Meu Perfil" />

            <div className="space-y-6">
                {status && <div className="rounded border border-cyber-success/40 bg-cyber-success/10 p-3 text-sm text-cyber-success">{status}</div>}

                <section className="cyber-card p-6">
                    <h3 className="font-display text-lg mb-4">Avatar</h3>
                    <div className="flex flex-col gap-4 md:flex-row md:items-center">
                        {auth.user?.avatar_url ? (
                            <img src={auth.user.avatar_url} alt="avatar" className="h-24 w-24 rounded-full object-cover border border-cyber-border" />
                        ) : (
                            <div className="h-24 w-24 rounded-full bg-cyber-primary text-cyber-bg flex items-center justify-center text-4xl font-bold">
                                {initials}
                            </div>
                        )}

                        <form onSubmit={submitAvatar} className="space-y-2">
                            <input
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                className="cyber-input"
                                onChange={(e) => setAvatarFile(e.target.files?.[0] || null)}
                            />
                            <PrimaryButton className="bg-black text-white hover:bg-black" disabled={avatarForm.processing || !avatarFile}>
                                {avatarForm.processing ? 'A carregar...' : 'Guardar avatar'}
                            </PrimaryButton>
                            <InputError message={avatarForm.errors.avatar} className="mt-1" />
                        </form>
                    </div>
                </section>

                <section className="cyber-card p-6">
                    <h3 className="font-display text-lg mb-4">Dados da conta</h3>
                    <form onSubmit={submitProfile} className="space-y-4 max-w-xl">
                        <div>
                            <InputLabel htmlFor="display_name" value="Nome exibido" />
                            <TextInput
                                id="display_name"
                                className="mt-1 block w-full"
                                value={profileForm.data.display_name}
                                onChange={(e) => profileForm.setData('display_name', e.target.value)}
                                required
                            />
                            <InputError message={profileForm.errors.display_name} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={profileForm.data.email}
                                onChange={(e) => profileForm.setData('email', e.target.value)}
                                required
                            />
                            <InputError message={profileForm.errors.email} className="mt-1" />
                        </div>

                        <div className="rounded border border-cyber-border bg-cyber-secondary p-3 text-sm text-cyber-muted">
                            Perfil: <span className="text-cyber-text font-semibold">{auth.user?.role}</span>
                            <br />
                            User ID: <span className="font-mono">{auth.user?.id}</span>
                        </div>

                        <PrimaryButton className="bg-black text-white hover:bg-black" disabled={profileForm.processing}>
                            {profileForm.processing ? 'A guardar...' : 'Guardar alterações'}
                        </PrimaryButton>
                    </form>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
