import { useEffect } from "react";
import { Outlet, ScrollRestoration } from "react-router-dom";
import { Navigation } from "@/components/layout/Navigation";
import { Footer } from "@/components/layout/Footer";
import { useAuthStore } from "@/lib/store/use-auth-store";

export function PageShell() {
  const hydrate = useAuthStore((store) => store.hydrate);
  const hasHydrated = useAuthStore((store) => store.hasHydrated);

  useEffect(() => {
    if (!hasHydrated) {
      void hydrate();
    }
  }, [hasHydrated, hydrate]);

  return (
    <div className="relative flex min-h-screen flex-col overflow-hidden bg-gradient-to-b from-bg via-bg-muted/40 to-bg">
      <div className="pointer-events-none absolute inset-0">
        <div
          className="floating-blob floating-blob--primary"
          style={{ top: "-14rem", right: "-10rem", width: "32rem", height: "32rem" }}
        />
        <div
          className="floating-blob floating-blob--accent"
          style={{ bottom: "-16rem", left: "-8rem", width: "30rem", height: "30rem", animationDelay: "4s" }}
        />
        <div
          className="floating-blob floating-blob--secondary"
          style={{ top: "45%", right: "10%", width: "24rem", height: "24rem", animationDelay: "8s" }}
        />
      </div>
      <div className="noise-overlay" />
      <Navigation />
      <main className="relative z-10 flex-1">
        <Outlet />
      </main>
      <Footer />
      <ScrollRestoration />
    </div>
  );
}
