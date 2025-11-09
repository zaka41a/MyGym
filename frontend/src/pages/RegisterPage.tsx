import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { register as registerApi } from "@/lib/api/auth";
import { useAuthStore } from "@/lib/store/use-auth-store";
import { useNavigate } from "react-router-dom";
import { CheckCircle2 } from "lucide-react";

const registerSchema = z
  .object({
    fullName: z.string().min(2, "Name is required"),
    email: z.string().email("Provide a valid email"),
    goal: z.string().min(3, "Tell us about your goal"),
    password: z.string().min(6, "Minimum 6 characters"),
    confirmPassword: z.string()
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: "Passwords must match",
    path: ["confirmPassword"]
  });

type RegisterValues = z.infer<typeof registerSchema>;

const onboardingSteps = [
  {
    title: "Week 0 · Performance diagnostics",
    detail: "Force plate testing, metabolic profiling and mobility mapping to baseline your metrics."
  },
  {
    title: "Week 1 · Custom architecture",
    detail: "Personalised training, recovery and fuel blueprint designed with your assigned coach."
  },
  {
    title: "Week 2 · Concierge integration",
    detail: "Wearable sync, accountability scheduling and strategy consult to re-align the plan."
  }
];

const membershipPerks = [
  "Unlimited access to recovery sanctum and fuel bar",
  "Weekly performance reviews with lead coach",
  "Exclusive member events and brand partnerships"
];

export function RegisterPage() {
  const [status, setStatus] = useState<"idle" | "success" | "error">("idle");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const setUser = useAuthStore((store) => store.setUser);
  const navigate = useNavigate();
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting }
  } = useForm<RegisterValues>({
    resolver: zodResolver(registerSchema)
  });

  const onSubmit = async (values: RegisterValues) => {
    setStatus("idle");
    setErrorMessage(null);
    try {
      const profile = await registerApi({
        fullName: values.fullName,
        email: values.email,
        goal: values.goal,
        password: values.password
      });
      setUser(profile);
      setStatus("success");
      reset();

      // Redirect to PHP dashboards based on role
      const dashboardUrls = {
        ADMIN: 'http://localhost/MyGym/admin/',
        COACH: 'http://localhost/MyGym/coach/',
        MEMBER: 'http://localhost/MyGym/member/'
      };

      const dashboardUrl = dashboardUrls[profile.role] || 'http://localhost/MyGym/member/';
      window.location.href = dashboardUrl;
    } catch (error) {
      console.error(error);
      setStatus("error");
      const message = error instanceof Error ? error.message : "Unable to register";
      setErrorMessage(message);
    }
  };

  return (
    <div className="px-6 pb-24 pt-16">
      <div className="mx-auto grid max-w-6xl gap-10 rounded-[2.8rem] border border-white/10 bg-white/[0.04] p-10 shadow-[0_32px_80px_-40px_rgba(15,23,42,0.7)] md:grid-cols-[1.1fr,0.9fr]">
        <section className="space-y-8">
          <div className="space-y-4">
            <Badge className="bg-primary/20 text-primary">Join MyGym</Badge>
            <h1 className="font-display text-3xl uppercase tracking-[0.28em] text-white md:text-4xl">
              Your performance concierge awaits
            </h1>
            <p className="text-sm leading-6 text-fg-muted">
              Membership unlocks a relentless partnership between data, coaches and recovery
              specialists. Share your goal and we will build the structure that gets you there.
            </p>
          </div>

          <div className="space-y-4 rounded-3xl border border-white/10 bg-white/[0.05] p-6">
            <p className="text-xs uppercase tracking-[0.36em] text-fg-muted">
              Onboarding timeline
            </p>
            <div className="space-y-5">
              {onboardingSteps.map((step) => (
                <div key={step.title} className="rounded-2xl border border-white/10 bg-black/40 p-4">
                  <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
                    {step.title}
                  </h3>
                  <p className="mt-2 text-sm text-fg-muted">{step.detail}</p>
                </div>
              ))}
            </div>
          </div>

          <ul className="grid gap-3 sm:grid-cols-2">
            {membershipPerks.map((perk) => (
              <li
                key={perk}
                className="flex items-center gap-3 rounded-2xl border border-white/10 bg-black/50 px-3 py-2 text-sm text-fg-muted"
              >
                <CheckCircle2 className="h-4 w-4 text-primary" />
                <span>{perk}</span>
              </li>
            ))}
          </ul>
        </section>

        <form
          onSubmit={handleSubmit(onSubmit)}
          className="space-y-6 rounded-3xl border border-white/10 bg-white/8 p-8"
        >
          <div className="space-y-3 text-center">
            <p className="text-xs uppercase tracking-[0.35em] text-fg-muted">Create account</p>
            <p className="font-display text-2xl uppercase tracking-[0.3em] text-white">
              Member credentials
            </p>
          </div>

          <div className="space-y-5">
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Full name
              </label>
              <Input placeholder="Jordan Alvarez" {...register("fullName")} />
              {errors.fullName ? (
                <p className="text-xs text-error">{errors.fullName.message}</p>
              ) : null}
            </div>
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Email
              </label>
              <Input type="email" placeholder="you@mygym.pro" {...register("email")} />
              {errors.email ? <p className="text-xs text-error">{errors.email.message}</p> : null}
            </div>
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Performance goal
              </label>
              <Textarea rows={3} placeholder="Share your primary training goal" {...register("goal")} />
              {errors.goal ? <p className="text-xs text-error">{errors.goal.message}</p> : null}
            </div>
            <div className="grid gap-5 md:grid-cols-2">
              <div className="space-y-2">
                <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                  Password
                </label>
                <Input type="password" placeholder="••••••••" {...register("password")} />
                {errors.password ? (
                  <p className="text-xs text-error">{errors.password.message}</p>
                ) : null}
              </div>
              <div className="space-y-2">
                <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                  Confirm password
                </label>
                <Input type="password" placeholder="••••••••" {...register("confirmPassword")} />
                {errors.confirmPassword ? (
                  <p className="text-xs text-error">{errors.confirmPassword.message}</p>
                ) : null}
              </div>
            </div>
          </div>

          <Button type="submit" disabled={isSubmitting} className="w-full tracking-[0.32em]">
            {isSubmitting ? "Creating account..." : "Create account"}
          </Button>
          {status === "success" ? (
            <p className="text-center text-xs text-success">
              Account created. Concierge will email your onboarding kit within 24 hours.
            </p>
          ) : null}
          {status === "error" ? (
            <p className="text-center text-xs text-error">
              {errorMessage ?? "Something went wrong. Please try again or contact support."}
            </p>
          ) : null}
        </form>
      </div>
    </div>
  );
}
