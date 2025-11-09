import { Card } from "@/components/ui/card";

const testimonials = [
  {
    name: "Amelia Chen",
    role: "Tech Founder",
    message:
      "MyGym engineered a program that fits relentless travel. I operate sharper and recover faster than ever."
  },
  {
    name: "Hicham El Idrissi",
    role: "Corporate Leader",
    message:
      "The combination of diagnostics, nutrition support and recovery rituals changed how my team performs."
  }
];

export function Testimonials() {
  return (
    <section className="border-t border-white/5 bg-black/80 px-6 py-20">
      <div className="mx-auto max-w-5xl space-y-10">
        <div className="text-center">
          <p className="text-xs uppercase tracking-[0.4em] text-primary">Client Stories</p>
          <h2 className="mt-3 font-display text-3xl uppercase tracking-[0.32em] text-white">
            Impact across industries
          </h2>
        </div>
        <div className="grid gap-6 md:grid-cols-2">
          {testimonials.map((item) => (
            <Card key={item.name} className="space-y-4 border-white/10 bg-white/5 p-7">
              <p className="text-sm text-fg-muted">“{item.message}”</p>
              <div>
                <p className="text-sm font-semibold text-white">{item.name}</p>
                <p className="text-xs uppercase tracking-[0.3em] text-fg-muted">{item.role}</p>
              </div>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
}
