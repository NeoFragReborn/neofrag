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
			<span class="nb-logo-text"><?php echo htmlspecialchars($this->config->nf_name) ?></span>
		</a>
		<nav class="nb-links" aria-label="<?php echo $this->lang('Navigation') ?>">
			<a href="<?php echo url('') ?>"><?php echo $this->lang('Accueil') ?></a>
			<a href="<?php echo url('news') ?>"><?php echo $this->lang('Actualités') ?></a>
			<a href="<?php echo url('forum') ?>"><?php echo $this->lang('Forum') ?></a>
			<a href="<?php echo url('gallery') ?>"><?php echo $this->lang('Galerie') ?></a>
			<a href="<?php echo url('members') ?>"><?php echo $this->lang('Membres') ?></a>
		</nav>
		<div class="nb-actions">
			<?php if ($logged): ?>
				<a class="nb-btn nb-btn-ghost" href="<?php echo url('user') ?>"><i class="fas fa-user-astronaut"></i> <span><?php echo htmlspecialchars($this->user->username) ?></span></a>
				<?php if ($is_admin): ?><a class="nb-btn nb-btn-primary" href="<?php echo url('admin') ?>"><i class="fas fa-gauge-high"></i> <?php echo $this->lang('Admin') ?></a><?php endif ?>
			<?php else: ?>
				<?php /* Masqué quand les inscriptions sont fermées : la route répond 404 par conception. */ ?>
				<?php if ($this->config->nf_registration_status): ?><a class="nb-btn nb-btn-ghost" href="<?php echo url('user/registration') ?>"><?php echo $this->lang('Inscription') ?></a><?php endif ?>
				<a class="nb-btn nb-btn-primary" href="<?php echo url('user/login') ?>"><?php echo $this->lang('Connexion') ?></a>
			<?php endif ?>
		</div>
	</div>
</nav>

<main class="nb-main<?php echo $is_home ? ' nb-main-home' : '' ?>">
	<?php if ($is_home): ?>
	<header class="nb-hero">
		<div class="nb-hero-bg" aria-hidden="true"></div>
		<div class="nb-hero-in">
			<span class="nb-chip"><i class="fas fa-meteor"></i> <?php echo $this->lang('Communauté') ?></span>
			<h1><?php echo $this->lang('Bienvenue sur') ?> <span class="nb-grad"><?php echo htmlspecialchars($this->config->nf_name) ?></span></h1>
			<?php if ($desc = $this->config->nf_description): ?>
			<p class="nb-hero-lead"><?php echo htmlspecialchars($desc) ?></p>
			<?php endif ?>
		</div>
	</header>
	<?php endif ?>

	<div class="nb-shell container">
		<?php if (!$is_home && ($zone = $this->output->zone(1))): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>

		<?php if ($zone = $this->output->zone(2)): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>

		<?php if ($zone = $this->output->zone(3)): ?>
			<div class="nb-zone"><?php echo $zone ?></div>
		<?php endif ?>
	</div>
</main>

<footer class="nb-foot">
	<div class="nb-foot-in">
		<div class="nb-foot-top">
			<a class="nb-logo nb-logo-foot" href="<?php echo url('') ?>">
				<span class="nb-mark"><i class="fas fa-meteor"></i></span>
				<span class="nb-logo-text"><?php echo htmlspecialchars($this->config->nf_name) ?></span>
			</a>
			<?php echo $this->view('socials') ?>
		</div>
		<div class="nb-foot-bar">
			<div class="nb-copy">
				© <?php echo date('Y') ?> <span class="fg-site-name"><?php echo htmlspecialchars($this->config->nf_name) ?></span>
				· <?php echo $this->lang('Propulsé par') ?> <a href="https://neofr.ag" target="_blank" rel="noopener">NeoFrag Reborn</a>
			</div>
			<div class="nb-foot-tools">
				<?php
				$nf_themes = array_map('strval', NeoFrag()->db->select('a.name')->from('nf_addon a')->join('nf_addon_type t', 't.id = a.type_id')->where('t.name', 'theme')->where('a.name !=', 'admin')->order_by('a.name')->get());
				if (count($nf_themes) > 1):
					$nf_cur = $this->config->nf_default_theme;
					if (!empty($_COOKIE['nf_theme']) && in_array($nf_c = preg_replace('/[^a-z0-9_]/i', '', (string) $_COOKIE['nf_theme']), $nf_themes, TRUE)) { $nf_cur = $nf_c; }
				?>
				<div class="nf-theme-switch dropup">
					<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo icon('fas fa-palette') ?> <?php echo htmlspecialchars(ucfirst($nf_cur)) ?></button>
					<div class="dropdown-menu dropdown-menu-end">
						<?php foreach ($nf_themes as $nf_t): ?>
						<button type="button" class="dropdown-item<?php echo $nf_t === $nf_cur ? ' active' : '' ?>" data-theme-pick="<?php echo htmlspecialchars($nf_t, ENT_QUOTES) ?>"><?php echo icon('fas fa-palette') ?> <?php echo htmlspecialchars(ucfirst($nf_t)) ?></button>
						<?php endforeach ?>
					</div>
				</div>
				<?php endif ?>
				<?php if (count($this->config->langs) > 1): $cur = $this->config->lang->info(); ?>
				<form method="post" action="<?php echo url('ajax/settings/languages') ?>" class="fg-lang dropup">
					<input type="hidden" name="url" value="<?php echo htmlspecialchars($this->url->base.implode('/', array_merge([$cur->name], $this->url->segments)).$this->url->query) ?>" />
					<button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $cur->icon ?> <?php echo strtoupper($cur->name) ?></button>
					<div class="dropdown-menu dropdown-menu-end">
						<?php foreach ($this->config->langs as $l): $i = $l->info(); ?>
						<button type="submit" name="language" value="<?php echo $i->name ?>" class="dropdown-item<?php echo $i->name === $cur->name ? ' active' : '' ?>"><?php echo $i->icon ?> <?php echo htmlspecialchars((string)$i->title) ?></button>
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
