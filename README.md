# CALCULATEUR BILOBAY

Le calculateur Bilobay est une application développée sous **Symfony 4.4** prévue pour fonctionner sous **PHP 7.4**.
La structure classique du framework est utilisée et le code est documenté avec *PhpDocumentor*.

Le calculateur est composé de 2 interfaces :
- Interface utilisateur
- Interface Administrateur

## Chapitres de la documentation

Il est préférable de lire au préalable la partie "**Concepts importants**" ci-dessous avant de consulter les autres chapitres.

1. [Installation](doc/installation)
2. [Configuration Frontend](doc/frontend.md)
3. [Interface Utilisateur](doc/front.md)
4. [Interface Administrateur](doc/back.md)
5. [Supports de diffusion](doc/supports.md)

## Concepts importants

### Organismes

=> Entité `Organisme`"

Les organismes représentent les différents "environnements" de la plateforme. Ils sont les propriétaires des campagnes et des utilisateurs.
Ils sont hiérarchisés sur deux niveaux de la manière suivante :
- Niveau 0 = **Entreprise**
- Niveau 1 = **Organisation**

Une entreprise peut donc contenir des organismes enfants appelés "Organisations", et est de donc de manière récursive propriétaire de leurs données.

Lorsqu'un utilisateur se connecte ([Interface Utilisateur](doc/front.md)), il n'a accès qu'aux données de son organisme. (= plateforme dans le jargon Bilobay)

### Utilisateurs

=> Entité `User`

Il existe 3 profils d'utilisateurs :
- **Administrateur** (à ne pas confondre avec les Administrateurs de l'interface Back)
- **Adjoint**
- **Invité**

Ces profils permettent de restreindre les fonctionnalités accessibles aux utilisateurs ([Interface Utilisateur](doc/front.md)). Ces restrictions sont définies dans les Voters de l'application.

Un utilisateur rattaché à une entreprise aura également accès aux données des organisations de cette dernière, en fonction de son profil.

### Campagnes

=> Entité `Campaign\Campaign`

Les campagnes sont les principales données des organismes. Elle permettent aux utilisateurs d'une organisation, après avoir renseigné les données d'une campagne, d'obtenir des stastistiques sur l'impact carbone de celle-ci (exprimés en tonnes equi. CO₂ ou Kg equi. CO₂).

Chaque campagne est caractérisée par différentes propriétés (Nom, date de début, date de fin, Pays, etc) et contient systématiquement une variante "Master". Elle peut également contenir d'autres variantes (3 maximum).

Une campagne peut passer par 4 statuts : 
- "Estimation en cours" = si la campagne est créée
- "Campagne archivée" = si la campagne a été créée il y a plus de 365 jours
- "Post-campagne" = si la date de fin de la campagne est dépassée
- "Estimation terminé" = si la campagne a été flagguée comme "terminée"

### Variantes

=> Entité `Campaign\Variant`

Une variante de campagne contient son propre jeu de données renseigné par les utilisateurs (= données de campagne), et par conséquent ses propres statistiques calculées. Les variantes permettent donc, pour une même campagne, d'obtenir différents résultats.

### Support de diffusion

=> Entité `Support\Support`

Un support de diffusion représente une étape d'une campagne et est composé d'un formulaire (en fait différents "éléments de formulaires"), et d'un algorithme (en fait plusieurs "formules"). Ces derniers peuvent-être gerés depuis l'interface Administrateur. 

L'impact carbone d'un support est donc calculé grâce à son algorithme, et ce dernier est alimenté avec les données renseignées par un utilisateur dans son formulaire et par les facteurs d'émission. 

Lorsqu'un support est activé pour une variante de campagne, celui-ci est alors ajouté dans les étapes de la campagne, et il est alors possible de visualiser et de compléter le formulaire du support.

Un support de diffusion est également associé à un référentiel (Entité `Support\Referential`) et à un type de support (Entité `Support\Type`) à des fins d'affichage.

Les supports de diffusion sont abordés plus en détail dans la partie 5. [Supports de diffusion](doc/supports.md).

### Elément de formulaire

=> Entité `Support\FormElement`

Les éléments de formulaires permettent de constituer le formulaire d'un support de diffusion. Ils peuvent être de différents types (section, liste déroulante, champ numérique, collection, etc) et disposent chacun de leur propre configuration. 

Ils sont organisés sur différents niveaux, ces derniers permettant de définir leur apparence au niveau de l'affichage. Ce dernier est géré grâce au système de templating de symfony dans le fichier 
`front/forms/campaign.html.twig`.

Lorsqu'ils sont complétés par les utilisateurs, les données sont alors enregistrées (Entité `Campaign\Data`).

Les éléments de formulaires sont abordés plus en détail dans la partie [Supports de diffusion](doc/supports.md).

### Formule (algorithme)

=> Entité `Support\Formula`

Les formules permettent de constituer l'algorithme d'un support de diffusions. Lorsqu'elles sont exécutées, elles sont alimentées ave les données de formulaires et les facteurs d'émission pour calculer un impact carbone.

Chaque formule peut contenir ses sous-formules, ses variables intermédiaire, et fait forcément référence à un élement de formulaire.

Les formules sont abordées plus en détail dans la partie [Supports de diffusion](doc/supports.md).

### Facteurs d'émission

=> Entité `Campaign\FormElement`

Les facteurs d'émission sont des variables définies par les administrateurs qui peuvent ensuite être exploitées dans les algorithmes des supports de diffusion.

Chaque facteur d'émission contient une valeur (Entité `Emission\Value`) et est définie par différentes propriétés (unité de la valeur, remarque, source, etc). Un facteur d'émission peut également contenir des facteurs enfants, afin de consituer des groupes.

### Administrateurs

Un administrateur (à ne pas confondre avec les utilisateurs de profil "Administrateur") ont accès à une interface spécifique ([Interface Administrateur](doc/back.md)) leur permettant de gérer différentes choses :
- Organismes et leurs utilisateurs
- Facteurs d'émissions
- Support de diffusion et leurs formulaires et algorithmes

Un administrateur peut également posséder des droits plus élévés s'il est "superadmin" : définir par la propriété booléene "super".

## Commandes utiles

Tous les commandes doivent être prefixées par `php bin/console ` et lancées depuis le répertoire racine du projet.

| Commande  | Action | Options
| ------------- | ------------- | ------------- |
| `d:s:u --force`  | Mettre à jour la bdd  |  |
| `app:campaign:defaultize` | Installation (ou mise à jour) des supports | `--purge` > Pour supprimer au préalable tous les supports |
| `app:emission:defaultize` | Installation (ou mise à jour) des facteurs d'emission |
| `app:create:user {email} {password}` | Création d'un user
| `app:create:admin {email} {password} {super}` | Création d'un admin
| `app:test:email {email}` | Envoi d'un email de test à l'adresse fournie en argument

### <u>Mode maintenance</u>

Le mode maintenance peut être activé en créant un fichier .lock à la racine du projet.