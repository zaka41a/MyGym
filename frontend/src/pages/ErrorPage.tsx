import { isRouteErrorResponse, useRouteError } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";

export function ErrorPage() {
  const error = useRouteError();
  const message = isRouteErrorResponse(error)
    ? `${error.status} ${error.statusText}`
    : "Something went wrong";

  return (
    <div className="flex min-h-[60vh] flex-col items-center justify-center gap-6 px-6 text-center">
      <p className="text-sm uppercase tracking-[0.4em] text-primary">Error</p>
      <h1 className="font-display text-4xl uppercase tracking-[0.3em] text-white">{message}</h1>
      <p className="max-w-md text-sm text-fg-muted">
        The page you were looking for may have moved. Return to the dashboard or contact our
        concierge team.
      </p>
      <Button asChild>
        <Link to="/">Back to home</Link>
      </Button>
    </div>
  );
}
