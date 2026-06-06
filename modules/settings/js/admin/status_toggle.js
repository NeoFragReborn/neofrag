$(function(){
	$('.nf-status-card').each(function(){
		var $card = $(this);
		var endpoint = $card.data('toggle-endpoint');
		if (!endpoint) return;
		if ($card.data('nf-status-bound')) return;
		$card.data('nf-status-bound', 1);

		$card.find('.switch > .btn').click(function(e){
			e.preventDefault();
			var $first = $card.find('.switch > .btn:first-child');
			var $last  = $card.find('.switch > .btn:last-child');
			var clickedFirst = $(this)[0] === $first[0];
			var closed = clickedFirst ? 0 : 1;

			$.post(endpoint, {closed: closed}, function(data){
				var off = !!data.status;

				if (off) {
					$first.removeClass('btn-success active').addClass('btn-secondary');
					$last.removeClass('btn-secondary').addClass('btn-danger active');
					$card.removeClass('is-on').addClass('is-off');
					$card.find('.nf-status-icon i').attr('class', $card.data('off-icon'));
					$card.find('.nf-status-title').text($card.data('off-title'));
					$card.find('.nf-status-desc').text($card.data('off-desc'));
				} else {
					$first.removeClass('btn-secondary').addClass('btn-success active');
					$last.removeClass('btn-danger active').addClass('btn-secondary');
					$card.removeClass('is-off').addClass('is-on');
					$card.find('.nf-status-icon i').attr('class', $card.data('on-icon'));
					$card.find('.nf-status-title').text($card.data('on-title'));
					$card.find('.nf-status-desc').text($card.data('on-desc'));
				}
			});
		});
	});
});
