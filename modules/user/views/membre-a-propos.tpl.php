<?php
/*
 * L'onglet « À propos » du profil public (chantier A, étape A2) : ce que le membre dit de lui, les champs publics
 * du site, et ses chiffres (`$chiffres`, préparés par le contrôleur selon ce qu'il montre).
 */
$profil  = $user->profile();
$moi     = $this->user() && (int) $this->user->id === (int) $user->id;
$prive   = '<span class="nf-membre-prive" data-bs-toggle="tooltip" title="'.$this->lang('Visible par toi seul').'">'.icon('fas fa-lock').'</span>';
$details = [];

if ($profil())
{
	if (($nom = trim($profil->first_name.' '.$profil->last_name)) !== '')
	{
		$details[] = icon('far fa-user').' '.nf_texte($nom);
	}

	if ($profil->date_of_birth && $user->montre('age'))
	{
		$age       = $profil->date_of_birth->interval('today')->y;
		$details[] = icon('fas fa-cake-candles').' '.$this->lang('%d an|%d ans', $age, $age).($user->montre_aux_autres('age') ? '' : ' '.$prive);
	}

	if ($profil->sex)
	{
		$details[] = $profil->sex === 'female' ? icon('fas fa-venus').' '.$this->lang('Femme') : icon('fas fa-mars').' '.$this->lang('Homme');
	}

	// Le drapeau n'est posé que pour un code pays CONNU : un pays saisi en toutes lettres donnait un drapeau introuvable.
	$pays = strtolower(trim((string) $profil->country));

	if (($lieu = trim((string) $profil->location)) !== '' || country_name($pays) !== '')
	{
		$details[] = (country_name($pays) !== '' ? '<img class="nf-membre-drapeau" src="'.image('flags/'.$pays.'.png', $this->theme('default')).'" alt="" />' : icon('fas fa-location-dot'))
			.' '.nf_texte($lieu !== '' ? $lieu : country_name($pays));
	}

	$liens = [];

	foreach ([
		['website',   'fas fa-globe',       '',                             $this->lang('Site web')],
		['linkedin',  'fab fa-linkedin-in', 'https://www.linkedin.com/in/', 'LinkedIn'],
		['github',    'fab fa-github',      'https://github.com/',          'GitHub'],
		['instagram', 'fab fa-instagram',   'https://www.instagram.com/',   'Instagram'],
		['twitch',    'fab fa-twitch',      'https://www.twitch.tv/',       'Twitch'],
	] as [$champ, $icone, $prefixe, $titre])
	{
		$valeur = trim(html_entity_decode((string) $profil->$champ, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

		// Un site saisi sans « https:// » devenait un lien relatif, vers une page du site lui-même.
		if ($valeur !== '' && $prefixe === '' && !preg_match('#^https?://#i', $valeur))
		{
			$valeur = 'https://'.$valeur;
		}

		if ($valeur !== '' && nf_url_sure($prefixe.$valeur))
		{
			$liens[] = '<a class="btn btn-sm btn-outline-secondary" href="'.nf_texte($prefixe.$valeur).'" target="_blank" rel="noopener nofollow me">'.icon($icone).' '.nf_texte($titre).'</a>';
		}
	}
}

$champs = $this->module('user')->model('fields')->get_public_values($user->id);
?>
<div class="nf-membre-a-propos">
	<div class="nf-membre-colonne">
		<?php if ($profil() && trim((string) $profil->quote) !== ''): ?>
			<blockquote class="nf-membre-citation"><?php echo nf_texte($profil->quote) ?></blockquote>
		<?php endif ?>

		<?php if ($details): ?>
			<ul class="nf-membre-details">
				<?php foreach ($details as $detail): ?><li><?php echo $detail ?></li><?php endforeach ?>
			</ul>
		<?php endif ?>

		<?php if (!empty($liens)): ?>
			<div class="nf-membre-liens"><?php echo implode(' ', $liens) ?></div>
		<?php endif ?>

		<?php if ($champs): ?>
			<dl class="nf-membre-champs">
				<?php foreach ($champs as $champ): ?>
					<dt><?php echo nf_texte($champ['label']) ?></dt>
					<dd>
						<?php if ($champ['type'] === 'url' && filter_var($champ['value'], FILTER_VALIDATE_URL) && nf_url_sure((string) $champ['value'])): ?>
							<a href="<?php echo nf_texte($champ['value']) ?>" target="_blank" rel="noopener nofollow"><?php echo nf_texte($champ['value']) ?></a>
						<?php elseif ($champ['type'] === 'checkbox'): ?>
							<?php echo icon('fas fa-check text-success') ?>
						<?php else: ?>
							<?php echo nl2br(nf_texte($champ['value'])) ?>
						<?php endif ?>
					</dd>
				<?php endforeach ?>
			</dl>
		<?php endif ?>

		<?php if (!($profil() && trim((string) $profil->quote) !== '') && !$details && empty($liens) && !$champs): ?>
			<p class="nf-membre-vide">
				<?php if ($moi): ?>
					<?php echo $this->lang('Ton profil ne dit encore rien de toi.') ?> <a href="<?php echo url('user/profile') ?>"><?php echo $this->lang('Modifier mon profil') ?></a>
				<?php else: ?>
					<?php echo $this->lang('Ce membre ne dit encore rien de lui.') ?>
				<?php endif ?>
			</p>
		<?php endif ?>
	</div>

	<?php if ($chiffres): ?>
		<aside class="nf-membre-chiffres">
			<h2><?php echo $this->lang('En chiffres') ?></h2>
			<dl>
				<?php foreach ($chiffres as $chiffre): ?>
					<div>
						<dt><?php echo icon($chiffre['icone']).' '.nf_texte($chiffre['titre']) ?></dt>
						<dd><?php echo ($chiffre['html'] ?? nf_texte($chiffre['valeur'])).(!empty($chiffre['prive']) ? ' '.$prive : '') ?></dd>
					</div>
				<?php endforeach ?>
			</dl>
		</aside>
	<?php endif ?>
</div>
