NF.ready(function(){
	var addons = document.getElementById('addons');
	if (!addons){ return; }

	/*
	 * Le type, le statut et la recherche se combinent : « les modules inactifs », « les widgets qui
	 * parlent de Discord ». La page s'ouvre sur les modules, en liste ; le dernier choix de chacun est
	 * gardé pour la visite (type, statut) ou pour le navigateur (vue). Elle alignait jusqu'ici les
	 * quelque 120 extensions en grandes cartes, sur plus de 10 000 pixels (relevé le 2026-10-02).
	 */
	var etat = { type: 'module', statut: '', q: '' };
	var vue = 'liste';

	try {
		etat.type = sessionStorage.getItem('nf-addons-type') || etat.type;
		etat.statut = sessionStorage.getItem('nf-addons-statut') || '';
		vue = localStorage.getItem('nf-addons-vue') || vue;
	} catch (e) {}

	// Un type disparu (aucun thème installé, par exemple) : on revient à tous.
	if (!document.querySelector('.addons-filter-btn[data-type="' + etat.type + '"]')){ etat.type = 'all'; }

	var mix = mixitup(addons, {
		controls: { enable: false },
		animation: { enable: false }
	});

	var cartes = Array.prototype.slice.call(addons.querySelectorAll('.addon-card'));
	var recherche = document.querySelector('.addons-recherche');
	var vide = document.querySelector('.addons-vide');

	var appliquer = function(){
		var q = etat.q.trim().toLowerCase();
		var gardees = cartes.filter(function(c){
			return (etat.type === 'all' || c.getAttribute('data-type') === etat.type)
				&& (etat.statut === '' || c.classList.contains(etat.statut))
				&& (q === '' || (c.getAttribute('data-texte') || '').indexOf(q) !== -1);
		});

		mix.filter(gardees.length ? gardees : 'none');
		if (vide){ vide.hidden = gardees.length > 0; }

		document.querySelectorAll('.addons-filter-btn[data-type]').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-type') === etat.type); });
		document.querySelectorAll('.addons-filter-btn[data-statut]').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-statut') === etat.statut); });

		try {
			sessionStorage.setItem('nf-addons-type', etat.type);
			sessionStorage.setItem('nf-addons-statut', etat.statut);
		} catch (e) {}
	};

	var afficher = function(){
		addons.classList.toggle('is-liste', vue === 'liste');
		document.querySelectorAll('.addons-vue-btn').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-vue') === vue); });
		try { localStorage.setItem('nf-addons-vue', vue); } catch (e) {}
	};

	document.addEventListener('click', function(e){
		var type = e.target.closest('.addons-filter-btn[data-type]');
		var statut = e.target.closest('.addons-filter-btn[data-statut]');
		var bouton = e.target.closest('.addons-vue-btn');

		if (type){ etat.type = type.getAttribute('data-type'); appliquer(); }
		else if (statut){ var s = statut.getAttribute('data-statut'); etat.statut = etat.statut === s ? '' : s; appliquer(); }
		else if (bouton){ vue = bouton.getAttribute('data-vue'); afficher(); }
	});

	if (recherche){
		recherche.addEventListener('input', function(){
			etat.q = recherche.value;
			// Une recherche porte sur toutes les extensions : un module qu'on cherche ne doit pas rester
			// caché parce que le filtre montre les widgets.
			if (etat.q.trim() !== '' && etat.type !== 'all'){ etat.type = 'all'; }
			appliquer();
		});
	}

	afficher();
	appliquer();
});
