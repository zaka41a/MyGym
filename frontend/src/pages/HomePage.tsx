import { motion } from "framer-motion";
import { Link } from "react-router-dom";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CoachShowcase } from "@/components/sections/CoachShowcase";
import { Testimonials } from "@/components/sections/Testimonials";
import { VideoBanner } from "@/components/sections/VideoBanner";
import { cn } from "@/lib/utils";
import { FacilityHighlights } from "@/components/sections/FacilityHighlights";
import { ArrowUpRight } from "lucide-react";

const stats = [
  { label: "Active Members", value: "2.4K+" },
  { label: "Weekly Sessions", value: "180" },
  { label: "Performance Coaches", value: "35" },
  { label: "Avg. PR Increase", value: "27%" }
];

const heroHighlights = [
  {
    title: "Metabolic 360° onboarding",
    description: "Full VO₂ max, force plate analysis and body composition mapping in 90 minutes.",
    cta: "See evaluation protocol"
  },
  {
    title: "Concierge periodisation",
    description: "3D program architecture integrating training, recovery and fuel in one dashboard.",
    cta: "Preview the planner"
  }
];

const partnerLogos = ["Apex Athletics", "Nordic Wellness", "Synapse eSports", "Pulse Recovery Lab"];

const pillars = [
  {
    title: "Performance Lab",
    description:
      "Diagnostics for VO₂ max, force plates and mobility screening inform every personalised program.",
  },
  {
    title: "Recovery Rituals",
    description:
      "Contrast therapy, infrared suites and guided mobility accelerate adaptation between training blocks.",
  },
  {
    title: "Community Energy",
    description:
      "Signature classes, curated playlists and world-class coaches deliver electric motivation every session.",
  }
];

const memberships = [
  {
    tier: "Hybrid Pro",
    price: "$189",
    billing: "monthly",
    features: [
      "Unlimited lab access",
      "Performance coach check-ins",
      "Weekly recovery protocols",
      "MyGym+ digital programming"
    ]
  },
  {
    tier: "Executive",
    price: "$289",
    billing: "monthly",
    features: [
      "Dedicated coach & periodisation",
      "Private locker suite",
      "Priority recovery scheduling",
      "Concierge nutrition labs"
    ]
  },
  {
    tier: "Corporate Teams",
    price: "Custom",
    billing: "plans",
    features: [
      "Executive wellness retreats",
      "On-site performance testing",
      "Motivational workshops",
      "Quarterly analytics review"
    ]
  }
];

