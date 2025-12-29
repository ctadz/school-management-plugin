# 📘 School Management Plugin

[![Version](https://img.shields.io/badge/version-0.5.5-blue.svg)](https://github.com/ctadz/school-management-plugin/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](LICENSE)
[![WordPress](https://img.shields.io/badge/wordpress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://php.net/)

> **Status:** 🚧 Active Development

**This plugin is under active development for [Cyber Tech Academy](https://ctadz.org).**
While the code is open source (GPL-2.0+), this is currently a **work in progress**.

- ⚠️ Not yet ready for production use by third parties
- 🚧 Breaking changes may occur during development phase (0.x.x versions)
- 📧 Official support only available for licensed installations
- 🌍 Available in **English**, **French** (Français), and soon **Arabic** (العربية)

**Interested in using this for your school?** Contact us: **[info@ctadz.org](mailto:info@ctadz.org)**

---

## 🌐 Language Support

This plugin is fully internationalized and currently supports:

- 🇬🇧 **English** - Default language
- 🇫🇷 **Français** - Complete translation (100%)
- 🇸🇦 **العربية (Arabic)** - Coming soon

All UI elements, admin pages, and user-facing messages are translatable.

---

## 🇫🇷 Version Française

### 📖 Description

Un plugin WordPress complet pour gérer les étudiants, les cours, les emplois du temps, les présences et les paiements dans les écoles privées et centres de formation.

Développé pour **Cyber Tech Academy**, ce plugin offre une solution tout-en-un pour la gestion scolaire avec prise en charge multilingue.

### ✨ Fonctionnalités Principales

#### 📚 Gestion Académique
- **Étudiants** : Inscriptions, profils détaillés, codes étudiants uniques, photos
- **Enseignants** : Profils, taux horaires, modalités de paiement
- **Cours** : Gestion complète (français & anglais, tous niveaux)
- **Niveaux** : Système flexible de niveaux académiques
- **Salles de classe** : Attribution et gestion des espaces

#### 💰 Gestion Financière
- **Inscriptions & Paiements** : Suivi automatisé des frais de scolarité
- **Échéanciers de paiement** : Plans mensuels, trimestriels, personnalisés
- **Remises familiales** : Calcul automatique pour les familles avec plusieurs étudiants
- **Alertes de paiement** : Notifications pour les paiements en retard
- **Rapports financiers** : États détaillés et exports

#### 📅 Emploi du Temps & Présences
- **Calendrier hebdomadaire** : Vue d'ensemble des cours et enseignants
- **Suivi des présences** : Interface colorée et intuitive
- **Événements scolaires** : Gestion du calendrier académique

#### 🔐 Rôles & Permissions
- **Administrateur École** : Rôle WordPress personnalisé avec permissions granulaires
- **Enseignants** : Accès limité aux informations pertinentes
- **Étudiants** : Portail dédié (via extension Student Portal)

#### 🎨 Interface Utilisateur
- Design moderne et responsive
- Interface CRUD basée sur shortcode
- Tableaux de bord interactifs
- Mode sombre (à venir)

### 🔌 Extensions Disponibles

Le système principal peut être étendu avec :

- **[School Management - Calendar & Schedule](https://github.com/ctadz/school-management-calendar)** : Calendrier avancé et gestion des emplois du temps
- **[School Management - Student Portal](https://github.com/ctadz/school-management-student-portal)** : Portail étudiant avec authentification personnalisée

### 🚀 Installation

1. **Télécharger le plugin :**
   ```bash
   cd wp-content/plugins
   git clone https://github.com/ctadz/school-management-plugin.git school-management
   ```

2. **Activer le plugin :**
   - Aller dans **WordPress Admin → Extensions**
   - Activer **School Management**

3. **Configuration :**
   - Le plugin crée automatiquement les tables de base de données
   - Accéder au menu **Gestion Scolaire** dans l'admin WordPress
   - Configurer les niveaux, salles de classe et modalités de paiement

### 🔄 Mises à Jour Automatiques

Le plugin supporte les mises à jour automatiques via GitHub Releases.
WordPress vérifie les nouvelles versions toutes les 12 heures et affiche les notifications de mise à jour.

### 🛠️ Développement

```
main    → Branche stable (production)
develop → Développement actif
feature/ → Nouvelles fonctionnalités
```

**Contribuer :**
- Ce projet utilise [Semantic Versioning](https://semver.org/)
- Les Pull Requests sont les bienvenues
- Merci de tester vos modifications avant de soumettre

### 📄 Licence

GPL-2.0+ - Ce plugin est un logiciel libre, vous pouvez le redistribuer et/ou le modifier selon les termes de la Licence Publique Générale GNU.

---

## 🇬🇧 English Version

### 📖 Description

A comprehensive WordPress plugin for managing students, courses, schedules, attendance, and payments in private schools and training centers.

Built for **Cyber Tech Academy**, this plugin provides an all-in-one solution for school management with multilingual support.

### ✨ Key Features

#### 📚 Academic Management
- **Students**: Registration, detailed profiles, unique student codes, photos
- **Teachers**: Profiles, hourly rates, payment terms
- **Courses**: Complete management (French & English, all levels)
- **Levels**: Flexible academic level system
- **Classrooms**: Space allocation and management

#### 💰 Financial Management
- **Enrollments & Payments**: Automated tuition fee tracking
- **Payment Schedules**: Monthly, quarterly, and custom plans
- **Family Discounts**: Automatic calculation for families with multiple students
- **Payment Alerts**: Notifications for overdue payments
- **Financial Reports**: Detailed statements and exports

#### 📅 Scheduling & Attendance
- **Weekly Calendar**: Overview of courses and teachers
- **Attendance Tracking**: Color-coded intuitive interface
- **School Events**: Academic calendar management

#### 🔐 Roles & Permissions
- **School Administrator**: Custom WordPress role with granular permissions
- **Teachers**: Limited access to relevant information
- **Students**: Dedicated portal (via Student Portal add-on)

#### 🎨 User Interface
- Modern and responsive design
- Shortcode-based CRUD interface
- Interactive dashboards
- Dark mode (coming soon)

### 🔌 Available Add-ons

The core system can be extended with:

- **[School Management - Calendar & Schedule](https://github.com/ctadz/school-management-calendar)**: Advanced calendar and timetable management
- **[School Management - Student Portal](https://github.com/ctadz/school-management-student-portal)**: Student portal with custom authentication

### 🚀 Installation

1. **Download the plugin:**
   ```bash
   cd wp-content/plugins
   git clone https://github.com/ctadz/school-management-plugin.git school-management
   ```

2. **Activate the plugin:**
   - Go to **WordPress Admin → Plugins**
   - Activate **School Management**

3. **Configuration:**
   - The plugin automatically creates database tables
   - Access the **School Management** menu in WordPress admin
   - Configure levels, classrooms, and payment terms

### 🔄 Automatic Updates

The plugin supports automatic updates via GitHub Releases.
WordPress checks for new versions every 12 hours and displays update notifications.

### 🛠️ Development Workflow

```
main    → Stable, production-ready branch
develop → Active development branch
feature/ → New feature branches
```

**Contributing:**
- This project uses [Semantic Versioning](https://semver.org/)
- Pull Requests are welcome
- Please test your changes before submitting

### 📄 License

GPL-2.0+ - This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License.

---

## 📊 Versioning

We use [Semantic Versioning](https://semver.org/) for release management:

- **0.x.x** = Development phase (current)
- **1.0.0** = First stable production release
- **1.x.x** = Feature releases
- **x.x.1** = Bug fixes and patches

### Current Version: 0.5.5

**Recent Updates:**
- ✅ French translations for payment alerts and family discounts
- ✅ Automatic update system via GitHub
- ✅ Family discount calculator with bulk recalculation tools
- ✅ Deactivation dependency protection for add-ons
- 🚧 Responsive design improvements (in progress)
- 🔜 Arabic translation (coming soon)

See [CHANGELOG.md](CHANGELOG.md) for full version history.

---

## 🤝 Support & Contact

- **Official Website:** [https://ctadz.org](https://ctadz.org)
- **Support Email:** [info@ctadz.org](mailto:info@ctadz.org)
- **Issues:** [GitHub Issues](https://github.com/ctadz/school-management-plugin/issues)
- **Releases:** [GitHub Releases](https://github.com/ctadz/school-management-plugin/releases)

---

## 🙏 Credits

**Developed by:** [CTADZ (Cyber Tech Academy)](https://ctadz.org)
**Author:** Ahmed Sebaa
**License:** GPL-2.0+
**Repository:** [github.com/ctadz/school-management-plugin](https://github.com/ctadz/school-management-plugin)

---

## 📸 Screenshots

![School Management Dashboard](screenshots/dashboard.png)
*Coming soon: Dashboard overview*

---

## ⚠️ Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **Recommended:** PHP 8.0+, WordPress 6.4+

---

## 🔒 Security

If you discover a security vulnerability, please email [info@ctadz.org](mailto:info@ctadz.org) instead of using the issue tracker.

---

**Made with ❤️ for educational institutions by [Cyber Tech Academy](https://ctadz.org)**
