# Feuille de route — NeoFrag Reborn

Ce document dit **où en est le projet et ce qui vient ensuite**. Il est la version écrite de la
[feuille de route du site](https://neofrag-reborn.xyz/fr#roadmap), et les deux disent la même chose.

Le détail version par version est dans le [CHANGELOG](CHANGELOG.md) ; la façon de contribuer, dans
[.github/CONTRIBUTING.md](.github/CONTRIBUTING.md).

> **Rien n'est annoncé ici comme livré sans l'être.** Un jalon reste « prévu » tant qu'on ne peut pas
> le prendre en main. C'est pourquoi la première publication figure encore dans les jalons à venir,
> alors même que le produit tourne en production sur le site officiel, version après version.

---

## Où en est le projet

**Version 1.2** — le numéro exact est en tête du [CHANGELOG](CHANGELOG.md) —, bien au-delà de l'alpha
0.2.4 dont NeoFrag Reborn est la continuité. Le catalogue
propose **35 modules, 24 widgets et 4 thèmes**, ajoutables en un clic depuis l'administration.

Le code est complet et éprouvé à chaque modification — tests automatisés, analyse statique, contrôles
qui ouvrent un vrai navigateur. **Il n'est pas encore publié** : c'est le prochain jalon, et le seul
qui sépare le projet de ses premiers utilisateurs.

---

## Fait

### Le socle
- **Fondations modernisées** : PHP 8.2 à 8.5, MariaDB, migrations versionnées, intégration continue,
  tests d'intégration, durcissement de la sécurité.
- **Installeur web en un clic** : prérequis, base de données, compte administrateur. Un installeur en
  ligne de commande existe aussi, pour les déploiements automatisés.
- **Installation à la carte** : au moment d'installer, vous choisissez le type de site que vous
  montez — complet, gaming et eSport, communauté, ou le cœur seul. Rien n'est figé : tout reste
  activable ensuite.
- **Mise à jour du CMS en un clic** : le paquet est vérifié par empreinte avant d'être posé, une
  sauvegarde complète — fichiers et base — est prise juste avant d'écrire, et si quelque chose échoue
  en route, le site revient seul dans son état d'avant.
- **Marketplace distant sécurisé** : téléchargement en HTTPS, vérifié par empreinte SHA-256.

### Le contenu et la communauté
- **Forum, actualités, articles, galerie, wiki, FAQ, annonces, téléchargements, liens, sondages,
  recrutement, calendrier, suivi de bugs, dons** — et le reste du catalogue.
- **Équipes, matchs, palmarès, jeux, partenaires, serveurs de jeu** pour les communautés gaming.
- **Profils membres enrichis** : mur d'activité et contributions réunis sur le profil.
- **Statistiques de communauté** : tableau de bord d'activité couvrant tout le contenu.
- **Publication programmée** : actualités, articles, pages et galeries paraissent à l'heure choisie.
- **Recherche globale instantanée** : une barre unique qui suggère des résultats dès la frappe.
- **Newsletter et événements avancés** : envois programmés et segmentés, événements récurrents avec
  participants et rappels — une série entière se modifie ou se supprime d'un geste.
- **Webhooks et statut live** : webhooks signés vers Discord, Zapier et vos outils à chaque événement
  du site ; statut en direct de plusieurs chaînes Twitch et YouTube, avec lecteur intégré.
- **API REST et bot Discord** : une API pour que des programmes lisent le site et écrivent sur son forum
  ou son Bugtracker ; le bot Discord s'en sert pour relier un serveur Discord au site — rôles, pseudos,
  forum et salons Forum, tickets.
- **Connexion par Discord, GitHub ou Google**, à côté du compte classique.

### L'apparence et l'administration
- **Constructeur de menus** multi-niveaux, réutilisables partout.
- **Éditeur en direct** : les blocs se déplacent et se règlent sur la page elle-même.
- **Sept thèmes livrés**, dont quatre proposés au catalogue, tous en Bootstrap 5 sans jQuery.
- **Documentation complète**, utilisateur et développeur, consultable sur le site — ce qui manquait à
  NeoFrag depuis toujours.
- **Six langues complètes** : français, anglais, espagnol, italien, allemand, portugais.
- **Les heures dans le fuseau de chacun** : chaque visiteur lit les dates à son heure, et le site a son
  fuseau par défaut.
- **Une administration repensée** : neuf rubriques, une recherche rapide, la même charte pour tous les
  écrans, et un Monitoring qui montre le journal des erreurs et règle les outils de diagnostic.
- **Site installable (PWA)** : il s'installe sur un téléphone comme une application, et peut garder ses
  pages pour fonctionner sans réseau.

---

## Prévu

### Première publication
La mise à disposition du code et des paquets d'installation, pour que n'importe qui puisse monter son
site. Les paquets sont construits et vérifiés à chaque version, et le canal de mise à jour est en
service sur le site officiel ; il reste à ouvrir le dépôt public.

### Personnalisation des thèmes
La **police** se choisit déjà dans les réglages ; viendra le choix des **couleurs**, pour donner au
site une identité propre. Le vocabulaire de couleurs partagé par tous les thèmes existe déjà.

### Notifications push
Des notifications dans le navigateur, même quand le site est fermé. Le site est déjà installable comme
une application.

### Améliorations continues
Performances, qualité du code et sécurité, renforcées au fil des versions **sans bouleverser votre
site**. C'est un engagement de méthode : chaque version est éprouvée avant d'être publiée, et la mise
à jour sait revenir en arrière.

---

## Ce qui n'est pas prévu, et pourquoi

Ces choix ont été tranchés ; les rouvrir demanderait un fait nouveau.

| Sujet | Décision |
|---|---|
| **Statut live Kick** | Écarté. Twitch et YouTube couvrent le besoin ; l'API de Kick est par ailleurs impraticable depuis un serveur. |
| **Bibliothèques servies par un CDN** | Écarté. Un CDN est un tiers, incompatible avec notre politique de sécurité stricte. Tout est servi par votre site. |
| **Distribution des addons par Composer** | Écarté. NeoFrag Reborn vise aussi l'hébergement mutualisé sans accès au terminal ; le marketplace fait le même travail. |
| **Anglais comme langue source** | Écarté. Le français est la langue source, et les six langues sont tenues complètes par un contrôle automatique. |

---

*Dernière mise à jour : 2026-10-02.*
