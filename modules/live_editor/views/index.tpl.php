<form target="live-editor-iframe" action="<?php echo url() ?>" method="post">
	<input type="hidden" name="live_editor" value="<?php echo $live_editor = $this->session('live_editor') ?: $this->output->live_editor() ^ \NF\NeoFrag\Core\Output::WIDGETS ?>" />
	<nav class="live-editor-navbar navbar navbar-expand-lg">
		<a class="navbar-brand" href="<?php echo url('admin/live-editor') ?>"><?php echo icon('fas fa-bolt') ?><span><b>Live</b><span data-typer="Editor"></span></span></a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#modules-links-collapse" aria-controls="modules-links-collapse" aria-expanded="false" aria-label="<?php echo $this->lang('Menu') ?>">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="modules-links-collapse">
			<ul class="navbar-nav me-auto align-items-center">
				<li class="nav-item dropdown">
					<a class="nav-link" href="#" id="navbarDropdownModules" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?php echo icon('fas fa-link').' '.$this->lang('Navigation').' '.icon('fas fa-angle-down') ?>
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdownModules">
						<?php foreach ($modules as $name => $title): ?>
							<a class="dropdown-item" href="<?php echo url($name) ?>"><?php echo $title ?></a>
						<?php endforeach ?>
					</div>
				</li>
				<li class="nav-item ms-2">
					<span id="live-editor-map"><?php echo icon('fas fa-spinner fa-spin').' '.$this->lang('Chargement en cours...') ?></span>
				</li>
			</ul>
			<ul class="navbar-nav ms-auto align-items-center">
				<li class="nav-item">
					<div class="btn-group" role="group" aria-label="<?php echo $this->lang('Mode d\'édition') ?>">
						<button type="button" class="btn live-editor-mode<?php echo $live_editor & \NF\NeoFrag\Core\Output::ZONES   ? ' active' : '' ?>" data-mode="<?php echo \NF\NeoFrag\Core\Output::ZONES ?>"><?php echo icon('far fa-square').' '.$this->lang('Zones') ?></button>
						<button type="button" class="btn live-editor-mode<?php echo $live_editor & \NF\NeoFrag\Core\Output::ROWS    ? ' active' : '' ?>" data-mode="<?php echo \NF\NeoFrag\Core\Output::ROWS ?>"><?php echo icon('fas fa-grip-lines').' '.$this->lang('Lignes') ?></button>
						<button type="button" class="btn live-editor-mode<?php echo $live_editor & \NF\NeoFrag\Core\Output::COLS    ? ' active' : '' ?>" data-mode="<?php echo \NF\NeoFrag\Core\Output::COLS ?>"><?php echo icon('fas fa-columns').' '.$this->lang('Colonnes') ?></button>
						<button type="button" class="btn live-editor-mode active" data-mode="<?php echo \NF\NeoFrag\Core\Output::WIDGETS ?>"><?php echo icon('fas fa-th-large').' '.$this->lang('Widgets') ?></button>
					</div>
				</li>
				<li class="nav-item dropdown ms-2">
					<a class="nav-link live-editor-screen" href="#" id="navbarDropdownScreen" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="<?php echo $this->lang('Aperçu') ?>">
						<?php echo icon('fas fa-desktop').icon('fas fa-angle-down') ?>
					</a>
					<div class="dropdown-menu screen dropdown-menu-end" aria-labelledby="navbarDropdownScreen">
						<ul class="list-unstyled m-0">
							<li><button type="button" class="btn live-editor-screen active" data-width="100%" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo $this->lang('Ordinateur') ?>"><?php echo icon('fas fa-desktop') ?></button></li>
							<li><button type="button" class="btn live-editor-screen" data-width="992px" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo $this->lang('Tablette paysage') ?>"><?php echo icon('fas fa-tablet-alt fa-rotate-270') ?></button></li>
							<li><button type="button" class="btn live-editor-screen" data-width="768px" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo $this->lang('Tablette portrait') ?>"><?php echo icon('fas fa-tablet-alt') ?></button></li>
							<li><button type="button" class="btn live-editor-screen" data-width="400px" data-bs-toggle="tooltip" data-bs-placement="left" title="<?php echo $this->lang('Smartphone') ?>"><?php echo icon('fas fa-mobile-alt') ?></button></li>
						</ul>
					</div>
				</li>
				<li class="nav-item ms-2">
					<a href="<?php echo url('admin') ?>" class="nav-link" title="<?php echo $this->lang('Tableau de bord') ?>"><?php echo icon('fas fa-tachometer-alt') ?></a>
				</li>
				<li class="nav-item">
					<a href="<?php echo url() ?>" class="nav-link text-danger" title="<?php echo $this->lang('Quitter') ?>"><?php echo icon('fas fa-power-off') ?></a>
				</li>
			</ul>
		</div>
	</nav>
