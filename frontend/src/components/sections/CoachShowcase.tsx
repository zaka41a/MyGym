import { Card } from "@/components/ui/card";

const coaches = [
  {
    name: "Lina Herrera",
    specialty: "Strength diagnostics",
    quote: "Every cycle we challenge movement patterns, nervous system capacity and mindset." 
  },
  {
    name: "Malik Saito",
    specialty: "Metabolic conditioning",
    quote: "Athletes don’t burn out. They lose structure. We bring relentless structure." 
  },
  {
    name: "Sofia Dupont",
    specialty: "Recovery & mindset",
    quote: "Recovery is a skill. We track compliance and accelerate adaptation." 
  }
];

export function CoachShowcase() {
  return (
    <section className="px-6 py-20">
      <div className="mx-auto flex max-w-6xl flex-col gap-10">
        <div>
          <h2 className="font-display text-3xl uppercase tracking-[0.32em] text-white">
            Coaches that build resilience
          </h2>
          <p className="mt-3 max-w-3xl text-sm text-fg-muted">
            Our team blends Olympic lifting, endurance, combat and sports therapy backgrounds to
            engineer potent programming for founders, executives and competitive athletes.
          </p>
        </div>
        <div className="grid gap-6 md:grid-cols-3">
          {coaches.map((coach) => (
            <Card key={coach.name} className="space-y-3 border-white/10 bg-white/5 p-6 text-sm">
              <p className="text-xs uppercase tracking-[0.32em] text-primary">
                {coach.specialty}
              </p>
              <h3 className="font-semibold uppercase tracking-[0.3em] text-white">
                {coach.name}
              </h3>
              <p className="text-fg-muted">“{coach.quote}”</p>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}
