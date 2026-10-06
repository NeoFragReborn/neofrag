<?php
/*
 * Le prochain rendez-vous (Calendrier, affichage « Le prochain rendez-vous ») : sa date en grand — le jour de la
 * semaine, le quantième, le mois —, son titre, quand (dans combien de jours, à quelle heure) et où, le début de sa
 * description, et le chemin vers lui. La feuille css/calendar.css le dessine avec les jetons du thème.
 *
 * La case de la date n'est ni un lien ni du <small> : les thèmes éclaircissent les liens et les petits textes de leurs
 * panneaux colorés (blanc, parfois en !important), et la case, claire, y devenait blanc sur blanc (Pulse, 2026-10-06).
 * Le titre et le bouton mènent déjà au rendez-vous.
 */
$evenement = $evenement ?? NULL;
$debut     = $debut ?? NULL;
$fin       = $fin ?? NULL;
$jours     = $jours ?? NULL;

if (!$evenement || !$debut): ?>
<p class="nf-prochain-vide"><?php echo $this->lang('Aucun événement à venir') ?></p>
<?php return; endif;

$journee = !empty($evenement['all_day']);
$lien    = url('calendar/'.$evenement['id'].'/'.url_title($evenement['title']));
$quand   = [$jours === 0 ? $this->lang('Aujourd\'hui') : ($jours === 1 ? $this->lang('Demain') : $this->lang('Dans %d jour|Dans %d jours', $jours, $jours))];

if ($journee)
{
	$quand[] = $this->lang('Toute la journée');
}
else
{
	$heure = timetostr($this->lang('H:i'), $debut->getTimestamp());

	// L'heure de fin, quand le rendez-vous finit le même jour.
	if ($fin && $fin->format('Y-m-d') === $debut->format('Y-m-d') && $fin > $debut)
	{
		$heure .= ' – '.timetostr($this->lang('H:i'), $fin->getTimestamp());
	}

	$quand[] = $heure;
}

if (trim((string) $evenement['location']) !== '')
{
	$quand[] = $evenement['location'];
}

$texte = trim(strip_tags(utf8_html_entity_decode((string) $evenement['description'])));
?>
<div class="nf-prochain">
	<div class="nf-prochain-date" aria-hidden="true">
		<span class="nf-prochain-jour"><?php echo nf_texte(timetostr('D', $debut->format('Y-m-d'))) ?></span>
		<span class="nf-prochain-quantieme"><?php echo $debut->format('j') ?></span>
		<span class="nf-prochain-jour"><?php echo nf_texte(timetostr('M', $debut->format('Y-m-d'))) ?></span>
	</div>
	<div class="nf-prochain-corps">
		<h3 class="nf-prochain-titre"><a href="<?php echo $lien ?>"><?php echo nf_texte($evenement['title']) ?></a></h3>
		<p class="nf-prochain-quand">
			<time datetime="<?php echo $journee ? $debut->format('Y-m-d') : $debut->format('c') ?>"><?php echo nf_texte(timetostr($this->lang('l j F'), $debut->format('Y-m-d'))) ?></time>
			· <?php echo nf_texte(implode(' · ', $quand)) ?>
		</p>
		<?php if ($texte !== ''): ?>
		<p class="nf-prochain-texte"><?php echo nf_texte(str_shortener($texte, 150, '…')) ?></p>
		<?php endif ?>
		<a class="btn btn-sm btn-primary nf-prochain-lien" href="<?php echo $lien ?>"><?php echo $this->lang('Voir le rendez-vous') ?></a>
	</div>
</div>
