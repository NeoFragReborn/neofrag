<?php
/**
 * Étape 2 — profil du site : ce que l'installation embarque en plus du cœur.
 *
 * Rétablie le 2026-09-15. Elle avait été retirée le 2026-06-08 (`c4aa961`) au profit du modèle
 * « tout bundlé », parce qu'une installation allégée dont le marketplace était injoignable finissait
 * en 500 : des modules du cœur interrogeaient des tables optionnelles sans garde. Ces gardes
 * existent désormais, et tools/check-install-profiles.php vérifie à chaque exécution que chacun des
 * quatre profils s'installe, démarre et répond sans erreur serveur.
 *
 * Aucune liste d'addons n'est écrite ici : les profils viennent de Installer::presets(), qui les
 * compose à partir des déclarations `'presets' => [...]` de chaque addon.
 *
 * Variables fournies par index.php : $CSRF, $NF_ROOT, nf_e(). Le titre est porté par le bandeau.
 */

use NF\Install\Lib\Installer;

$presets    = Installer::presets($NF_ROOT);
$defaut     = (string) array_key_first($presets);
$choisi     = (string) ($old['preset'] ?? $defaut);
$declarations = Installer::addon_declarations($NF_ROOT);

/** Modules cochés d'un profil, triés par titre, avec leurs dépendances déclarées. */
$modules_du_profil = static function (array $p) use ($declarations, $NF_ROOT): array {
	$liste = [];

	foreach ($p['module'] as $nom)
	{
		$liste[$nom] = [
			'titre'    => lang_addon($NF_ROOT.'/modules/'.$nom, $declarations['module:'.$nom]['title'] ?? ucfirst($nom)),
			'requires' => $declarations['module:'.$nom]['requires'] ?? [],
		];
	}

	uasort($liste, static fn (array $a, array $b): int => strcoll($a['titre'], $b['titre']));

	return $liste;
};
?>
<p class="hint" style="margin-bottom:22px">
	<?php echo lang('Choisissez ce que votre site embarque au départ. <strong>Rien n\'est figé</strong> : tout reste activable, désactivable ou installable ensuite depuis l\'administration et le marketplace.'); ?>
</p>

<form method="post" action="?step=modules" id="nf-form-profil">
	<input type="hidden" name="csrf" value="<?php echo nf_e($CSRF); ?>">

	<ul class="profils">
		<?php foreach ($presets as $cle => $p): $sel = $cle === $choisi; ?>
			<li>
				<label class="profil<?php echo $sel ? ' sel' : ''; ?>">
					<input type="radio" name="preset" value="<?php echo nf_e($cle); ?>"<?php echo $sel ? ' checked' : ''; ?>>
					<span class="pico" aria-hidden="true"><?php echo nf_e($p['icon']); ?></span>
					<span class="pbody">
						<span class="ptitre"><?php echo nf_e($p['title']); ?></span>
						<span class="ptag"><?php echo nf_e($p['tagline']); ?></span>
						<span class="pcompte">
							<?php
							$n = count($p['module']);
							echo nf_e($n === 0 ? lang('cœur seul') : lang('%d module en plus du cœur|%d modules en plus du cœur', $n, $n));
							?>
						</span>
					</span>
				</label>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php foreach ($presets as $cle => $p): $modules = $modules_du_profil($p); ?>
		<fieldset class="modgroup" id="mods-<?php echo nf_e($cle); ?>"<?php echo $cle === $choisi ? '' : ' hidden'; ?>>
			<legend><?php echo nf_e(lang('Modules inclus — décochez ce dont vous ne voulez pas')); ?></legend>

			<?php if (!$modules): ?>
				<p class="hint" style="margin:0">
					<?php echo nf_e(lang('Aucun module en plus du cœur. Vous aurez tout de même les pages, les commentaires, le menu, le formulaire de contact, les membres et la messagerie.')); ?>
				</p>
			<?php else: ?>
				<div class="modgrid">
					<?php foreach ($modules as $nom => $info): ?>
						<label class="mod">
							<input type="checkbox" name="modules[]" value="<?php echo nf_e($nom); ?>" checked
								data-requires="<?php echo nf_e(implode(',', $info['requires'])); ?>">
							<span><?php echo nf_e($info['titre']); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="hint" style="margin:14px 0 0">
					<?php echo nf_e(lang('Certains modules en réclament d\'autres pour fonctionner — le palmarès a besoin des équipes, par exemple. Ce qui manque est ajouté automatiquement, et vous en êtes informé.')); ?>
				</p>
			<?php endif; ?>
		</fieldset>
	<?php endforeach; ?>

	<button class="btn" type="submit"><?php echo nf_e(lang('Continuer')); ?></button>
</form>

<script>
/* Un seul comportement : afficher les modules du profil coché. Sans JS, tous les groupes
   restent visibles et le formulaire fonctionne quand même — le serveur ne retient que les
   modules appartenant au profil choisi. */
(function () {
	var form = document.getElementById('nf-form-profil');
	if (!form) { return; }

	form.addEventListener('change', function (e) {
		if (!e.target.matches('input[name="preset"]')) { return; }

		form.querySelectorAll('.profil').forEach(function (l) { l.classList.remove('sel'); });
		e.target.closest('.profil').classList.add('sel');

		form.querySelectorAll('.modgroup').forEach(function (g) { g.hidden = true; });
		var groupe = document.getElementById('mods-' + e.target.value);
		if (groupe) { groupe.hidden = false; }
	});
})();
</script>