</form>
<?php echo icon('far fa-save live-editor-save') ?>
<div class="live-editor-styles-row">
	<?php echo $styles_row ?>
</div>
<div class="live-editor-styles-widget">
	<?php echo $styles_widget ?>
</div>
<div class="live-editor-iframe">
	<iframe name="live-editor-iframe" src=""></iframe>
</div>

<template id="nf-le-tpl-modal-style">
	<div class="modal live-editor-modal fade" role="dialog">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?php echo icon('fas fa-paint-brush') ?> <span class="nf-le-modal-title-text"></span></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer">
					<button type="button" class="btn btn-dark"  data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
					<button type="button" class="btn btn-info"  data-action="confirm"><?php echo $this->lang('Valider') ?></button>
				</div>
			</div>
		</div>
	</div>
</template>

<template id="nf-le-tpl-modal-settings">
	<div class="modal live-editor-modal fade" role="dialog">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?php echo icon('fas fa-cogs') ?> <span class="nf-le-modal-title-text"></span></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer nf-le-wiz-footer">
					<button type="button" class="btn btn-dark" data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
					<span class="nf-le-wiz-nav">
						<button type="button" class="btn btn-secondary" data-action="wiz-prev"><?php echo icon('fas fa-angle-left') ?> <?php echo $this->lang('Précédent') ?></button>
						<button type="button" class="btn btn-secondary" data-action="wiz-next"><?php echo $this->lang('Suivant') ?> <?php echo icon('fas fa-angle-right') ?></button>
					</span>
					<button type="button" class="btn btn-info" data-action="confirm"><?php echo $this->lang('Valider') ?></button>
				</div>
			</div>
		</div>
	</div>
</template>

<template id="nf-le-tpl-modal-fork">
	<div class="modal live-editor-modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?php echo icon('fas fa-code-branch') ?> <span class="nf-le-modal-title-text"><?php echo $this->lang('Revenir à la disposition commune') ?></span></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
				</div>
				<div class="modal-body"><?php echo $this->lang('Êtes-vous sûr(e) de vouloir revenir à la disposition commune ?<br />Toutes les <b>colonnes</b> et <b>widgets</b> associés à cette zone seront perdus.') ?></div>
				<div class="modal-footer">
					<button type="button" class="btn btn-dark"   data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
					<button type="button" class="btn btn-danger" data-action="confirm"><?php echo $this->lang('Continuer') ?></button>
				</div>
			</div>
		</div>
	</div>
</template>

<template id="nf-le-tpl-modal-delete">
	<div class="modal live-editor-modal fade" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><?php echo icon('far fa-trash-alt') ?> <span class="nf-le-modal-title-text"><?php echo $this->lang('Confirmation de suppression') ?></span></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo $this->lang('Fermer') ?>"></button>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer">
					<button type="button" class="btn btn-dark"   data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
					<button type="button" class="btn btn-danger" data-action="confirm"><?php echo icon('far fa-trash-alt') ?> <?php echo $this->lang('Supprimer') ?></button>
				</div>
			</div>
		</div>
	</div>
</template>
