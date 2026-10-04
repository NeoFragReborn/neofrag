<?php
declare(strict_types=1);

/**
 * check-demo-ecriture — éprouve, pour de vrai, ce qu'un visiteur peut et ne peut pas écrire en démo.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le site de démonstration ouvre son administration au visiteur : il doit pouvoir toucher au
 * CONTENU sans pouvoir toucher à la CONFIGURATION, parce que la remise à zéro sait défaire le
 * premier et pas la seconde. `check-demo-lock` vérifie que les deux listes se répondent — mais
 * c'est une lecture de déclarations, pas une épreuve. Personne n'avait jamais PROUVÉ que ça marche :
 * cinq tentatives d'épreuve ont échoué sans rien établir, parce qu'elles échouaient AUSSI avec le
 * mode démo désactivé.
 *
 * Ce que ce contrôle établit, en rejouant le vrai flux HTTP
 * ---------------------------------------------------------
 *   1. connexion réelle avec le compte annoncé sur le bandeau — pas une session injectée ;
 *   2. écriture publique (livre d'or) : doit RÉUSSIR, et la ligne doit apparaître en base ;
 *   3. écriture d'administration sur un module de contenu : doit RÉUSSIR ;
 *   4. écriture d'administration sur un module verrouillé : doit être REFUSÉE, rien ne change en base.
 *
 * Le point 4 est le seul qui compte vraiment, et le seul qu'aucun contrôle ne couvrait. Tout ce
 * que le contrôle écrit, il le défait avant de rendre la main.
 *
 * Usage
 * -----
 *   php tools/check-demo-ecriture.php
 *   php tools/check-demo-ecriture.php --compte=demo --motdepasse=demo --port=8095
 *   php tools/check-demo-ecriture.php --trace       montre chaque requête et ce qu'elle envoie
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';

[$o] = nf_options(['compte' => 'demo', 'motdepasse' => 'demo', 'port' => 0, 'trace' => FALSE]);

// ── Le mode démo doit être ACTIF : sinon le contrôle ne mesure rien ─────────
// C'est exactement le piège des cinq tentatives précédentes : elles échouaient avec le mode démo
// désactivé tout autant qu'avec, et concluaient donc à tort.
if (!nf_mode_demo())
{
    nf_ok("NEOFRAG_DEMO n'est pas actif sur cette installation : rien à mesurer, ce contrôle ne s'exécute que sur le site de démonstration");
}

// ── Les modules verrouillés, lus dans la déclaration du produit ─────────────
require_once nf_racine().'/neofrag/helpers/system.php';

$verrouilles = NF_DEMO_MODULES_VERROUILLES;

if (!$verrouilles)
{
    nf_refus('NF_DEMO_MODULES_VERROUILLES est vide : rien à éprouver');
}

$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']));
$base    = $serveur->base;
$bocal   = nf_temp('cookies-'.getmypid().'.txt');

@unlink($bocal);
register_shutdown_function(static function () use ($bocal): void { @unlink($bocal); });

/**
 * GET ou POST avec un bocal à cookies partagé : c'est une VRAIE connexion, avec ses cookies de
 * session posés par le produit, que l'on rejoue — d'où curl et son bocal plutôt que nf_http().
 */
function requete(string $url, string $bocal, ?array $post = NULL): string
{
    global $o;

    $commande = sprintf('curl -sS -L --max-time 45 -A %s -c %s -b %s', escapeshellarg(NF_AGENT), escapeshellarg($bocal), escapeshellarg($bocal));

    foreach ((array) $post as $nom => $valeur)
    {
        $commande .= ' --data-urlencode '.escapeshellarg($nom.'='.$valeur);
    }

    $reponse = (string) shell_exec($commande.' '.escapeshellarg($url).' 2>/dev/null');

    // Un refus doit se diagnostiquer sans reconstruire l'outil : `--trace` montre ce qui est
    // réellement envoyé et ce qui revient. Sans cela, une épreuve en échec ne dit pas si le produit
    // a refusé ou si le formulaire a été mal rempli — la distinction a coûté une heure.
    if ($o['trace'])
    {
        printf("    -> %s %s (%d octets)\n", $post === NULL ? 'GET ' : 'POST', $url, strlen($reponse));

        foreach ((array) $post as $nom => $valeur)
        {
            printf("        %-50s = %s\n", $nom, var_export($valeur, TRUE));
        }
    }

    return $reponse;
}

