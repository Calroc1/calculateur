# Supports de diffusion

Cette documentation présente de manière détaillée la gestion des supports de diffusion sur la plateforme Bilobay.

Un support de diffusion est constitué des éléments suivants : 
- Un nom
- Une couleur
- **Un formulaire**
- **Un algorithme**
- Un référentiel
- Un type

La gestion des supports est accessible dans l’interface superadmin via le bouton "**Formulaires**" du menu de gauche.

Dans ces pages, les actions suivantes sont possibles : 

- Création / édition / suppression / activation / désactivation de supports
- Création / édition / suppression de référentiel
- Création / édition / suppression de type de support
  
La configuration des formulaires et des algorithmes est détaillée plus bas dans cette documentation. **Attention, il est nécessaire d’être familier avec le format YAML** pour la suite de cette documentation. Voici un lien d’explication sur ce format : https://docs.fileformat.com/fr/programming/yaml/

## Configuration de formulaire

Le formulaire d’un support de diffusion est configuré en uploadant un fichier *YAML*. Ce fichier doit respecter la structure suivante : la racine du fichier est un tableau dont chaque élément représente un élément de formulaire.

### Les éléments de formulaire

| Propriété | Type | Obligatoire | Description
| ------------- | ------------- | ------------- | -------------
| `name` | string | Oui |  Le nom de l'élément. <br/>Cette propriété est très importante car elle permet d’identifier le champ dans les algorithmes. <br/>Il est préférable d’éviter les caractères spéciaux, hors underscore.
| `type` | string | Oui | Le type de l’élément, parmi la sélection suivante : <br/> - *section* => un groupe d’éléments de formulaire <br/> - *collection* => un group d'éléments de formulaire, mais qui peut-être répété via un bouton “ajouter” <br/> - *integer* => un champ numérique (les virgules sont autorisées) <br/> - *select* => une liste déroulante <br/>- *select_with_detail* => une liste déroulante avec une valeur “Autre” et un champ numérique qui contient la valeur <br/>- *textarea* => une zone de texte <br/>- *boolean* => des cases à cocher “oui” et “non” <br/>- *country* => la liste déroulante des pays
| `label` | string | Non | Le libellé de l’élément
| `children` | array | Non | **Pour les sections et les collections uniquement**.<br/> Cette propriété est un tableau d'éléments de formulaires.
| `phase` | string | Non | La phase de l’élement parmis les choix suivants : <br/> ( *conception* / *production* / *diffusion* ) <br/> Lorsque la phase est précisée pour un élément, alors celui-ci n’est affiché que si la phase en question est activée sur la campagne.
| `default` | string | Non | **Pour tous les éléments sauf les sections**. <br/> Permet de renseigner la valeur par défaut du champ de formulaire. <br/>Pour une collection, la valeur “entry” permet d’afficher par défaut un élément de la collection.
| `unit` | string | Non | **Pour tous les éléments sauf les collections et les sections**. <br/> Permet de préciser l’unité de mesure du champ.
| `help` | string | Non | Permet d’afficher un icône “?” à côté de l'élément de formulaire avec une infobulle qui contient le texte renseignée.
| `addendum` | string | Non | Permet d’afficher un texte supplémentaire sous l’élément de formulaire.
| `linebreak` | bool | Non | Permet d’effectuer un retour à la ligne après un champ
| `display` | string | Non | **Pour les sections uniquement**. <br/> Permet d’indiquer un type d’affichage parmi les choix suivants : <br/> - table => affichage resserré et en ligne
| `percentage` | bool | Non | **Pour les sections uniquement**. <br/> Permet d’activer l’affichage automatique du pourcentage de la répartition entre tous les champs enfants de la section.
| `renamable` | bool | Non | **Pour les collections uniquement**. <br/> Permet d’activer le renommage de chaque entrée de la collection.
| `choices` | array d'objets avec <br/>propriétés "label" et "value" | Non | **Pour les select et select_with_detail uniquement**. <br/> Permet de renseigner toutes les valeurs possibles de la liste déroulante : <br/> - label => Libellé de la valeur <br/> - value => Valeur réelle
| `rows` | int | Non | **Pour les textarea uniquement**. <br/> Permet de définir le nombre de lignes du champ. Par défaut, le nombre est fixé à 5.
| `italic` | bool | Non | Permet d’afficher le label en italique
| `bold` | bool | Non | Permet d’afficher le label en gras
| `underline` | bool | Non | Permet d’afficher le label en souligné
| `size` | string | Non | **Pour les select et integer uniquement**. <br/>Permet de définir la largeur du champ avec les valeurs suivantes : <br/> (*small* / *medium* / *large* / *very_large* )

