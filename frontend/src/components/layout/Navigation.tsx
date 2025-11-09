import { useEffect, useMemo, useState } from "react";
import { Link, NavLink, useLocation } from "react-router-dom";
import { Menu, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useNavStore } from "@/lib/store/use-nav-store";
import { cn } from "@/lib/utils";
import { useAuthStore } from "@/lib/store/use-auth-store";

const navigation = [
  { label: "Home", path: "/" },
  { label: "About", path: "/about" },
  { label: "Services", path: "/services" },
  { label: "Contact", path: "/contact" }
];

export function Navigation() {
  const location = useLocation();
  const { isOpen, toggle, close } = useNavStore();
  const user = useAuthStore((store) => store.user);
  const authStatus = useAuthStore((store) => store.status);
  const signOut = useAuthStore((store) => store.signOut);
  const [signingOut, setSigningOut] = useState(false);

  // Hide navigation on dashboard pages
  if (location.pathname.startsWith("/dashboard")) {
    return null;
  }

  const firstName = useMemo(() => {
    if (!user) return "";
    return user.fullName.split(" ")[0] ?? user.fullName;
  }, [user]);

  useEffect(() => {
    const handler = () => close();
    window.addEventListener("resize", handler);
    return () => window.removeEventListener("resize", handler);
  }, [close]);

  const handleSignOut = async () => {
    setSigningOut(true);
    try {
      await signOut();
    } finally {
      setSigningOut(false);
      close();
    }
  };

  return (
    <header className="sticky top-0 z-40 border-b border-white/10 bg-[#090b16]/75 backdrop-blur-hero shadow-[0_18px_60px_-32px_rgba(15,23,42,0.9)]">
      <div className="mx-auto flex h-20 max-w-6xl items-center justify-between gap-6 px-6">
        <Link to="/" className="flex items-center gap-3">
          <img
            src="/logo.svg"
            alt="MyGym Logo"
            className="h-14 w-auto transition-transform duration-300 hover:scale-105"
          />
        </Link>

        <nav className="hidden items-center gap-8 md:flex">
          {navigation.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              className={({ isActive }) =>
                cn(
                  "relative text-[0.65rem] font-semibold uppercase tracking-[0.36em] text-fg-muted transition-colors duration-200 hover:text-white",
                  isActive &&
                    "text-white after:absolute after:-bottom-2.5 after:left-0 after:h-[2px] after:w-full after:rounded-full after:bg-gradient-to-r after:from-primary after:via-accent/70 after:to-primary"
                )
              }
            >
              {item.label}
            </NavLink>
          ))}
        </nav>

        <div className="hidden items-center gap-4 md:flex">
          {user ? (
            <>
              <div className="text-right">
                <p className="text-[0.55rem] uppercase tracking-[0.36em] text-fg-muted">
                  {authStatus === "loading" ? "Syncing" : "Member"}
                </p>
                <p className="text-sm font-semibold uppercase tracking-[0.28em] text-white">
                  {firstName}
                </p>
              </div>
              <Button
                variant="outline"
                onClick={handleSignOut}
                disabled={signingOut}
                className="tracking-[0.3em]"
              >
                {signingOut ? "Signing out..." : "Sign out"}
              </Button>
            </>
          ) : (
            <>
              <Link
                to="/login"
                className="group inline-flex items-center gap-2 rounded-full border border-accent/60 bg-white/5 px-6 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.32em] text-white transition-all duration-300 hover:-translate-y-0.5 hover:border-accent hover:bg-accent/10 hover:shadow-[0_20px_60px_-28px_rgba(99,102,241,0.45)]"
              >
                Member Login
              </Link>
              <Link
                to="/register"
                className="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-primary via-primary-700 to-accent px-7 py-2 text-[0.65rem] font-semibold uppercase tracking-[0.34em] text-white shadow-glow transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_26px_70px_-26px_rgba(255,45,85,0.6)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
              >
                Join Now
              </Link>
            </>
          )}
        </div>

        <button
          aria-label="Toggle menu"
          className="rounded-2xl border border-white/10 bg-white/5 p-3 text-white shadow-soft transition hover:border-white/30 active:scale-95 md:hidden"
          onClick={toggle}
        >
          {isOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </button>
      </div>

      {isOpen ? (
        <div className="border-t border-white/10 bg-black/80 px-6 py-6 md:hidden">
          <nav className="flex flex-col gap-4">
            {navigation.map((item) => (
              <NavLink
                key={item.path}
                to={item.path}
                onClick={close}
                className={({ isActive }) =>
                  cn(
                    "rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.36em] text-fg-muted",
                    isActive && "border-primary/60 bg-primary/10 text-white"
                  )
                }
              >
                {item.label}
              </NavLink>
            ))}
            {user ? (
              <Button
                variant="outline"
                onClick={handleSignOut}
                disabled={signingOut}
                className="tracking-[0.36em]"
              >
                {signingOut ? "Signing out..." : "Sign out"}
              </Button>
            ) : (
              <>
                <Link
                  to="/login"
                  onClick={close}
                  className="rounded-full border border-accent/50 bg-white/5 px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.36em] text-white transition-all duration-300 hover:-translate-y-0.5 hover:border-accent hover:bg-accent/10"
                >
                  Member Login
                </Link>
                <Link
                  to="/register"
                  onClick={close}
                  className="rounded-full bg-gradient-to-r from-primary via-primary-700 to-accent px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.36em] text-white shadow-glow transition-all duration-300 hover:-translate-y-0.5"
                >
                  Join Now
                </Link>
              </>
            )}
          </nav>
        </div>
      ) : null}
    </header>
  );
}
