import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";

const studios = [
  {
    name: "Strength & Conditioning Lab",
    sessions: ["Barbell diagnostics", "Velocity-based training", "Skill acquisition micro-blocks"],
  },
  {
    name: "Metabolic Studios",
    sessions: ["Signature HIIT", "Combat conditioning", "Endurance engines"],
  },
  {
    name: "Recovery Suite",
    sessions: ["Contrast therapy", "Infrared & compression", "Mobility therapy"]
  }
];

const addOns = [
  {
    title: "Executive concierge",
    description: "Personal coach, nutrition strategist and quarterly performance review."
  },
  {
    title: "Corporate labs",
    description: "On-site activations, team diagnostics and motivational workshops."
  },
  {
    title: "Athlete residency",
    description: "Short-term high intensity camps with full recovery support."
  }
];

export function ServicesPage() {
  return (
    <div className="px-6 pb-20 pt-14">
      <div className="mx-auto flex max-w-6xl flex-col gap-12">
        <header className="space-y-5">
          <Badge className="bg-primary/20 text-primary">Programs</Badge>
          <h1 className="font-display text-3xl uppercase tracking-[0.32em] text-white">
            Memberships engineered for measurable output
          </h1>
          <p className="max-w-3xl text-sm text-fg-muted">
            Select the training ecosystem that fits your schedule and ambition. Every membership
            includes onboarding analytics, monthly reviews and recovery access.
          </p>
        </header>

        <section className="grid gap-6 md:grid-cols-3">
          {studios.map((studio) => (
            <Card key={studio.name} className="space-y-4 border-white/10 bg-white/5">
              <h2 className="font-display text-xl uppercase tracking-[0.3em] text-white">
                {studio.name}
              </h2>
              <ul className="space-y-2 text-sm text-fg-muted">
                {studio.sessions.map((session) => (
                  <li key={session} className="flex items-start gap-2">
                    <span className="mt-1 h-1.5 w-1.5 rounded-full bg-primary"></span>
                    {session}
                  </li>
                ))}
              </ul>
            </Card>
          ))}
        </section>

        <section className="glass-card rounded-3xl border border-white/10 bg-white/5 p-10">
          <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 className="font-display text-2xl uppercase tracking-[0.3em] text-white">
                Concierge enhancements
              </h2>
              <p className="mt-3 text-sm text-fg-muted">
                Plug premium services into any membership. Our concierge will craft a proposal within
                24 hours.
              </p>
            </div>
            <Button asChild className="tracking-[0.32em]">
              <Link to="/contact">Book Consultation</Link>
            </Button>
          </div>
          <div className="mt-8 grid gap-6 md:grid-cols-3">
            {addOns.map((item) => (
              <Card key={item.title} className="h-full border-white/10 bg-black/40">
                <h3 className="text-lg font-semibold uppercase tracking-[0.3em] text-white">
                  {item.title}
                </h3>
                <p className="mt-3 text-sm text-fg-muted">{item.description}</p>
              </Card>
            ))}
          </div>
        </section>
      </div>
    </div>
  );
}
