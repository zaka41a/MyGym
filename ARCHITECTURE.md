# 🏗️ Architecture MyGym - Version Finale

## 📐 Vue d'ensemble

MyGym est une application **monopage (SPA)** construite avec React pour l'interface utilisateur et PHP pour les APIs backend.

```
┌─────────────────────────────────────────────────────────┐
│                  UTILISATEUR                            │
│                 (Navigateur Web)                        │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ HTTP/HTTPS
                     ▼
┌─────────────────────────────────────────────────────────┐
│              FRONTEND - REACT SPA                       │
│                                                         │
│  • React 18 + TypeScript                               │
│  • Vite (build tool)                                   │
│  • TailwindCSS (styling)                               │
│  • Zustand (state management)                          │
│  • React Router (navigation)                           │
│                                                         │
│  Port: 5173 (dev) ou /MyGym/frontend/ (prod)          │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ API REST (JSON)
                     ▼
┌─────────────────────────────────────────────────────────┐
│              BACKEND - PHP APIs                         │
│                                                         │
│  • PHP 7.4+                                            │
│  • PDO (MySQL)                                         │
│  • Sessions natives                                    │
│  • Password hashing (bcrypt)                           │
│                                                         │
│  Endpoints:                                            │
│  - /backend/api/auth/login.php                         │
│  - /backend/api/auth/register.php                      │
│  - /backend/api/auth/logout.php                        │
│  - /backend/api/auth/me.php                            │
│  - /backend/api/contact.php                            │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ SQL
                     ▼
┌─────────────────────────────────────────────────────────┐
│              DATABASE - MySQL                           │
│                                                         │
│  Tables principales:                                   │
│  - users (fullname, email, username, role, etc.)       │
│  - subscriptions (plan_id, user_id, status, etc.)      │
│  - courses (title, description, coach_id, etc.)        │
│  - contact_requests (full_name, email, goal, etc.)     │
└─────────────────────────────────────────────────────────┘
```

---

## 🎨 Thème & Design

### Couleurs principales
```css
Rouge primaire:    #dc2626
Rouge foncé:       #991b1b
Rouge secondaire:  #7f1d1d
Rouge accent:      #ef4444

Noir profond:      #0a0a0a
Noir moyen:        #1a1a1a

Texte blanc:       #f5f7fb
Texte grisé:       #9ca3af
```

### Effets visuels
- **Glassmorphism**: `backdrop-filter: blur(22px)` + transparence
- **Gradients radiaux**: Pour créer des halos de lumière rouge
- **Animations**: Framer Motion pour les transitions fluides
- **Blobs flottants**: Formes animées en arrière-plan
- **Noise overlay**: Texture subtile pour profondeur

---

## 📂 Structure des fichiers

### Frontend (`/frontend/`)
```
frontend/
├── src/
│   ├── components/          # Composants réutilisables
│   │   ├── layout/          # Navigation, Footer, PageShell
│   │   └── ui/              # Button, Input, Card, Badge, etc.
│   │
│   ├── pages/               # Pages de l'application
│   │   ├── HomePage.tsx     # Page d'accueil
│   │   ├── LoginPage.tsx    # Connexion
│   │   ├── RegisterPage.tsx # Inscription
│   │   ├── DashboardPage.tsx # Dashboard (ADMIN/COACH/MEMBER)
│   │   ├── AboutPage.tsx
│   │   ├── ServicesPage.tsx
│   │   ├── ContactPage.tsx
│   │   └── ErrorPage.tsx
│   │
│   ├── lib/
│   │   ├── api/             # Clients API
│   │   │   ├── client.ts    # Config de base (fetch wrapper)
│   │   │   ├── auth.ts      # Login, register, logout, whoami
│   │   │   └── contact.ts   # Contact form
│   │   │
│   │   ├── store/           # State management (Zustand)
│   │   │   ├── use-auth-store.ts  # Auth state global
│   │   │   └── use-nav-store.ts   # Navigation state
│   │   │
│   │   └── types/           # TypeScript types
│   │       └── user.ts      # UserProfile, UserRole
│   │
│   ├── routes/
│   │   └── router.tsx       # Configuration React Router
│   │
│   └── styles/
│       └── global.css       # Styles globaux + thème rouge/noir
│
├── dist/                    # Build de production (généré)
├── index.php                # Entry point XAMPP (sert le build)
├── .htaccess                # Routing Apache pour SPA
├── vite.config.ts           # Config Vite + proxy backend
├── .env                     # Variables d'environnement
└── package.json             # Dépendances npm
```

### Backend (`/backend/`)
```
backend/
├── api/
│   ├── auth/                # Authentification
│   │   ├── login.php        # POST - Login
│   │   ├── register.php     # POST - Register
│   │   ├── logout.php       # POST - Logout
│   │   └── me.php           # GET - Current user
│   │
│   ├── bootstrap.php        # Init API (CORS, headers JSON)
│   ├── helpers.php          # Fonctions utilitaires
│   └── contact.php          # POST - Contact form
│
├── auth.php                 # Gestion sessions PHP
├── db.php                   # Connexion MySQL (PDO)
├── login.php                # Legacy login endpoint
├── register.php             # Legacy register endpoint
└── logout.php               # Legacy logout endpoint
```

---

## 🔐 Flux d'authentification

### 1. **Inscription (Register)**
```
User → RegisterPage.tsx
     ↓ (form submit)
auth.ts → register()
     ↓ (POST /backend/api/auth/register.php)
register.php → Crée user dans DB
     ↓ (hash password, insert)
     ↓ (session PHP créée)
     ← {status: "ok", user: {...}}
auth-store → setUser(profile)
     ↓
navigate("/dashboard")
```

