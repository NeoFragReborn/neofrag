<?php
/**
 * Nebula — NeoFrag Reborn. Chrome 100% propre (aucun héritage forge/granite/
 * blockcraft) : navbar glass sticky + hero d'accueil + contenu en conteneur +
 * footer dédié. Ambiance « nebula » (glows teal/cyan) gérée en CSS.
 */
$is_home = ((string) $this->url->request === '');
$logged  = (bool) $this->user();
$is_admin = $logged && $this->access->effective_admin();
?>
<nav class="nb-nav" id="nb-nav">
	<div class="nb-nav-in">
		<a class="nb-logo" href="<?php echo url('') ?>">
			<span class="nb-mark"><i class="fas fa-meteor"></i></span>
			<span class="nb-logo-text"><?php echo nf_texte($this->config->nf_name) ?></span>
		</a>
		<?php /* LE MENU vient de la zone « Navigation », rendue ICI, dans la barre. Il etait ecrit
		        en dur jusqu'au 2026-09-22 : cinq liens que personne ne pouvait corriger depuis
		        l'administration, qui restaient affiches meme module desactive, et qui faisaient
		        DOUBLON avec le widget Navigation que l'installation placait sous l'en-tete. */ ?>
		<?php if ($menu = $this->output->region('navigation')): ?>
			<nav class="nb-links" id="nb-links" aria-label="<?php echo $this->lang('Navigation') ?>"><?php echo $menu ?></nav>
		<?php endif ?>
		<div class="nb-actions">
			<?php /* Sur un téléphone, le menu se range dans un panneau que ce bouton déroule. Il était
			        simplement MASQUÉ sous 860 px : aucun moyen de naviguer (signalé le 2026-09-23). */ ?>
			<?php if ($menu): ?>
				<button class="nb-burger" id="nb-burger" type="button" aria-label="<?php echo $this->lang('Menu') ?>" aria-expanded="false" aria-controls="nb-links"><i class="fas fa-bars"></i></button>
			<?php endif ?>
			<?php if ($logged): ?>
				<a class="nb-btn nb-btn-ghost" href="<?php echo url('user') ?>"><i class="fas fa-user-astronaut"></i> <span><?php echo nf_texte($this->user->username) ?></span></a>
				<?php if ($is_admin): ?><a class="nb-btn nb-btn-primary" href="<?php echo url('admin') ?>"><i class="fas fa-gauge-high"></i> <span><?php echo $this->lang('Admin') ?></span></a><?php endif ?>
			<?php else: ?>
				<?php /* Masqué quand les inscriptions sont fermées : la route répond 404 par conception. */ ?>
				<?php if ($this->config->nf_registration_status): ?><a class="nb-btn nb-btn-ghost" href="<?php echo url('user/registration') ?>"><?php echo $this->lang('Inscription') ?></a><?php endif ?>
				<a class="nb-btn nb-btn-primary" href="<?php echo url('user/login') ?>"><?php echo $this->lang('Connexion') ?></a>
			<?php endif ?>
		</div>
	</div>
</nav>

<?php /* Zone « Header » du thème : déclarée dans __info(), elle doit être rendue — sinon un
        widget qu'on y place disparaît sans rien dire. */ ?>
<?php if ($zone = $this->output->region('header')): ?>
	<div class="nb-shell container"><div class="nb-zone"><?php echo $zone ?></div></div>
<?php endif ?>

<main class="nb-main<?php echo $is_home ? ' nb-main-home' : '' ?>">
	<?php if ($is_home): ?>
	<header class="nb-hero">
		<div class="nb-hero-bg" aria-hidden="true"></div>
		<div class="nb-hero-in">
			<span class="nb-chip"><i class="fas fa-meteor"></i> <?php echo $this->lang('Communauté') ?></span>
			<h1><?php echo $this->lang('Bienvenue sur') ?> <span class="nb-grad"><?php echo nf_texte($this->config->nf_name) ?></span></h1>
			<?php if ($desc = $this->config->nf_description): ?>
			<p class="nb-hero-lead"><?php echo nf_texte($desc) ?></p>
			<?php endif ?>
		</div>
	</header>
	<?php endif ?>

	<div class="nb-shell container">
		<?php if (!$is_home && ($zone = $this->output->region('before_content'))): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>

		<?php if ($zone = $this->output->region('content')): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>

		<?php if ($zone = $this->output->region('after_content')): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>
	</div>
</main>

<footer class="nb-foot">
	<div class="nb-foot-in">
		<?php if ($zone = $this->output->region('footer')): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>
		<div class="nb-foot-top">
			<a class="nb-logo nb-logo-foot" href="<?php echo url('') ?>">
				<span class="nb-mark"><i class="fas fa-meteor"></i></span>
				<span class="nb-logo-text"><?php echo nf_texte($this->config->nf_name) ?></span>
			</a>
			<?php echo $this->view('socials') ?>
		</div>
		<div class="nb-foot-bar">
			<div class="nb-copy">
				© <?php echo date('Y') ?> <span class="fg-site-name"><?php echo nf_texte($this->config->nf_name) ?></span>
				· <?php echo $this->lang('Propulsé par') ?> <a href="https://neofrag-reborn.xyz" target="_blank" rel="noopener">NeoFrag Reborn</a>
				· <?php echo nf_liens_legaux() ?>
			</div>
			<div class="nb-foot-tools">
				<?php echo nf_selecteur_theme() ?>
				<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
				<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="fg-lang dropup">
					<input type="hidden" name="url" value="<?php echo nf_texte($this->url->base.trim($cur->name.'/'.nf_chemin_public(), '/').$this->url->query) ?>" />
					<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $cur->icon ?> <?php echo strtoupper($cur->name) ?></button>
					<div class="dropdown-menu dropdown-menu-end">
						<?php foreach ($this->config->langs as $l): $i = $l->info(); ?>
						<button type="submit" name="language" value="<?php echo $i->name ?>" class="dropdown-item<?php echo $i->name === $cur->name ? ' active' : '' ?>"><?php echo $i->icon ?> <?php echo nf_texte($i->title) ?></button>
						<?php endforeach ?>
					</div>
				</form>
				<?php endif ?>
			</div>
		</div>
		<div class="nb-credit">
			<?php echo $this->lang('NeoFrag Reborn est la continuité communautaire de NeoFrag, créé par Michaël BILCOT &amp; Jérémy VALENTIN. Open source, LGPLv3.') ?>
		</div>
	</div>
</footer>
