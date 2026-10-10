<?php
/**
 * Enveloppe de l'assistant d'installation — gabarit à deux colonnes.
 *
 * Refonte du 2026-09-15. Le gabarit précédent empilait bandeau, logo et fil d'étapes horizontal
 * au-dessus d'une carte étroite, et portait un jeu de tokens recopié à la main qui avait DÉRIVÉ de
 * celui du thème `nebula` : fond, texte, surfaces, bordures, rayons et couleurs sémantiques
 * différaient tous légèrement, et ni les ombres ni la lueur signature n'étaient reprises.
 *
 * Ici chaque valeur vient de themes/nebula/css/style.css (palette FIXE navy + teal, accent
 * #2dd4bf). L'installeur est la première chose que voit quelqu'un qui essaie le CMS : il doit
 * ressembler au site qu'il va obtenir.
 *
 * Deux points d'attention hérités de la relecture :
 *   - le dégradé ne passe JAMAIS derrière du texte (il rayonnait sous le titre du bandeau, dont
 *     l'encre sombre disparaissait là où le dégradé s'assombrissait) : il monte du bord inférieur
 *     gauche, s'éteint avant la zone de lecture, et se referme en filet net sous le bandeau ;
 *   - le logo est serti dans une pastille arrondie aux surfaces du thème — posé brut, son carré à
 *     angles vifs tranchait avec les formes arrondies de tout le reste.
 *
 * CSS inliné volontairement : l'installeur intercepte toutes les URLs, un .css externe
 * compliquerait les chemins relatifs. Variables fournies par index.php ($step, $errors, nf_steps(),
 * nf_e(), NEOFRAG_VERSION). Textes par lang() (install/lib/langue.php).
 */
$nf_steps  = nf_steps();
$step_keys = array_keys($nf_steps);
$current   = array_search($step, $step_keys, true);
$total     = count($step_keys);

/**
 * Titre et sous-titre du bandeau, plus la ligne d'annonce sous chaque étape de la colonne.
 * Les étapes elles-mêmes ne portent plus de <h2> : le titre appartient au bandeau.
 */
$nf_entetes = [
	'modules'      => [lang('Profil du site'),       lang('Ce que votre site embarque au départ, en plus du cœur.'),            lang('Modules inclus')],
	'requirements' => [lang('Prérequis serveur'),    lang('Ce que votre hébergement doit fournir pour faire tourner NeoFrag.'), lang('Version de PHP, extensions')],
	'database'     => [lang('Base de données'),      lang('Les identifiants MySQL ou MariaDB fournis par votre hébergeur.'),    lang('Hôte, base, identifiants')],
	'admin'        => [lang('Administrateur'),       lang('Le nom du site et votre compte super-administrateur.'),              lang('Votre compte')],
	'finish'       => [lang('Installation terminée'), lang('Votre communauté est prête.'),                                     lang('Récapitulatif')],
];
[$nf_titre, $nf_soustitre] = $nf_entetes[$step] ?? [$nf_steps[$step] ?? '', ''];
$nf_langue = nf_install_langue();
?>
<!DOCTYPE html>
<html lang="<?php echo nf_e($nf_langue); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo nf_e(lang('Installation de NeoFrag Reborn')); ?></title>
<?php /* Aucune police distante (2026-10-08) : l'assistant tourne avant le moteur du site, qui sert les polices locales ;
         il s'affiche avec les polices du système (les piles de repli ci-dessous), sans envoyer personne chez Google. */ ?>
