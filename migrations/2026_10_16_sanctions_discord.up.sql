-- Les sanctions reliées entre le site et Discord (entrée m03b du tableau, 2026-10-16).
--
-- Une sanction dit désormais d'où elle vient : prononcée sur le site, ou rapportée par le bot depuis Discord (un
-- modérateur dont le compte Discord est relié au site, et qui en a le droit sur le site). Seule une sanction venue de
-- Discord peut être levée depuis Discord : celle du site se lève sur le site.

ALTER TABLE `nf_sanctions`
  ADD COLUMN `origine` varchar(16) NOT NULL DEFAULT 'site' AFTER `notify_user`;
