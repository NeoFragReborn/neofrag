-- Retour a 100 caracteres : les adresses plus longues sont tronquees, faute de quoi la colonne ne
-- pourrait pas revenir a sa taille d'origine.

UPDATE `nf_user_auth` SET `avatar` = LEFT(`avatar`, 100) WHERE CHAR_LENGTH(`avatar`) > 100;
ALTER TABLE `nf_user_auth` MODIFY `avatar` varchar(100) DEFAULT NULL;
