# 🏋️ MyGym – Gym Management System

Application professionnelle de gestion de salle de sport avec **React frontend** (moderne) et **PHP backend** (APIs REST).

## 🎨 Architecture

### Frontend React (Moderne - Recommandé)
- **Framework**: React 18 + TypeScript
- **Build**: Vite 5.4
- **Styling**: TailwindCSS avec thème rouge/noir
- **State**: Zustand (authentication, navigation)
- **Router**: React Router v6
- **Animations**: Framer Motion

### Backend PHP
- **APIs REST**: JSON format avec CORS
- **Auth**: Sessions PHP + bcrypt hashing
- **Database**: MySQL via XAMPP/PDO
- **Security**: CSRF protection, input validation

---

## 🚀 Démarrage Rapide

### Mode Développement (⭐ Recommandé)

**1. Démarrer XAMPP**
```bash
# Lancer Apache et MySQL depuis XAMPP Control Panel
```

**2. Démarrer React**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/MyGym/frontend
npm install    # Première fois seulement
npm run dev    # Démarre sur http://localhost:5173
```

**3. Accéder à l'application**
- Frontend React: `http://localhost:5173`
- APIs Backend: `http://localhost/MyGym/backend/api/`

### Mode Production

```bash
# Builder React
cd frontend && npm run build

# Accéder via XAMPP
# http://localhost/MyGym/frontend/
```

---

## 📡 Endpoints API

### Authentification
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/backend/api/auth/login.php` | POST | Login avec identifier + password |
| `/backend/api/auth/register.php` | POST | Inscription (fullName, email, password) |
| `/backend/api/auth/logout.php` | POST | Déconnexion |
| `/backend/api/auth/me.php` | GET | Récupérer utilisateur actuel |

### Contact
| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/backend/api/contact.php` | POST | Soumettre formulaire contact |

---

## 🔗 Intégration React + PHP

### Comment ça marche ?

**En développement:**
- React (port 5173) → Vite proxy → Backend PHP (port 80)
- Configuration proxy dans `frontend/vite.config.ts`
- Variables d'environnement dans `frontend/.env`

**En production:**
- Build React dans `frontend/dist/`
- `frontend/index.php` sert le build via Apache
- APIs accessibles directement

### Configuration

**frontend/.env**
```env
VITE_API_BASE_URL=http://localhost/MyGym/backend/api
```

**frontend/vite.config.ts**
```typescript
server: {
  proxy: {
    '/MyGym/backend': {
      target: 'http://localhost',
      changeOrigin: true
    }
  }
}
```

---

## 🎨 Thème Rouge/Noir

### Couleurs principales
```css
--color-primary: #dc2626      /* Rouge */
--color-secondary: #7f1d1d    /* Rouge foncé */
--color-bg: #0a0a0a           /* Noir */
--color-bg-muted: #1a1a1a     /* Noir moyen */
```

### Localisation
- React: `frontend/src/styles/global.css`
- PHP: Inline CSS dans `index.php`, `login.php`, `register.php`

---

## 🚀 Features

### ✅ Implémentées
- 🔑 **Authentification complète**
  - Login/Register React + PHP
  - Sessions PHP sécurisées
  - Vérification automatique (whoami)
  - 3 rôles: ADMIN, COACH, MEMBER

- 🎨 **Interface moderne**
  - Design rouge/noir professionnel
  - Animations fluides (Framer Motion)
  - Responsive mobile-first
  - Glassmorphism effects

- 🏠 **Pages principales**
  - Home avec hero, features, pricing, testimonials
  - Login/Register avec validation
  - About, Services, Contact

- 📊 **Dashboards complets** ⭐ NEW!
  - Dashboard ADMIN (KPIs, activity, stats)
  - Dashboard COACH (sessions, members, actions)
  - Dashboard MEMBER (upcoming sessions, progress, recovery)
  - Sidebar navigation adaptée par rôle
  - Navbar automatiquement masquée sur dashboard

