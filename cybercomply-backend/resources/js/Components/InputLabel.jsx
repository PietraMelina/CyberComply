export default function InputLabel({ value, className = '', children, ...props }) {
    return (
        <label {...props} className={`block font-mono text-xs uppercase tracking-wider text-cyber-muted ` + className}>
            {value ? value : children}
        </label>
    );
}
