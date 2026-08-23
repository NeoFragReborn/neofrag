<?php /* Fichier JS traité par PHP (pour url()), comme modules/reactions/js/reactions.js */ ?>
NF.ready(function(){
	document.body.addEventListener('click', function(e){
		var trigger = e.target.closest('[data-notif-read-all]');
		if (!trigger){ return; }
		e.preventDefault();

		fetch('<?php echo \url('ajax/notifications/read-all') ?>', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(function(r){ return r.json(); })
		.then(function(){
			document.querySelectorAll('.nf-notif-badge').forEach(function(b){ b.remove(); });
			document.querySelectorAll('.nf-notif-item.unread').forEach(function(i){ i.classList.remove('unread'); });
		})
		.catch(function(){});
	});

	// Marque une notif lue au clic (la navigation vers le lien continue normalement).
	document.body.addEventListener('click', function(e){
		var item = e.target.closest('.nf-notif-item[data-notif-id]');
		if (!item){ return; }
		var id = item.getAttribute('data-notif-id');
		try {
			fetch('<?php echo \url('ajax/notifications/read') ?>/' + encodeURIComponent(id), {
				method: 'POST',
				credentials: 'same-origin',
				keepalive: true,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			});
		} catch (e2) {}
	});

	// Bouton Suivre / Suivi (toggle abonnement).
	document.body.addEventListener('click', function(e){
		var btn = e.target.closest('[data-follow-toggle]');
		if (!btn){ return; }
		if (btn.classList.contains('nf-follow-loading')){ return; }

		var type = btn.getAttribute('data-follow-type');
		var id   = btn.getAttribute('data-follow-id');
		btn.classList.add('nf-follow-loading');

		fetch('<?php echo \url('ajax/notifications/subscribe') ?>/' + encodeURIComponent(type) + '/' + encodeURIComponent(id), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(function(r){ return r.json(); })
		.then(function(d){
			if (d && d.ok){
				btn.classList.toggle('following', !!d.following);
				btn.classList.toggle('btn-secondary', !!d.following);
				btn.classList.toggle('btn-outline-secondary', !d.following);

				var icon = btn.querySelector('i');
				if (icon){ icon.className = (d.following ? 'fas' : 'far') + ' fa-bell'; }

				var label = btn.querySelector('.nf-follow-label');
				if (label){ label.textContent = d.following ? btn.getAttribute('data-label-following') : btn.getAttribute('data-label-follow'); }
			}
		})
		.catch(function(){})
		.finally(function(){ btn.classList.remove('nf-follow-loading'); });
	});
});
