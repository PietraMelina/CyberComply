import { Link } from '@inertiajs/react';
import { ComplianceIcon } from '@/Components/Icons/CyberIcons';

export default function Guest({ children }) {
    return (
        <div className="relative min-h-screen overflow-hidden bg-cyber-bg px-4 py-8 sm:px-6">
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-16 -left-10 h-64 w-64 rounded-full bg-cyber-primary/10 blur-3xl" />
                <div className="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-cyber-secondaryAccent/10 blur-3xl" />
            </div>

            <div className="relative mx-auto flex min-h-[85vh] w-full max-w-md items-center">
                <div className="w-full cyber-card p-7 sm:p-8">
                    <div className="mb-6 flex items-center justify-center">
                        <Link href="/" className="inline-flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-cyber-primary to-cyber-secondaryAccent text-cyber-bg shadow-cyber">
                                <ComplianceIcon className="h-7 w-7" />
                            </div>
                            <div>
                                <div className="font-display text-xl font-bold">CyberComply</div>
                                <div className="text-[10px] font-mono uppercase tracking-[0.2em] text-cyber-primary">Secure Portal</div>
                            </div>
                        </Link>
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}

