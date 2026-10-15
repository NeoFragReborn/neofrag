-- Les partenaires peuvent enfin être traduits (2026-10-14, trouvé en traduisant la démonstration, m23).
--
-- `nf_partners_lang` porte une ligne par langue, comme les seize autres tables de traduction du produit — sauf que
-- sa clé primaire était `partner_id` SEUL, et auto-incrémentée par-dessus le marché. Un partenaire ne pouvait donc
-- avoir qu'UNE langue : la deuxième était refusée en double, ou écrasait la première. Le module s'en était tiré
-- jusque-là parce qu'il n'écrit jamais qu'une langue — celle où l'on travaille.
--
-- La clé devient (partner_id, lang), et l'auto-incrément part : ce numéro vient du partenaire, il ne s'invente pas.

ALTER TABLE `nf_partners_lang` MODIFY `partner_id` int(11) unsigned NOT NULL;

ALTER TABLE `nf_partners_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`partner_id`, `lang`);
