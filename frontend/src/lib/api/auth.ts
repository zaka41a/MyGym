import { apiFetch, ApiError } from "@/lib/api/client";
import type { UserProfile, UserRole } from "@/lib/types/user";

export type LoginPayload = {
  identifier: string;
  password: string;
};

export type RegisterPayload = {
  fullName: string;
  email: string;
  password: string;
  goal?: string;
};

type ApiUser = {
  id: number;
  fullName: string;
  email: string;
  username: string;
  role: string;
  membership?: string | null;
  goal?: string | null;
};

type AuthResponse = {
  status: "ok";
  user: ApiUser;
};

type SuccessResponse = {
  status: "ok";
};

const normalizeRole = (role: string): UserRole => {
  const value = role.toUpperCase();
  if (value === "ADMIN" || value === "COACH") {
    return value;
  }
  return "MEMBER";
};

const toUserProfile = (user: ApiUser): UserProfile => ({
  id: Number(user.id),
  fullName: user.fullName,
  email: user.email,
  username: user.username,
  role: normalizeRole(user.role),
  membership: user.membership ?? null,
  goal: user.goal ?? null
});

const handleAuthError = (error: unknown, fallback: string): never => {
  if (error instanceof ApiError) {
    throw new Error(error.message || fallback);
  }
  throw error;
};

export async function login(payload: LoginPayload): Promise<UserProfile> {
  try {
    const response = await apiFetch<AuthResponse>("/auth/login.php", {
      method: "POST",
      body: JSON.stringify(payload)
    });
    return toUserProfile(response.user);
  } catch (error) {
    handleAuthError(error, "Unable to authenticate");
  }
}

export async function register(payload: RegisterPayload): Promise<UserProfile> {
  try {
    const response = await apiFetch<AuthResponse>("/auth/register.php", {
      method: "POST",
      body: JSON.stringify(payload)
    });
    return toUserProfile(response.user);
  } catch (error) {
    handleAuthError(error, "Unable to register");
  }
}

export async function logout(): Promise<void> {
  await apiFetch<SuccessResponse>("/auth/logout.php", {
    method: "POST"
  });
}

export async function whoami(): Promise<UserProfile | null> {
  try {
    const response = await apiFetch<AuthResponse>("/auth/me.php", {
      method: "GET"
    });
    return toUserProfile(response.user);
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      return null;
    }
    throw error;
  }
}
