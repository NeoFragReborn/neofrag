<?php /* Fichier JS traité par PHP (pour url() + map des emojis), comme modules/moderation/js/moderation.js */ ?>
var NF_REACTIONS = <?php echo json_encode(\NF\Modules\Reactions\Reactions::REACTIONS, JSON_UNESCAPED_UNICODE) ?>;

NF.ready(function(){
	// Desktop : la CSS gère l'affichage du picker au survol. Tactile : un tap sur le bouton principal
	// ouvre/ferme le picker (classe .open) ; un clic extérieur ferme.
	document.body.addEventListener('click', function(e){
		var main = e.target.closest('.nf-reactions:not(.is-guest) .nf-reaction-main');
		if (!main){ return; }
		e.preventDefault();
		var box = main.closest('.nf-reactions');
		if (box){ box.classList.toggle('open'); }
	});

	document.addEventListener('click', function(e){
		if (!e.target.closest('.nf-reactions')){
			document.querySelectorAll('.nf-reactions.open').forEach(function(b){ b.classList.remove('open'); });
		}
	});

	document.body.addEventListener('click', function(e){
		var pick = e.target.closest('.nf-reactions:not(.is-guest) .nf-reaction-pick');
		if (!pick){ return; }
		e.preventDefault();
		e.stopPropagation();

		var box = pick.closest('.nf-reactions');
		if (box.classList.contains('nf-reaction-loading')){ return; }

		var type = box.getAttribute('data-reaction-type');
		var id   = box.getAttribute('data-reaction-id');
		var key  = pick.getAttribute('data-reaction');
		box.classList.add('nf-reaction-loading');

		fetch('<?php echo \url('ajax/reactions/toggle') ?>/' + encodeURIComponent(type) + '/' + encodeURIComponent(id), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
			body: 'reaction=' + encodeURIComponent(key)
		})
		.then(function(r){ return r.json(); })
		.then(function(d){
			if (!d || !d.ok){ return; }

			box.classList.toggle('has-mine', !!d.mine);
			box.classList.remove('open');

			var mainEl = box.querySelector('.nf-reaction-main');
			if (mainEl){ mainEl.classList.toggle('reacted', !!d.mine); }

			var emoji = box.querySelector('.nf-reaction-emoji');
			if (emoji){ emoji.textContent = d.mine ? (NF_REACTIONS[d.mine] || '🙂') : '🙂'; }

			box.querySelectorAll('.nf-reaction-pick').forEach(function(p){
				p.classList.toggle('picked', p.getAttribute('data-reaction') === d.mine);
			});

			// Résumé : pills emoji + compte, triées par compte décroissant (valeurs serveur = sûres).
			var counts = d.counts || {};
			var html   = '';
			Object.keys(counts).sort(function(a, b){ return counts[b] - counts[a]; }).forEach(function(k){
				if (NF_REACTIONS[k]){
					html += '<span class="nf-reaction-tally" data-reaction="' + k + '">' + NF_REACTIONS[k] + ' ' + counts[k] + '</span>';
				}
			});

			var summary = box.querySelector('.nf-reaction-summary');
			if (summary){ summary.innerHTML = html; }
		})
		.catch(function(){})
		.finally(function(){ box.classList.remove('nf-reaction-loading'); });
	});
});
