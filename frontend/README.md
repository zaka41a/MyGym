# MyGym Frontend Pro

A fully redesigned MyGym frontend built with **React + TypeScript + Vite**, featuring a premium black/red aesthetic and production-ready tooling.

## ✨ Highlights

- 🖥️ **Modern SPA** with React Router, Zustand, React Hook Form & Zod
- 🎨 **Design system** powered by Tailwind CSS & shadcn-inspired components
- 💡 **Micro-interactions** via Framer Motion, glassmorphism cards, dramatic typography
- 🧪 **Quality tooling**: ESLint, Prettier, Husky, lint-staged, Vitest + Testing Library
- 🛠️ **DX ready**: absolute imports, environment support, Tailwind tokens, reusable UI primitives

## 🚀 Getting Started

```bash
npm install
npm run dev
```

Visit <http://localhost:5173/>.

If your PHP backend runs on another host/port, set the API base URL before starting dev:

```bash
cp .env.example .env
echo "VITE_API_BASE_URL=http://localhost/MyGym/backend/api" >> .env
npm run dev
```

## 🏗️ Structure

```
src/
  components/
    layout/      # Navigation, footer, shell
    sections/    # (extendable) hero/testimonial blocks
    ui/          # Buttons, cards, inputs using tailwind tokens
  hooks/         # Custom hooks
  lib/           # Zustand stores, helpers, zod validators
  pages/         # Page-level views (Home, About, Services, Contact, Login, Error)
  routes/        # Router configuration + basename handling
  styles/        # Tailwind + CSS variables theme
```

## 📦 Scripts

| Command        | Description                            |
| -------------- | -------------------------------------- |
| `npm run dev`  | Start Vite dev server                  |
| `npm run build`| Build for production (base `/MyGym/frontend/`) |
| `npm run preview` | Preview built app locally          |
| `npm run lint` | Run ESLint                            |
| `npm run format` | Prettier format                      |
| `npm run test` | Vitest unit tests                      |

### Backend integration

- API calls default to `/MyGym/backend/api`. During local dev we proxy to `http://localhost/MyGym/backend/api`.
- Required PHP endpoints (created in `backend/api`):
  - `auth/login.php`, `auth/register.php`, `auth/logout.php`, `auth/me.php`
  - `contact.php` for concierge form submissions (persists to `contact_requests` table)
- Responses follow `{ status: "ok", user: {...} }`—see `src/lib/api/auth.ts` for typing.
- Sessions rely on the existing PHP session logic (`backend/auth.php`), so cookies must be accepted.

## 🧩 Tech Stack

- **React 18**, **TypeScript**, **Vite**
- **Tailwind CSS**, **tailwindcss-animate**, **lucide-react** icons
- **React Router v6**, **Zustand** state, **react-hook-form + Zod**
- **Framer Motion** for hero/panel animations
- **ESLint (flat config)**, **Prettier**, **Husky**, **lint-staged**
- **Vitest** + **@testing-library/react** for component testing

## 🔐 Environment

Add environment variables to a `.env` file if needed. `__APP_ENV__` is defined via `vite.config.ts` for runtime checks.

## 📄 License

MIT © MyGym Performance Club
