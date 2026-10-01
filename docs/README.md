# Documentation — NeoFrag Reborn 1.2.8

Documentation **vérifiée contre le code réel** (60 modules · 40 widgets · 7 thèmes · 10 addons livrés).
Ses chiffres, ses renvois et sa structure sont contrôlés en intégration continue par
`tools/check-docs.php` : un chiffre faux, un lien mort, un document orphelin ou une phrase recopiée
d'un document à l'autre fait échouer la CI.

## Où chercher

Chaque document répond à **une** question. Trois étages, plus l'outillage.

| Vous voulez savoir… | Lisez |
|---|---|
| **ce que fait le CMS et comment on s'en sert** | les [guides](guide/README.md) : installation, concepts, administration, marketplace |
| **comment l'étendre** — un module, un widget, un thème | les guides développeur : [le framework](guide/framework.md), [créer un module](guide/create-a-module.md), [un widget](guide/create-a-widget.md), [un thème](guide/create-a-theme.md) |
| **comment le CMS est fait** | [architecture.md](architecture.md) — pile, cycle de requête, service-locator, ORM, surcharge, événements, carrefours, données, dette |
| **ce qu'il contient** | [components.md](components.md) — l'inventaire des modules, widgets, thèmes et addons, et leur anatomie |
| **la gamification et la monétisation** | [gamification.md](gamification.md) — karma, points, boutique, VIP, régie |
| **comment on développe, éprouve et déploie** | [development.md](development.md) — les trois installations, la boucle de travail, la base de test, les migrations, la batterie, la CI, la configuration, le déploiement |
| **ce que fait chaque outil de `tools/`** | [tools/README.md](../tools/README.md) — le catalogue, engendré depuis les en-têtes, et les conventions |
| **déployer sur un mutualisé, par FTP** | [deploy-ftp.md](deploy-ftp.md) |
| **publier une version** | [RELEASING.md](RELEASING.md) — versionnage, les deux dépôts, la chaîne de publication |
| **où va le projet** | [../ROADMAP.md](../ROADMAP.md), écrit pour les utilisateurs |
| **ce qui a changé** | [../CHANGELOG.md](../CHANGELOG.md) |
| **ce qui reste à faire, et pourquoi** | [internal/](internal/README.md) — les documents de travail : tableau de bord, fiches, décisions, leçons, journal |
| **contribuer, signaler une faille** | [.github/](../.github/) : [contribuer](../.github/CONTRIBUTING.md), [sécurité](../.github/SECURITY.md), [code de conduite](../.github/CODE_OF_CONDUCT.md) |

## Les règles

Elles existent parce que la documentation a longtemps été « remise à jour » par passes, et que
chaque passe était à refaire : un même fait vivait dans cinq documents, et il en manquait toujours
une mise à jour. `tools/check-docs.php` tient chacune de ces règles.

1. **Un fait, un endroit.** Chaque type d'information a un document de référence ; les autres y
   renvoient, ils ne recopient pas. La liste des outils vit dans `tools/README.md` ; le statut d'un
   chantier dans `internal/fiches.md` ; une décision dans `internal/decisions.md` ; une nouveauté
   dans `CHANGELOG.md`. *Vérifié : une phrase de prose identique dans deux documents vivants est refusée.*
2. **Les chiffres s'écrivent sous une forme que le contrôle sait lire** — un titre (`## 60 modules`)
   ou une chaîne séparée par « · » — ou ne s'écrivent pas. Une phrase qui parle d'un sous-ensemble
   (« 19 modules de statistiques ») n'est pas un inventaire et n'est pas contrôlée. *Vérifié : modules,
   widgets, thèmes, addons, contrôles, fichiers stricts, et les chiffres publics du hero et de la
   feuille de route contre le catalogue.*
3. **Tout renvoi mène quelque part**, ancre comprise. *Vérifié.*
4. **Aucun document orphelin** : tout `.md` sous `docs/` est la cible d'un renvoi — son index, au
   moins. *Vérifié.*
5. **Un outil nommé existe.** Renommer ou fusionner un outil met la documentation à jour dans le même
   geste. *Vérifié, hors archives et CHANGELOG, qui racontent le passé.*
6. **Ce qui n'est plus vrai part en archive**, avec une note de gel en tête et une ligne dans l'index
   de [internal/README.md](internal/README.md#archive--les-documents-gelés). On ne le laisse pas
   pourrir à côté des documents vivants, et on ne le supprime pas : il porte les raisons.
7. **Les documents vivants restent lisibles** : le journal tourne vers l'archive au-delà de 400
   lignes ; aucun document vivant ne dépasse 900 lignes. *Vérifié.*
8. **`internal/fiches.md` prime** sur tout autre document en cas de contradiction sur un statut ; le
   tableau de bord `internal/reste-a-faire.md` se tient à jour dans le même geste que la fiche.
9. **Le français**, partout ; les identifiants du code hérité restent en anglais.

Le détail de la partie interne — quel document pour quel type d'information — est dans
[internal/README.md](internal/README.md#où-va-quoi).
