<?php
/*
 * La semaine en cours (Calendrier, affichage « La semaine ») : sept jours, du lundi au dimanche ; un jour qui porte un
 * événement est marqué (et mène à l'événement, ou au calendrier s'il y en a plusieurs), aujourd'hui est entouré ; puis
 * le prochain rendez-vous. La feuille css/calendar.css le dessine avec les jetons du thème.
 */
$jours    = $jours ?? [];
$prochain = $prochain ?? NULL;
$debut    = $debut ?? NULL;
?>
<div class="nf-semaine">
	<ol class="nf-semaine-jours">
		<?php foreach ($jours as $jour): ?>
		<?php
			$nom    = timetostr('l', $jour['date']);
			$titres = array_map(static fn ($e) => nf_texte($e['title']), $jour['evenements']);
			$lien   = count($jour['evenements']) === 1
				? url('calendar/'.$jour['evenements'][0]['id'].'/'.url_title($jour['evenements'][0]['title']))
				: ($jour['evenements'] ? url('calendar') : '');
			$classes = 'nf-semaine-jour'.($jour['evenements'] ? ' is-evenement' : '').($jour['aujourdhui'] ? ' is-aujourdhui' : '');
		?>
		<li class="<?php echo $classes ?>"<?php if ($jour['aujourdhui']) echo ' aria-current="date"' ?>>
			<?php if ($lien): ?><a href="<?php echo $lien ?>"><?php endif ?>
				<span class="nf-semaine-nom" aria-hidden="true"><?php echo nf_texte(timetostr('D', $jour['date'])) ?></span>
				<b class="nf-semaine-numero" aria-hidden="true"><?php echo $jour['numero'] ?></b>
				<span class="visually-hidden"><?php echo nf_texte($nom.' '.$jour['numero']).($titres ? ' — '.implode(', ', $titres) : '') ?></span>
			<?php if ($lien): ?></a><?php endif ?>
		</li>
		<?php endforeach ?>
	</ol>
	<?php if ($prochain && $debut): ?>
	<p class="nf-semaine-prochain">
		<span class="nf-semaine-libelle"><?php echo $this->lang('Prochain rendez-vous') ?></span>
		<time datetime="<?php echo $debut->format(empty($prochain['all_day']) ? 'c' : 'Y-m-d') ?>"><?php
			echo nf_texte(timetostr($this->lang('l j F'), $debut->format('Y-m-d')));
			if (empty($prochain['all_day'])) echo ' · '.nf_texte(timetostr($this->lang('H:i'), $debut->getTimestamp()));
		?></time>
		<a href="<?php echo url('calendar/'.$prochain['id'].'/'.url_title($prochain['title'])) ?>"><?php echo nf_texte($prochain['title']) ?></a>
	</p>
	<?php else: ?>
	<p class="nf-semaine-prochain"><?php echo $this->lang('Aucun événement à venir') ?></p>
	<?php endif ?>
</div>
