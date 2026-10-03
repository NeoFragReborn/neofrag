-- Retrait des tables du référencement. Les titres et descriptions saisis par contenu, les
-- redirections et les adresses suivies pour IndexNow se perdent : le site retombe sur les titres et descriptions
-- automatiques, et une ancienne adresse répond 404.

DROP TABLE IF EXISTS `nf_indexnow`;
DROP TABLE IF EXISTS `nf_redirects`;
DROP TABLE IF EXISTS `nf_seo_meta`;