- 🔌 **APIs REST**
  - Auth complète (login, register, logout, me)
  - Contact form
  - CORS configuré

### 📋 Prochaines étapes
- 💳 Pages gestion abonnements (CRUD)
- 📅 Pages gestion cours (CRUD)
- 👤 Pages profil utilisateur (edit)
- 👥 Pages gestion users (ADMIN)

---

## 📁 Structure du Projet

```
MyGym/
├── frontend/                 # React App
│   ├── src/
│   │   ├── components/      # Composants réutilisables
│   │   ├── pages/           # Pages (Home, Login, Dashboard)
│   │   ├── lib/
│   │   │   ├── api/         # API clients
│   │   │   ├── store/       # Zustand stores
│   │   │   └── types/       # TypeScript types
│   │   └── styles/          # global.css (thème)
│   ├── dist/                # Build production
│   ├── index.php            # Entry point XAMPP
│   ├── .htaccess            # Apache routing
│   ├── vite.config.ts       # Config Vite
│   └── package.json
│
├── backend/
│   ├── api/                 # APIs REST
│   │   ├── auth/            # Authentification
│   │   ├── bootstrap.php    # Init (CORS, JSON)
│   │   ├── helpers.php      # Utils
│   │   └── contact.php
│   ├── auth.php             # Session management
│   ├── db.php               # MySQL connection
│   └── *.php                # Endpoints classiques
│
├── admin/                   # Dashboard Admin PHP
├── coach/                   # Dashboard Coach PHP
├── member/                  # Dashboard Member PHP
│
├── .htaccess                # Redirections Apache
├── README.md                # Ce fichier
└── QUICKSTART.md            # Guide rapide
```

---

## 🛠️ Commandes Utiles

### Développement
```bash
cd frontend
npm install          # Installer dépendances
npm run dev          # Dev server (http://localhost:5173)
npm run lint         # Linter
npm run test         # Tests
```

### Production
```bash
npm run build        # Builder pour production
npm run preview      # Prévisualiser le build
```

---

## 🐛 Dépannage

### Port 5173 occupé
```bash
# Vite choisira automatiquement 5174
# Ou libérer le port:
lsof -ti:5173 | xargs kill -9
```

### Erreur "Build Required"
```bash
cd frontend && npm run build
ls -la frontend/dist/  # Vérifier que le build existe
```

### APIs retournent 404
- ✓ XAMPP Apache démarré
- ✓ Chemin correct: `http://localhost/MyGym/backend/api/...`
- ✓ mod_rewrite activé dans Apache

### CORS errors
- Headers CORS dans `backend/api/bootstrap.php`
- Origines autorisées: localhost:5173, localhost:5174

---

## 📚 Technologies

**Frontend**
- React 18.2, TypeScript 5.6
- Vite 5.4, TailwindCSS 3.4
- Framer Motion, React Router 6
- Zustand, React Hook Form, Zod

**Backend**
- PHP 7.4+, MySQL
- PDO, Sessions natives
- Password hashing (bcrypt)

**DevOps**
- XAMPP (Apache + MySQL)
- npm, ESLint, Prettier, Vitest

---

## 🎯 Prochaines Étapes

1. **Migrer dashboards vers React**
   - Créer pages Dashboard React
   - APIs pour users, cours, abonnements
   - Tables et formulaires

2. **Sécurité**
   - Rate limiting
   - Refresh tokens
   - CSRF pour toutes APIs

3. **Performance**
   - Cache API queries
   - Image optimization
   - Lazy loading routes

---

## 📝 URLs Importantes

- **Application React** ⭐: http://localhost:5173 (développement)
- **Dashboard React**: http://localhost:5173/dashboard (après login)
- **Production**: http://localhost/MyGym/ (après build)
- **APIs Backend**: http://localhost/MyGym/backend/api/
- **Admin Dashboard PHP**: http://localhost/MyGym/admin/ (legacy, à remplacer)

---

**Développé avec ❤️ - Thème Rouge & Noir**
