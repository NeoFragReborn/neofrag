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
 * @param  ?string $json   les réglages tels qu'ils seront STOCKÉS (JSON), ou NULL pour aucun
 * @param  ?string $taille la largeur de sa colonne, classe de grille (`col-4`), ou NULL pour toute la zone
 * @return callable(): void  retire le widget et sa disposition — sans effet la seconde fois
 */
function nf_banc_widget(mysqli $db, string $theme, string $widget, string $type, ?string $json, string $page = 'contact/*', string $zone = '1', ?string $taille = NULL): callable
{
    $reglages = $json === NULL ? 'NULL' : "'".$db->real_escape_string($json)."'";

    $db->query("INSERT INTO `nf_widgets` (`widget`, `type`, `title`, `settings`) VALUES ('"
        .$db->real_escape_string($widget)."', '".$db->real_escape_string($type)."', NULL, ".$reglages.")");
    $id = (int) $db->insert_id;

    $disposition = '[{"style":"row-default","cols":[{"size":'.($taille === NULL ? 'null' : (string) json_encode($taille)).',"widgets":[{"id":'.$id.',"style":null,"size":null}]}]}]';

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
