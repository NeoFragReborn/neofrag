<?php
$socials = [
	'nf_social_facebook'   => ['Facebook',   'fab fa-facebook-f'],
	'nf_social_twitter'    => ['Twitter',    'fab fa-twitter'],
	'nf_social_youtube'    => ['Youtube',    'fab fa-youtube'],
	'nf_social_twitch'     => ['Twitch',     'fab fa-twitch'],
	'nf_social_discord'    => ['Discord',    'fab fa-discord'],
	'nf_social_github'     => ['GitHub',     'fab fa-github'],
	'nf_social_instagram'  => ['Instagram',  'fab fa-instagram'],
	'nf_social_tiktok'     => ['TikTok',     'fab fa-tiktok']
];

$active = array_filter($socials, function($_, $key){
	return !empty($this->config->$key);
}, ARRAY_FILTER_USE_BOTH);

if (empty($active)) return;
?>
<div class="fg-socials" role="group">
	<?php foreach ($active as $var => $social): list($title, $icon) = $social; ?>
		<a href="<?php echo $this->config->$var ?>" target="_blank" rel="noopener" title="<?php echo $title ?>" aria-label="<?php echo $title ?>" class="fg-social"><?php echo icon($icon) ?></a>
	<?php endforeach ?>
</div>