<style>
/* Tokens repris de themes/nebula/css/style.css — ne pas les faire diverger à nouveau. */
:root{
	--bg:#070a10;                    /* nebula --fg-bg */
	--surface:#0d121b;               /* nebula --fg-surface */
	--surface2:#131a26;              /* nebula --fg-surface-2 */
	--surface3:#1b2433;              /* nebula --fg-surface-3 */
	--line:rgba(255,255,255,.09);    /* nebula --fg-border */
	--line2:rgba(255,255,255,.16);   /* nebula --fg-border-strong */
	--text:#e7eef6;                  /* nebula --fg-text */
	--strong:#ffffff;
	--muted:#8593a6;                 /* nebula --fg-muted */
	--muted2:#5b6678;                /* nebula --fg-muted-2 */
	--accent:#2dd4bf;                /* nebula --fg-accent */
	--accent-soft:rgba(45,212,191,.14);
	--accent-text:#7fe6da;
	--ok:#36d07a;                    /* nebula --fg-success */
	--ok-soft:rgba(54,208,122,.12);
	--ko:#ff6b6b;                    /* nebula --fg-danger */
	--ko-soft:rgba(255,107,107,.12);
	--amber:#f6b352;                 /* nebula --fg-warning */
	--amber-soft:rgba(246,179,82,.12);
	--radius:12px; --radius-sm:9px; --radius-xs:6px;
	--shadow:0 12px 32px -12px rgba(0,0,0,.75),0 3px 8px -2px rgba(0,0,0,.55);
	--glow:0 0 18px -2px rgba(45,212,191,.55);
	--grad:linear-gradient(90deg,#2dd4bf 0%,#22d3ee 100%);
	--font:'Inter',"Segoe UI",system-ui,sans-serif;
	--display:'Space Grotesk','Inter',"Segoe UI",sans-serif;
	--mono:'JetBrains Mono',ui-monospace,"SF Mono",monospace;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.55 var(--font);-webkit-font-smoothing:antialiased}
.app{display:grid;grid-template-columns:300px 1fr;min-height:100vh}

/* ───────────────────────── Colonne ───────────────────────── */
.side{background:#0a0f17;border-right:1px solid var(--line);padding:34px 28px 30px;display:flex;flex-direction:column}
.brand{display:flex;align-items:center;gap:13px}
.mark{
	width:46px;height:46px;flex:none;border-radius:14px;
	background:linear-gradient(160deg,var(--surface3),var(--surface));
	border:1px solid var(--line2);display:grid;place-items:center;
	box-shadow:var(--glow),inset 0 1px 0 rgba(255,255,255,.06);
}
.mark img{width:27px;height:27px;display:block;border-radius:4px}
.brand .n{font-family:var(--display);font-weight:700;font-size:17px;color:var(--strong);line-height:1.15}
.brand .n small{display:block;font-size:10px;letter-spacing:2.6px;color:var(--accent);font-weight:600;margin-top:3px}
.lead{color:var(--muted);font-size:12.5px;line-height:1.65;margin:20px 0 32px}
.langues{display:flex;flex-wrap:wrap;gap:6px;margin:-14px 0 28px}
.langues a{font-size:11.5px;color:var(--muted);text-decoration:none;padding:4px 9px;border:1px solid var(--line);border-radius:999px;line-height:1.3}
.langues a:hover,.langues a:focus-visible{color:var(--strong);border-color:var(--line2)}
.langues a[aria-current]{color:var(--strong);border-color:var(--accent)}
.fork{
	display:inline-flex;align-items:center;gap:7px;margin:0 0 4px;padding:6px 11px;border-radius:999px;
	border:1px solid color-mix(in srgb,var(--amber) 35%,transparent);background:var(--amber-soft);
	color:color-mix(in srgb,var(--amber) 72%,#fff 28%);font-size:11px;font-weight:600;letter-spacing:.2px;
}
.fork svg{width:12px;height:12px;flex:none}

.vsteps{list-style:none;padding:0;margin:0;flex:1}
.vsteps li{display:flex;gap:14px;padding:0 0 26px;position:relative}
.vsteps li::before{content:"";position:absolute;left:14px;top:30px;bottom:0;width:2px;background:var(--line)}
.vsteps li:last-child::before{display:none}
.vsteps li.done::before{background:var(--ok)}
.vsteps .num{
	width:30px;height:30px;flex:none;border-radius:50%;border:1.5px solid var(--line2);
	display:grid;place-items:center;font-size:12px;font-weight:700;color:var(--muted);
	background:var(--surface2);position:relative;z-index:1;
}
.vsteps li.done .num{background:var(--ok);border-color:var(--ok);color:#06251a}
.vsteps li.active .num{border-color:var(--accent);color:var(--accent);box-shadow:0 0 0 4px var(--accent-soft)}
.vsteps .vt{padding-top:5px}
.vsteps .vl{display:block;font-size:14px;color:var(--muted)}
.vsteps li.active .vl{color:var(--strong);font-weight:600}
.vsteps li.done .vl{color:var(--text)}
.vsteps .vd{display:block;font-size:11.5px;color:var(--muted2);margin-top:2px}

.prog{height:4px;background:var(--surface2);border-radius:99px;overflow:hidden;margin-top:6px}
.prog i{display:block;height:100%;background:var(--grad);transition:width .3s ease}
.pl{display:flex;justify-content:space-between;gap:10px;font-size:11px;color:var(--muted2);margin-top:9px;font-family:var(--mono)}

/* ───────────────────────── Bandeau ─────────────────────────
   Le dégradé ne passe pas derrière le texte : il rayonne du bord inférieur gauche
   et se referme en filet net. Titre blanc sur navy — contraste constant. */
.main{display:flex;flex-direction:column;min-width:0}
.hero{position:relative;padding:40px 56px 34px;background:var(--surface);border-bottom:1px solid var(--line);overflow:hidden}
.hero::before{
	content:"";position:absolute;inset:0;
	background:radial-gradient(620px 240px at -6% 125%,rgba(45,212,191,.30),transparent 68%),
	           radial-gradient(520px 200px at 24% 145%,rgba(34,211,238,.16),transparent 70%);
}
.hero::after{content:"";position:absolute;left:0;right:0;bottom:0;height:2px;background:var(--grad)}
.hero .in{position:relative;z-index:1}
.hero .eb{display:inline-block;font:500 10.5px/1 var(--mono);letter-spacing:.14em;text-transform:uppercase;color:var(--accent-text);margin-bottom:12px}
.hero h1{font-family:var(--display);font-size:28px;font-weight:600;margin:0 0 8px;color:var(--strong);letter-spacing:-.01em;text-wrap:balance}
.hero p{margin:0;color:var(--muted);font-size:14.5px;max-width:64ch}

.body{padding:38px 56px 48px;max-width:720px}
h2{font-family:var(--display);font-size:19px;font-weight:600;margin:0 0 16px;color:var(--strong)}

/* ─────────────────── Contrôles partagés par les étapes ─────────────────── */
label{display:block;margin-bottom:18px;font-size:12.5px;color:var(--muted);font-weight:500}
input{
	display:block;width:100%;margin-top:8px;padding:13px 15px;background:var(--surface2);
	border:1px solid var(--line2);border-radius:var(--radius-sm);color:var(--text);
	font-size:14px;font-family:var(--font);
}
input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)}
.btn{
	display:inline-block;margin-top:10px;padding:13px 28px;border:0;border-radius:var(--radius-sm);
	font:700 14px/1.2 var(--font);cursor:pointer;text-decoration:none;
	background:var(--grad);color:#06231e;box-shadow:var(--glow);
}
.btn:hover{filter:brightness(1.06)}
.btn:focus-visible{outline:2px solid var(--accent-text);outline-offset:3px}
.btn.ghost{background:none;border:1px solid var(--line2);color:var(--text);box-shadow:none;margin-left:10px}
.btn.ghost:hover{border-color:var(--muted);filter:none}

.errors{
	background:var(--ko-soft);border:1px solid var(--ko);border-left:3px solid var(--ko);
	color:color-mix(in srgb,var(--ko) 55%,#fff 45%);border-radius:var(--radius-sm);
	padding:13px 16px;margin-bottom:22px;font-size:13.5px;
}
.errors p{margin:4px 0}

/* Prérequis : une grille de cartes plutôt qu'une liste plate. */
.checks{list-style:none;padding:0;margin:0 0 26px;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}
.checks li{
	display:flex;align-items:center;gap:12px;padding:14px 16px;
	background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);
}
.checks li .ico{
	width:30px;height:30px;flex:none;border-radius:var(--radius-sm);display:grid;place-items:center;
	font-size:14px;font-weight:700;background:var(--ok-soft);color:var(--ok);
}
.checks li.ko{border-color:color-mix(in srgb,var(--ko) 40%,transparent)}
.checks li.ko .ico{background:var(--ko-soft);color:var(--ko)}
.checks li .t{display:block;font-size:13.5px;color:var(--text);font-weight:500}
.checks li .v{display:block;font-size:11.5px;color:var(--muted);font-family:var(--mono);margin-top:2px}
.checks li.ko .v{color:var(--ko)}

/* Champs appaires : deux par ligne quand ils vont ensemble (hote+port, mot de passe+confirmation). */
/* ── Étape « Modules » : profils et cases à cocher ── */
.profils{list-style:none;padding:0;margin:0 0 26px;display:grid;gap:10px}
.profil{
	display:flex;align-items:flex-start;gap:14px;padding:16px 18px;margin:0;
	background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);
	cursor:pointer;transition:border-color .15s,background .15s;font-weight:400;
}
.profil:hover{border-color:var(--line2)}
.profil.sel{border-color:var(--accent);background:var(--accent-soft)}
.profil:focus-within{outline:2px solid var(--accent-text);outline-offset:2px}
.profil input{width:auto;margin:4px 0 0;flex:none;accent-color:var(--accent)}
.profil .pico{font-size:22px;line-height:1.2;flex:none}
.profil .pbody{display:block;min-width:0}
.profil .ptitre{display:block;font-size:15px;font-weight:600;color:var(--strong);font-family:var(--display)}
.profil .ptag{display:block;color:var(--muted);font-size:13px;margin-top:3px;line-height:1.55}
.profil .pcompte{display:block;margin-top:7px;font:500 11px/1 var(--mono);letter-spacing:.06em;color:var(--muted2)}
.profil.sel .pcompte{color:var(--accent-text)}

.modgroup{border:1px solid var(--line);border-radius:var(--radius);padding:16px 18px;margin:0 0 24px;background:var(--surface)}
.modgroup legend{padding:0 8px;font:500 10.5px/1.4 var(--mono);letter-spacing:.1em;text-transform:uppercase;color:var(--muted2)}
.modgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:9px 18px}
.mod{display:flex;align-items:center;gap:9px;margin:0;font-size:13.5px;color:var(--text);font-weight:400;cursor:pointer}
.mod input{width:auto;margin:0;flex:none;accent-color:var(--accent)}

