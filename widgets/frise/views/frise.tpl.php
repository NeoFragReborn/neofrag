<?php
/*
 * La frise de la saison : une borne par mois, puis ses entrées — rendez-vous (le point des rendez-vous à venir bat),
 * actualités, discussions, albums photo. css/frise.css la dessine avec les jetons du thème, qui peut tout redessiner
 * (le thème Chronique en fait des bornes de pierre).
 */
$entrees    = $entrees ?? [];
$aujourdhui = $aujourdhui ?? new \DateTimeImmutable('today', nf_fuseau());

if (!$entrees): ?>
<p class="nf-frise-vide"><?php echo $this->lang('Rien à raconter pour le moment : la saison commence.') ?></p>
<?php return; endif ?>
<ol class="nf-frise">
	<?php $mois = ''; foreach ($entrees as $e): ?>
	<?php if ($e['moment']->format('Y-m') !== $mois): $mois = $e['moment']->format('Y-m'); ?>
	<li class="nf-frise-borne"><span><?php echo nf_texte(timetostr($this->lang('F Y'), $mois.'-01')) ?></span></li>
	<?php endif ?>
	<li class="nf-frise-entree nf-frise-<?php echo $e['genre'] ?><?php if ($e['a_venir']) echo ' is-a-venir' ?>">
		<time datetime="<?php echo $e['journee'] ? $e['moment']->format('Y-m-d') : $e['moment']->format('c') ?>"><?php
			echo nf_texte(timetostr($this->lang('l j F'), $e['moment']->format('Y-m-d')));
			if (!$e['journee']) echo ' · '.nf_texte(timetostr($this->lang('H:i'), $e['moment']->getTimestamp()));
			if ($e['a_venir'])
			{
				$jours = (int) $aujourdhui->diff($e['moment']->setTime(0, 0))->days;
				echo ' <em class="nf-frise-bientot">'.($jours === 0 ? $this->lang('Aujourd\'hui') : ($jours === 1 ? $this->lang('Demain') : $this->lang('Dans %d jour|Dans %d jours', $jours, $jours))).'</em>';
			}
		?></time>
		<span class="nf-frise-genre"><?php echo $e['libelle'] ?></span>
		<h3 class="nf-frise-titre"><a href="<?php echo $e['url'] ?>"><?php echo $e['titre'] ?></a></h3>
		<?php if ($e['image']): ?>
		<a class="nf-frise-image" href="<?php echo $e['url'] ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo $e['image'] ?>" alt="" loading="lazy" /></a>
		<?php endif ?>
		<?php if ($e['texte'] !== ''): ?>
		<p class="nf-frise-texte"><?php echo $e['texte'] ?></p>
		<?php endif ?>
	</li>
	<?php endforeach ?>
</ol>
