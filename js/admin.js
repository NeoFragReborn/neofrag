(function(){
	function activate(item){
		document.querySelectorAll('.list-group-item, .tab-content .tab-pane').forEach(function(el){
			el.classList.remove('active');
		});
		item.classList.add('active');

		var tab = (item.getAttribute('href') || '').replace('#', '');
		var pane = document.querySelector('.tab-content .tab-pane[data-tab="' + tab + '"]');
		if (pane){ pane.classList.add('active'); }

		var content = document.querySelector('.tab-content');
		var card = content ? content.closest('.card') : null;
		var header = card ? card.querySelector('h6.card-header') : null;
		if (header){ header.innerHTML = item.innerHTML; }
	}

	function init(){
		document.querySelectorAll('.list-group-item').forEach(function(item){
			item.addEventListener('click', function(){ activate(this); });
		});

		function hashchange(){
			var item = window.location.hash
				? document.querySelector('[href="' + window.location.hash + '"]')
				: null;
			if (!item){ item = document.querySelector('.list-group-item'); }
			if (item){ item.click(); }
		}

		window.addEventListener('hashchange', hashchange);
		hashchange();
	}

	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
