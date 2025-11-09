import type { Config } from "tailwindcss";
import plugin from "tailwindcss-animate";

const config: Config = {
  darkMode: ["class"],
  content: ["./index.html", "./src/**/*.{ts,tsx}"] ,
  theme: {
    extend: {
      colors: {
        bg: "var(--color-bg)",
        "bg-muted": "var(--color-bg-muted)",
        fg: "var(--color-fg)",
        "fg-muted": "var(--color-fg-muted)",
        primary: "var(--color-primary)",
        "primary-700": "var(--color-primary-700)",
        accent: "var(--color-accent)",
        secondary: "var(--color-secondary)",
        success: "var(--color-success)",
        warning: "var(--color-warning)",
        error: "var(--color-error)"
      },
      fontFamily: {
        sans: ["Poppins", "system-ui", "sans-serif"],
        display: ["Poppins", "sans-serif"]
      },
      borderRadius: {
        xl: "1.25rem",
        "2xl": "1.75rem"
      },
      boxShadow: {
        glow: "0 24px 72px -18px rgba(255, 45, 85, 0.55)",
        "glow-accent": "0 24px 72px -18px rgba(99, 102, 241, 0.45)",
        soft: "0 18px 50px -20px rgba(2, 6, 23, 0.75)"
      },
      backdropBlur: {
        hero: "48px"
      }
    }
  },
  plugins: [plugin]
};

export default config;
