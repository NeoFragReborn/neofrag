-- champs de profil definis par l'administrateur.
--
-- Le profil etait FIXE : seize colonnes dans nf_user_profile (prenom, nom, avatar, banniere,
-- signature, naissance, sexe, pays, fuseau, lieu, citation, site, LinkedIn, GitHub, Instagram,
-- Twitch). Une communaute gaming veut y ajouter ce qui lui est propre — pseudo en jeu, plateforme,
-- rang — sans qu'on livre une migration a chaque fois.
--
-- Deux tables, et non une colonne JSON sur le profil : les valeurs doivent pouvoir etre exportees
-- membre par membre (RGPD), effacees avec le compte, et lues sans deserialiser tout un profil. Le
-- module `recruits` fait autrement — ses champs vivent en JSON dans la candidature — mais ses
-- donnees sont figees a l'envoi, la ou un profil se relit et se modifie.

CREATE TABLE IF NOT EXISTS `nf_user_fields` (
  `field_id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
  -- Le nom technique : ce qui sert de cle au formulaire et a l'export. Immuable une fois cree.
  `name`        varchar(64) NOT NULL,
  `label`       varchar(200) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `type`        enum('text','textarea','select','radio','checkbox','url','number','date') NOT NULL DEFAULT 'text',
  -- Les choix des types `select` et `radio`, un par ligne. Vide pour les autres.
  `options`     mediumtext DEFAULT NULL,
  `required`    tinyint(1) NOT NULL DEFAULT 0,
  -- Visible sur la fiche PUBLIQUE du membre. A 0, le champ ne se voit que par son proprietaire et
  -- par l'administration : c'est le defaut, parce qu'on ne publie pas une donnee par inadvertance.
  `public`      tinyint(1) NOT NULL DEFAULT 0,
  `sort_order`  smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`field_id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_user_fields_values` (
  `field_id` int(11) unsigned NOT NULL,
  `user_id`  int(11) unsigned NOT NULL,
  `value`    mediumtext NOT NULL,
  PRIMARY KEY (`field_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  -- Supprimer un champ efface ses valeurs ; supprimer un membre efface les siennes. Sans ces deux
  -- cascades, une table de donnees personnelles survivrait a la suppression du compte.
  CONSTRAINT `fk_user_fields_values_field` FOREIGN KEY (`field_id`) REFERENCES `nf_user_fields` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_fields_values_user`  FOREIGN KEY (`user_id`)  REFERENCES `nf_user` (`id`)             ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
