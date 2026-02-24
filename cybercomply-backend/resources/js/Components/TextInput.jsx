import { forwardRef, useEffect, useRef } from 'react';

export default forwardRef(function TextInput({ type = 'text', className = '', isFocused = false, ...props }, ref) {
    const input = ref ? ref : useRef();

    useEffect(() => {
        if (isFocused) {
            input.current.focus();
        }
    }, []);

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-lg border border-cyber-border bg-cyber-secondary text-cyber-text shadow-sm focus:border-cyber-primary focus:ring-cyber-primary ' +
                className
            }
            ref={input}
        />
    );
});
