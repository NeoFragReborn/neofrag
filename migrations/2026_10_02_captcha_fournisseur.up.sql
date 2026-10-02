-- Le fournisseur de captcha.
--
-- Jusqu'ici, le captcha n'existait que si l'administrateur avait posé deux clés reCAPTCHA : la plupart
-- des sites — le site vitrine compris — n'en avaient aucun, et des robots envoyaient leurs annonces par
-- le formulaire de contact. Le réglage nomme désormais le fournisseur : ALTCHA, hébergé par le site et
-- sans clé, par défaut ; reCAPTCHA pour un site qui en avait déjà les deux clés, afin de ne rien changer
-- à ce qui marchait.
--
-- Les colonnes sont celles de `nf_settings` : name, site, lang, value, type.

INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`)
SELECT 'nf_captcha_provider', '', '',
       IF(EXISTS (SELECT 1 FROM `nf_settings` WHERE `name` = 'nf_captcha_public_key' AND `value` <> '')
          AND EXISTS (SELECT 1 FROM `nf_settings` WHERE `name` = 'nf_captcha_private_key' AND `value` <> ''),
          'recaptcha', 'altcha'),
       'string'
 WHERE NOT EXISTS (SELECT 1 FROM `nf_settings` WHERE `name` = 'nf_captcha_provider');
