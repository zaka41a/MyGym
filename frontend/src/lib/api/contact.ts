import { apiFetch, ApiError } from "@/lib/api/client";

export type ContactPayload = {
  fullName: string;
  email: string;
  goal: string;
  phone?: string;
};

export async function submitContact(payload: ContactPayload): Promise<void> {
  try {
    await apiFetch("/contact.php", {
      method: "POST",
      body: JSON.stringify(payload)
    });
  } catch (error) {
    if (error instanceof ApiError) {
      throw new Error(error.message || "Unable to submit contact form");
    }
    throw error;
  }
}
