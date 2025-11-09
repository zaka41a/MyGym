export type UserRole = "ADMIN" | "COACH" | "MEMBER";

export interface UserProfile {
  id: number;
  fullName: string;
  email: string;
  username: string;
  role: UserRole;
  membership?: string | null;
  goal?: string | null;
}
