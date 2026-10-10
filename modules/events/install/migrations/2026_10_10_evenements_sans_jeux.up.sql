-- Le module Événements sans les modules Jeux et Équipes (m17, 2026-10-10) : une association, un club s'en servent sans
-- jeux ni équipes, et ses tables ne doivent plus exiger celles des jeux. Le mode de jeu d'un match et la carte d'une
-- manche restent des numéros ; supprimer un mode ou une carte ne supprime plus le match ni la manche qui l'emploient
-- (ils s'affichent sans), là où la contrainte les effaçait en cascade.

ALTER TABLE `nf_events_matches` DROP FOREIGN KEY `nf_events_matches_ibfk_3`;
ALTER TABLE `nf_events_matches_rounds` DROP FOREIGN KEY `nf_events_matches_rounds_ibfk_1`;
