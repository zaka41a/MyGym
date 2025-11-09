import { create } from "zustand";
import { logout, whoami } from "@/lib/api/auth";
import type { UserProfile } from "@/lib/types/user";

export type AuthStatus = "idle" | "loading" | "authenticated" | "unauthenticated";

interface AuthState {
  user: UserProfile | null;
  status: AuthStatus;
  hasHydrated: boolean;
  setUser: (user: UserProfile | null) => void;
  hydrate: () => Promise<void>;
  signOut: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  status: "idle",
  hasHydrated: false,
  setUser: (user) =>
    set({
      user,
      status: user ? "authenticated" : "unauthenticated"
    }),
  hydrate: async () => {
    if (get().hasHydrated) {
      return;
    }
    set({ status: "loading" });
    try {
      const user = await whoami();
      set({
        user,
        status: user ? "authenticated" : "unauthenticated",
        hasHydrated: true
      });
    } catch (error) {
      console.error("Failed to fetch current user", error);
      set({
        user: null,
        status: "unauthenticated",
        hasHydrated: true
      });
    }
  },
  signOut: async () => {
    try {
      await logout();
    } catch (error) {
      console.error("Unable to sign out", error);
    } finally {
      set({ user: null, status: "unauthenticated" });
    }
  }
}));
