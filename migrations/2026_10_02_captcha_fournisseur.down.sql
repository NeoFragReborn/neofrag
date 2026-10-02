-- Retrait du fournisseur de captcha. Sans lui, la façade retombe d'elle-même sur la règle d'avant :
-- reCAPTCHA si ses deux clés existent, ALTCHA sinon.

DELETE FROM `nf_settings` WHERE `name` = 'nf_captcha_provider';