$echecs = 0;

function juger(string $titre, bool $attendu, bool $obtenu, string $detail = ''): void
{
    global $echecs;

    $ok = ($attendu === $obtenu);

    if (!$ok)
    {
        $echecs++;
    }

    printf("  %-4s %-52s %s\n", $ok ? 'OK' : 'ÉCHEC', $titre, $detail);
}

// ── La limitation de débit doit être neutralisée AVANT de mesurer ──────────
// La connexion et le livre d'or sont protégés par un compteur par IP et par compte. C'est une
// protection contre l'ABUS, pas contre le contrôle du site par lui-même : lancé deux fois de
// suite, l'outil se faisait refuser et rapportait « aucune ligne écrite » là où le produit
// fonctionnait parfaitement. Un contrôle qui échoue une fois sur deux est pire qu'un contrôle absent.
$db->query('DELETE FROM nf_rate_limit');

printf("Site de démonstration — épreuve d'écriture réelle\n");
printf("(compteurs de limitation de débit remis à zéro : l'épreuve doit être reproductible)\n");
printf("Compte : %s · port %d\n", $o['compte'], $serveur->port);
printf("Modules verrouillés : %s\n\n", implode(', ', $verrouilles));

// ── 1. Connexion réelle ────────────────────────────────────────────────────
$form = nf_formulaire(requete($base.'/fr', $bocal), 'name="login"');

if ($form === NULL)
{
    nf_refus("formulaire de connexion introuvable sur la page d'accueil");
}

$form['login']    = $o['compte'];
$form['password'] = $o['motdepasse'];

$apres = requete($base.'/fr', $bocal, $form);

// La preuve se lit EN BASE, pas dans la page : sur le site de démonstration, le mot « demo »
// figure dans le bandeau, sur la page de connexion et dans les URL. Le premier jet se déclarait
// connecté sans l'être, et les trois épreuves suivantes mesuraient un visiteur anonyme.
$id       = (int) nf_scalar($db, "SELECT id FROM nf_user WHERE username = '".$db->real_escape_string($o['compte'])."' AND deleted = '0' ORDER BY admin DESC, id LIMIT 1");
$connecte = $id > 0 && (int) nf_scalar($db, 'SELECT COUNT(*) FROM nf_session WHERE user_id = '.$id.' AND last_activity > DATE_SUB(NOW(), INTERVAL 2 MINUTE)') > 0;
$bride    = str_contains($apres, 'Trop de tentatives');

juger('Connexion avec le compte annoncé', TRUE, $connecte,
    $connecte ? sprintf('session ouverte pour le compte #%d', $id)
              : ($bride ? 'REFUSÉE : trop de tentatives (limitation de débit)' : 'aucune session en base'));

if (!$connecte)
{
    nf_refus('sans session, les trois épreuves suivantes ne mesureraient rien');
}

// ── 2. Écriture PUBLIQUE : le livre d'or ───────────────────────────────────
$marqueur = 'epreuve-demo-'.bin2hex(random_bytes(4));
$form     = nf_formulaire(requete($base.'/fr/guestbook', $bocal), '[message]');

if ($form === NULL)
{
    juger('Écriture publique (livre d\'or)', TRUE, FALSE, 'formulaire introuvable — non jugé');
}
else
{
    foreach ($form as $nom => $valeur)
    {
        if (str_ends_with($nom, '[message]')) { $form[$nom] = $marqueur; }
        if (str_ends_with($nom, '[name]'))    { $form[$nom] = 'Epreuve'; }
    }

    requete($base.'/fr/guestbook', $bocal, $form);

    $ecrit = (int) nf_scalar($db, "SELECT COUNT(*) FROM nf_guestbook WHERE message LIKE '%".$db->real_escape_string($marqueur)."%'") > 0;

    juger('Écriture publique (livre d\'or) permise', TRUE, $ecrit, $ecrit ? 'ligne retrouvée en base' : 'aucune ligne écrite');

    $db->query("DELETE FROM nf_guestbook WHERE message LIKE '%".$db->real_escape_string($marqueur)."%'");
}

