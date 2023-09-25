# Interface utilisateur

Il s'agit de l'interface principale du calculateur. Elle est accessible à la racine du projet : `/` 

En terme de sécurité, le firewall "front" couvre cette interface et est accessible aux utilisateurs (entité `User`).

Elle est composée de 4 rubriques :
- **Tableau de bord**
- **Gérer mes campagnes**
- **Mon entreprise**
- **Analyses**

Un utilisateur est toujours rattaché à un organisme. Lorsqu'il se connecte, il a alors accès aux données liés à son organisme (campagnes, sous-organismes, collaborateurs).

## Tableau de bord

Le tableau de bord contient un récapitulatif des actions effectuées sur l'organisme lié à l'utilisateur connecté sous formes de modules.

**Module "Dernières campagnes"**

Les 4 dernières campagnes créées sont affichées. Au-dessous se trouvent 4 compteurs :
- Nombre de campagnes à l'état "Estimations en cours"
- Nombre de campagnes à l'état "Estimations terminées"
- Nombre de campagnes à l'état "Post-campagne"
- Nombre de campagnes à l'état "Campagnes archivées"

**Module "Evénements"**

Il s'agit d'un calendrier recensant les événements de la campagne, basés sur les dates des campagnes de l'organisme.

**Module "Statistiques"**

Il s'agit d'un graphique permettant d'afficher les statistiques cumulatives des différents supports de diffusions des campagnes sur une période donnée. La période est personnalisable.

**Module "Exports"**

Ce module permet, après avoir défini ses critères de recherche, d'effectuer un export CSV des campagnes correspondantes, chaque ligne du fichier représentant un élément de la campagne, avec le calcul de son impact carbone.

## Gérer mes campagnes

**Cette rubrique permet de créer / d'éditer et de supprimer des campagnes.**

Lorsque qu'une campagne est configurée, elle est alors composée de différentes étapes et il est alors directement possible de naviguer entre les différentes étapes.

**Il est également possible de créer / renommer et supprimer des variantes. Différents supports de diffusion peuvent être activés sur chaque variante.**

Voici les différentes étapes d'une campagne :

- **Information de la campagne** 

Cette étape permet de modifier la configuration de la campagne. 

L'option "`phases actives`" permet de désactiver certaines éléments de formulaire en fonction de la phase à laquelle ils sont rattachés. Ils ne sont alors pas visibles dans le formulaire, et sont ignorés par l'algorithme lors du calcul de l'impact carbone.

L'option "`Supports de diffusion`" permet d'activer et de désactiver des supports de diffusion pour la variante de la campagne. Chaque support de diffusion actif représente alors une nouvelle étape de la campagne.

- **Supports de diffusions actifs** 

Il y a en fait d'autant d'étapes que de supports de diffusions activés pour la variante de la campagne consultée.
Pour chaque support, l'étape contient le formulaire affiché, et il est alors possible de renseigner des données qui serviront à son calcul.

- **Demande supplémentaire** 

Cette étape permet d'envoyer une demande personnalisée aux superadmins par email. L'email est envoté automatiquement dès la sauvegarde de cette étape.

- **Bilan**

Cette partie contient l'affichage des statistiques détaillées de la variante. Les statistiques de la campagne sont recalculées à chaque enregistrement de données.

## Mon entreprise

Cette rubrique permet de créer / éditer et supprimer des organisations et des collaborateurs. La création d'organisation n'est possible de créer qu'au sein d'une entreprise.

Les fonctionnalités disponibles sont restreintes à certains utilisateurs en fonction de leur profil. 

## Analyses

Cette rubrique permet de consulter deux types d'analyses.

- **Analyse cumulative** 

De la même manière que sur le tableau de bord, après avoir sélectionné certaines critères de recherches, les statistiques détaillées et cumulées des campagnes correspondantes sont alors affichées.

- **Analyse comparative** 

Cette analyse permet d'afficher en parallèle, sous forme de colonnes, les statistiques détaillées de 3 campagnes, ce qui en facilite la comparaison.