export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-lg bg-cyber-primary px-4 py-2 text-xs font-semibold uppercase tracking-widest text-cyber-bg transition-all hover:shadow-cyber hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-cyber-primary ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
