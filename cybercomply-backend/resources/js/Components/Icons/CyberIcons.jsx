export function ComplianceIcon({ className = '' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.5" />
            <path d="M8.5 12.2l2.2 2.2 4.8-5.2" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M12 3v2M12 19v2M3 12h2M19 12h2" stroke="currentColor" strokeWidth="1.2" opacity="0.55" />
        </svg>
    );
}

export function ShieldCheckIcon({ className = '' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden="true">
            <path d="M12 22s7-3.8 7-9.3V6l-7-3-7 3v6.7C5 18.2 12 22 12 22z" stroke="currentColor" strokeWidth="1.6" />
            <path d="M9.1 12.1l2 2L15 10.4" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export function LayersIcon({ className = '' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden="true">
            <path d="M12 3l9 5-9 5-9-5 9-5z" stroke="currentColor" strokeWidth="1.5" />
            <path d="M3 12l9 5 9-5M3 16l9 5 9-5" stroke="currentColor" strokeWidth="1.5" />
        </svg>
    );
}

export function FileLockIcon({ className = '' }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" className={className} aria-hidden="true">
            <path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z" stroke="currentColor" strokeWidth="1.5" />
            <path d="M14 3v5h5" stroke="currentColor" strokeWidth="1.5" />
            <rect x="9" y="13" width="6" height="5" rx="1.2" stroke="currentColor" strokeWidth="1.5" />
            <path d="M10.5 13v-1a1.5 1.5 0 013 0v1" stroke="currentColor" strokeWidth="1.5" />
        </svg>
    );
}

