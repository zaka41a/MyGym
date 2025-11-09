import { createBrowserRouter } from "react-router-dom";
import { PageShell } from "@/components/layout/PageShell";
import { DashboardLayout } from "@/components/layout/DashboardLayout";
import { HomePage } from "@/pages/HomePage";
import { AboutPage } from "@/pages/AboutPage";
import { ServicesPage } from "@/pages/ServicesPage";
import { ContactPage } from "@/pages/ContactPage";
import { LoginPage } from "@/pages/LoginPage";
import { RegisterPage } from "@/pages/RegisterPage";
import { DashboardPage } from "@/pages/DashboardPage";
import { ErrorPage } from "@/pages/ErrorPage";

const rawBase = import.meta.env.BASE_URL ?? "/";
const normalizedBase = rawBase.replace(/\/$/, "");

export const router = createBrowserRouter(
  [
    {
      element: <PageShell />,
      errorElement: <ErrorPage />,
      children: [
        { index: true, element: <HomePage /> },
        { path: "about", element: <AboutPage /> },
        { path: "services", element: <ServicesPage /> },
        { path: "contact", element: <ContactPage /> },
        { path: "login", element: <LoginPage /> },
        { path: "register", element: <RegisterPage /> }
      ]
    },
    {
      path: "dashboard",
      element: <DashboardLayout />,
      errorElement: <ErrorPage />,
      children: [
        { index: true, element: <DashboardPage /> }
      ]
    }
  ],
  {
    basename: normalizedBase || undefined
  }
);
