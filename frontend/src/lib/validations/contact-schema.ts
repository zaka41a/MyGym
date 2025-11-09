import { z } from "zod";

export const contactSchema = z.object({
  fullname: z
    .string()
    .min(2, "Name is required")
    .max(80, "Name is too long"),
  email: z.string().email("Provide a valid email"),
  goal: z
    .string()
    .min(3, "Tell us about your goal"),
  phone: z
    .string()
    .optional()
    .refine((value) => !value || /^\+?[0-9\s-]{7,15}$/.test(value), {
      message: "Provide a valid phone number"
    })
});

export type ContactFormValues = z.infer<typeof contactSchema>;
