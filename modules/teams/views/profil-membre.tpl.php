<?php
/*
 * L'onglet « Équipes » du profil public d'un membre (Teams::profil_membre(), chantier A, étape A2) : ses équipes,
 * son rôle dans chacune, leur jeu.
 */
?>
<ul class="nf-membre-liste">
	<?php foreach ($equipes as $equipe): ?>
		<li>
			<span>
				<?php echo NeoFrag()->model2('file', $equipe['icon_id'])->img('class="img-icon me-1" alt=""') ?>
				<a href="<?php echo url('teams/'.(int) $equipe['team_id'].'/'.url_title((string) $equipe['name'])) ?>"><?php echo nf_texte($equipe['title']) ?></a>
				<?php if (!empty($equipe['role'])): ?><span class="badge text-bg-secondary ms-1"><?php echo nf_texte($equipe['role']) ?></span><?php endif ?>
			</span>
			<?php if (!empty($equipe['jeu'])): ?><small><?php echo icon('fas fa-gamepad').' '.nf_texte($equipe['jeu']) ?></small><?php endif ?>
		</li>
	<?php endforeach ?>
</ul>
