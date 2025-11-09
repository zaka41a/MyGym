import { useEffect } from "react";
import { Outlet, Navigate } from "react-router-dom";
import { DashboardSidebar } from "@/components/layout/DashboardSidebar";
import { useAuthStore } from "@/lib/store/use-auth-store";

export function DashboardLayout() {
  const user = useAuthStore((store) => store.user);
  const status = useAuthStore((store) => store.status);
  const hydrate = useAuthStore((store) => store.hydrate);
  const hasHydrated = useAuthStore((store) => store.hasHydrated);

  useEffect(() => {
    if (!hasHydrated) {
      void hydrate();
    }
  }, [hasHydrated, hydrate]);

  // Loading state
  if (status === "loading" || !hasHydrated) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-bg">
        <div className="text-center">
          <div className="mb-4 inline-block h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
          <p className="text-sm uppercase tracking-[0.3em] text-fg-muted">
            Loading dashboard...
          </p>
        </div>
      </div>
    );
  }

  // Not authenticated
  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="flex min-h-screen bg-gradient-to-br from-bg via-bg-muted/40 to-bg">
      {/* Floating background blobs */}
      <div className="pointer-events-none fixed inset-0">
        <div
          className="floating-blob floating-blob--primary"
          style={{ top: "-10rem", right: "-8rem", width: "28rem", height: "28rem" }}
        />
        <div
          className="floating-blob floating-blob--secondary"
          style={{ bottom: "-12rem", left: "-6rem", width: "26rem", height: "26rem", animationDelay: "5s" }}
        />
      </div>
      <div className="noise-overlay" />

      {/* Sidebar */}
      <DashboardSidebar />

      {/* Main Content */}
      <main className="relative z-10 ml-64 flex-1 overflow-y-auto">
        <div className="min-h-screen px-8 py-8">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
