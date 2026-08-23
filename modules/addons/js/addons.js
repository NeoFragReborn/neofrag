NF.ready(function(){
	var addons = document.getElementById('addons');
	if (!addons){ return; }

	var FILTER_KEY = 'nf-addons-filter';

	var syncActive = function(filter){
		document.querySelectorAll('.addons-filter-btn').forEach(function(btn){
			btn.classList.remove('active', 'is-active', 'mixitup-control-active');
		});
		var active = document.querySelector('.addons-filter-btn[data-filter="' + filter + '"]');
		if (active){ active.classList.add('active'); }
	};

	var mix = mixitup(addons, {
		selectors: { control: '[data-filter]' },
		animation: { enable: false }
	});

	// Sync de l'état actif au clic (même quand MixItUp pose sa propre classe) : on efface toutes les
	// classes possibles puis on applique la classe active sur le bouton cliqué.
	document.addEventListener('click', function(e){
		var btn = e.target.closest('.addons-filter-btn');
		if (!btn){ return; }
		var filter = btn.getAttribute('data-filter') || 'all';
		syncActive(filter);
		try { sessionStorage.setItem(FILTER_KEY, filter); } catch (err) {}
	});

	// Restaure le dernier filtre au chargement.
	try {
		var saved = sessionStorage.getItem(FILTER_KEY);
		if (saved && saved !== 'all'){
			var btn = document.querySelector('.addons-filter-btn[data-filter="' + saved + '"]');
			if (btn){
				mix.filter(saved);
				syncActive(saved);
			}
		}
	} catch (err) {}
});
