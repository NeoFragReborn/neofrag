<?php
declare(strict_types=1);

/**
 * banc — poser un widget sur une page le temps d'une mesure, puis tout remettre.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Trente-six des quarante widgets ne sont posés sur AUCUNE page : pour les voir rendus, il faut les
 * placer quelque part. `capturer-apercus` l'a appris le premier, pour photographier chaque widget ;
 * `check-widget-contract` en a eu besoin le 2026-09-22, pour rendre chaque couple widget/type SANS
 * RÉGLAGES et lire ce que le rendu écrit au journal. Deux outils, une seule façon de poser et de
 * retirer : elle vit ici.
 *
 * Le banc crée une ligne `nf_widgets`, la place seule dans une zone d'UNE page (`contact/*` par
 * défaut, pour ne toucher à aucune autre), et rend la fonction qui retire les deux lignes. Cette
 * fonction est aussi inscrite pour la fin de l'outil : une interruption ne laisse pas un widget
 * d'épreuve posé sur la page de contact d'un site.
 *
 * Usage
 * -----
 *   $retirer = nf_banc_widget($db, 'nebula', 'about', 'index', NULL);   // posé SANS réglages
 *   $retirer = nf_banc_widget($db, 'nebula', 'rss', 'index', '{"url":"…"}');
 *   $retirer = nf_banc_widget($db, 'nebula', 'socials', 'index', NULL, taille: 'col-4');   // largeur d'une colonne
 *   … servir /fr/contact …
 *   $retirer();
 */

require_once __DIR__.'/outil.php';

/**
 * Un widget fait pour une colonne étroite — une barre latérale — se pose avec `$taille` (`col-4`) :
 * seul sur toute la largeur de la page, il s'étirait en un bandeau qui ne lui ressemble pas.
 *
 * Un widget se pose aussi dans un STYLE de panneau (`panel-header`, `panel-color`) : c'est là que les thèmes changent
 * ses couleurs, et qu'un texte peut disparaître — la case de date du « prochain rendez-vous », blanche sur blanc
 * dans le panneau coloré de Pulse, ne se voyait dans aucune mesure faite au style par défaut (2026-10-06).
 *
 * @param  ?string $json   les réglages tels qu'ils seront STOCKÉS (JSON), ou NULL pour aucun
 * @param  ?string $taille la largeur de sa colonne, classe de grille (`col-4`), ou NULL pour toute la zone
 * @param  ?string $style  le style du panneau (`panel-default`, `panel-header`, `panel-color`), ou NULL pour celui du thème
 * @return callable(): void  retire le widget et sa disposition — sans effet la seconde fois
 */
function nf_banc_widget(mysqli $db, string $theme, string $widget, string $type, ?string $json, string $page = 'contact/*', string $zone = '1', ?string $taille = NULL, ?string $style = NULL): callable
{
    $reglages = $json === NULL ? 'NULL' : "'".$db->real_escape_string($json)."'";

    $db->query("INSERT INTO `nf_widgets` (`widget`, `type`, `title`, `settings`) VALUES ('"
        .$db->real_escape_string($widget)."', '".$db->real_escape_string($type)."', NULL, ".$reglages.")");
    $id = (int) $db->insert_id;

    $disposition = '[{"style":"row-default","cols":[{"size":'.($taille === NULL ? 'null' : (string) json_encode($taille)).',"widgets":[{"id":'.$id.',"style":'.($style === NULL ? 'null' : (string) json_encode($style)).',"size":null}]}]}]';

    $db->query("INSERT INTO `nf_dispositions` (`theme`, `page`, `zone`, `disposition`) VALUES ('"
        .$db->real_escape_string($theme)."', '".$db->real_escape_string($page)."', '".$db->real_escape_string($zone)."', '"
        .$db->real_escape_string($disposition)."')");
    $disposition_id = (int) $db->insert_id;

    $fait    = FALSE;
    $retirer = static function () use ($db, $id, $disposition_id, &$fait): void {
        if ($fait)
        {
            return;
        }

        $fait = TRUE;
        $db->query('DELETE FROM `nf_dispositions` WHERE `disposition_id` = '.$disposition_id);
        $db->query('DELETE FROM `nf_widgets` WHERE `widget_id` = '.$id);
    };

    register_shutdown_function($retirer);

    return $retirer;
}

/**
 * La zone où un thème rend le CONTENU des pages, par son rang (celui que `nf_dispositions.zone` attend).
 *
 * Le banc pose par défaut dans la zone 1, la seconde : une bande rendue sur toutes les pages dans les premiers thèmes
 * (« Avant-contenu »). Elle ne l'est plus partout — la « Mosaïque » de Pulse n'existe qu'à l'accueil, et un widget
 * posé là sur la page de contact ne se rend jamais. La région `content`, elle, est rendue sur toute page.
 *
 * Lu dans le fichier du thème, sans charger le moteur : la liste `zones` et l'entrée `content` de `regions`.
 *
 * @return ?string le rang, ou NULL si le thème ne déclare pas de région `content`
 */
function nf_banc_zone_contenu(string $theme): ?string
{
    $fichier = nf_racine().'/themes/'.$theme.'/'.$theme.'.php';
    $source  = is_file($fichier) ? (string) file_get_contents($fichier) : '';

    if (!preg_match("/'zones'\s*=>\s*\[([^\]]*)\]/", $source, $zones)
     || !preg_match("/'regions'\s*=>\s*\[[^\]]*?'content'\s*=>\s*'([^']*)'/s", $source, $contenu))
    {
        return NULL;
    }

    preg_match_all("/'([^']*)'/", $zones[1], $noms);
    $rang = array_search($contenu[1], $noms[1], TRUE);

    return $rang === FALSE ? NULL : (string) $rang;
}
