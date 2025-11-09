import { forwardRef, type ButtonHTMLAttributes } from "react";
import { Slot } from "@radix-ui/react-slot";
import { cn } from "@/lib/utils";

const variants = {
  primary:
    "relative inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-tr from-primary via-primary-700 to-accent px-6 py-2.5 font-semibold uppercase tracking-[0.22em] text-sm text-white shadow-glow transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_28px_70px_-20px_rgba(99,102,241,0.55)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary",
  outline:
    "inline-flex items-center justify-center gap-2 rounded-2xl border border-white/15 bg-white/5 px-6 py-2.5 font-semibold uppercase tracking-[0.22em] text-sm text-white shadow-soft transition-all duration-300 hover:border-white/40 hover:bg-white/10 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary",
  ghost:
    "inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 text-sm font-medium text-fg-muted transition hover:text-white"
} as const;

export type ButtonVariant = keyof typeof variants;

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  asChild?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ variant = "primary", className, asChild = false, type = "button", ...props }, ref) => {
    const Comp = asChild ? Slot : "button";
    const componentProps = asChild ? props : { type, ...props };

    return (
      <Comp ref={ref} className={cn(variants[variant], className)} {...componentProps} />
    );
  }
);

Button.displayName = "Button";
