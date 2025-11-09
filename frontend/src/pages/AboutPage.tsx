import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";

const timeline = [
  {
    year: "2012",
    title: "Loft beginnings",
    description:
      "We opened in an industrial loft with a mission to blend boutique energy with elite sport science.",
  },
  {
    year: "2016",
    title: "Performance Lab",
    description:
      "Launch of data-driven diagnostics: force plates, metabolic carts and mobility mapping for every athlete.",
  },
  {
    year: "2021",
    title: "Hybrid evolution",
    description:
      "MyGym+ app syncs with in-club sessions, remote coaching and daily recovery guidance.",
  },
  {
    year: "2024",
    title: "Global partnerships",
    description:
      "Corporate programs and athlete residencies bring our method to teams worldwide.",
  }
];

const values = [
  {
    title: "Athlete-first",
    description: "Every touchpoint—from onboarding to recovery—prioritises the athlete journey.",
  },
  {
    title: "Holistic data",
    description: "Strength, endurance, sleep and mindset. We track, we iterate, we improve.",
  },
  {
    title: "Driven community",
    description: "We celebrate consistent effort and create an atmosphere that keeps you hungry.",
  }
];

export function AboutPage() {
  return (
    <div className="px-6 pb-16 pt-14">
      <div className="mx-auto flex max-w-5xl flex-col gap-12">
        <header className="space-y-5 text-center">
          <Badge className="bg-primary/20 text-primary">Inside MyGym</Badge>
          <h1 className="font-display text-3xl uppercase tracking-[0.32em] text-white">
            Designed to rebuild what you believe is possible
          </h1>
          <p className="mx-auto max-w-3xl text-sm text-fg-muted">
            MyGym is an ecosystem staffed by specialists in strength, conditioning, recovery and
            mindset. We deliver curated training pathways for founders, executives and athletes who
            refuse average performance.
          </p>
        </header>

        <section className="grid gap-6 md:grid-cols-2">
          {timeline.map((item) => (
            <Card key={item.year} className="space-y-2 border-white/10 bg-white/5">
              <p className="text-xs uppercase tracking-[0.4em] text-primary">{item.year}</p>
              <h2 className="text-lg font-semibold uppercase tracking-[0.28em] text-white">
                {item.title}
              </h2>
              <p className="text-sm text-fg-muted">{item.description}</p>
            </Card>
          ))}
        </section>

        <section className="grid gap-6 md:grid-cols-3">
          {values.map((value) => (
            <Card key={value.title} className="space-y-3 border-white/10 bg-white/5">
              <h3 className="text-lg font-semibold uppercase tracking-[0.3em] text-white">
                {value.title}
              </h3>
              <p className="text-sm text-fg-muted">{value.description}</p>
            </Card>
          ))}
        </section>
      </div>
    </div>
  );
}
