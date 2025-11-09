import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { contactSchema, type ContactFormValues } from "@/lib/validations/contact-schema";
import { useState } from "react";
import { submitContact } from "@/lib/api/contact";
import { Clock, MapPin, PhoneCall, Sparkles, Mail as MailIcon } from "lucide-react";

export function ContactPage() {
  const [status, setStatus] = useState<"idle" | "success" | "error">("idle");
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting }
  } = useForm<ContactFormValues>({
    resolver: zodResolver(contactSchema),
    defaultValues: {
      fullname: "",
      email: "",
      goal: "",
      phone: ""
    }
  });

  const onSubmit = async (values: ContactFormValues) => {
    setStatus("idle");
    setErrorMessage(null);
    try {
      await submitContact({
        fullName: values.fullname,
        email: values.email,
        goal: values.goal,
        phone: values.phone
      });
      setStatus("success");
      reset();
    } catch (error) {
      setStatus("error");
      setErrorMessage(error instanceof Error ? error.message : "Unable to submit form");
    }
  };

  const conciergeDetails = [
    {
      icon: MapPin,
      label: "Visit us",
      value: "228 Strength Avenue, Casablanca"
    },
    {
      icon: PhoneCall,
      label: "Concierge",
      value: "+212 5 23 45 67 89"
    },
    {
      icon: MailIcon,
      label: "Email",
      value: "support@mygym.pro"
    }
  ];

  return (
    <div className="px-6 pb-20 pt-14">
      <div className="mx-auto grid max-w-6xl gap-10 rounded-[2.5rem] border border-white/10 bg-white/[0.04] p-10 shadow-[0_32px_80px_-40px_rgba(15,23,42,0.7)] md:grid-cols-[1.1fr,0.9fr]">
        <section className="space-y-6">
          <div className="space-y-4">
            <Badge className="bg-primary/20 text-primary">Concierge</Badge>
            <h1 className="font-display text-3xl uppercase tracking-[0.3em] text-white md:text-4xl">
              Book your strategy session
            </h1>
            <p className="text-sm leading-6 text-fg-muted">
              Share your objective, availability and any data you already track. Our concierge team
              will craft your onboarding sequence and reserve your first diagnostics within 24 hours.
            </p>
          </div>
          <form
            onSubmit={handleSubmit(onSubmit)}
            className="space-y-5 rounded-3xl border border-white/10 bg-white/8 p-8"
          >
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Full name
              </label>
              <Input placeholder="Jordan Alvarez" {...register("fullname")} />
              {errors.fullname ? (
                <p className="text-xs text-error">{errors.fullname.message}</p>
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
                Phone (optional)
              </label>
              <Input placeholder="+212 5 23 45 67 89" {...register("phone")} />
              {errors.phone ? <p className="text-xs text-error">{errors.phone.message}</p> : null}
            </div>
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase tracking-[0.3em] text-fg-muted">
                Coaching goal
              </label>
              <Textarea rows={4} placeholder="Tell us about your performance goal" {...register("goal")} />
              {errors.goal ? <p className="text-xs text-error">{errors.goal.message}</p> : null}
            </div>
            <Button type="submit" disabled={isSubmitting} className="tracking-[0.3em]">
              {isSubmitting ? "Submitting..." : "Submit"}
            </Button>
            {status === "success" ? (
              <p className="text-xs text-success">
                Received. Our concierge will contact you shortly.
              </p>
            ) : null}
            {status === "error" ? (
              <p className="text-xs text-error">
                {errorMessage ?? "Something went wrong. Please try again later."}
              </p>
            ) : null}
          </form>
        </section>

        <aside className="space-y-8 rounded-3xl border border-white/10 bg-black/60 p-8">
          <div className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/20 text-primary">
              <Sparkles className="h-5 w-5" />
            </span>
            <div>
              <h2 className="font-display text-xl uppercase tracking-[0.3em] text-white">
                Visit the club
              </h2>
              <p className="text-xs uppercase tracking-[0.32em] text-fg-muted">
                Performance concierge
              </p>
            </div>
          </div>

          <div className="space-y-4">
            {conciergeDetails.map((detail) => (
              <div
                key={detail.label}
                className="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 px-4 py-3"
              >
                <detail.icon className="h-5 w-5 text-primary" />
                <div>
                  <p className="text-xs uppercase tracking-[0.32em] text-fg-muted">{detail.label}</p>
                  <p className="text-sm text-white">{detail.value}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="rounded-3xl border border-white/10 bg-white/5 p-6">
            <div className="flex items-center gap-2 text-xs uppercase tracking-[0.35em] text-primary">
              <Clock className="h-4 w-4" />
              <span>Hours</span>
            </div>
            <ul className="mt-3 space-y-2 text-sm text-fg-muted">
              <li>Weekdays · 05h30 – 23h00</li>
              <li>Weekends · 07h00 – 21h00</li>
            </ul>
          </div>

          <div className="rounded-3xl border border-white/10 bg-gradient-to-br from-white/8 via-primary/10 to-transparent p-6">
            <p className="text-sm font-semibold uppercase tracking-[0.32em] text-white">
              Need immediate assistance?
            </p>
            <p className="mt-2 text-sm text-fg-muted">
              Message our concierge team directly through the member portal chat for same-day
              adjustments or travel programming.
            </p>
          </div>
        </aside>
      </div>
    </div>
  );
}