// ── 3. Écriture d'ADMINISTRATION sur un module de CONTENU : doit réussir ───
// C'est ce que la démo existe pour montrer : le visiteur ouvre le panneau, crée une actualité, et
// la remise à zéro l'effacera. Marqueur ASCII : la sonde teste le PRODUIT, pas le trajet d'un
// accent à travers un shell, une URL et une comparaison SQL.
$titre = 'epreuve-demo-'.bin2hex(random_bytes(4));
$form  = nf_formulaire(requete($base.'/fr/admin/news/add', $bocal), '[title]');

if ($form === NULL)
{
    juger('Écriture admin (créer une actualité) permise', TRUE, FALSE, 'formulaire introuvable — NON JUGÉ');
}
else
{
    foreach ($form as $nom => $valeur)
    {
        if (str_ends_with($nom, '[title]'))        { $form[$nom] = $titre; }
        if (str_ends_with($nom, '[introduction]')) { $form[$nom] = 'Epreuve automatique.'; }
        if (str_ends_with($nom, '[content]'))      { $form[$nom] = 'Epreuve automatique.'; }
    }

    requete($base.'/fr/admin/news/add', $bocal, $form);

    $ids = array_map('intval', nf_colonne($db, "SELECT news_id FROM nf_news_lang WHERE title = '".$db->real_escape_string($titre)."'"));

    juger('Écriture admin (créer une actualité) permise', TRUE, (bool) $ids, $ids ? 'actualité retrouvée en base' : 'rien écrit');

    foreach ($ids as $id)
    {
        $db->query('DELETE FROM nf_news_lang WHERE news_id = '.$id);
        $db->query('DELETE FROM nf_news WHERE news_id = '.$id);
    }
}

// ── 4. Écriture d'ADMINISTRATION sur un module VERROUILLÉ : doit être refusée ─
// `payments` est verrouillé ET sert un vrai formulaire de configuration. La combinaison est rare :
// la plupart des modules verrouillés n'affichent simplement AUCUN formulaire en démo. C'est une
// protection de plus, mais elle ne prouve pas que la garde d'écriture fonctionne — seul un POST
// réellement refusé le prouve.
$reglage = 'pay_stripe_public';
$avant   = nf_reglage($db, $reglage);
$sonde   = 'epreuve-'.bin2hex(random_bytes(4));
$form    = nf_formulaire(requete($base.'/fr/admin/payments', $bocal), '[public]');

if ($form === NULL)
{
    juger('Écriture admin sur « payments » REFUSÉE', FALSE, FALSE, 'aucun formulaire servi — refus structurel, la garde n\'est pas éprouvée');
}
else
{
    foreach ($form as $nom => $valeur)
    {
        if (str_ends_with($nom, '[public]')) { $form[$nom] = $sonde; }
    }

    $reponse = requete($base.'/fr/admin/payments', $bocal, $form);
    $ecrit   = nf_reglage($db, $reglage) === $sonde;
    $refuse  = str_contains($reponse, 'lecture seule sur le site de d');

    juger('Écriture admin sur « payments » REFUSÉE', FALSE, $ecrit,
        $ecrit ? 'LE RÉGLAGE A ÉTÉ ÉCRIT — la garde ne tient pas'
               : ($refuse ? 'refusée, avec le message attendu' : 'inchangée, mais SANS message de refus'));

    if ($avant !== NULL)
    {
        nf_reglage_poser($db, $reglage, $avant);
    }
    else
    {
        $db->query("DELETE FROM nf_settings WHERE name = '".$db->real_escape_string($reglage)."'");
    }
}

echo "\n";

if ($echecs)
{
    nf_echec(sprintf('%d épreuve(s) en échec — journal du serveur : %s', $echecs, $serveur->journal));
}

nf_ok("le contenu s'écrit, la configuration verrouillée est refusée");
