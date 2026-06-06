<?php
/**
 * Vue d'enveloppe du wizard : <head> + barre d'étapes + zone d'erreurs + contenu.
 * CSS inliné volontairement : l'installeur intercepte toutes les URLs, un fichier
 * .css externe compliquerait les chemins relatifs. Variables fournies par index.php.
 */
$step_keys = array_keys(NF_STEPS);
$current   = array_search($step, $step_keys, true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation de NeoFrag</title>
<style>
:root{--bg:#11131a;--card:#1a1d27;--line:#2a2e3d;--text:#e7e9f0;--muted:#9aa0b4;--accent:#5b8cff;--ok:#3ecf8e;--ko:#ff6b6b}
*{box-sizing:border-box}
body{margin:0;font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;align-items:flex-start;justify-content:center;padding:40px 16px}
.wrap{width:100%;max-width:560px}
.head{text-align:center;margin-bottom:24px}
.head h1{margin:0;font-size:30px;letter-spacing:.5px}
.head p{margin:4px 0 0;color:var(--muted)}
.steps{display:flex;list-style:none;padding:0;margin:0 0 20px;gap:6px}
.steps li{flex:1;font-size:12px;color:var(--muted);text-align:center;padding:8px 4px;border-top:2px solid var(--line)}
.steps li.active{color:var(--text);border-top-color:var(--accent)}
.steps li.done{color:var(--ok);border-top-color:var(--ok)}
.steps .num{display:block;font-weight:700;font-size:14px;margin-bottom:2px}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:26px}
.card h2{margin:0 0 18px;font-size:20px}
label{display:block;margin-bottom:14px;font-size:13px;color:var(--muted)}
input{display:block;width:100%;margin-top:5px;padding:10px 12px;background:#12141c;border:1px solid var(--line);border-radius:8px;color:var(--text);font-size:14px}
input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(91,140,255,.18)}
.btn{display:inline-block;margin-top:8px;padding:11px 20px;background:var(--accent);color:#fff;border:0;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none}
.btn:hover{filter:brightness(1.08)}
.btn.ghost{background:transparent;border:1px solid var(--line);color:var(--text);margin-left:8px}
.errors{background:rgba(255,107,107,.1);border:1px solid var(--ko);color:#ffc9c9;border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:13px}
.errors p{margin:4px 0}
.checks{list-style:none;padding:0;margin:0 0 20px}
.checks li{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--line)}
.checks li span{font-weight:700}
.checks li.ok span{color:var(--ok)}
.checks li.ko{color:var(--ko)}
.checks li.ko span{color:var(--ko)}
.hint{color:var(--muted);font-size:13px}
.foot{text-align:center;color:var(--muted);font-size:12px;margin-top:18px}
code{background:#12141c;padding:2px 6px;border-radius:5px;font-size:13px}
</style>
</head>
<body>
<div class="wrap">
	<div class="head"><h1>NeoFrag Reborn</h1><p>Assistant d'installation</p></div>
	<ol class="steps">
		<?php foreach (NF_STEPS as $key => $label): $idx = array_search($key, $step_keys, true); ?>
			<li class="<?php echo $idx < $current ? 'done' : ($key === $step ? 'active' : ''); ?>">
				<span class="num"><?php echo $idx + 1; ?></span><?php echo nf_e($label); ?>
			</li>
		<?php endforeach; ?>
	</ol>
	<div class="card">
		<?php if ($errors): ?>
			<div class="errors"><?php foreach ($errors as $err): ?><p><?php echo nf_e($err); ?></p><?php endforeach; ?></div>
		<?php endif; ?>
		<?php require __DIR__ . '/steps/' . $step . '.php'; ?>
	</div>
	<div class="foot">NeoFrag Reborn <?php echo NEOFRAG_VERSION ?></div>
</div>
</body>
</html>
