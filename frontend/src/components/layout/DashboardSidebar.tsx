import { Link, useLocation } from "react-router-dom";
import { useAuthStore } from "@/lib/store/use-auth-store";
import {
  LayoutDashboard,
  Users,
  Dumbbell,
  CreditCard,
  UserCircle,
  LogOut,
  BarChart3,
  Calendar,
  Settings
} from "lucide-react";
import { Button } from "@/components/ui/button";

const adminLinks = [
  { href: "/dashboard", icon: LayoutDashboard, label: "Overview" },
  { href: "/dashboard/users", icon: Users, label: "Users" },
  { href: "/dashboard/courses", icon: Dumbbell, label: "Courses" },
  { href: "/dashboard/subscriptions", icon: CreditCard, label: "Subscriptions" },
  { href: "/dashboard/analytics", icon: BarChart3, label: "Analytics" }
];

const coachLinks = [
  { href: "/dashboard", icon: LayoutDashboard, label: "Overview" },
  { href: "/dashboard/sessions", icon: Calendar, label: "My Sessions" },
  { href: "/dashboard/members", icon: Users, label: "My Members" },
  { href: "/dashboard/courses", icon: Dumbbell, label: "Courses" },
  { href: "/dashboard/profile", icon: UserCircle, label: "Profile" }
];

const memberLinks = [
  { href: "/dashboard", icon: LayoutDashboard, label: "Overview" },
  { href: "/dashboard/courses", icon: Dumbbell, label: "Available Courses" },
  { href: "/dashboard/subscribe", icon: CreditCard, label: "Subscription" },
  { href: "/dashboard/profile", icon: UserCircle, label: "My Profile" }
];

export function DashboardSidebar() {
  const user = useAuthStore((store) => store.user);
  const signOut = useAuthStore((store) => store.signOut);
  const location = useLocation();

  if (!user) return null;

  const links =
    user.role === "ADMIN"
      ? adminLinks
      : user.role === "COACH"
        ? coachLinks
        : memberLinks;

  const handleLogout = async () => {
    await signOut();
    window.location.href = "/";
  };

  return (
    <aside className="fixed left-0 top-0 z-40 h-screen w-64 border-r border-white/10 bg-gradient-to-b from-black/95 via-bg/98 to-black/95 backdrop-blur-xl">
      {/* Header */}
      <div className="border-b border-white/10 px-6 py-6">
        <Link to="/" className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-accent shadow-lg shadow-primary/30">
            <Dumbbell className="h-6 w-6 text-white" />
          </div>
          <div>
            <h1 className="text-lg font-bold uppercase tracking-[0.2em] text-white">
              MyGym
            </h1>
            <p className="text-[0.65rem] uppercase tracking-[0.3em] text-primary">
              {user.role}
            </p>
          </div>
        </Link>
      </div>

      {/* User Info */}
      <div className="border-b border-white/10 px-6 py-4">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-primary/30 to-accent/30 text-sm font-semibold uppercase text-white">
            {user.fullName.charAt(0)}
          </div>
          <div className="flex-1 overflow-hidden">
            <p className="truncate text-sm font-semibold text-white">
              {user.fullName}
            </p>
            <p className="truncate text-xs text-fg-muted">{user.email}</p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 space-y-1 px-3 py-4">
        {links.map((link) => {
          const isActive = location.pathname === link.href;
          const Icon = link.icon;

          return (
            <Link
              key={link.href}
              to={link.href}
              className={`flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition-all ${
                isActive
                  ? "bg-gradient-to-r from-primary/20 to-accent/20 text-white shadow-lg shadow-primary/10"
                  : "text-fg-muted hover:bg-white/5 hover:text-white"
              }`}
            >
              <Icon className="h-5 w-5" />
              <span className="uppercase tracking-[0.15em]">{link.label}</span>
            </Link>
          );
        })}
      </nav>

      {/* Footer */}
      <div className="border-t border-white/10 px-3 py-4">
        <Button
          onClick={handleLogout}
          variant="outline"
          className="w-full justify-start gap-3 border-white/10 bg-white/5 text-fg-muted hover:bg-primary/20 hover:text-white"
        >
          <LogOut className="h-5 w-5" />
          <span className="uppercase tracking-[0.15em]">Logout</span>
        </Button>
      </div>
    </aside>
  );
}
