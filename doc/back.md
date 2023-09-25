## Interface administrateur

Il s'agit de l'interface d'administration du calculateur. Elle est accessible via le chemin suivant : `/superadmin` 

En terme de sécurité, le firewall "back" couvre cette interface et est accessible aux administrateurs (entité `Admin`).

Elle est composée de 4 rubriques :
- **Facteurs d'émissions**
- **Formulaires**
- **Organismes**
- **Administrateurs**

## Facteurs d'émissions

**Cette rubrique permet d'éditer les différents facteurs d'émission.** 
L'édition d'un facteur propose 4 modules :

### Informations

Ce module permet de modifier les informations liées au facteur (entité `Emission\Rate`).

### Valeur

Ce module permet de modifier la valeur d'un facteur (entité `Emission\Value`). Les anciennes valeurs sont conservées et sont consultables dans le tableau.

### Alerte

Ce module permet de définir une date d'alerte pour le facteur. **Ce module est inachevé, pour le moment aucune action n'est effectuée suite à l'enregistrement de la date.**

### Historique des mises à jour

Ce module permet de consulter l'historique des mises à jour effectuées sur le facteur d'émission.

## Formulaires

**La rubrique formulaire permet de gérer les différents supports de diffusions.** Cette rubrique est abordé en détail dans le chapitre [Supports de diffusion](doc/supports.md).

## Organismes

Cette rubrique est accessible via le chemin suivant : `/superadmin/entreprises`

**Elle permet de créer, d'éditer et supprimer des entreprises.** 
Pour chaque entreprise, il est possible de créer des organismes et des utilisateurs.

## Administrateurs

Cette rubrique est accessible **uniquement pour les superadmins** via le chemin suivant : `/superadmin/administrateurs`

**Elle permet de créer, d'éditer et supprimer des administrateurs.** 