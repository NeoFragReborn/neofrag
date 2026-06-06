$(function(){
	var $addons = $('#addons');
	if (!$addons.length) return;

	var FILTER_KEY = 'nf-addons-filter';

	var syncActive = function(filter){
		$('.addons-filter-btn').removeClass('active is-active mixitup-control-active');
		$('.addons-filter-btn[data-filter="' + filter + '"]').addClass('active');
	};

	var mix = mixitup($addons[0], {
		selectors: { control: '[data-filter]' },
		animation: { enable: false }
	});

	// Sync active state visually on every filter button click. Works even when
	// MixItUp uses its own toggleClass (mixitup-control-active) — we wipe all
	// possible classes and apply the indigo one only on the clicked button.
	$(document).on('click', '.addons-filter-btn', function(){
		var f = $(this).attr('data-filter') || 'all';
		syncActive(f);
		try { sessionStorage.setItem(FILTER_KEY, f); } catch (e) {}
	});

	// Restore last filter selection on page load
	try {
		var saved = sessionStorage.getItem(FILTER_KEY);
		if (saved && saved !== 'all') {
			var $btn = $('.addons-filter-btn[data-filter="' + saved + '"]');
			if ($btn.length) {
				mix.filter(saved);
				syncActive(saved);
			}
		}
	} catch (e) {}
});
