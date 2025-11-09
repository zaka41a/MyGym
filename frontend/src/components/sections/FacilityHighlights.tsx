import { Activity, Dumbbell, Flame, Waves } from "lucide-react";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

const highlights = [
  {
    title: "Altitude Sprint Lab",
    description:
      "Hypoxic treadmills, curved tracks and laser timing systems for programmable intervals and sprint profiling.",
    icon: Activity,
    metric: "VO₂ recalibration in 6 weeks"
  },
  {
    title: "Strength Arsenal",
    description:
      "Keiser performance racks, velocity trackers and custom platforms engineered for explosive power development.",
    icon: Dumbbell,
    metric: "210+ personalised protocols"
  },
  {
    title: "Recovery Sanctum",
    description:
      "Infrared suites, contrast therapy pools and guided breathwork lounges restore nervous system balance.",
    icon: Waves,
    metric: "12 min guided reset circuits"
  },
  {
    title: "Pro Fuel Bar",
    description:
      "Clinically curated pre and post-session formulations, electrolytes and functional shots aligned to your block.",
    icon: Flame,
    metric: "Macro-matched concierge"
  }
];

export function FacilityHighlights() {
  return (
    <section className="relative overflow-hidden px-6 py-20">
      <div className="mx-auto max-w-6xl space-y-12">
        <div className="flex flex-col items-start gap-4 md:flex-row md:items-end md:justify-between">
          <div className="space-y-3">
            <Badge className="bg-primary/20 text-primary">Spaces</Badge>
            <h2 className="font-display text-3xl uppercase tracking-[0.32em] text-white md:text-4xl">
              Every zone engineered for impact
            </h2>
            <p className="max-w-2xl text-sm text-fg-muted">
              From performance diagnostics to parasympathetic recovery, each space in the club is
              choreographed to move you from stimulus to adaptation with precision.
            </p>
          </div>
          <p className="text-xs uppercase tracking-[0.4em] text-fg-muted">
            24/7 performance concierge
          </p>
        </div>

        <div className="grid gap-6 md:grid-cols-2">
          {highlights.map((item) => (
            <Card
              key={item.title}
              className="group relative overflow-hidden border-white/10 bg-white/[0.07] p-6 transition duration-300 hover:border-primary/40 hover:bg-gradient-to-br hover:from-white/10 hover:via-primary/5 hover:to-transparent"
            >
              <div className="flex items-center gap-4">
                <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/50 via-primary-700/40 to-accent/40 text-white shadow-glow">
                  <item.icon className="h-6 w-6" />
                </span>
                <div>
                  <h3 className="font-display text-lg uppercase tracking-[0.28em] text-white">
                    {item.title}
                  </h3>
                  <p className="text-xs uppercase tracking-[0.32em] text-secondary">
                    {item.metric}
                  </p>
                </div>
              </div>
              <p className="mt-4 text-sm leading-6 text-fg-muted transition duration-300 group-hover:text-white">
                {item.description}
              </p>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}
