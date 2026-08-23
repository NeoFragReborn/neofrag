-- Retire les traductions ajoutées (toutes sauf le français d'origine).
DELETE FROM `nf_email_template_translations` WHERE `lang` IN ('en', 'de', 'es', 'it', 'pt');
