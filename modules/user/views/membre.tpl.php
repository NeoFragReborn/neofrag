<?php
/*
 * Le profil public d'un membre (chantier A, étape A2, 2026-10-05) : sa couverture en bannière, son avatar qui la
 * chevauche, son pseudo, son rang, ses groupes et sa présence — ce qu'il montre, Models\User::montre() —, les
 * boutons, puis les onglets (User::onglets_profil()), chacun à son adresse, et la page de l'onglet ouvert.
 */
$profil      = $user->profile();
$couverture  = $profil() ? (string) $profil->cover->path() : '';
$moi         = $this->user() && (int) $this->user->id === (int) $user->id;
$groupes     = NeoFrag()->groups->visibles($user->id);
$adresse     = 'user/'.(int) $user->id.'/'.url_title((string) $user->username);
$gamification = $this->module('gamification');
$prive       = '<span class="nf-membre-prive" data-bs-toggle="tooltip" title="'.$this->lang('Visible par toi seul').'">'.icon('fas fa-lock').'</span>';
?>
<div class="nf-membre">
	<div class="nf-membre-entete">
		<div class="nf-membre-couverture<?php echo $couverture === '' ? ' nf-membre-couverture-vide' : '' ?>"<?php if ($couverture !== '') echo ' style="background-image: url(\''.nf_texte($couverture).'\')"' ?>></div>
		<div class="nf-membre-identite">
			<?php echo $user->avatar() ?>
			<div class="nf-membre-nom">
				<h1><?php echo nf_texte($user->username) ?></h1>
				<div class="nf-membre-etiquettes">
					<?php if ($gamification): $palier = $gamification->tier($gamification->get($user->id)) ?>
						<span class="badge nf-membre-rang" style="background-color:<?php echo $palier['color'] ?>;color:<?php echo couleur_lisible_sur($palier['color']) ?>;"><?php echo icon($palier['icon']).' '.nf_texte($gamification->nom_palier($palier['name'])) ?></span>
						<?php if ($user->montre('vip') && $gamification->is_vip($user->id)): ?>
							<?php echo $gamification->vip_badge($user->id) ?><?php if (!$user->montre_aux_autres('vip')) echo $prive ?>
						<?php endif ?>
					<?php endif ?>
					<?php foreach ($groupes as $gid): ?>
						<?php echo NeoFrag()->groups->display($gid, TRUE, FALSE) ?>
					<?php endforeach ?>
					<?php if ($user->montre('statut')): ?>
						<span class="nf-membre-presence <?php echo $user->is_online() ? 'is-online' : 'is-offline' ?>"><?php echo $user->is_online() ? $this->lang('En ligne') : $this->lang('Hors ligne') ?></span><?php if (!$user->montre_aux_autres('statut')) echo $prive ?>
					<?php endif ?>
				</div>
			</div>
			<div class="nf-membre-actions">
				<?php if ($moi): ?>
					<a class="btn btn-primary" href="<?php echo url('user/profile') ?>"><?php echo icon('fas fa-pen').' '.$this->lang('Modifier mon profil') ?></a>
					<a class="btn btn-outline-secondary" href="<?php echo url('user/privacy') ?>"><?php echo icon('far fa-eye').' '.$this->lang('Ce que mon profil montre') ?></a>
				<?php else: ?>
					<?php if ($this->user() && $this->module('talks')): ?>
						<a class="btn btn-primary" href="<?php echo url('talks/new?type=direct&user='.urlencode((string) $user->username)) ?>"><?php echo icon('far fa-envelope').' '.$this->lang('Contacter') ?></a>
					<?php endif ?>
					<?php if ($this->user() && ($moderation = $this->module('moderation'))) echo $moderation->report_button('profile', (int) $user->id, $this->url->request, NULL, (int) $user->id) ?>
				<?php endif ?>
			</div>
		</div>
	</div>

	<nav class="nf-membre-onglets" aria-label="<?php echo $this->lang('Profil') ?>">
		<?php foreach ($onglets as $onglet): ?>
			<a class="nf-membre-onglet<?php echo $onglet['onglet'] === $actif ? ' is-actif' : '' ?>" href="<?php echo url($adresse.($onglet['onglet'] !== '' ? '/'.$onglet['onglet'] : '')) ?>"<?php if ($onglet['onglet'] === $actif) echo ' aria-current="page"' ?>>
				<?php echo icon((string) $onglet['icone']).' '.nf_texte((string) $onglet['titre']) ?>
				<?php if (isset($onglet['nombre'])): ?><span class="nf-membre-nombre"><?php echo (int) $onglet['nombre'] ?></span><?php endif ?>
			</a>
		<?php endforeach ?>
	</nav>

	<div class="nf-membre-page">
		<?php echo $contenu ?>
	</div>
</div>
