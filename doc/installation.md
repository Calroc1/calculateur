# Installation

### 1. Installation des dépendances PHP avec composer

Lancer la commande suivante :
```bash
composer update
```

### 2. Définition des variables d'environnement

Créer un fichier .env.local et y ajouter les variables suivantes avec les valeurs appropriées :

- **DATABASE_URL** : DSN de la base de données
- **BREVO_KEY**  : Clé API Brevo

### 3. Génération de la bdd

Lancer la commande suivante :
```bash
php bin/console d:s:u --force
```

### 4. Publication des asset

Lancer la commande suivante :
```bash
php bin/console assets:install
```

### 5. Chargement des supports de diffusion en bdd

Lancer la commande suivante :
```bash
php bin/console app:campaign:defaultize
```

### 6. Chargement des facteurs d'émission en bdd

Lancer la commande suivante :
```bash
php bin/console app:emission:defaultize
```

###  7. Configuration de la partie frontend

Se référer au chapitre [Configuration frontend](frontend.md) de la documentation.

