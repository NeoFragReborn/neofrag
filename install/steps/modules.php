<?php
/**
 * Étape « Modules » — choix d'un profil (preset) de départ + modules Tier 1 décochables +
 * modules Tier 2 optionnels téléchargés depuis le marketplace distant ($nf_catalog, fourni par
 * index.php ; null = marketplace injoignable → mode dégradé).
 *
 * Variables héritées de index.php : $CSRF, $nf_catalog, fonction nf_e(). Installer déjà chargée.
 */

use NF\Install\Lib\Installer;

$presets   = Installer::presets();
$first_key = (string) array_key_first($presets);

// Tier 2 (marketplace distant) regroupé par catégorie, depuis le catalogue.
$tier2      = ['contenu' => [], 'monetisation' => [], 'theme' => []];
$cat_labels = ['contenu' => 'Contenu', 'monetisation' => 'Monétisation', 'theme' => 'Thèmes'];
if (is_array($nf_catalog ?? null)) {
	foreach ($nf_catalog['addons'] as $a) {
		if (($a['tier'] ?? null) == 2 && in_array($a['type'] ?? '', ['module', 'theme'], true)) {
			$cat = isset($cat_labels[$a['category'] ?? '']) ? $a['category'] : 'contenu';
			$tier2[$cat][] = $a;
		}
	}
}
$has_tier2 = (bool) array_filter($tier2);
?>
<style>
.presets{list-style:none;padding:0;margin:0 0 18px}
.preset{display:flex;align-items:flex-start;gap:12px;padding:14px;margin-bottom:10px;border:1px solid var(--line);border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s}
.preset:hover{border-color:var(--accent)}
.preset input{width:auto;margin:3px 0 0}
.preset .pico{font-size:22px;line-height:1}
.preset .pbody{flex:1}
.preset .ptitle{font-weight:700;color:var(--text);font-size:15px}
.preset .ptag{color:var(--muted);font-size:12.5px;margin-top:2px}
.preset.sel{border-color:var(--accent);background:rgba(91,140,255,.08)}
.modgroup{border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:16px}
.modgroup legend{padding:0 6px;font-size:12px;color:var(--muted)}
.modgrid{display:flex;flex-wrap:wrap;gap:8px 16px}
.mod{display:flex;align-items:center;gap:6px;margin:0;font-size:13px;color:var(--text)}
.mod input{width:auto;margin:0}
.mod small{font-size:11px}
.tier2{margin-bottom:16px}
.tier2>summary{cursor:pointer;color:var(--text);font-size:13.5px;font-weight:600;margin-bottom:8px}
.tier2 .t2intro{color:var(--muted);font-size:12.5px;margin:0 0 10px}
.t2group{margin-bottom:10px}
</style>

<h2>Profil du site</h2>
<p class="hint">Choisissez un profil de départ. Rien n'est figé : chaque module reste activable ou désactivable
ensuite depuis l'administration.</p>

<form method="post" action="?step=modules" id="nf-modules-form">
	<input type="hidden" name="csrf" value="<?php echo nf_e($CSRF); ?>">

	<ul class="presets">
		<?php foreach ($presets as $key => $p): $sel = $key === $first_key; ?>
			<li>
				<label class="preset<?php echo $sel ? ' sel' : ''; ?>" data-preset="<?php echo nf_e($key); ?>">
					<input type="radio" name="preset" value="<?php echo nf_e($key); ?>"<?php echo $sel ? ' checked' : ''; ?>>
					<span class="pico"><?php echo nf_e($p['icon'] ?? '•'); ?></span>
					<span class="pbody">
						<span class="ptitle"><?php echo nf_e($p['title']); ?></span>
						<span class="ptag"><?php echo nf_e($p['tagline']); ?></span>
					</span>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php foreach ($presets as $key => $p): $sel = $key === $first_key; ?>
		<fieldset class="modgroup" id="mods-<?php echo nf_e($key); ?>"<?php echo $sel ? '' : ' style="display:none"'; ?>>
			<legend>Modules inclus — décochez pour exclure</legend>
			<?php if (empty($p['modules'])): ?>
				<p class="hint" style="margin:0">Cœur seul (aucun module additionnel)<?php echo !empty($p['welcome_page']) ? ' + page d\'accueil de bienvenue' : ''; ?>.</p>
			<?php else: ?>
				<div class="modgrid">
					<?php foreach ($p['modules'] as $mod => $widgets): ?>
						<label class="mod">
							<input type="checkbox" name="modules[]" value="<?php echo nf_e($mod); ?>" checked>
							<?php echo nf_e($mod); ?>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</fieldset>
	<?php endforeach; ?>

	<details class="tier2"<?php echo $has_tier2 ? ' open' : ''; ?>>
		<summary>Modules additionnels (marketplace)</summary>
		<?php if (!is_array($nf_catalog ?? null)): ?>
			<p class="t2intro">Marketplace momentanément indisponible. Vous pourrez ajouter des modules
			optionnels plus tard depuis l'administration.</p>
		<?php elseif (!$has_tier2): ?>
			<p class="t2intro">Aucun module additionnel disponible pour le moment.</p>
		<?php else: ?>
			<p class="t2intro">Optionnels, téléchargés depuis le marketplace (intégrité SHA-256 vérifiée).
			Cochez ce que vous voulez — tout reste ajoutable plus tard depuis l'administration.</p>
			<?php foreach ($tier2 as $cat => $items): if (!$items) continue; ?>
				<fieldset class="modgroup t2group">
					<legend><?php echo nf_e($cat_labels[$cat]); ?></legend>
					<div class="modgrid">
						<?php foreach ($items as $a): ?>
							<label class="mod" title="<?php echo nf_e($a['description'] ?? ''); ?>">
								<input type="checkbox" name="tier2[]" value="<?php echo nf_e($a['name']); ?>">
								<?php echo nf_e($a['title'] ?: $a['name']); ?>
								<small style="color:var(--muted)">(<?php echo (int) round(($a['size'] ?? 0) / 1024); ?> Ko)</small>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>
		<?php endif; ?>
	</details>

	<button class="btn" type="submit">Continuer</button>
</form>

<script>
(function(){
	var form = document.getElementById('nf-modules-form');
	function activate(key){
		// On ne touche QUE les groupes de modules des presets (id « mods-… »), jamais les groupes Tier 2.
		form.querySelectorAll('fieldset[id^="mods-"]').forEach(function(g){
			var on = g.id === 'mods-' + key;
			g.style.display = on ? '' : 'none';
			g.querySelectorAll('input').forEach(function(i){ i.disabled = !on; });
		});
		form.querySelectorAll('.preset').forEach(function(l){
			l.classList.toggle('sel', l.getAttribute('data-preset') === key);
		});
	}
	form.querySelectorAll('input[name="preset"]').forEach(function(r){
		r.addEventListener('change', function(){ if (r.checked) activate(r.value); });
	});
	// Init : avec JS, seules les cases du preset actif sont actives (décochage propre). SANS JS,
	// toutes restent actives → l'union est soumise, et apply_preset() ne retient que le preset choisi.
	var checked = form.querySelector('input[name="preset"]:checked');
	if (checked) { activate(checked.value); }
})();
</script>
