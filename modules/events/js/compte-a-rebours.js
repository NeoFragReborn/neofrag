// Le temps qui reste avant un événement à venir, à la seconde : `.nf-countdown[data-countdown]` porte l'instant visé, en
// secondes depuis 1970. Ce script vivait dans le thème « dungeon », retiré le jour même (2026-06-03) : depuis, le sablier
// des événements à venir s'affichait à côté d'un compteur vide, dans tous les thèmes (trouvé le 2026-10-15). Il vit
// désormais avec le module, chargé par la liste et par la page d'un événement.
NF.ready(function(){
	var compteurs = document.querySelectorAll('.nf-countdown[data-countdown]');

	if (!compteurs.length)
	{
		return;
	}

	// Les unités dans la langue de la page, par le navigateur : « 3j 4h 12min » en français, « 3d 4h 12m » en anglais.
	var langue = document.documentElement.lang || 'fr';
	var unites = ['day', 'hour', 'minute', 'second'].map(function(unite){
		return new Intl.NumberFormat(langue, {style: 'unit', unit: unite, unitDisplay: 'narrow'});
	});

	function afficher()
	{
		var maintenant = Date.now();

		compteurs.forEach(function(compteur){
			var reste = Math.floor((parseInt(compteur.getAttribute('data-countdown'), 10) * 1000 - maintenant) / 1000);

			// L'événement a commencé : le compteur s'efface.
			if (!(reste > 0))
			{
				var ligne = compteur.closest('li');

				if (ligne)
				{
					ligne.hidden = true;
				}

				return;
			}

			var j = Math.floor(reste / 86400), h = Math.floor(reste % 86400 / 3600), m = Math.floor(reste % 3600 / 60), s = reste % 60;
			var parties = [];

			// Les jours s'il y en a, les heures dès qu'il y a des jours, les minutes toujours, les secondes le dernier jour.
			if (j)      parties.push(unites[0].format(j));
			if (j || h) parties.push(unites[1].format(h));
			parties.push(unites[2].format(m));
			if (!j)     parties.push(unites[3].format(s));

			compteur.textContent = parties.join(' ');
		});
	}

	afficher();
	setInterval(afficher, 1000);
});
