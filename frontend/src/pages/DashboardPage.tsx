import { useMemo } from "react";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { useAuthStore } from "@/lib/store/use-auth-store";
import { Activity, TrendingUp, Users, Calendar } from "lucide-react";

const nextSessions = [
  {
    id: "session-1",
    title: "Lab Strength Diagnostics",
    date: "Tuesday, 07:00",
    coach: "Coach Lina"
  },
  {
    id: "session-2",
    title: "Metabolic HIIT Signature",
    date: "Thursday, 18:30",
    coach: "Coach Malik"
  }
];

const recoveryTasks = [
  "Cold plunge 3 min x 3 rounds",
  "Guided mobility – hips/shoulders (15 min)",
  "Hydration target 3.2L"
];

export function DashboardPage() {
  const user = useAuthStore((store) => store.user);

  const welcome = useMemo(() => {
    if (!user) return "Athlete";
    return user.fullName.split(" ")[0] ?? user.fullName;
  }, [user]);

  // Auth is handled by DashboardLayout, so user is always defined here
  if (!user) return null;

  if (user.role === "ADMIN") {
    const metrics = [
      {
        title: "Active Members",
        value: "1,248",
        trend: "+8.2%",
        icon: Users
      },
      {
        title: "Total Revenue",
        value: "$184k",
        trend: "+12.4%",
        icon: TrendingUp
      },
      {
        title: "Active Coaches",
        value: "35",
        trend: "+3 new",
        icon: Activity
      },
      {
        title: "Sessions Today",
        value: "48",
        trend: "92% capacity",
        icon: Calendar
      }
    ];

    return (
      <div className="space-y-8">
        <header className="space-y-2">
          <Badge className="bg-accent/20 text-accent">Admin Overview</Badge>
          <h1 className="font-display text-4xl uppercase tracking-[0.3em] text-white">
            Welcome back, {welcome}
          </h1>
          <p className="max-w-2xl text-sm text-fg-muted">
            Monitor club performance and keep every member's experience at elite level.
          </p>
        </header>

        <section className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          {metrics.map((metric) => {
            const Icon = metric.icon;
            return (
              <Card
                key={metric.title}
                className="space-y-3 border-white/10 bg-white/5 p-6 transition duration-300 hover:border-primary/40 hover:bg-white/8"
              >
                <div className="flex items-center justify-between">
                  <Icon className="h-8 w-8 text-primary" />
                  <span className="text-xs font-semibold uppercase tracking-[0.3em] text-accent">
                    {metric.trend}
                  </span>
                </div>
                <p className="font-display text-3xl uppercase tracking-[0.2em] text-white">
                  {metric.value}
                </p>
                <p className="text-xs uppercase tracking-[0.3em] text-fg-muted">
                  {metric.title}
                </p>
              </Card>
            );
          })}
        </section>

        <section className="grid gap-6 md:grid-cols-2">
          <Card className="border-white/10 bg-white/5 p-6">
            <h2 className="mb-4 text-lg font-semibold uppercase tracking-[0.3em] text-white">
              Recent Activity
            </h2>
            <div className="space-y-3">
              <div className="flex items-center justify-between rounded-xl border border-white/5 bg-black/30 p-4">
                <div>
                  <p className="text-sm font-semibold text-white">New member registered</p>
                  <p className="text-xs text-fg-muted">John Doe • 2 hours ago</p>
                </div>
                <Badge className="bg-success/20 text-success">New</Badge>
              </div>
              <div className="flex items-center justify-between rounded-xl border border-white/5 bg-black/30 p-4">
                <div>
                  <p className="text-sm font-semibold text-white">Subscription upgraded</p>
                  <p className="text-xs text-fg-muted">Sarah Smith • 4 hours ago</p>
                </div>
                <Badge className="bg-primary/20 text-primary">Upgrade</Badge>
              </div>
            </div>
          </Card>

          <Card className="border-white/10 bg-white/5 p-6">
            <h2 className="mb-4 text-lg font-semibold uppercase tracking-[0.3em] text-white">
              Quick Stats
            </h2>
            <div className="space-y-4">
              <div>
                <div className="mb-2 flex justify-between text-xs text-fg-muted">
                  <span>Membership Retention</span>
                  <span>94%</span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-white/10">
                  <div className="h-full w-[94%] bg-gradient-to-r from-primary to-accent"></div>
                </div>
              </div>
              <div>
                <div className="mb-2 flex justify-between text-xs text-fg-muted">
                  <span>Facility Utilization</span>
                  <span>87%</span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-white/10">
                  <div className="h-full w-[87%] bg-gradient-to-r from-primary to-accent"></div>
                </div>
              </div>
            </div>
          </Card>
        </section>
      </div>
    );
  }

  if (user.role === "COACH") {
    const coachSessions = [
      {
        title: "Strength Lab · Elite squad",
        time: "07:30",
        focus: "Lower power / velocity tracking"
      },
      {
        title: "Metabolic Signature · Group 2",
        time: "12:00",
        focus: "Threshold conditioning / breath work"
      },
      {
        title: "Mobility Reset · Executive pod",
        time: "18:00",
        focus: "Spine decompression / assisted PNF"
      }
    ];

    return (
      <div className="space-y-8">
        <header className="space-y-2">
          <Badge className="bg-accent/20 text-accent">Coach Overview</Badge>
          <h1 className="font-display text-4xl uppercase tracking-[0.3em] text-white">
            Welcome back, {welcome}
          </h1>
          <p className="max-w-2xl text-sm text-fg-muted">
            Keep your sessions sharp and prepare tomorrow's blocks for your roster.
          </p>
        </header>

        <section className="grid gap-6 lg:grid-cols-3">
          <Card className="border-white/10 bg-white/5 p-6">
            <div className="mb-2 flex items-center gap-2">
              <Calendar className="h-5 w-5 text-primary" />
              <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
                Sessions Today
              </h3>
            </div>
            <p className="font-display text-3xl text-white">3</p>
          </Card>
          <Card className="border-white/10 bg-white/5 p-6">
            <div className="mb-2 flex items-center gap-2">
              <Users className="h-5 w-5 text-primary" />
              <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
                Active Members
              </h3>
            </div>
            <p className="font-display text-3xl text-white">24</p>
          </Card>
          <Card className="border-white/10 bg-white/5 p-6">
            <div className="mb-2 flex items-center gap-2">
              <Activity className="h-5 w-5 text-primary" />
              <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
                Avg Attendance
              </h3>
            </div>
            <p className="font-display text-3xl text-white">89%</p>
          </Card>
        </section>

        <section className="grid gap-6 md:grid-cols-2">
          <Card className="border-white/10 bg-white/5 p-6">
            <h2 className="mb-4 text-lg font-semibold uppercase tracking-[0.3em] text-white">
              Today's Sessions
            </h2>
            <div className="space-y-3">
              {coachSessions.map((session) => (
                <div
                  key={session.title}
                  className="rounded-xl border border-white/10 bg-black/40 p-4"
                >
                  <div className="mb-1 flex items-center justify-between">
                    <span className="text-xs font-semibold uppercase tracking-[0.3em] text-accent">
                      {session.time}
                    </span>
                  </div>
                  <p className="mb-1 text-sm font-semibold text-white">{session.title}</p>
                  <p className="text-xs text-fg-muted">{session.focus}</p>
                </div>
              ))}
            </div>
          </Card>

          <Card className="border-white/10 bg-white/5 p-6">
            <h2 className="mb-4 text-lg font-semibold uppercase tracking-[0.3em] text-white">
              Priority Actions
            </h2>
            <div className="space-y-3">
              <div className="rounded-xl border border-white/10 bg-black/40 p-4">
                <p className="text-sm text-white">Upload metrics for Elite squad block 2</p>
              </div>
              <div className="rounded-xl border border-white/10 bg-black/40 p-4">
                <p className="text-sm text-white">Review member feedback for recovery pod</p>
              </div>
              <div className="rounded-xl border border-white/10 bg-black/40 p-4">
                <p className="text-sm text-white">Sync with nutritionist on Team Atlas</p>
              </div>
            </div>
          </Card>
        </section>
      </div>
    );
  }

  // Member dashboard
  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <Badge className="bg-primary/20 text-primary">Member Overview</Badge>
        <h1 className="font-display text-4xl uppercase tracking-[0.3em] text-white">
          Welcome back, {welcome}
        </h1>
        <p className="max-w-2xl text-sm text-fg-muted">
          Track your upcoming sessions and stay aligned with your performance targets.
        </p>
      </header>

      <section className="grid gap-6 md:grid-cols-3">
        <Card className="border-white/10 bg-white/5 p-6">
          <div className="mb-2 flex items-center gap-2">
            <Calendar className="h-5 w-5 text-primary" />
            <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
              Next Session
            </h3>
          </div>
          <p className="font-display text-2xl text-white">Tuesday 07:00</p>
          <p className="text-xs text-fg-muted">Strength Lab</p>
        </Card>
        <Card className="border-white/10 bg-white/5 p-6">
          <div className="mb-2 flex items-center gap-2">
            <Activity className="h-5 w-5 text-primary" />
            <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
              This Week
            </h3>
          </div>
          <p className="font-display text-2xl text-white">4 Sessions</p>
          <p className="text-xs text-fg-muted">Completed 2/4</p>
        </Card>
        <Card className="border-white/10 bg-white/5 p-6">
          <div className="mb-2 flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-primary" />
            <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-white">
              Progress
            </h3>
          </div>
          <p className="font-display text-2xl text-white">Week 3/6</p>
          <p className="text-xs text-fg-muted">Strength Block</p>
        </Card>
      </section>

      <section className="grid gap-6 md:grid-cols-2">
        <Card className="border-white/10 bg-white/5 p-6">
          <h2 className="mb-4 text-lg font-semibold uppercase tracking-[0.3em] text-white">
            Upcoming Sessions
          </h2>
          <div className="space-y-3">
            {nextSessions.map((session) => (
              <div
                key={session.id}
                className="rounded-xl border border-white/10 bg-black/40 p-4"
              >
                <p className="mb-1 text-xs font-semibold uppercase tracking-[0.3em] text-primary">
                  {session.date}
                </p>
                <p className="mb-1 text-sm font-semibold text-white">{session.title}</p>
                <p className="text-xs text-fg-muted">{session.coach}</p>
              </div>
            ))}
          </div>
        </Card>

        <div className="space-y-6">
          <Card className="border-white/10 bg-white/5 p-6">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-[0.3em] text-white">
              This Week's Focus
            </h3>
            <div className="space-y-2 text-sm text-fg-muted">
              <p>✓ Strength Block: Week 3 of 6 (Lower emphasis)</p>
              <p>✓ Energy System: Aerobic capacity</p>
              <p>✓ Nutrition: +15% carbs on session days</p>
            </div>
          </Card>

          <Card className="border-white/10 bg-white/5 p-6">
            <h3 className="mb-4 text-sm font-semibold uppercase tracking-[0.3em] text-white">
              Recovery Checklist
            </h3>
            <div className="space-y-2 text-sm text-fg-muted">
              {recoveryTasks.map((task) => (
                <div key={task} className="flex items-start gap-2">
                  <span className="mt-1 h-1.5 w-1.5 rounded-full bg-primary"></span>
                  <span>{task}</span>
                </div>
              ))}
            </div>
          </Card>
        </div>
      </section>
    </div>
  );
}
