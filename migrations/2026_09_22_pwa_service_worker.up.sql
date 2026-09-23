-- L'interrupteur du service worker (PWA), ETEINT par defaut.
--
-- POURQUOI IL EXISTE. Un service worker installe dans un navigateur y reste et continue de repondre
-- a la place du reseau, MEME si le fichier disparait du serveur. Supprimer le fichier ne le
-- desinstalle pas. C'est le seul composant du produit capable de casser un site de facon durable
-- chez des visiteurs qu'on ne peut pas joindre.
--
-- Ce reglage est donc le chemin de SORTIE autant que celui d'entree : eteint, `/service-worker.js`
-- rend un worker qui efface ses caches, se desinscrit et recharge les onglets ouverts. Les
-- navigateurs reverifiant le script a chaque navigation, eteindre desinstalle reellement.
--
-- Eteint par defaut, et c'est delibere : une mise a jour du coeur ne doit jamais installer toute
-- seule quelque chose dont le retrait demande une manoeuvre. C'est l'administrateur qui l'allume.
--
-- Les colonnes sont celles de `nf_settings` : name, site, lang, value, type. Il n'y a ni `title`
-- ni `description` — les libelles vivent dans les fichiers de langue, pas en base.

INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`)
SELECT 'nf_pwa', '', '', '0', 'bool'
 WHERE NOT EXISTS (SELECT 1 FROM `nf_settings` WHERE `name` = 'nf_pwa');
