var popover = new function(){
	var _cache = {};

	this.load = function(el){
		if (bootstrap.Popover.getInstance(el)){
			bootstrap.Popover.getOrCreateInstance(el).show();
			return;
		}

		var mouseover = true;
		var url       = NF.data(el, 'popover-ajax');

		el.addEventListener('mouseleave', function(){
			mouseover = false;
			setTimeout(function(){
				if (!mouseover){
					bootstrap.Popover.getOrCreateInstance(el).hide();
				}
			}, 200);
		});

		var fetched = (typeof _cache[url] !== 'undefined')
			? Promise.resolve()
			: NF.ajax({ url: url, dataType: 'text' }).then(function(data){ _cache[url] = data; });

		fetched.then(function(){
			var pop = new bootstrap.Popover(el, {
				content:   _cache[url],
				trigger:   'manual',
				placement: 'auto',
				container: 'body',
				sanitize:  false,
				html:      true
			});

			if (mouseover){
				// La popover BS5 expose son id via aria-describedby une fois affichée.
				el.addEventListener('shown.bs.popover', function(){
					var tip = document.getElementById(el.getAttribute('aria-describedby'));
					if (tip){
						tip.addEventListener('mouseenter', function(){ mouseover = true; });
						tip.addEventListener('mouseleave', function(){ mouseover = false; pop.hide(); });
					}
				}, { once: true });

				pop.show();
			}
		});
	};

	return this;
};

NF.ready(function(){
	// Délégation de mouseenter (non-bubbling) via la phase de capture.
	document.addEventListener('mouseenter', function(e){
		var trigger = e.target.closest ? e.target.closest('[data-popover-ajax]') : null;
		if (!trigger){ return; }
		popover.load(trigger);
	}, true);
});
