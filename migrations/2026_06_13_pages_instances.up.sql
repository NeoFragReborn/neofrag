-- Page-builder Palier 1 : blocs de module ordonnés et configurés par page.
-- Une page peut désormais embarquer N blocs (carrefour `block.php`, contrat fields/settings du
-- Palier 0) en plus de son contenu libre. `settings` = JSON (champ neofrag/fields/json.php).
-- Base préservée : une page sans ligne ici rend exactement comme avant (contenu + [block:]).
CREATE TABLE IF NOT EXISTS `nf_pages_instances` (
	`instance_id` int(11)     NOT NULL AUTO_INCREMENT,
	`page_id`     int(11)     NOT NULL,
	`block`       varchar(64) NOT NULL,
	`settings`    mediumtext  DEFAULT NULL,
	`position`    int(11)     NOT NULL DEFAULT 0,
	`enabled`     enum('0','1') NOT NULL DEFAULT '1',
	PRIMARY KEY (`instance_id`),
	KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
