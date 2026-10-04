<?php
declare(strict_types=1);

/**
 * check-reglages — l'écran de réglages de chaque addon installé s'ouvre, s'enregistre et se rouvre, sans rien écrire au journal.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le 2026-10-04, le site officiel répondait 500 à l'ouverture des réglages du Recrutement et du
 * Forum : le champ « nombre » des formulaires passait la valeur enregistrée à `str_replace()`, et
 * depuis la vague `strict_types` du 2026-09-21, PHP refuse un entier là où il attend du texte. Or
 * un réglage numérique se RELIT en entier dès qu'il a été enregistré une fois : sur un site neuf,
 * l'écran s'ouvrait ; sur un site dont l'administrateur avait réglé quelque chose, il plantait.
 * Aucun contrôle n'ouvrait ces écrans, et aucun ne les enregistrait.
 *
 * Celui-ci, en administrateur, pour chaque addon installé qui a des réglages :
 *   1. ouvre l'écran (les réglages d'usine, ou ceux du site) ;
 *   2. avec `--enregistrer`, l'enregistre TEL QUEL — ce qu'aurait fait l'administrateur — puis le
 *      rouvre : c'est la seconde ouverture qui relit les valeurs typées ;
 *   3. lit le journal PHP du site : il doit être resté muet.
 * Les modules et les authentificateurs ont une fenêtre de réglages ; les thèmes, une page
 * « Personnaliser », qu'il ouvre seulement (leurs formulaires sont éprouvés par `check-mise-en-page`).
 * Les widgets ont leur propre contrôle : `check-widget-contract`.
 *
 * `--enregistrer` RÉÉCRIT les réglages du site (avec leurs propres valeurs, mais typées comme le fait
 * l'administration) : sur une installation jetable seulement — la CI, une installation de test.
 *
 * Usage
 * -----
 *   php tools/check-reglages.php                  ouvre chaque écran de réglages
 *   php tools/check-reglages.php --enregistrer    l'enregistre tel quel et le rouvre (site jetable)
 *   php tools/check-reglages.php --addon=forum    un seul addon
 *   php tools/check-reglages.php --port=8115
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/journal.php';

[$o] = nf_options(['enregistrer' => FALSE, 'addon' => '', 'port' => 0]);

$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']), ['NF_OUTIL_SESSION' => nf_session_admin($db), 'NF_OUTIL_CONSENT' => 'essentials']);
$journal = nf_racine().'/logs/php.log';

if (!nf_journal_preparer($journal))
{
    nf_refus("le journal du site n'est pas inscriptible ({$journal}) : le contrôle serait aveugle");
}

// Notre session doit être reconnue : sinon chaque écran répondrait « accès refusé », et le rapport
// accuserait le produit d'un défaut qui n'est que le nôtre.
$controle = nf_http($serveur->base.'/fr/admin', ['suivre' => 0]);

if ($controle['code'] !== 200)
{
    nf_refus(sprintf("la session d'administrateur n'est pas reconnue (HTTP %d sur /fr/admin). Journal du serveur : %s", $controle['code'], $serveur->journal));
}

$enregistrer = $o['enregistrer'];

if ($enregistrer && nf_mode_demo())
{
    nf_avertir('Site de démonstration : l\'administration y est en lecture seule, les écrans sont seulement ouverts.');
    $enregistrer = FALSE;
}

// ── Les addons installés ──────────────────────────────────────────────────────
// L'adresse d'une action d'addon : admin/addons/<action>/<id>/<nom> — l'identifiant est celui de
// la table des addons, le nom sert de titre d'adresse (Admin_Checker::_check_addon()).
$addons = [];

foreach ($db->query("SELECT a.id, a.name, t.name AS type FROM nf_addon a JOIN nf_addon_type t ON t.id = a.type_id
    WHERE t.name IN ('module', 'authenticator', 'theme') ORDER BY t.name, a.name") ?: [] as $ligne)
{
    if ($o['addon'] === '' || $o['addon'] === $ligne['name'])
    {
        $addons[] = $ligne;
    }
}

if (!$addons)
{
    nf_refus($o['addon'] !== '' ? "addon introuvable ou non installé : {$o['addon']}" : 'aucun addon installé');
}

$echecs   = [];
$ouverts  = 0;
$sans     = 0;
$enregistres = 0;
$octet    = nf_journal_taille($journal);

$ouvrir = static function (string $chemin, bool $fenetre) use ($serveur): array {
    return nf_http($serveur->base.$chemin, ['suivre' => 0, 'timeout' => 60, 'ajax' => $fenetre]);
};

printf("%d addon(s) installé(s) à éprouver%s\n\n", count($addons), $enregistrer ? ' — ouverture, enregistrement tel quel, réouverture' : ' — ouverture');

foreach ($addons as $addon)
{
    $theme   = $addon['type'] === 'theme';
    $action  = $theme ? 'customize' : 'settings';
    $chemin  = '/fr/admin/addons/'.$action.'/'.$addon['id'].'/'.rawurlencode($addon['name']);
    $nom     = $addon['type'].':'.$addon['name'];
    $reponse = $ouvrir($chemin, !$theme);

    // Un addon sans réglages n'a pas cette action : l'adresse n'existe pas, c'est légitime.
    if ($reponse['code'] === 404)
    {
        $sans++;
        continue;
    }

    if ($reponse['code'] !== 200)
    {
        $echecs[] = sprintf('%-28s ouverture : HTTP %s', $nom, $reponse['code'] ?: 'pas de réponse ('.$reponse['raison'].')');
        continue;
    }

    $ouverts++;

    if (!$enregistrer || $theme)
    {
        continue;
    }

    $formulaire = nf_formulaire(nf_balisage($reponse['corps']), 'name="');

    if ($formulaire === NULL)
    {
        $echecs[] = sprintf('%-28s la fenêtre de réglages ne porte aucun formulaire', $nom);
        continue;
    }

    $envoi = nf_http($serveur->base.$chemin, ['post' => $formulaire, 'suivre' => 0, 'timeout' => 60, 'ajax' => TRUE]);

    if ($envoi['code'] >= 500 || $envoi['code'] === 0)
    {
        $echecs[] = sprintf('%-28s enregistrement : HTTP %s', $nom, $envoi['code'] ?: 'pas de réponse');
        continue;
    }

    $enregistres++;
    $retour = $ouvrir($chemin, TRUE);

    if ($retour['code'] !== 200)
    {
        $echecs[] = sprintf('%-28s réouverture après enregistrement : HTTP %s', $nom, $retour['code'] ?: 'pas de réponse');
    }
}

printf("  %d écran(s) de réglages ouvert(s)%s\n", $ouverts, $enregistrer ? sprintf(', %d enregistré(s) tel(s) quel(s) puis rouvert(s)', $enregistres) : '');
printf("  %d addon(s) sans réglages\n\n", $sans);

foreach ($echecs as $echec)
{
    echo '  ✗ ', $echec, "\n";
}

$classe  = nf_journal_classer(nf_journal_depuis_octet($journal, $octet));
$fautifs = nf_journal_montrer($classe, nf_racine());

if ($echecs || $fautifs)
{
    nf_echec(sprintf('%d écran(s) en échec, %d message(s) au journal — un réglage enregistré doit se relire sans erreur', count($echecs), $fautifs));
}

nf_ok(sprintf('les %d écran(s) de réglages s\'ouvrent%s, et le journal est resté muet', $ouverts, $enregistrer ? ', s\'enregistrent et se rouvrent' : ''));
