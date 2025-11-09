# 🚀 Guide de Démarrage Rapide - MyGym

## Démarrer le projet en 3 étapes

### ✅ Étape 1: XAMPP
```bash
# Ouvrir XAMPP Control Panel
# ✓ Démarrer Apache
# ✓ Démarrer MySQL
```

### ✅ Étape 2: React Frontend
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/MyGym/frontend

# Première fois seulement:
npm install

# À chaque fois:
npm run dev
```

### ✅ Étape 3: Ouvrir le navigateur
```
http://localhost:5173
```

---

## 🎯 URLs à connaître

| Description | URL |
|-------------|-----|
| **Application React** ⭐ | http://localhost:5173 |
| **Production (après build)** | http://localhost/MyGym/ |
| **APIs Backend** | http://localhost/MyGym/backend/api/ |
| **Admin Dashboard PHP** | http://localhost/MyGym/admin/ |

---

## 🔐 Comptes de test

### Admin
- **Email**: admin@mygym.com
- **Password**: admin123

### Coach
- **Email**: coach@mygym.com
- **Password**: coach123

### Member
- **Email**: member@mygym.com
- **Password**: member123

> **Note**: Si ces comptes n'existent pas, créez-les via la page register ou directement dans phpMyAdmin.

---

## 🎨 Thème actuel

**Rouge & Noir** - Professionnel et moderne
- Couleur primaire: `#dc2626` (Rouge)
- Background: `#0a0a0a` (Noir profond)
- Effets: Glassmorphism, gradients, animations

---

## 🛠️ Commandes utiles

### Développement
```bash
npm run dev          # Démarrer dev server
npm run lint         # Vérifier le code
npm run test         # Lancer les tests
```

### Production
```bash
npm run build        # Builder pour production
npm run preview      # Prévisualiser le build
```

### Debug
```bash
# Vérifier que les APIs fonctionnent
curl http://localhost/MyGym/backend/api/auth/me.php

# Voir les logs Apache
tail -f /Applications/XAMPP/xamppfiles/logs/error_log

# Libérer le port 5173
lsof -ti:5173 | xargs kill -9
```

---

## ⚠️ Problèmes courants

### "Port 5173 is in use"
→ Vite utilisera automatiquement le port 5174
→ Ou libérer le port: `lsof -ti:5173 | xargs kill -9`

### "Cannot connect to backend"
→ Vérifier que XAMPP Apache est démarré
→ Vérifier l'URL: `http://localhost/MyGym/backend/api/...`

### "Build Required" en production
→ Lancer: `npm run build` dans `/frontend`

### Erreurs CORS
→ Vérifier `backend/api/bootstrap.php`
→ Origines autorisées: localhost:5173, localhost:5174

---

## 📁 Fichiers de configuration

### Frontend
- `frontend/.env` - Variables d'environnement
- `frontend/vite.config.ts` - Config Vite + proxy
- `frontend/src/styles/global.css` - Thème global

### Backend
- `backend/db.php` - Connexion MySQL
- `backend/api/bootstrap.php` - CORS et headers
- `backend/auth.php` - Gestion sessions

---

## 🔄 Workflow typique

1. **Démarrer XAMPP** (Apache + MySQL)
2. **Démarrer React** (`npm run dev`)
3. **Développer** dans `frontend/src/`
4. **Tester** dans le navigateur
5. **Commit** les changements
6. **Builder** pour production si nécessaire

---

## 💡 Astuces

### React Hot Reload
Les modifications dans `src/` sont automatiquement reflétées dans le navigateur (pas besoin de rafraîchir).

### API Testing
Utilisez **Postman** ou **curl** pour tester les APIs:
```bash
# Test login
curl -X POST http://localhost/MyGym/backend/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"identifier":"admin@mygym.com","password":"admin123"}'
```

### DevTools
- **F12** pour ouvrir les DevTools
- **Console** pour voir les logs
- **Network** pour voir les requêtes API
- **React DevTools** extension recommandée

---

## 📞 Aide

**README complet**: `README.md`

**Documentation React**: https://react.dev
**Documentation Vite**: https://vitejs.dev
**Documentation PHP**: https://php.net

---

**Bon développement! 🏋️**
