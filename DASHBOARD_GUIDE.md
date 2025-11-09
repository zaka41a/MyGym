# 🎯 Guide des Dashboards MyGym

## 📊 Vue d'ensemble

Les dashboards React sont maintenant entièrement intégrés avec **sidebar navigation** et **sans navbar** pour une expérience immersive.

---

## 🏗️ Architecture Dashboard

### Structure des fichiers

```
frontend/src/
├── components/layout/
│   ├── DashboardSidebar.tsx    # Sidebar avec navigation par rôle
│   ├── DashboardLayout.tsx     # Layout wrapper avec auth
│   └── Navigation.tsx           # Masqué sur /dashboard/*
│
├── pages/
│   └── DashboardPage.tsx        # Dashboard adapté par rôle
│
└── routes/
    └── router.tsx               # Routes séparées pour dashboard
```

---

## 🎨 Dashboards par Rôle

### 1. **ADMIN Dashboard**

**Accès**: Après login avec rôle ADMIN
**URL**: `http://localhost:5174/dashboard`

**Features**:
- ✅ 4 KPI cards (Members, Revenue, Coaches, Sessions)
- ✅ Recent Activity feed
- ✅ Quick Stats avec progress bars
- ✅ Sidebar avec liens vers Users, Courses, Subscriptions, Analytics

**Liens Sidebar**:
- Overview (`/dashboard`)
- Users (`/dashboard/users`)
- Courses (`/dashboard/courses`)
- Subscriptions (`/dashboard/subscriptions`)
- Analytics (`/dashboard/analytics`)

---

### 2. **COACH Dashboard**

**Accès**: Après login avec rôle COACH
**URL**: `http://localhost:5174/dashboard`

**Features**:
- ✅ 3 stats cards (Sessions Today, Active Members, Avg Attendance)
- ✅ Today's Sessions avec horaires
- ✅ Priority Actions checklist
- ✅ Sidebar avec liens vers Sessions, Members, Courses

**Liens Sidebar**:
- Overview (`/dashboard`)
- My Sessions (`/dashboard/sessions`)
- My Members (`/dashboard/members`)
- Courses (`/dashboard/courses`)
- Profile (`/dashboard/profile`)

---

### 3. **MEMBER Dashboard**

**Accès**: Après login avec rôle MEMBER
**URL**: `http://localhost:5174/dashboard`

**Features**:
- ✅ 3 stats cards (Next Session, This Week, Progress)
- ✅ Upcoming Sessions list
- ✅ This Week's Focus
- ✅ Recovery Checklist
- ✅ Sidebar avec liens vers Courses, Subscription, Profile

**Liens Sidebar**:
- Overview (`/dashboard`)
- Available Courses (`/dashboard/courses`)
- Subscription (`/dashboard/subscribe`)
- My Profile (`/dashboard/profile`)

---

## 🔐 Flux d'Authentification

### 1. **Login**
```
User entre credentials → LoginPage
     ↓
API POST /backend/api/auth/login.php
     ↓
Session PHP créée + user data retourné
     ↓
useAuthStore.setUser(profile)
     ↓
navigate("/dashboard")
```

### 2. **Dashboard Load**
```
DashboardLayout component monte
     ↓
useAuthStore.hydrate() si pas déjà fait
     ↓
API GET /backend/api/auth/me.php
     ↓
Si authentifié → Affiche dashboard selon rôle
Si non authentifié → Redirect vers /login
```

### 3. **Logout**
```
User clique "Logout" dans sidebar
     ↓
useAuthStore.signOut()
     ↓
API POST /backend/api/auth/logout.php
     ↓
Session PHP détruite
     ↓
window.location.href = "/"
```

---

## 🎨 Design System

### Couleurs Dashboard
```css
/* Cards */
background: rgba(255, 255, 255, 0.05)
border: rgba(255, 255, 255, 0.1)

/* Sidebar */
background: gradient noir avec transparence
border-right: rgba(255, 255, 255, 0.1)

/* Active Link */
background: gradient primary/accent avec 20% opacity
shadow: primary/10

/* Hover States */
hover:bg-white/8
hover:border-primary/40
```

### Spacing
- Sidebar width: `16rem` (64px * 4)
- Main content: `ml-64` (décalé de la sidebar)
- Padding: `px-8 py-8`

