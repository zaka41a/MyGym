# 🏋️ MyGym - Guide d'Utilisation

## ✨ Nouveau Design Premium !

Votre projet MyGym a été complètement redesigné avec un **thème premium moderne** :

### 🎨 **Caractéristiques du Design**
- 💜 **Couleurs** : Violet et Rose (gradient premium)
- 🌙 **Mode Sombre** : Toggle disponible dans chaque dashboard
- ✨ **Animations** : Effets de survol fluides et élégants
- 📱 **Responsive** : S'adapte à tous les écrans

---

## 🌐 **URLs Correctes à Utiliser**

### 📍 **Pages Publiques**
```
✅ Page d'accueil    : http://localhost/MyGym/
✅ Login (Connexion)  : http://localhost/MyGym/login.php
✅ Register (Inscription) : http://localhost/MyGym/register.php
```

### 🔐 **Dashboards (après connexion)**
```
✅ Admin Dashboard   : http://localhost/MyGym/admin/index.php
✅ Coach Dashboard   : http://localhost/MyGym/coach/index.php
✅ Member Dashboard  : http://localhost/MyGym/member/index.php
```

### ⚠️ **URLs Obsolètes (NE PLUS UTILISER)**
```
❌ http://localhost:5173/... (Frontend React - désactivé)
❌ http://localhost/MyGym/frontend/login/... (Ancien chemin)
```

---

## 🚀 **Comment Démarrer**

### 1️⃣ **Première Visite**
1. Ouvrez votre navigateur
2. Allez sur : `http://localhost/MyGym/`
3. Cliquez sur **"Get Started"** ou **"Create Account"**

### 2️⃣ **Créer un Compte**
1. Remplissez le formulaire d'inscription
2. Tous les champs sont obligatoires
3. Le mot de passe doit faire au moins 6 caractères
4. Après inscription, vous serez automatiquement connecté

### 3️⃣ **Se Connecter**
1. Allez sur : `http://localhost/MyGym/login.php`
2. Entrez votre **username** ou **email**
3. Entrez votre **mot de passe**
4. Vous serez redirigé vers votre dashboard selon votre rôle

---

## 👥 **Rôles et Accès**

### 🔵 **MEMBER (Membre)**
**Dashboard** : `/MyGym/member/index.php`

**Fonctionnalités** :
- ✅ Voir ses cours réservés
- ✅ Gérer son abonnement (Basic/Plus/Pro)
- ✅ Réserver des cours (si Plan Plus/Pro)
- ✅ Modifier son profil
- ✅ Voir les jours restants d'abonnement

### 🟢 **COACH (Entraîneur)**
**Dashboard** : `/MyGym/coach/index.php`

**Fonctionnalités** :
- ✅ Voir ses prochains cours
- ✅ Gérer son planning
- ✅ Voir ses membres assignés
- ✅ Créer/Modifier/Supprimer des créneaux
- ✅ Voir les statistiques de ses cours

### 🔴 **ADMIN (Administrateur)**
**Dashboard** : `/MyGym/admin/index.php`

**Fonctionnalités** :
- ✅ Gérer tous les utilisateurs (CRUD)
- ✅ Approuver/Rejeter les demandes d'abonnement
- ✅ Voir les statistiques globales
- ✅ Gérer les activités et sessions
- ✅ Voir les revenus mensuels

---

## 🎨 **Mode Sombre**

### Comment l'activer ?
1. Connectez-vous à votre dashboard
2. En haut à droite, cliquez sur le **bouton toggle** (rond)
3. Le thème change automatiquement
4. Votre préférence est sauvegardée dans votre navigateur

---

## 🔐 **Sécurité**

### ✅ **Protections Implémentées**
- 🔒 Protection CSRF sur tous les formulaires
- 🔑 Mots de passe hashés (bcrypt)
- 🚪 Contrôle d'accès par rôle
- 🔐 Sessions sécurisées
- ✉️ Validation des emails

---

## 🛠️ **Structure du Projet**

```
MyGym/
├── index.php           ← Page d'accueil (Landing Page)
├── login.php           ← Page de connexion
├── register.php        ← Page d'inscription
├── .htaccess          ← Redirections automatiques
│
├── admin/             ← Zone Admin
│   ├── index.php      ← Dashboard Admin
│   ├── users.php      ← Gestion utilisateurs
│   ├── courses.php    ← Gestion cours
│   └── subscriptions.php ← Gestion abonnements
│
├── coach/             ← Zone Coach
│   ├── index.php      ← Dashboard Coach
│   ├── courses.php    ← Mes cours
│   ├── members.php    ← Mes membres
│   └── profile.php    ← Mon profil
│
├── member/            ← Zone Membre
│   ├── index.php      ← Dashboard Membre
│   ├── courses.php    ← Mes réservations
│   ├── subscribe.php  ← Mon abonnement
│   └── profile.php    ← Mon profil
│
└── backend/           ← Traitement PHP
    ├── login.php      ← Traitement connexion
    ├── register.php   ← Traitement inscription
    ├── logout.php     ← Déconnexion
    ├── auth.php       ← Authentification
    └── db.php         ← Base de données
```

---

## 🐛 **Résolution de Problèmes**

### ❓ **Je vois "404 Not Found"**
→ Vérifiez que vous utilisez les bons chemins :
- ✅ `http://localhost/MyGym/`
- ❌ PAS `http://localhost:5173/`

### ❓ **"Access Denied"**
→ Vous essayez d'accéder à une zone interdite pour votre rôle
→ Exemple : Un MEMBER ne peut pas accéder à `/admin/`

### ❓ **Le mode sombre ne fonctionne pas**
→ Vérifiez que JavaScript est activé
→ Essayez de vider le cache : `Ctrl + F5`

### ❓ **"Username or email already exists"**
→ Ce compte existe déjà
→ Utilisez un autre username/email ou connectez-vous

---

## 📞 **Besoin d'Aide ?**

### 🔧 **Problèmes Techniques**
1. Vérifiez que XAMPP est démarré (Apache + MySQL)
2. Vérifiez que la base de données existe
3. Consultez les logs : `/Applications/XAMPP/logs/`

### 📧 **Contact Support**
- Email fictif : support@mygym.com
- Documentation : README.md

---

## 🎉 **Fonctionnalités Ajoutées**

### ✨ **Nouvelles Pages**
- 🏠 Page d'accueil moderne avec animations
- 🔐 Pages Login/Register avec design premium
- 🎨 Thème violet/rose cohérent partout
- 🌙 Mode sombre sur tous les dashboards

### 🔧 **Corrections**
- ✅ Tous les chemins corrigés
- ✅ Redirections automatiques (`.htaccess`)
- ✅ Messages d'erreur clairs
- ✅ Validation des formulaires

---

## 📝 **Changelog**

### Version 2.0 (Aujourd'hui)
- ✨ Nouveau design premium violet/rose
- 🌙 Mode sombre ajouté
- 🎨 Animations et effets de survol
- 🔧 Correction de tous les chemins
- 📱 Design 100% responsive
- 🔐 Sécurité renforcée

### Version 1.0 (Avant)
- ❌ Design rouge basique
- ❌ Pas de mode sombre
- ❌ Chemins mélangés (React + PHP)
- ❌ Peu d'animations

---

## 🎯 **Prochaines Étapes**

Vous pouvez maintenant :
1. ✅ Créer un compte de test
2. ✅ Tester les 3 rôles (Admin, Coach, Member)
3. ✅ Explorer le mode sombre
4. ✅ Personnaliser les couleurs si besoin

---

**🏋️ Bon entraînement avec MyGym !** 💪
