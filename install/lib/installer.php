<?php
declare(strict_types=1);

/**
 * L'ancien emplacement de la bibliothèque d'installation, gardé pour ce qui l'inclurait encore.
 *
 * Elle vit dans `neofrag/installer.php` depuis le 2026-10-01 : la mise à jour ne réécrivait jamais
 * `install/`, et un site gardait ainsi le code de mise à jour du jour de son installation. Le nom
 * `NF\Install\Lib\Installer` reste un alias de `NF\NeoFrag\Installer`.
 */

require_once dirname(__DIR__, 2).'/neofrag/installer.php';

if (!class_exists('NF\Install\Lib\Installer', FALSE))
{
	class_alias(\NF\NeoFrag\Installer::class, 'NF\Install\Lib\Installer');
}
