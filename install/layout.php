<?php
/**
 * Vue d'enveloppe du wizard : <head> + logo + barre d'étapes + zone d'erreurs + contenu.
 * CSS inliné volontairement : l'installeur intercepte toutes les URLs, un fichier .css
 * externe (ou les PNG d'ombre du logo) compliquerait les chemins relatifs — le logo est
 * donc rendu en SVG vectoriel pur (carré + wordmark NeoFrag), sans dépendance fichier.
 * Variables fournies par index.php.
 */
$step_keys = array_keys(NF_STEPS);
$current   = array_search($step, $step_keys, true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation de NeoFrag Reborn</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
:root{
	--bg:#0a0c11;--bg2:#0f1219;--card:rgba(24,27,37,.72);--line:#272c3b;--line2:#333a4d;
	--text:#e8ebf4;--muted:#8b92a8;--accent:#2dd4bf;--accent2:#0d9488;
	--ok:#34d399;--ko:#f87171;--amber:#fbbf24;
	--font:'Inter',ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
	--display:'Space Grotesk','Inter',ui-sans-serif,system-ui,sans-serif;
	--mono:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;
}
*{box-sizing:border-box}
body{
	margin:0;color:var(--text);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:44px 16px;
	font:15px/1.55 var(--font);
	background:
		radial-gradient(1200px 620px at 50% -8%,rgba(45,212,191,.14),transparent 60%),
		radial-gradient(820px 480px at 86% 12%,rgba(20,184,166,.08),transparent 55%),
		linear-gradient(180deg,var(--bg2),var(--bg));
}
.wrap{width:100%;max-width:600px}
.fork-banner{
	display:flex;align-items:center;gap:8px;justify-content:center;width:max-content;max-width:100%;
	margin:0 auto 24px;padding:7px 15px;border:1px solid rgba(251,191,36,.35);background:rgba(251,191,36,.08);
	color:#fcd34d;border-radius:999px;font-size:12.5px;font-weight:600;letter-spacing:.2px;
}
.fork-banner svg{width:14px;height:14px;flex:none}
.head{text-align:center;margin-bottom:28px}
.logo{width:88px;height:88px;max-width:40%;display:block;margin:0 auto;filter:drop-shadow(0 8px 26px rgba(45,212,191,.28))}
.logo .sq{fill:url(#nf_g)}
.logo .t{fill:#f3f6fc}
.head .sub{margin:12px 0 0;color:var(--muted);font-size:13px;font-family:var(--display);letter-spacing:.3px}
.head .sub b{color:var(--accent);font-weight:700;letter-spacing:2.5px}
.steps{display:flex;list-style:none;padding:0;margin:0 0 22px}
.steps li{flex:1;text-align:center;position:relative;font-size:11.5px;color:var(--muted)}
.steps li::before{content:"";position:absolute;top:15px;left:-50%;width:100%;height:2px;background:var(--line);z-index:0}
.steps li:first-child::before{display:none}
.steps .num{
	width:32px;height:32px;border-radius:50%;border:2px solid var(--line);background:var(--bg2);
	display:flex;align-items:center;justify-content:center;margin:0 auto 7px;font-family:var(--display);font-weight:700;font-size:13px;position:relative;z-index:1;
}
.steps li.active{color:var(--text)}
.steps li.active .num{border-color:var(--accent);color:var(--accent);box-shadow:0 0 0 4px rgba(45,212,191,.16)}
.steps li.done{color:var(--ok)}
.steps li.done .num{border-color:var(--ok);background:var(--ok);color:#053226}
.steps li.done::before{background:var(--ok)}
.card{
	background:var(--card);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
	border:1px solid var(--line);border-radius:16px;padding:28px;box-shadow:0 26px 64px -22px rgba(0,0,0,.65);
}
.card h2{margin:0 0 18px;font-size:20px;font-family:var(--display);font-weight:600;letter-spacing:.2px}
label{display:block;margin-bottom:14px;font-size:13px;color:var(--muted)}
input{display:block;width:100%;margin-top:6px;padding:11px 13px;background:#0d0f16;border:1px solid var(--line2);border-radius:9px;color:var(--text);font-size:14px}
input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(45,212,191,.16)}
.btn{display:inline-block;margin-top:8px;padding:11px 22px;border:0;border-radius:9px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;
	background:linear-gradient(180deg,var(--accent),var(--accent2));color:#042620;box-shadow:0 8px 20px -8px rgba(45,212,191,.55)}
.btn:hover{filter:brightness(1.06)}
.btn.ghost{background:none;border:1px solid var(--line2);color:var(--text);box-shadow:none;margin-left:8px}
.btn.ghost:hover{border-color:var(--muted);filter:none}
.errors{background:rgba(248,113,113,.1);border:1px solid var(--ko);color:#ffc9c9;border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:13px}
.errors p{margin:4px 0}
.checks{list-style:none;padding:0;margin:0 0 20px}
.checks li{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line)}
.checks li span{font-weight:700}
.checks li.ok span{color:var(--ok)}
.checks li.ko{color:var(--ko)}
.checks li.ko span{color:var(--ko)}
.hint{color:var(--muted);font-size:13px}
.foot{text-align:center;color:var(--muted);font-size:11.5px;margin-top:24px;line-height:1.75}
.foot b{color:#aeb4c8;font-weight:600}
.foot a{color:var(--accent);text-decoration:none}
.foot a:hover{text-decoration:underline}
.foot .ver{display:block;margin-top:6px;opacity:.7}
code{background:#0d0f16;padding:2px 6px;border-radius:5px;font-size:12.5px;font-family:var(--mono)}
</style>
</head>
<body>
<div class="wrap">
	<div class="fork-banner">
		<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4.5 13.5H11l-1 8.5L19.5 10H13z"/></svg>
		Fork non officiel de NeoFrag
	</div>

	<div class="head">
		<img class="logo" src="nf-logo.png" width="88" height="88" alt="NeoFrag Reborn">
		<p class="sub"><b>NEOFRAG REBORN</b> · Assistant d'installation</p>
	</div>

	<ol class="steps">
		<?php foreach (NF_STEPS as $key => $label): $idx = array_search($key, $step_keys, true); $done = $idx < $current; ?>
			<li class="<?php echo $done ? 'done' : ($key === $step ? 'active' : ''); ?>">
				<span class="num"><?php echo $done ? '&#10003;' : ($idx + 1); ?></span><?php echo nf_e($label); ?>
			</li>
		<?php endforeach; ?>
	</ol>

	<div class="card">
		<?php if ($errors): ?>
			<div class="errors"><?php foreach ($errors as $err): ?><p><?php echo nf_e($err); ?></p><?php endforeach; ?></div>
		<?php endif; ?>
		<?php require __DIR__ . '/steps/' . $step . '.php'; ?>
	</div>

	<div class="foot">
		NeoFrag Reborn est la continuité communautaire de NeoFrag, créé à l'origine par
		<b>Michaël BILCOT</b> &amp; <b>Jérémy VALENTIN</b> (<a href="https://neofr.ag" target="_blank" rel="noopener">neofr.ag</a>).
		Projet open source sous licence <b>LGPLv3</b>.
		<span class="ver">NeoFrag Reborn <?php echo NEOFRAG_VERSION ?></span>
	</div>
</div>
</body>
</html>
