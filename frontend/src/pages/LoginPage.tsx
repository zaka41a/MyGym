import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { login as loginApi } from "@/lib/api/auth";
import { useAuthStore } from "@/lib/store/use-auth-store";
import { Link, useNavigate } from "react-router-dom";
import { useEffect } from "react";
import { CheckCircle2 } from "lucide-react";
const loginSchema = z.object({
  email: z.string().email("Provide a valid email"),
  password: z.string().min(6, "Minimum 6 characters")
});

type LoginValues = z.infer<typeof loginSchema>;

const loginHighlights = [
  "Concierge onboarding call within 24h of login",
  "Weekly accountability sync with your coach",
  "Access to recovery and fuel programming in-app"
];

export function LoginPage() {
  const setUser = useAuthStore((store) => store.setUser);
  const status = useAuthStore((store) => store.status);
  const user = useAuthStore((store) => store.user);
  const navigate = useNavigate();
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting }
  } = useForm<LoginValues>({
    resolver: zodResolver(loginSchema)
  });

  const onSubmit = async (values: LoginValues) => {
    setErrorMessage(null);
    try {
      const profile = await loginApi({
        identifier: values.email,
        password: values.password
      });
      setUser(profile);

      // Redirect to PHP dashboards based on role
      const dashboardUrls = {
        ADMIN: 'http://localhost/MyGym/admin/',
        COACH: 'http://localhost/MyGym/coach/',
        MEMBER: 'http://localhost/MyGym/member/'
      };

      const dashboardUrl = dashboardUrls[profile.role] || 'http://localhost/MyGym/member/';
      window.location.href = dashboardUrl;
    } catch (error) {
      const message = error instanceof Error ? error.message : "Unable to authenticate";
      setErrorMessage(message);
    }
  };

  useEffect(() => {
    if (status === "authenticated" && user) {
      const dashboardUrls = {
        ADMIN: 'http://localhost/MyGym/admin/',
        COACH: 'http://localhost/MyGym/coach/',
        MEMBER: 'http://localhost/MyGym/member/'
      };

      const dashboardUrl = dashboardUrls[user.role] || 'http://localhost/MyGym/member/';
      window.location.href = dashboardUrl;
    }
  }, [status, user]);

  return (
    <div className="px-6 pb-24 pt-16">
      <div className="mx-auto grid max-w-5xl gap-8 rounded-[2.5rem] border border-white/10 bg-white/[0.05] p-8 shadow-[0_32px_80px_-40px_rgba(255,45,85,0.45)] md:grid-cols-[0.9fr,1.1fr]">
        <aside className="flex flex-col justify-between rounded-3xl border border-white/10 bg-gradient-to-br from-primary/15 via-black/60 to-accent/10 p-8">
          <div className="space-y-4">
            <Badge className="bg-primary/25 text-primary">Performance account</Badge>
            <h2 className="font-display text-2xl uppercase tracking-[0.3em] text-white">
              Welcome back athlete
            </h2>
            <p className="text-sm leading-6 text-fg-muted">
              Sync with your dashboard to unlock daily programming, metric tracking and concierge
              recovery cues customised to your training block.
            </p>
          </div>
          <ul className="mt-8 space-y-3 text-sm text-fg-muted">
            {loginHighlights.map((item) => (
              <li key={item} className="flex items-center gap-3 rounded-2xl border border-white/5 bg-black/40 px-3 py-2">
                <CheckCircle2 className="h-4 w-4 text-primary" />
                <span>{item}</span>
              </li>
            ))}
          </ul>
        </aside>

        <div className="rounded-3xl border border-white/10 bg-white/8 p-8">
          <div className="space-y-1 text-center">
            <p className="text-xs uppercase tracking-[0.36em] text-fg-muted">Secure portal</p>
            <p className="font-display text-2xl uppercase tracking-[0.3em] text-white">Log in</p>
          </div>
          <form onSubmit={handleSubmit(onSubmit)} className="mt-10 space-y-6">
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Email
              </label>
              <Input type="email" placeholder="you@mygym.pro" {...register("email")} />
              {errors.email ? <p className="text-xs text-error">{errors.email.message}</p> : null}
            </div>
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Password
              </label>
              <Input type="password" placeholder="••••••••" {...register("password")} />
              {errors.password ? (
                <p className="text-xs text-error">{errors.password.message}</p>
              ) : null}
            </div>
            <Button type="submit" disabled={isSubmitting} className="w-full tracking-[0.3em]">
              {isSubmitting ? "Authenticating..." : "Log in"}
            </Button>
            {errorMessage ? (
              <p className="text-center text-xs text-error">{errorMessage}</p>
            ) : null}
          </form>
          <p className="mt-6 text-center text-xs text-fg-muted">
            No account yet? <Link to="/register" className="text-primary">Create one</Link> or contact concierge.
          </p>
        </div>
      </div>
    </div>
  );
}
