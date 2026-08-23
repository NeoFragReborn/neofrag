NF.ready(function(){
	function slideDown(el, done){
		el.style.display = '';
		if (getComputedStyle(el).display === 'none'){ el.style.display = 'block'; }
		var target = el.scrollHeight;
		el.style.overflow = 'hidden';
		el.style.height = '0px';
		el.style.transition = 'height 200ms ease';
		requestAnimationFrame(function(){ el.style.height = target + 'px'; });
		setTimeout(function(){
			el.style.removeProperty('height');
			el.style.removeProperty('overflow');
			el.style.removeProperty('transition');
			if (done){ done(); }
		}, 200);
	}

	function slideUp(el, done){
		el.style.overflow = 'hidden';
		el.style.height = el.scrollHeight + 'px';
		el.style.transition = 'height 200ms ease';
		requestAnimationFrame(function(){ el.style.height = '0px'; });
		setTimeout(function(){
			el.style.display = 'none';
			el.style.removeProperty('height');
			el.style.removeProperty('overflow');
			el.style.removeProperty('transition');
			if (done){ done(); }
		}, 200);
	}

	function close(menu, parent){
		var sub = menu.nextElementSibling;
		if (sub && sub.classList.contains('nav')){
			slideUp(sub, function(){ parent.classList.remove('active'); });
		} else {
			parent.classList.remove('active');
		}
	}

	document.querySelectorAll('.nav .nav-link[data-bs-toggle="collapse"]').forEach(function(menu){
		var parent = menu.parentNode;

		menu.addEventListener('click', function(){
			if (parent.classList.contains('active')){
				close(menu, parent);
				return;
			}

			var sub = menu.nextElementSibling;
			if (sub && sub.classList.contains('nav')){
				slideDown(sub, function(){ parent.classList.add('active'); });
			} else {
				parent.classList.add('active');
			}

			// Accordéon : referme les autres sous-menus ouverts.
			document.querySelectorAll('.nav .nav-link[data-bs-toggle="collapse"]').forEach(function(other){
				if (other !== menu && other.parentNode.classList.contains('active')){
					close(other, other.parentNode);
				}
			});
		});
	});
});
