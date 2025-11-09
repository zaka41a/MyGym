import { Button } from "@/components/ui/button";
import { Play } from "lucide-react";

export function VideoBanner() {
  return (
    <section className="relative overflow-hidden px-6 py-20">
      <div className="mx-auto flex max-w-5xl flex-col gap-6 rounded-3xl border border-white/10 bg-gradient-to-r from-primary/25 via-black/90 to-primary/25 p-10 shadow-glow">
        <div className="space-y-3 text-white">
          <p className="text-xs uppercase tracking-[0.4em] text-primary-700">Experience</p>
          <h2 className="font-display text-3xl uppercase tracking-[0.32em]">
            Step inside the MyGym performance lab
          </h2>
          <p className="text-sm text-fg-muted">
            Discover the spaces, equipment and rituals powering our athletes. Watch the short film
            and feel the energy.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-4">
          <Button className="tracking-[0.3em]" variant="primary">
            <Play className="h-4 w-4" /> Watch film
          </Button>
          <p className="text-xs uppercase tracking-[0.34em] text-fg-muted">
            3 min documentary • Shot in Casablanca lab
          </p>
        </div>
      </div>
    </section>
  );
}
