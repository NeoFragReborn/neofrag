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

	document.querySelectorAll('.nav .nav-link[data-nf-sous-menu]').forEach(function(menu){
		var parent = menu.parentNode;

		menu.addEventListener('click', function(e){
			e.preventDefault();

			if (parent.classList.contains('active')){
				menu.setAttribute('aria-expanded', 'false');
				close(menu, parent);
				return;
			}

			menu.setAttribute('aria-expanded', 'true');

			var sub = menu.nextElementSibling;
			if (sub && sub.classList.contains('nav')){
				slideDown(sub, function(){ parent.classList.add('active'); });
			} else {
				parent.classList.add('active');
			}

			// Accordéon : referme les autres sous-menus ouverts.
			document.querySelectorAll('.nav .nav-link[data-nf-sous-menu]').forEach(function(other){
				if (other !== menu && other.parentNode.classList.contains('active')){
					other.setAttribute('aria-expanded', 'false');
					close(other, other.parentNode);
				}
			});
		});
	});

	// Le menu horizontal qui ne tient plus sur une ligne se replie derrière son bouton « Menu ».
	// La mesure se refait à chaque changement de largeur : un menu court reste déplié sur un
	// ordinateur et se replie sur un téléphone ; un menu long se replie plus tôt.
	document.querySelectorAll('.nf-nav-repliable').forEach(function(bloc){
		var bouton = bloc.querySelector(':scope > .nf-nav-toggle');
		var liste  = bloc.querySelector(':scope > .nav');

		if (!bouton || !liste){
			return;
		}

		function mesurer(){
			var ouvert = bloc.classList.contains('nf-nav-ouvert');

			bloc.classList.remove('nf-nav-replie', 'nf-nav-ouvert');

			var entrees = Array.prototype.filter.call(liste.children, function(li){ return li.offsetParent !== null; });
			var deborde = entrees.length > 1 && entrees[entrees.length - 1].offsetTop > entrees[0].offsetTop + 2;

			if (deborde){
				bloc.classList.add('nf-nav-replie');

				if (ouvert){
					bloc.classList.add('nf-nav-ouvert');
				}
			}

			bouton.setAttribute('aria-expanded', deborde && ouvert ? 'true' : 'false');
		}

		bouton.addEventListener('click', function(){
			var ouvert = bloc.classList.toggle('nf-nav-ouvert');
			bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
		});

		mesurer();

		if (window.ResizeObserver){
			var largeur = bloc.clientWidth;

			new ResizeObserver(function(){
				if (bloc.clientWidth !== largeur){
					largeur = bloc.clientWidth;
					mesurer();
				}
			}).observe(bloc);
		}
		else {
			window.addEventListener('resize', mesurer);
		}
	});
});
