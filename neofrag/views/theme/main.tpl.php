<!DOCTYPE html>
<html lang="<?php echo $this->config->lang->info()->name ?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, maximum-scale=1, initial-scale=1, user-scalable=0">
<meta content="IE=edge, chrome=1" http-equiv="X-UA-Compatible">
<script>(function(){try{var k=<?php echo $this->url->admin ? "'nf-admin-theme'" : "'nf-dungeon-theme'" ?>;var t=localStorage.getItem(k);if(!t){t=(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<?php if ($this->config->nf_theme_color): ?>
<meta name="theme-color" content="<?php echo $this->config->nf_theme_color ?>">
<?php endif ?>
<?php if ($this->config->nf_analytics) echo $this->view('theme/analytics') ?>
<?php if ($this->config->nf_humans_txt): ?>
<link rel="author" href="<?php echo url('humans.txt') ?>" type="text/plain">
<?php endif ?>
<link rel="shortcut icon" href="<?php echo $path = ($this->config->nf_favicon && ($favicon = NeoFrag()->model2('file', $this->config->nf_favicon)->path())) ? $favicon : image('favicon.png') ?>" type="<?php echo get_mime_by_extension(extension($path)) ?>">
<?php echo $this->output->css() ?>
<?php foreach ($this->config->langs as $lang): ?>
<link rel="alternate" href="<?php echo $this->url->base.implode('/', array_merge([$lang->info()->name], $this->url->segments)).$this->url->query ?>" hreflang="<?php echo $lang->info()->name ?>">
<?php endforeach ?>
<?php
// SEO : description, canonical, Open Graph et Twitter Card. URLs absolues (les crawlers les exigent).
$nf_origin    = ($this->url->https ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
$nf_canonical = $nf_origin.$this->url->base.implode('/', array_merge([$this->config->lang->info()->name], $this->url->segments));
$nf_seo_desc  = trim((string)($description ?? $this->config->nf_description));
$nf_og_image  = '';
if ($this->config->nf_logo && ($nf_img = NeoFrag()->model2('file', $this->config->nf_logo)->path())) {
	$nf_og_image = $nf_img;
} else if ($this->config->nf_favicon && ($nf_fav = NeoFrag()->model2('file', $this->config->nf_favicon)->path())) {
	$nf_og_image = $nf_fav;
}
if ($nf_og_image && strpos($nf_og_image, '://') === FALSE) {
	$nf_og_image = $nf_origin.'/'.ltrim($nf_og_image, '/');
}
?>
<?php if ($nf_seo_desc): ?>
<meta name="description" content="<?php echo htmlspecialchars($nf_seo_desc, ENT_QUOTES) ?>">
<?php endif ?>
<link rel="canonical" href="<?php echo htmlspecialchars($nf_canonical, ENT_QUOTES) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars((string)$this->config->nf_name, ENT_QUOTES) ?>">
<meta property="og:title" content="<?php echo htmlspecialchars((string)$title, ENT_QUOTES) ?>">
<?php if ($nf_seo_desc): ?>
<meta property="og:description" content="<?php echo htmlspecialchars($nf_seo_desc, ENT_QUOTES) ?>">
<?php endif ?>
<meta property="og:url" content="<?php echo htmlspecialchars($nf_canonical, ENT_QUOTES) ?>">
<?php if ($nf_og_image): ?>
<meta property="og:image" content="<?php echo htmlspecialchars($nf_og_image, ENT_QUOTES) ?>">
<?php endif ?>
<meta name="twitter:card" content="<?php echo $nf_og_image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?php echo htmlspecialchars((string)$title, ENT_QUOTES) ?>">
<?php if ($nf_seo_desc): ?>
<meta name="twitter:description" content="<?php echo htmlspecialchars($nf_seo_desc, ENT_QUOTES) ?>">
<?php endif ?>
<?php if ($nf_og_image): ?>
<meta name="twitter:image" content="<?php echo htmlspecialchars($nf_og_image, ENT_QUOTES) ?>">
<?php endif ?>
<title><?php echo $title ?></title>
</head>
<body>
<?php if ($this->config->nf_maintenance && !$this->url->admin && isset($this->user) && $this->access->effective_admin() && $this->output->module()->name != 'live_editor'): ?>
	<div class="bg-danger py-2">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-6 text-white"><?php echo icon('fas fa-power-off').' '.$this->lang('Site en opération de maintenance') ?></div>
				<div class="col-6 text-right"><a href="<?php echo url('admin/settings/maintenance') ?>" class="btn btn-outline-light"><?php echo $this->lang('Ouvrir le site') ?></a></div>
			</div>
		</div>
	</div>
<?php endif ?>
<?php
// R1.9 — Bandeau preview persistent : indique que l'admin voit le site avec les permissions d'un autre rôle/user.
if (isset($this->user) && $this->user->admin && method_exists($this->access, 'get_preview_target') && ($preview = $this->access->get_preview_target())):
?>
	<style>
	.nf-preview-banner { position: fixed; top: 0; left: 0; right: 0; z-index: 1100; background: #ffc107; color: #212529; padding: 8px 0; border-bottom: 2px solid #d39e00; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
	.nf-preview-banner a.btn { font-weight: 500; }
	body.nf-preview-active { padding-top: 46px !important; }
	</style>
	<script>document.body.classList.add('nf-preview-active');</script>
	<div class="nf-preview-banner">
		<div class="container-fluid">
			<div class="row align-items-center">
				<div class="col">
					<i class="fas fa-eye"></i>
					<strong><?php echo $this->lang('Mode preview actif') ?></strong> ·
					<?php echo $this->lang('Tu vois le site comme %s : <strong>%s</strong>', $preview['type'] === 'role' ? $this->lang('rôle') : $this->lang('user'), htmlspecialchars($preview['label'])) ?>
					<small class="ml-2 text-muted">
						<?php echo $this->lang('(actif depuis %s, expire dans %d min)', date('H:i', $preview['started_at']), max(0, ceil((1800 - (time() - $preview['started_at'])) / 60))) ?>
					</small>
				</div>
				<div class="col-auto">
					<a href="<?php echo url('admin/access/preview/exit') ?>" class="btn btn-sm btn-dark">
						<i class="fas fa-times"></i> <?php echo $this->lang('Quitter le preview') ?>
					</a>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>
<?php echo $body ?>
<?php echo $debug_bar ?>
<?php echo $this->output->js() ?>
<script type="text/javascript">
$(function(){
	$('body').trigger('nf.load');

	$('body').popover({
		selector: '[data-toggle=popover]',
		container: 'body',
		trigger: 'hover'
	});

	$('body').tooltip({
		selector: '[data-toggle=tooltip]'
	});

	<?php echo $this->output->js_load() ?>
});
</script>
<?php if (empty($_COOKIE['nf_consent'])): ?>
<style>
/* Couleurs via tokens du thème actif (dungeon puis admin), fallback codé en dur. */
.nf-cookie-banner {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: var(--dungeon-surface-2, var(--nf-surface-2, #2b373a));
	color: var(--dungeon-text, var(--nf-text, #fff));
	padding: 16px 20px;
	z-index: 9999;
	border-top: 1px solid var(--dungeon-border, var(--nf-border, rgba(255,255,255,0.12)));
	box-shadow: 0 -4px 12px rgba(0,0,0,0.2);
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 16px;
	font-size: 14px;
}
.nf-cookie-banner__text { flex: 1 1 280px; line-height: 1.5; }
.nf-cookie-banner__text a { color: var(--dungeon-link, var(--nf-accent, #03c1a2)); }
.nf-cookie-banner__buttons { display: flex; gap: 8px; flex-wrap: wrap; }
.nf-cookie-banner__btn {
	padding: 8px 16px;
	border: none;
	border-radius: var(--dungeon-radius-sm, var(--nf-radius-sm, 4px));
	cursor: pointer;
	font-weight: 500;
	font-size: 13px;
}
.nf-cookie-banner__btn--accept { background: var(--dungeon-accent, var(--nf-accent, #03c1a2)); color: #fff; }
.nf-cookie-banner__btn--reject { background: transparent; color: var(--dungeon-text, var(--nf-text, #fff)); border: 1px solid var(--dungeon-border-strong, var(--nf-border, #888)); }
.nf-cookie-banner__btn:hover { opacity: 0.85; }
</style>
<div id="nf-cookie-banner" class="nf-cookie-banner" role="dialog" aria-label="<?php echo $this->lang('Consentement aux cookies') ?>">
	<div class="nf-cookie-banner__text">
		<strong>🍪 <?php echo $this->lang('Cookies & confidentialité') ?></strong> — <?php echo $this->lang('Ce site utilise des cookies essentiels pour fonctionner. Vous pouvez accepter les cookies analytiques pour nous aider à améliorer le site, ou les refuser.') ?> <a href="<?php echo url('mentions-legales') ?>"><?php echo $this->lang('En savoir plus') ?></a>
	</div>
	<div class="nf-cookie-banner__buttons">
		<button class="nf-cookie-banner__btn nf-cookie-banner__btn--accept" onclick="nfCookieConsent('full')"><?php echo $this->lang('Tout accepter') ?></button>
		<button class="nf-cookie-banner__btn nf-cookie-banner__btn--reject" onclick="nfCookieConsent('essentials')"><?php echo $this->lang('Refuser non-essentiels') ?></button>
	</div>
</div>
<script>
function nfCookieConsent(level) {
	var d = new Date();
	d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));
	document.cookie = "nf_consent=" + level + ";expires=" + d.toUTCString() + ";path=/;SameSite=Lax";
	var banner = document.getElementById('nf-cookie-banner');
	if (banner) banner.style.display = 'none';
	document.dispatchEvent(new CustomEvent('nf:consent', { detail: { level: level } }));
}
</script>
<?php endif ?>
</body>
</html>
