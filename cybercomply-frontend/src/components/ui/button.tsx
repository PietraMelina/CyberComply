import { ButtonHTMLAttributes, PropsWithChildren } from "react";

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "default" | "outline";
};

export function Button({
  children,
  className = "",
  variant = "default",
  ...props
}: PropsWithChildren<ButtonProps>) {
  const base = "inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition disabled:opacity-50";
  const variants = {
    default: "bg-slate-900 text-white hover:bg-slate-700",
    outline: "border border-slate-300 bg-white text-slate-800 hover:bg-slate-50",
  };

  return (
    <button className={`${base} ${variants[variant]} ${className}`} {...props}>
      {children}
    </button>
  );
}