---

## 📱 Responsive Design

### Desktop (≥1024px)
- Sidebar visible (fixed)
- Content décalé de 64px (ml-64)
- Grid layouts: 2-4 colonnes

### Tablet (768px - 1023px)
- Sidebar collapsible (à implémenter)
- Content full width quand sidebar fermée
- Grid layouts: 2 colonnes

### Mobile (<768px)
- Sidebar en overlay (à implémenter)
- Content full width
- Grid layouts: 1 colonne

---

## 🚀 Utilisation

### Démarrer l'application

1. **XAMPP**: Apache + MySQL running
2. **Terminal**:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/MyGym/frontend
npm run dev
```
3. **Navigateur**: `http://localhost:5174`

### Tester les dashboards

**Option 1: Créer un compte**
1. Aller sur `/register`
2. S'inscrire (sera créé comme MEMBER par défaut)
3. Login → Redirigé vers dashboard MEMBER

**Option 2: Modifier le rôle en DB**
```sql
-- Promouvoir en ADMIN
UPDATE users SET role = 'ADMIN' WHERE email = 'ton@email.com';

-- Promouvoir en COACH
UPDATE users SET role = 'COACH' WHERE email = 'ton@email.com';
```

### Navigation dans le dashboard

- **Sidebar toujours visible** sur toutes les pages `/dashboard/*`
- **Navbar masquée** automatiquement sur `/dashboard/*`
- **Click sur liens sidebar** → Navigation sans reload
- **Click sur "Logout"** → Déconnexion + redirect home

---

## 🔧 Customisation

### Ajouter une nouvelle route dashboard

**1. Créer la page**
```typescript
// src/pages/dashboard/UsersPage.tsx
export function UsersPage() {
  return (
    <div>
      <h1>Users Management</h1>
      {/* Content */}
    </div>
  );
}
```

**2. Ajouter la route**
```typescript
// src/routes/router.tsx
{
  path: "dashboard",
  element: <DashboardLayout />,
  children: [
    { index: true, element: <DashboardPage /> },
    { path: "users", element: <UsersPage /> }  // ← Nouvelle route
  ]
}
```

**3. Lien dans sidebar**
```typescript
// src/components/layout/DashboardSidebar.tsx
const adminLinks = [
  // ...
  { href: "/dashboard/users", icon: Users, label: "Users" }
];
```

---

## 📊 Prochaines étapes

### À implémenter

1. **Pages dashboard supplémentaires**
   - `/dashboard/users` (ADMIN)
   - `/dashboard/courses` (ALL)
   - `/dashboard/subscriptions` (ADMIN)
   - `/dashboard/profile` (ALL)

2. **APIs Backend**
   - `GET /backend/api/users` - Liste utilisateurs
   - `GET /backend/api/courses` - Liste cours
   - `GET /backend/api/subscriptions` - Liste abonnements
   - `PUT /backend/api/users/:id` - Modifier utilisateur

3. **Features avancées**
   - Sidebar collapsible (mobile)
   - Dark mode toggle
   - Notifications en temps réel
   - Export données (CSV, PDF)

---

## 🐛 Dépannage

### Navbar toujours visible sur dashboard
→ Vérifier que `Navigation.tsx` contient:
```typescript
if (location.pathname.startsWith("/dashboard")) {
  return null;
}
```

### Redirect loop après login
→ Vérifier que l'API `/auth/me.php` retourne bien le user
→ Vérifier que `useAuthStore.setUser()` est appelé après login

### Sidebar ne s'affiche pas
→ Vérifier que `DashboardLayout` est bien le parent
→ Vérifier que le user est authentifié

### Liens sidebar ne fonctionnent pas
→ Vérifier que les routes sont définies dans `router.tsx`
→ Vérifier que les chemins commencent par `/dashboard/`

---

## 📚 Ressources

- **React Router**: https://reactrouter.com
- **Zustand (State)**: https://github.com/pmndrs/zustand
- **Lucide Icons**: https://lucide.dev
- **TailwindCSS**: https://tailwindcss.com

---

**Dashboards professionnels prêts! 🎉**

**URL principale**: http://localhost:5174/dashboard
