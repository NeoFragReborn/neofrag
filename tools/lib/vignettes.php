<?php
declare(strict_types=1);

/**
 * vignettes — le format des vignettes d'addons, et la liste motivée de ceux qui n'en ont pas.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La vignette d'un addon, `images/thumbnail.jpg`, est lue par trois outils : `capturer-apercus` la
 * produit, `check-vignettes` la juge dans le dépôt, `check-marketplace` dans le catalogue publié.
 * Chacun tenait sa propre idée du format, ou n'en tenait aucune. Le 2026-10-04 : un second outil
 * de capture écrivait encore du 480 × 270 quand le premier écrivait du 960 × 600, rien n'exigeait
 * qu'une vignette existe, et l'on comptait au catalogue deux modules sans aperçu (`api`, `discord`),
 * quatre thèmes en 480 × 270 et deux addons en 640 × 400. Le format et les exemptions vivent ici,
 * une seule fois.
 *
 * Usage
 * -----
 *   $defaut = nf_vignette_defaut($fichier);   // NULL si la vignette est conforme, sinon ce qui ne va pas
 *   isset(NF_VIGNETTES_EXEMPTEES['modules/tools'])
 */

require_once __DIR__.'/outil.php';

/*
 * Le format : du 16/10. La vignette s'affiche sur environ 300 px dans une carte, et près du double
 * dans la fiche de la place de marché — soit 1 000 px réels sur un écran à deux points par pixel.
 */
const NF_VIGNETTE_LARGEUR = 960;
const NF_VIGNETTE_HAUTEUR = 600;

/** Une capture réduite à ce format pèse de 30 à 120 Ko ; au-delà, elle alourdit l'archive pour rien. */
const NF_VIGNETTE_POIDS_MAX = 200 * 1024;

/**
 * Les addons SANS vignette, chacun avec sa raison. Toute autre absence est une faute.
 *
 * La clé est le dossier de l'addon, relatif à la racine. Une exemption n'est pas une commodité : elle
 * dit pourquoi une photo serait fausse, ou ne serait vue par personne.
 */
const NF_VIGNETTES_EXEMPTEES = [
    'modules/reactions'            => 'aucune page : il sert les « j’aime » des autres modules, sur leurs pages',
    'modules/revisions'            => 'aucune page : il garde l’historique des contenus que d’autres modules affichent',
    'modules/tools'                => 'aucune page : une API d’administration (cache, SCSS, base) sans écran propre',
    'widgets/module'               => 'aucune carte : infrastructure non désactivable, absente de « Thèmes & addons » — c’est la zone où chaque module s’affiche',
    'themes/admin'                 => 'aucune carte : le thème de l’administration ne se choisit pas, « Thèmes & addons » ne le liste pas',
    'addons/authenticator'         => 'aucun écran : le socle commun des connecteurs, qui ne se connecte à rien seul',
    'addons/authenticator_discord' => 'son bouton ne vit que dans la fenêtre de connexion ; sa carte montre le logo de la marque',
    'addons/authenticator_github'  => 'son bouton ne vit que dans la fenêtre de connexion ; sa carte montre le logo de la marque',
    'addons/authenticator_google'  => 'son bouton ne vit que dans la fenêtre de connexion ; sa carte montre le logo de la marque',
];

/**
 * Ce qui ne va pas dans une vignette, ou NULL si elle est conforme : présente, JPEG, au format,
 * d'un poids raisonnable.
 */
function nf_vignette_defaut(string $fichier): ?string
{
    if (!is_file($fichier))
    {
        return 'absente';
    }

    $info = @getimagesize($fichier);

    if ($info === FALSE)
    {
        return 'illisible';
    }

    if ($info[2] !== IMAGETYPE_JPEG)
    {
        return 'pas un JPEG ('.image_type_to_mime_type($info[2]).')';
    }

    if ($info[0] !== NF_VIGNETTE_LARGEUR || $info[1] !== NF_VIGNETTE_HAUTEUR)
    {
        return sprintf('%d × %d au lieu de %d × %d', $info[0], $info[1], NF_VIGNETTE_LARGEUR, NF_VIGNETTE_HAUTEUR);
    }

    if (($poids = (int) filesize($fichier)) > NF_VIGNETTE_POIDS_MAX)
    {
        return sprintf('%d Ko, au-delà des %d Ko permis', (int) round($poids / 1024), NF_VIGNETTE_POIDS_MAX / 1024);
    }

    return NULL;
}
