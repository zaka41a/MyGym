import { cn } from "@/lib/utils";
import type { HTMLAttributes } from "react";

export function Card({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "glass-card rounded-3xl border border-white/10 p-6 shadow-soft transition duration-200 hover:border-white/20",
        className
      )}
      {...props}
    />
  );
}
