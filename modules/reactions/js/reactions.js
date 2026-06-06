<?php /* Fichier JS traité par PHP (pour url()), comme modules/moderation/js/moderation.js */ ?>
$(function(){
	$('body').on('click', '[data-reaction-toggle]', function(){
		var btn = $(this);
		if (btn.hasClass('nf-reaction-loading')) return;

		var type = btn.attr('data-reaction-type');
		var id   = btn.attr('data-reaction-id');
		btn.addClass('nf-reaction-loading');

		fetch('<?php echo \url('ajax/reactions/toggle') ?>/' + encodeURIComponent(type) + '/' + encodeURIComponent(id), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		})
		.then(function(r){ return r.json(); })
		.then(function(d){
			if (d && d.ok) {
				btn.toggleClass('reacted', !!d.reacted);
				btn.find('i').attr('class', (d.reacted ? 'fas' : 'far') + ' fa-heart');
				btn.find('.nf-reaction-count').text(d.count);
			}
		})
		.catch(function(){})
		.finally(function(){ btn.removeClass('nf-reaction-loading'); });
	});
});
