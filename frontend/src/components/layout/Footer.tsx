import { Flame, Instagram, Mail, Phone } from "lucide-react";
import { Link } from "react-router-dom";

export function Footer() {
  const year = new Date().getFullYear();
  return (
    <footer className="mt-24 border-t border-white/10 bg-[#080912]/90">
      <div className="mx-auto grid max-w-6xl gap-10 px-6 py-14 md:grid-cols-4">
        <div className="space-y-3">
          <div className="flex items-center gap-3">
            <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-primary/50 via-primary-700/50 to-accent/50 shadow-glow">
              <Flame className="h-6 w-6 text-primary" />
            </span>
            <div className="leading-none">
              <p className="font-display text-xl uppercase tracking-[0.38em] text-white">MyGym</p>
              <p className="text-xs uppercase tracking-[0.32em] text-fg-muted">Performance Club</p>
            </div>
          </div>
          <p className="text-sm text-fg-muted">
            Elite training environments designed to rebuild strength, endurance and mindset.
          </p>
          <p className="text-xs uppercase tracking-[0.32em] text-fg-muted/70">
            Casablanca · London · Dubai · Montréal
          </p>
        </div>
        <div>
          <h4 className="text-sm font-semibold uppercase tracking-[0.32em] text-white">Visit</h4>
          <p className="mt-3 text-sm text-fg-muted">
            228 Strength Avenue
            <br />
            Casablanca, Morocco
          </p>
        </div>
        <div>
          <h4 className="text-sm font-semibold uppercase tracking-[0.32em] text-white">Connect</h4>
          <div className="mt-3 space-y-2 text-sm text-fg-muted">
            <p className="flex items-center gap-2">
              <Phone className="h-4 w-4" /> +212 5 23 45 67 89
            </p>
            <p className="flex items-center gap-2">
              <Mail className="h-4 w-4" /> support@mygym.pro
            </p>
            <a className="flex items-center gap-2 hover:text-white" href="https://instagram.com" target="_blank" rel="noreferrer">
              <Instagram className="h-4 w-4" /> @mygym.pro
            </a>
          </div>
        </div>
        <div>
          <h4 className="text-sm font-semibold uppercase tracking-[0.32em] text-white">Explore</h4>
          <nav className="mt-3 grid gap-2 text-sm text-fg-muted">
            <Link to="/services" className="hover:text-white">
              Membership Tiers
            </Link>
            <Link to="/contact" className="hover:text-white">
              Book a Strategy Session
            </Link>
            <Link to="/about" className="hover:text-white">
              Our Story
            </Link>
          </nav>
        </div>
      </div>
      <div className="border-t border-white/10 py-6 text-center text-xs uppercase tracking-[0.35em] text-fg-muted">
        © {year} MyGym. All rights reserved.
      </div>
    </footer>
  );
}
