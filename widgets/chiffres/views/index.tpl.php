<?php
/*
 * Le site en chiffres : chaque nombre en grand, son nom dessous. Le nombre exact est dans `data-valeur` : un thème peut
 * le faire défiler jusqu'à sa valeur quand le bloc paraît (Pulse le fait, sauf si l'on a demandé moins d'animations).
 */
$nombres = $nombres ?? [];

if (!$nombres): ?>
<p class="nf-chiffres-vide"><?php echo $this->lang('Rien à compter pour le moment.') ?></p>
<?php return; endif ?>
<ul class="nf-chiffres">
	<?php foreach ($nombres as $n): ?>
	<li class="nf-chiffre nf-chiffre-<?php echo $n['nom'] ?>">
		<strong class="nf-chiffre-valeur" data-valeur="<?php echo (int) $n['valeur'] ?>"><?php echo nf_texte(\NF\Widgets\Chiffres\Controllers\Index::nombre((int) $n['valeur'])) ?></strong>
		<span class="nf-chiffre-libelle"><?php echo nf_texte($n['libelle']) ?></span>
	</li>
	<?php endforeach ?>
</ul>
