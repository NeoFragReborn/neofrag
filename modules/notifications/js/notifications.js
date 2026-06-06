<?php /* Fichier JS traité par PHP (pour url()), comme modules/reactions/js/reactions.js */ ?>
$(function(){
	$('body').on('click', '[data-notif-read-all]', function(e){
		e.preventDefault();
		fetch('<?php echo \url('ajax/notifications/read-all') ?>', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(function(r){ return r.json(); })
		.then(function(){
			$('.nf-notif-badge').remove();
			$('.nf-notif-item.unread').removeClass('unread');
		})
		.catch(function(){});
	});

	// Marque une notif lue au clic (la navigation vers le lien continue normalement).
	$('body').on('click', '.nf-notif-item[data-notif-id]', function(){
		var id = $(this).attr('data-notif-id');
		try {
			fetch('<?php echo \url('ajax/notifications/read') ?>/' + encodeURIComponent(id), {
				method: 'POST',
				credentials: 'same-origin',
				keepalive: true,
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			});
		} catch (e) {}
	});

	// Bouton Suivre / Suivi (toggle abonnement).
	$('body').on('click', '[data-follow-toggle]', function(){
		var btn = $(this);
		if (btn.hasClass('nf-follow-loading')) return;
		var type = btn.attr('data-follow-type');
		var id   = btn.attr('data-follow-id');
		btn.addClass('nf-follow-loading');

		fetch('<?php echo \url('ajax/notifications/subscribe') ?>/' + encodeURIComponent(type) + '/' + encodeURIComponent(id), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(function(r){ return r.json(); })
		.then(function(d){
			if (d && d.ok) {
				btn.toggleClass('following', !!d.following)
				   .toggleClass('btn-secondary', !!d.following)
				   .toggleClass('btn-outline-secondary', !d.following);
				btn.find('i').attr('class', (d.following ? 'fas' : 'far') + ' fa-bell');
				btn.find('.nf-follow-label').text(d.following ? btn.attr('data-label-following') : btn.attr('data-label-follow'));
			}
		})
		.catch(function(){})
		.finally(function(){ btn.removeClass('nf-follow-loading'); });
	});
});
