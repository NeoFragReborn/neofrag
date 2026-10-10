-- Retour aux contraintes vers les tables des jeux.
-- couplage(games): ce retour en arrière exige le module Jeux, comme la version d’avant.

ALTER TABLE `nf_events_matches` ADD CONSTRAINT `nf_events_matches_ibfk_3` FOREIGN KEY (`mode_id`) REFERENCES `nf_games_modes` (`mode_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `nf_events_matches_rounds` ADD CONSTRAINT `nf_events_matches_rounds_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `nf_games_maps` (`map_id`) ON DELETE CASCADE ON UPDATE CASCADE;