.row2{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
.row2.large{grid-template-columns:2fr 1fr}
@media (max-width:560px){.row2,.row2.large{grid-template-columns:1fr}}
.hint{color:var(--muted);font-size:13px;line-height:1.6}
label .hint{display:block;margin-top:3px;font-weight:400}
code{background:var(--surface2);padding:2px 6px;border-radius:var(--radius-xs);font-size:12.5px;font-family:var(--mono);color:var(--accent-text)}

.foot{margin-top:34px;padding-top:20px;border-top:1px solid var(--line);color:var(--muted2);font-size:12px;line-height:1.8;max-width:64ch}
.foot b{color:var(--muted);font-weight:600}
.foot a{color:var(--accent-text);text-decoration:none}
.foot a:hover{text-decoration:underline}

@media (max-width:860px){
	.app{grid-template-columns:1fr}
	.side{border-right:0;border-bottom:1px solid var(--line);padding:24px 20px}
	.lead{display:none}
	.vsteps{display:flex;gap:0;margin-bottom:16px}
	.vsteps li{flex:1;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:0}
	.vsteps li::before{left:auto;right:50%;top:14px;bottom:auto;width:100%;height:2px}
	.vsteps li:first-child::before{display:none}
	.vsteps li:last-child::before{display:block}
	.vsteps .vd{display:none}
	.vsteps .vt{padding-top:0}
	.hero{padding:28px 20px 24px}
	.body{padding:26px 20px 40px}
}
@media (prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body data-nf-assistant>
<div class="app">

	<aside class="side">
		<div class="brand">
			<span class="mark"><img src="data:image/png;base64,<?php echo base64_encode((string) @file_get_contents(__DIR__.'/nf-logo.png')) ?>" width="27" height="27" alt=""></span>
			<span class="n">NeoFrag<small>REBORN</small></span>
		</div>

		<p class="lead">
			<span class="fork">
				<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4.5 13.5H11l-1 8.5L19.5 10H13z"/></svg>
				<?php echo nf_e(lang('Fork non officiel de NeoFrag')); ?>
			</span><br>
			<?php echo nf_e(lang('Assistant d\'installation. %d étapes, quelques minutes — votre communauté est prête ensuite.', $total)); ?>
		</p>

		<nav class="langues" aria-label="<?php echo nf_e(lang('Langue de l\'assistant')); ?>">
			<?php foreach (NF_INSTALL_LANGUES as $nf_code => $nf_nom): ?>
				<a href="?step=<?php echo nf_e($step); ?>&amp;lang=<?php echo nf_e($nf_code); ?>" hreflang="<?php echo nf_e($nf_code); ?>" lang="<?php echo nf_e($nf_code); ?>"<?php echo $nf_code === $nf_langue ? ' aria-current="true"' : ''; ?>><?php echo nf_e($nf_nom); ?></a>
			<?php endforeach; ?>
		</nav>

		<ol class="vsteps">
			<?php foreach ($nf_steps as $key => $label):
				$idx  = array_search($key, $step_keys, true);
				$done = $idx < $current;
				$note = $nf_entetes[$key][2] ?? '';
			?>
				<li class="<?php echo $done ? 'done' : ($key === $step ? 'active' : ''); ?>">
					<span class="num"><?php echo $done ? '&#10003;' : ($idx + 1); ?></span>
					<span class="vt">
						<span class="vl"><?php echo nf_e($label); ?></span>
						<?php if ($note !== ''): ?><span class="vd"><?php echo nf_e($note); ?></span><?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>

		<div class="prog"><i style="width:<?php echo (int) round(($current + 1) / $total * 100); ?>%"></i></div>
		<div class="pl">
			<span><?php echo nf_e(lang('étape %d sur %d', $current + 1, $total)); ?></span>
			<span>v<?php echo nf_e(NEOFRAG_VERSION); ?></span>
		</div>
	</aside>

	<main class="main">
		<div class="hero"><div class="in">
			<span class="eb"><?php echo nf_e(lang('Étape %d sur %d', $current + 1, $total)); ?></span>
			<h1><?php echo nf_e($nf_titre); ?></h1>
			<?php if ($nf_soustitre !== ''): ?><p><?php echo nf_e($nf_soustitre); ?></p><?php endif; ?>
		</div></div>

		<div class="body">
			<?php if ($errors): ?>
				<div class="errors"><?php foreach ($errors as $err): ?><p><?php echo nf_e($err); ?></p><?php endforeach; ?></div>
			<?php endif; ?>

			<?php require __DIR__ . '/steps/' . $step . '.php'; ?>

			<p class="foot">
				<?php echo lang('NeoFrag Reborn est la continuité communautaire de NeoFrag, créé à l\'origine par %s (%s). Projet open source sous licence %s.', '<b>Michaël BILCOT</b> &amp; <b>Jérémy VALENTIN</b>', '<a href="https://neofr.ag" target="_blank" rel="noopener">neofr.ag</a>', '<b>LGPLv3</b>'); ?>
			</p>
		</div>
	</main>

</div>
</body>
</html>
