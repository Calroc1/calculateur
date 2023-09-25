# Configuration Frontend

Technos employées sur le projet :

| Utilité | Librairies                                                                                                                                                       |
| :------ | :--------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CSS     | [Tailwindcss](https://tailwindcss.com/)                                                                                                                          |
| JS      | [Alpine JS](https://alpinejs.dev) / [Chart JS](https://www.chartjs.org/docs/latest/samples/other-charts/pie.html) / [Axios](https://www.npmjs.com/package/axios) |
| Bundler | [Webpack Encore](https://symfony.com/doc/current/frontend.html)                                                                                                  |

### Installation

Run `npm i`. Vérifier ensuite que la compilation des assets fonctionne avec `npm run dev`.

### Guidelines JS

- Minimiser le nombre de fichier JS, privilégier l'emplois d'AplineJs pour des modifications mineures
- 1 fichier `app.js` contenant le JS nécessaire sur toutes les pages
- Dans le cas où c'est nécessaire : 1 script js par page
- Utiliser dès que possible les attributs `defer` et `async` sur les appels aux scripts JS ([en savoir plus](https://fr.oncrawl.com/seo-technique/optimiser-les-ressources-javascript-pour-gagner-en-vitesse/)).

### Assets bundle

1. <ins>Scripts de compilation</ins>

| Commande | Action                                                                                                                         |
| -------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `dev`    | Compile les assets 1 fois                                                                                                      |
| `watch`  | Compile les assets dès qu'une modification est détectée                                                                        |
| `serve`  | Similaire à watch sur le principe, ajoute du [live reloading](https://symfony.com/doc/current/frontend/encore/dev-server.html) |
| `build`  | Compile les assets en mode production                                                                                          |

Les commandes sont à préfixer par `npm run <commande>` (voir le fichier `package.json` pour la configuration des scripts)

<b>Important :</b> avec la commande `serve` les assets sont uniquements disponibles en mémoire, et non ré-écris à chaque fois sur le disque. Ce qui veut dire qu'une fois la feature terminée, il faut penser à run la commande `dev` ou `build` pour réellement compiler les assets.

2. <ins>Config webpack encore</ins>

La config se trouve dans le fichier `webpack.config.js`.

<b>Important :</b>

- le projet doit être installé dans un dossier nommé bilobay (à savoir `http://localhost/bilobay/public/build`), dans le cas contraire, les chemins vers les assets seront faux (voir la config de la propriété [setPublicPath](https://symfony.com/doc/current/frontend/encore/faq.html#my-app-lives-under-a-subdirectory) de webpack encore).
- Ajouter la ligne suivante dans le fichier `.env.local` :

```
PUBLIC_PATH=[path to build directory]
# default set to : /bilobay/public/build
```