### 2. **Connexion (Login)**
```
User → LoginPage.tsx
     ↓ (form submit)
auth.ts → login()
     ↓ (POST /backend/api/auth/login.php)
login.php → Vérifie credentials
     ↓ (password_verify)
     ↓ (session PHP créée)
     ← {status: "ok", user: {...}}
auth-store → setUser(profile)
     ↓
navigate("/dashboard")
```

### 3. **Vérification au chargement (Hydrate)**
```
PageShell.tsx (useEffect)
     ↓
auth-store → hydrate()
     ↓ (GET /backend/api/auth/me.php)
me.php → Lit session PHP
     ↓ (session_start, $_SESSION['user'])
     ← {status: "ok", user: {...}}  OU  401 Unauthorized
auth-store → setUser(profile) ou null
```

### 4. **Déconnexion (Logout)**
```
User → Click "Logout"
     ↓
auth-store → signOut()
     ↓ (POST /backend/api/auth/logout.php)
logout.php → Détruit session
     ↓ (session_destroy)
     ← {status: "ok"}
auth-store → setUser(null)
     ↓
navigate("/")
```

---

## 🛣️ Routing

### Frontend (React Router)
```typescript
/ → HomePage               // Page d'accueil
/about → AboutPage         // À propos
/services → ServicesPage   // Services
/contact → ContactPage     // Contact
/login → LoginPage         // Connexion
/register → RegisterPage   // Inscription
/dashboard → DashboardPage // Dashboard (ADMIN/COACH/MEMBER)
```

### Backend (Apache .htaccess)
```apache
# Redirect root vers frontend
http://localhost/MyGym/ → /MyGym/frontend/

# APIs accessibles directement
/MyGym/backend/api/* → PHP APIs

# Dashboards PHP legacy
/MyGym/admin/* → Admin dashboard
/MyGym/coach/* → Coach dashboard
/MyGym/member/* → Member dashboard
```

---

## 🔄 Communication React ↔ PHP

### En développement
```
React Dev Server (port 5173/5174)
     ↓ Vite proxy
     ↓ /MyGym/backend/*
XAMPP Apache (port 80)
     ↓ PHP APIs
MySQL Database
```

**Configuration Vite**:
```typescript
server: {
  proxy: {
    '/MyGym/backend': {
      target: 'http://localhost',
      changeOrigin: true,
      secure: false
    }
  }
}
```

### En production
```
User Request: http://localhost/MyGym/
     ↓ .htaccess redirect
     ↓ /MyGym/frontend/
Apache sert index.php
     ↓
index.php sert dist/index.html (React build)
     ↓
React charge
     ↓ API calls
/MyGym/backend/api/* (PHP)
```

---

## 📊 Gestion des rôles

### Rôles disponibles
1. **ADMIN**: Accès total, gestion users/courses/subscriptions
2. **COACH**: Gestion cours, voir membres
3. **MEMBER**: Voir cours, s'abonner, profil

### Détection côté React
```typescript
// DashboardPage.tsx
if (user.role === "ADMIN") {
  // Afficher admin dashboard
} else if (user.role === "COACH") {
  // Afficher coach dashboard
} else {
  // Afficher member dashboard
}
```

### Protection côté PHP
```php
// backend/auth.php
function requireRole(string ...$allowedRoles): void {
  $user = currentUser();
  if (!$user) redirect_to_login();

  $role = normalize_role($user['role']);
  if (!in_array($role, $allowedRoles)) {
    http_response_code(403);
    die('Access denied');
  }
}
```

---

## 🚀 Déploiement

### Mode développement
```bash
# Terminal 1: XAMPP
# Démarrer Apache + MySQL

# Terminal 2: React
cd /Applications/XAMPP/xamppfiles/htdocs/MyGym/frontend
npm run dev

# Ouvrir: http://localhost:5173
```

### Mode production
```bash
# Builder React
cd frontend
npm run build

# Le build est dans dist/
# Apache sert via http://localhost/MyGym/
```

---

## 🔧 Configuration

### Variables d'environnement
**frontend/.env**:
```env
VITE_API_BASE_URL=http://localhost/MyGym/backend/api
```

### Base de données
**backend/db.php**:
```php
$host = 'localhost';
$db   = 'mygym';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
```

---

## 📈 Performance

### Optimisations frontend
- **Code splitting**: Vendor chunk séparé (React, Router)
- **Lazy loading**: Routes chargées à la demande
- **Tree shaking**: Code mort éliminé par Vite
- **Minification**: HTML/CSS/JS compressés

### Optimisations backend
- **Prepared statements**: Protection SQL injection + cache
- **Sessions**: Réutilisation connexions
- **PDO persistent**: `PDO::ATTR_PERSISTENT => true`

---

## 🔒 Sécurité

### Frontend
- ✅ Validation formulaires (Zod)
- ✅ Sanitization inputs
- ✅ HTTPS (recommandé en production)
- ✅ XSS protection (React échappe par défaut)

### Backend
- ✅ Password hashing (bcrypt)
- ✅ Prepared statements (PDO)
- ✅ CORS configuré
- ✅ Session management sécurisé
- ⚠️ CSRF tokens (à ajouter)
- ⚠️ Rate limiting (à ajouter)

---

## 📚 Stack technologique complète

### Frontend
- React 18.2
- TypeScript 5.6
- Vite 5.4
- TailwindCSS 3.4
- Framer Motion 11.3
- React Router 6.26
- Zustand 4.5
- React Hook Form 7.53
- Zod 3.23

### Backend
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+
- PDO
- Sessions natives

### DevOps
- XAMPP
- npm
- Git
- ESLint + Prettier

---

**Architecture professionnelle - Production ready! 🚀**
