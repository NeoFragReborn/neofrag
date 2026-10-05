<?php
/*
 * La fiche d'un membre : au survol de son pseudo (ajax/user/…), et sous un message envoyé par le formulaire de
 * contact. Elle montre ce que le membre montre (Models\User::montre(), chantier A, étape A2, 2026-10-05) : son
 * rang toujours ; ses points, son karma et ses jours de VIP s'il le veut ; son âge et sa présence sauf s'il les
 * cache. Un cadenas marque ce que seul le membre voit de lui.
 */
$prive = '<span class="nf-membre-prive" data-bs-toggle="tooltip" title="'.$this->lang('Visible par toi seul').'">'.icon('fas fa-lock').'</span>';
?>
<div class="user-profile">
	<?php echo $user->avatar() ?>
	<h4 class="mb-3"><?php echo nf_texte($user->username) ?></h4>

	<?php if (($gam = $this->module('gamification'))): $tier = $gam->tier($gam->get($user->id)); ?>
		<div class="user-profile-karma mb-3">
			<span class="badge" style="background-color:<?php echo $tier['color'] ?>;color:<?php echo couleur_lisible_sur($tier['color']) ?>;"><?php echo icon($tier['icon']).' '.nf_texte($gam->nom_palier($tier['name'])) ?></span>
			<?php if ($user->montre('vip') && $gam->is_vip($user->id)): ?><?php echo $gam->vip_badge($user->id).($user->montre_aux_autres('vip') ? '' : $prive) ?><?php endif ?>
			<?php if ($user->montre('karma')): ?><small class="text-muted"><?php echo $gam->get($user->id).' '.$this->lang('karma').($user->montre_aux_autres('karma') ? '' : ' '.$prive) ?></small><?php endif ?>
			<?php if ($user->montre('points')): ?><span class="badge text-bg-secondary"><?php echo icon('fas fa-coins').' '.$gam->get_points($user->id).' '.$this->lang('points') ?></span><?php if (!$user->montre_aux_autres('points')) echo $prive ?><?php endif ?>
		</div>
	<?php endif ?>

	<?php if ($user_group_ids = NeoFrag()->groups($user->id)): ?>
		<div class="user-profile-groups mb-2">
			<?php foreach ($user_group_ids as $gid): ?>
				<?php echo NeoFrag()->groups->display($gid, TRUE, FALSE) ?>
			<?php endforeach ?>
		</div>
	<?php endif ?>

	<?php if ($user->montre('statut')): $is_online = $user->is_online() ?>
		<div class="user-profile-status mb-3">
			<span class="user-profile-status-dot <?php echo $is_online ? 'online' : 'offline' ?>"></span>
			<?php echo ($is_online ? $this->lang('En ligne maintenant') : $this->lang('Hors ligne')).($user->montre_aux_autres('statut') ? '' : ' '.$prive) ?>
		</div>
	<?php endif ?>

	<?php
	$lignes = $liens = [];

	if (($profile = $user->profile()) && $profile())
	{
		if (trim((string) $profile->quote) !== '')
		{
			$lignes[] = '<i class="text-muted">'.nf_texte($profile->quote).'</i>';
		}

		if (($nom = trim($profile->first_name.' '.$profile->last_name)) !== '')
		{
			$lignes[] = nf_texte($nom);
		}

		// L'âge, si le membre le montre — sans la date de naissance, qui en dit davantage ; sinon le sexe seul.
		if ($profile->date_of_birth && $user->montre('age'))
		{
			$lignes[] = $this->label($this->lang('%d an|%d ans', $age = $profile->date_of_birth->interval('today')->y, $age), $profile->sex ? ($profile->sex == 'female' ? 'fas fa-venus' : 'fas fa-mars') : 'fas fa-cake-candles');
		}
		else if ($profile->sex)
		{
			$lignes[] = $this->label($profile->sex == 'female' ? $this->lang('Femme') : $this->lang('Homme'), $profile->sex == 'female' ? 'fas fa-venus' : 'fas fa-mars');
		}

		// Le drapeau n'est posé que pour un code pays CONNU : `image()` rend une adresse même pour un fichier
		// absent, et un pays saisi en toutes lettres (« France ») donnait un drapeau introuvable (2026-09-23).
		$pays = strtolower(trim((string) $profile->country));

		if (($lieu = trim((string) $profile->location)) !== '' || country_name($pays) !== '')
		{
			$lignes[] = $this->label(nf_texte($lieu !== '' ? $lieu : country_name($pays)), country_name($pays) !== '' ? '<img src="'.image('flags/'.$pays.'.png', $this->theme('default')).'" alt="" />' : 'fas fa-map-marker-alt');
		}

		foreach ([
			['website',   'fas fa-globe',       ''],
			['linkedin',  'fab fa-linkedin-in', 'https://www.linkedin.com/in/'],
			['github',    'fab fa-github',      'https://github.com/'],
			['instagram', 'fab fa-instagram',   'https://www.instagram.com/'],
			['twitch',    'fab fa-twitch',      'https://www.twitch.tv/'],
		] as [$champ, $icone, $prefixe])
		{
			$valeur = trim(html_entity_decode((string) $profile->$champ, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

			// Un site saisi sans « https:// » devenait un lien relatif, vers une page du site lui-même.
			if ($valeur !== '' && $prefixe === '' && !preg_match('#^https?://#i', $valeur))
			{
				$valeur = 'https://'.$valeur;
			}

			if ($valeur !== '' && nf_url_sure($prefixe.$valeur))
			{
				$liens[] = '<a href="'.nf_texte($prefixe.$valeur).'" class="btn '.$champ.'" target="_blank" rel="noopener nofollow">'.icon($icone).'</a>';
			}
		}
	}

	foreach ($lignes as $ligne)
	{
		echo '<h6>'.$ligne.'</h6>';
	}
	?>
	<?php if ($liens): ?><div class="socials"><?php echo implode($liens) ?></div><?php endif ?>

	<dl class="user-profile-meta mt-2 mb-0">
		<?php if ($user->registration_date): ?>
		<dt><i class="fas fa-calendar-plus"></i> <?php echo $this->lang('Inscrit') ?></dt>
		<dd><?php echo timetostr($this->lang('d/m/Y'), $user->registration_date) ?></dd>
		<?php endif ?>
		<?php if ($user->last_activity_date && $user->montre('statut')): ?>
		<dt><i class="far fa-clock"></i> <?php echo $this->lang('Vu') ?></dt>
		<dd><?php echo time_span($user->last_activity_date) ?></dd>
		<?php endif ?>
	</dl>

	<div class="user-profile-actions mt-3">
		<a href="<?php echo url('user/'.$user->id.'/'.url_title($user->username)) ?>" class="btn btn-sm btn-outline-secondary"><?php echo icon('far fa-user').' '.$this->lang('Voir le profil') ?></a>
		<?php if ($this->user() && $this->user != $user && $this->module('talks')) echo $this->button()->title('Contacter')->icon('far fa-envelope')->color('outline-primary')->url('talks/new?type=direct&user='.urlencode($user->username)) ?>
	</div>

	<?php
	if (($mod = $this->module('moderation')) && $this->user() && (int)$this->user->id !== (int)$user->id)
	{
		echo '<div class="mt-2 text-center">'.$mod->report_button('profile', (int)$user->id, $this->url->request, NULL, (int)$user->id).'</div>';
	}
	?>
</div>