### Prévisualisation

Une fois le fichier de formulaire uploadé, une prévisualisation en lecture seule est disponible en bas de la page dans la section “Prévisualisation formulaire”.

**Si une anomalie est détectée dans la structure du fichier, alors une erreur est déclenchée lors de l’upload.**

## Configuration d'algorithme

L’algorithme d’un support de diffusion est configuré en uploadant un fichier *YAML*. Ce fichier doit respecter la structure suivante :

La racine du fichier est un tableau dont chaque élément représente une formule de niveau 1. Ces dernières permettent l’affichage détaillé des résultats du support. 

### Les formules

| Propriété | Type | Obligatoire | Description
| ------------- | ------------- | ------------- | -------------
| `name` | string | Oui pour les formules de niveau 1 | Le nom de la formule, sert à l’affichage détaillé
| `path` | string | Oui | Le chemin vers le champ de formulaire concerné. Il est constitué des propriétés “name” des champs de formulaires, séparés par des “.”. <br/> Il permet de définir le contexte dans lequel sera appliqué le calcul.
| `formula` | string | Non | La formule mathématique qui sert au calcul. <br/> Une formule peut-être constituée des opérateurs arithmétique classiques ainsi que différentes fonctions détaillées sur cette page : https://github.com/NeonXP/MathExecutor <br/> Elle est également constituée de shortcodes (voir plus bas)
| `vars` | array d'objets avec <br/>propriétés "name" et "formula" | Non | Chaque élément du tableau représente une variable intermédiaire, cad une variable qui pourra être exploitée ultérieurement dans une formule (ou dans d’autres variables intermédiaires). <br/> La propriété “name” permet de nommer la variable et la propriété “formula” contient la formule mathématique (voir ci-dessus) 
| `children` | array de Formule | Non | Chaque élément représente une sous-formule. Le total de chaque formule est additionné au total de la formule “parente”.

### Les shortcodes

Les shortcodes sont des chaînes de caractères qui permettent de cibler des éléments de formulaires, des facteurs d’émission, des variables intermédiaires ou des fonctions.

Un shortcode est déclaré de la manière suivante : __{NOM_DU_SHORTCODE}[{ARGUMENT1, {ARGUMENT2}]__

| Shortcode | Argument 1 | Argument 2 | Description
| ------------- | ------------- | ------------- | -------------
| `FIELD` | Chemin vers le champ | | Permet de cibler un champ du formulaire, par rapport au contexte. <br/> Par exemple, si on est dans une sous-formule, le chemin doit être relatif au champ ciblé par la propriété “path” de la formule. 
| `EMISSION` | Chemin vers le facteur d’émission |  | Permet de cibler un facteur d’émission
| `VAR` | Nom de la variable intermédiaire |  | Permet de cibler une variable intermédiaire déclarée préalablement dans la formule en question et/ou dans la formule parente
| `FCT` | “campaign_conso” |  |  Permet d’utiliser la valeur de la consommation électrique en fonction du pays de la campagne
| `FCT` | “world_avg_conso” |  | Permet d’utiliser la valeur de la consommation mondiale moyenne
| `FCT` | “device_avg_conso” | Appareil concerné, aux choix parmi les valeurs suivantes : ( *box* / *smartphone* / *tablette* / *ordi_fixe* /*ordi_portable* / *tv* ) | Permet d’utiliser la valeur de la consommation moyenne de l’appareil en question


### Procédure de calcul

Lors de l’exécution du calcul du total d’un support de diffusion, chaque formule de niveau 1 est parcourue, et pour chacune d’entre elles, les traitements suivants sont exécutés dans cet ordre : 
1. *Exécution du calcul des variables intermédiaires*
2. *Exécution du calcul de la formule*
3. *Exécution des sous-formules, de manière récursive selon la même procédure*

Le résultat d’une formule est en fait l’addition des résultats des étapes 2 et 3.

### Prévisualisation

Une fois le fichier d’algorithme uploadé, une interprétation visuelle est disponible en bas de la page dans la section “Récapitulatif algorithme”.
Les différents éléments constitutifs des formules sont mis en évidence de la manière suivante : 

- En vert : les noms des formules de niveau 1 
- En bleu : les champs de formulaires, avec au survol les informations disponibles sur le champ concerné
- En violet : les facteurs d’émission, avec au survol les informations disponibles sur le facteur concerné
- En orange : les variables intermédiaires 

**Si un champ de formulaire ou un facteur d’émission n’est pas trouvé, alors celui-ci est mis en évidence en rouge.**

**Si une anomalie est détectée dans la structure du fichier, ou dans une expression arithmétique, alors une erreur est déclenchée lors de l’upload.**