export function HomePage() {
  return (
    <div className="relative overflow-hidden">
      <section className="relative px-6 pb-24 pt-20">
        <div className="mx-auto grid max-w-6xl items-start gap-12 md:grid-cols-[1.1fr,0.9fr]">
          <motion.div
            initial={{ opacity: 0, y: 40 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
            className="space-y-8"
          >
            <Badge className="bg-primary/20 text-primary">Built to redefine performance</Badge>
            <h1 className="font-display text-4xl uppercase tracking-[0.2em] text-white sm:text-5xl">
              Training spaces for bodies{" "}
              <span className="bg-gradient-to-r from-white via-primary to-accent bg-clip-text text-transparent">
                that refuse average
              </span>
            </h1>
            <p className="max-w-xl text-base leading-7 text-fg-muted">
              MyGym fuses applied sports science, immersive environments and concierge coaching to
              make elite-level performance accessible. Our team engineers your training, recovery
              and nutrition stack in a single, relentlessly measured experience.
            </p>
            <div className="flex flex-wrap gap-4">
              <Button asChild className="tracking-[0.32em]">
                <Link to="/contact">Book a Lab Tour</Link>
              </Button>
              <Link
                to="/services"
                className="inline-flex items-center rounded-2xl border border-white/10 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.36em] text-fg-muted transition hover:border-white/30 hover:text-white"
              >
                Explore Memberships
              </Link>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              {stats.map((stat) => (
                <Card
                  key={stat.label}
                  className="flex flex-col gap-1 rounded-3xl border-white/10 bg-white/5 p-5 transition duration-300 hover:border-primary/40 hover:bg-white/[0.12]"
                >
                  <span className="text-xs uppercase tracking-[0.36em] text-fg-muted">
                    {stat.label}
                  </span>
                  <span className="font-display text-3xl tracking-[0.2em] text-white">{stat.value}</span>
                </Card>
              ))}
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, scale: 0.92 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.6, ease: "easeOut", delay: 0.1 }}
            className="relative space-y-5 rounded-[2.5rem] border border-white/10 bg-white/5 p-8 shadow-[0_28px_80px_-36px_rgba(99,102,241,0.65)]"
          >
            <div className="absolute inset-x-10 top-10 h-[1px] bg-gradient-to-r from-transparent via-white/20 to-transparent" />
            <div className="flex items-center justify-between gap-4">
              <p className="text-xs uppercase tracking-[0.36em] text-fg-muted">
                This week at the performance lab
              </p>
              <Link
                to="/dashboard"
                className="inline-flex items-center gap-2 text-xs uppercase tracking-[0.3em] text-white/70 transition hover:text-white"
              >
                Member stories <ArrowUpRight className="h-4 w-4" />
              </Link>
            </div>
            {heroHighlights.map((item) => (
              <Card
                key={item.title}
                className="relative overflow-hidden border-white/10 bg-white/[0.07] p-6 transition duration-300 hover:border-primary/40 hover:bg-gradient-to-br hover:from-white/10 hover:via-primary/5 hover:to-transparent"
              >
                <h3 className="font-display text-lg uppercase tracking-[0.28em] text-white">
                  {item.title}
                </h3>
                <p className="mt-3 text-sm leading-6 text-fg-muted">{item.description}</p>
                <span className="mt-4 inline-flex items-center gap-2 text-xs uppercase tracking-[0.3em] text-primary">
                  {item.cta} <ArrowUpRight className="h-4 w-4" />
                </span>
              </Card>
            ))}
          </motion.div>
        </div>

        <div className="mx-auto mt-16 flex max-w-6xl flex-wrap items-center justify-between gap-4 border-t border-white/5 pt-6 text-[0.7rem] uppercase tracking-[0.36em] text-fg-muted/80">
          <span className="text-xs font-semibold text-white/80">Trusted by high performance teams</span>
          <div className="flex flex-wrap gap-x-8 gap-y-3 text-white/40">
            {partnerLogos.map((logo) => (
              <span key={logo} className="whitespace-nowrap">
                {logo}
              </span>
            ))}
          </div>
        </div>
      </section>

      <section className="relative border-y border-white/5 bg-black/70 py-20">
        <div className="mx-auto grid max-w-6xl gap-8 px-6 md:grid-cols-3">
          {pillars.map((pillar) => (
            <Card key={pillar.title} className="h-full space-y-4 border-white/10 bg-white/5">
              <h3 className="font-display text-xl uppercase tracking-[0.3em] text-white">
                {pillar.title}
              </h3>
              <p className="text-sm leading-6 text-fg-muted">{pillar.description}</p>
            </Card>
          ))}
        </div>
      </section>

      <section className="px-6 py-20">
        <div className="mx-auto flex max-w-6xl flex-col gap-10">
          <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
              <Badge className="bg-primary/20 text-primary">Memberships</Badge>
              <h2 className="mt-4 font-display text-3xl uppercase tracking-[0.32em] text-white">
                Choose your elevation path
              </h2>
            </div>
            <p className="max-w-xl text-sm text-fg-muted">
              Every plan includes biometric onboarding, monthly performance reviews and access to
              our recovery rituals. Scale from hybrid flexibility to executive concierge service.
            </p>
          </div>

          <div className="grid gap-6 md:grid-cols-3">
            {memberships.map((membership, index) => (
              <Card
                key={membership.tier}
                className={cn(
                  "flex h-full flex-col gap-5 border-white/10 bg-white/5 p-7",
                  index === 1 && "border-primary/40 bg-primary/10 shadow-glow"
                )}
              >
                <div>
                  <h3 className="font-display text-2xl uppercase tracking-[0.3em] text-white">
                    {membership.tier}
                  </h3>
                  <p className="mt-2 text-sm text-fg-muted">
                    {membership.price} <span className="text-xs uppercase">{membership.billing}</span>
                  </p>
                </div>
                <ul className="space-y-3 text-sm text-fg-muted">
                  {membership.features.map((feature) => (
                    <li key={feature} className="flex items-start gap-2">
                      <span className="mt-1 h-1.5 w-1.5 rounded-full bg-primary"></span>
                      {feature}
                    </li>
                  ))}
                </ul>
                <Button asChild className="mt-auto tracking-[0.32em]">
                  <Link to="/contact">Start Consultation</Link>
                </Button>
              </Card>
            ))}
          </div>
        </div>
      </section>

      <VideoBanner />

      <FacilityHighlights />

      <CoachShowcase />

      <Testimonials />

      <section className="border-t border-white/5 bg-black/75 px-6 py-20">
        <div className="mx-auto max-w-5xl rounded-3xl border border-white/10 bg-gradient-to-r from-primary/20 via-black/80 to-primary/10 p-10 text-center shadow-glow">
          <p className="font-display text-2xl uppercase tracking-[0.3em] text-white sm:text-3xl">
            Ready to train beyond limits?
          </p>
          <p className="mt-3 text-sm text-fg-muted">
            Schedule a strategy session with our performance concierge. We will map your goals,
            craft your training architecture and begin your first benchmark testing.
          </p>
          <div className="mt-6 flex flex-wrap justify-center gap-4">
            <Button className="tracking-[0.3em]">
              <Link to="/contact">Book Strategy Session</Link>
            </Button>
            <Link
              to="/services"
              className="inline-flex items-center rounded-2xl border border-white/10 bg-white/5 px-5 py-2 text-xs font-semibold uppercase tracking-[0.34em] text-fg-muted transition hover:border-white/30 hover:text-white"
            >
              Download Membership Deck
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
