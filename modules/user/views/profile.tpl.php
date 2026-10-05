<div class="user-profile">
	<?php echo $user->avatar() ?>
	<h4 class="mb-3"><?php echo $user->username ?></h4>

	<?php if (($gam = $this->module('gamification'))): $karma = $gam->get($user->id); $tier = $gam->tier($karma); ?>
		<div class="user-profile-karma mb-3">
			<?php if ($gam->is_vip($user->id)): ?><?php echo $gam->vip_badge($user->id) ?> <?php endif ?>
			<span class="badge" style="background-color:<?php echo $tier['color'] ?>;color:<?php echo couleur_lisible_sur($tier['color']) ?>;"><?php echo icon($tier['icon']).' '.nf_texte($tier['name']) ?></span>
			<small class="text-muted"><?php echo $karma.' '.$this->lang('karma') ?></small>
			<span class="badge text-bg-secondary"><?php echo icon('fas fa-coins').' '.$gam->get_points($user->id).' '.$this->lang('points') ?></span>
			<?php if ($gam->is_vip($user->id)): ?><small class="text-muted"><?php echo $this->lang('VIP — %d j restants', $gam->vip_days_left($user->id)) ?></small><?php endif ?>
		</div>
	<?php endif ?>

	<?php
	// Statut online + groupes (infos toujours visibles)
	$user_group_ids = NeoFrag()->groups($user->id);
	$is_online      = $user->is_online();
	?>

	<?php if (!empty($user_group_ids)): ?>
		<div class="user-profile-groups mb-2">
			<?php foreach ($user_group_ids as $gid): ?>
				<?php echo NeoFrag()->groups->display($gid, TRUE, FALSE) ?>
			<?php endforeach ?>
		</div>
	<?php endif ?>

	<div class="user-profile-status mb-3">
		<span class="user-profile-status-dot <?php echo $is_online ? 'online' : 'offline' ?>"></span>
		<?php echo $is_online ? $this->lang('En ligne maintenant') : $this->lang('Hors ligne') ?>
	</div>

	<?php
		if (($profile = $user->profile()) && $profile())
		{
			echo $this	->array
						->append_if($quote = $profile->quote, '<i class="text-muted">'.$quote.'</i>')
						->append_if(trim($profile->first_name.' '.$profile->last_name), trim($profile->first_name.' '.$profile->last_name))
						->append_if($profile->sex || $profile->date_of_birth, function() use ($profile){
							$sex = $profile->sex;
							$date_of_birth = $profile->date_of_birth;
							return $this->label($date_of_birth ? $this->lang('%d an|%d ans', $age = $date_of_birth->interval('today')->y, $age) : ($sex == 'female' ? 'Femme' : 'Homme'), $sex ? ($sex == 'female' ? 'fas fa-venus' : 'fas fa-mars').' '.$sex : 'fas fa-birthday-cake')
										->tooltip_if($date_of_birth, function($date){
											return $this->no_translate($date->short_date());
										});
						})
						// Le drapeau n'est posé que pour un code pays CONNU : `image()` rend une adresse même
						// pour un fichier absent, et un pays saisi en toutes lettres (« France ») donnait
						// un drapeau introuvable à côté d'un libellé vide (2026-09-23).
						->append_if($profile->location || country_name($profile->country) !== '', function() use ($profile){
							$country = strtolower(trim((string) $profile->country));
							$nom     = country_name($country);
							return $this->label($this->no_translate($profile->location) ?: $nom, $nom !== '' ? '<img src="'.image('flags/'.$country.'.png', $this->theme('default')).'" alt="" />' : 'fas fa-map-marker-alt');
						})
						->filter()
						->each(function($a){
							return '<h6>'.$a.'</h6>';
						});

			$socials = $this	->array([
									['website',   'fas fa-globe',       ''],
									['linkedin',  'fab fa-linkedin-in', 'https://www.linkedin.com/in/'],
									['github',    'fab fa-github',      'https://github.com/'],
									['instagram', 'fab fa-instagram',   'https://www.instagram.com/'],
									['twitch',    'fab fa-twitch',      'https://www.twitch.tv/']
								])
								->filter(function($a) use ($profile){
									return $profile->{$a[0]};
								})
								->each(function($a) use ($profile){
									return '<a href="'.$a[2].$profile->{$a[0]}.'" class="btn '.$a[0].'" target="_blank">'.icon($a[1]).'</a>';
								});
		}
	?>
	<?php if (isset($socials) && !$socials->empty()): ?><div class="socials"><?php echo $socials ?></div><?php endif ?>

	<dl class="user-profile-meta mt-2 mb-0">
		<?php if ($user->registration_date): ?>
		<dt><i class="fas fa-calendar-plus"></i> <?php echo $this->lang('Inscrit') ?></dt>
		<dd><?php echo timetostr($this->lang('d/m/Y'), $user->registration_date) ?></dd>
		<?php endif ?>
		<?php if ($user->last_activity_date): ?>
		<dt><i class="far fa-clock"></i> <?php echo $this->lang('Vu') ?></dt>
		<dd><?php echo time_span($user->last_activity_date) ?></dd>
		<?php endif ?>
	</dl>

	<div class="user-profile-actions mt-3">
		<a href="<?php echo url('user/'.$user->id.'/'.url_title($user->username)) ?>" class="btn btn-sm btn-outline-secondary"><?php echo icon('far fa-user').' '.$this->lang('Voir le profil') ?></a>
		<?php if ($this->user() && $this->user != $user) echo $this->button()->title('Contacter')->icon('far fa-envelope')->color('outline-primary')->url('talks/new?type=direct&user='.urlencode($user->username)) ?>
	</div>

	<?php
	if (($mod = $this->module('moderation')) && $this->user() && (int)$this->user->id !== (int)$user->id)
	{
		echo '<div class="mt-2 text-center">'.$mod->report_button('profile', (int)$user->id, $this->url->request, NULL, (int)$user->id).'</div>';
	}
	?>
</div>
