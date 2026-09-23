<?php
/**
 * Assistant d'ajout / de réglage d'un widget — quatre étapes : widget, type, titre, configuration.
 *
 * Remplace deux `<select>` empilés. Avec 38 widgets, une liste déroulante oblige à connaître le nom
 * de ce qu'on cherche ; des cartes à icône se parcourent du regard.
 *
 * CE QUI NE CHANGE PAS, et c'est délibéré : les champs envoyés au serveur. `widget` et `type`
 * deviennent des champs cachés pilotés par la sélection, `title` reste un champ texte, et les
 * réglages du widget gardent leur conteneur `#live-editor-settings`. Le contrôleur, les deux
 * checkers et la sérialisation du formulaire sont donc inchangés : l'assistant est une couche de
 * présentation, pas un nouveau protocole.
 *
 * Étapes escamotées quand elles n'ont pas lieu d'être : « Type » si le widget n'en a pas, « Titre »
 * pour le widget `module` (il porte celui de la page), « Configuration » si le widget n'a pas de
 * formulaire d'administration — ce dernier cas se constate à la réponse vide du serveur, donc sans
 * avoir à le déclarer quelque part de plus.
 *
 * Accessibilité : les cartes forment une `listbox` navigable aux flèches, avec `aria-selected`.
 */
?>
<form id="live-editor-settings-form" class="nf-le-wiz"
      data-lang-aucun-type="<?php echo $this->lang('Ce widget n\'a pas de variante.') ?>"
      data-lang-aucun-reglage="<?php echo $this->lang('Ce widget n\'a rien à régler.') ?>"
      data-lang-aucun-resultat="<?php echo $this->lang('Aucun widget ne correspond.') ?>">

	<input type="hidden" name="widget" id="live-editor-settings-widget" value="<?php echo utf8_htmlentities($widget) ?>">
	<input type="hidden" name="type"   id="live-editor-settings-type"   value="<?php echo utf8_htmlentities($type) ?>">

	<div class="nf-le-wiz-steps" role="tablist">
		<?php foreach ([
			'widget'   => $this->lang('Widget'),
			'type'     => $this->lang('Type'),
			'title'    => $this->lang('Titre'),
			'settings' => $this->lang('Configuration'),
		] as $cle => $libelle): ?>
			<button type="button" class="nf-le-wiz-step" role="tab"
			        data-step="<?php echo $cle ?>" aria-selected="false" tabindex="-1">
				<span class="nf-le-wiz-step-n" aria-hidden="true"></span>
				<span class="nf-le-wiz-step-l"><?php echo $libelle ?></span>
			</button>
		<?php endforeach ?>
	</div>

	<div class="nf-le-wiz-panels">

		<section class="nf-le-wiz-panel" data-panel="widget" role="tabpanel">
			<label class="nf-le-wiz-filtre">
				<?php echo icon('fas fa-magnifying-glass') ?>
				<input type="search" class="form-control" data-role="filtre" autocomplete="off"
				       placeholder="<?php echo $this->lang('Filtrer les widgets…') ?>"
				       aria-label="<?php echo $this->lang('Filtrer les widgets') ?>">
			</label>

			<div class="nf-le-wiz-cards" role="listbox" aria-label="<?php echo $this->lang('Widget') ?>" data-role="widgets">
				<?php foreach ($widgets as $nom => $libelle): ?>
					<button type="button" class="nf-le-wiz-card" role="option"
					        data-value="<?php echo utf8_htmlentities($nom) ?>"
					        data-recherche="<?php echo utf8_htmlentities(mb_strtolower($libelle . ' ' . $nom)) ?>"
					        aria-selected="<?php echo $nom === $widget ? 'true' : 'false' ?>"
					        tabindex="<?php echo $nom === $widget ? '0' : '-1' ?>">
						<span class="nf-le-wiz-card-i" aria-hidden="true"><?php echo icon($icones[$nom] ?? 'fas fa-puzzle-piece') ?></span>
						<span class="nf-le-wiz-card-l"><?php echo $libelle ?></span>
					</button>
				<?php endforeach ?>
			</div>

			<p class="nf-le-wiz-vide" data-role="vide" hidden></p>
		</section>

		<section class="nf-le-wiz-panel" data-panel="type" role="tabpanel" hidden>
			<div class="nf-le-wiz-cards" role="listbox" aria-label="<?php echo $this->lang('Type') ?>" data-role="types">
				<?php foreach ($types as $w => $liste): ?>
					<?php foreach ($liste as $nom => $libelle): ?>
						<button type="button" class="nf-le-wiz-card nf-le-wiz-card--texte" role="option"
						        data-widget="<?php echo utf8_htmlentities($w) ?>" data-value="<?php echo utf8_htmlentities($nom) ?>"
						        aria-selected="false" tabindex="-1" hidden>
							<span class="nf-le-wiz-card-l"><?php echo $libelle ?></span>
						</button>
					<?php endforeach ?>
				<?php endforeach ?>
			</div>
		</section>

		<section class="nf-le-wiz-panel" data-panel="title" role="tabpanel" hidden>
			<label class="form-label" for="live-editor-settings-title"><?php echo $this->lang('Titre') ?></label>
			<input type="text" class="form-control" id="live-editor-settings-title" name="title"
			       value="<?php echo utf8_htmlentities($title) ?>"
			       placeholder="<?php echo $this->lang('Titre par défaut') ?>">
			<p class="nf-le-wiz-aide"><?php echo $this->lang('Laissez vide pour garder le titre par défaut du widget.') ?></p>
		</section>

		<section class="nf-le-wiz-panel" data-panel="settings" role="tabpanel" hidden>
			<div id="live-editor-settings"
			     data-widget-id="<?php echo (int) $widget_id ?>"
			     data-original-widget="<?php echo utf8_htmlentities($widget) ?>"
			     data-original-type="<?php echo utf8_htmlentities($type) ?>"></div>
		</section>

	</div>
</form>
