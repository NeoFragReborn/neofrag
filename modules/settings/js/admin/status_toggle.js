// Interrupteur « ouvert / fermé » des cartes d'état de l'administration (mode maintenance,
// inscriptions). Un clic sur l'un des deux boutons POSTe `closed` — 0 pour le premier bouton, 1 pour le
// second — sur le point d'entrée porté par la carte (data-toggle-endpoint), puis repeint la carte d'après
// la réponse ({ status: true } = fermé) et les libellés et icônes portés en data-on-* / data-off-*.
//
// Vanilla. L'ancienne version était restée en jQuery après la sortie de jQuery (juin 2026) : elle levait
// « $ is not defined » au chargement, et basculer la maintenance ou les inscriptions depuis ces cartes
// ne faisait rien — sans erreur visible. tools/check-js-jquery.php refuse désormais ce cas.
(function(){
	function repeindre(carte, ferme){
		var boutons = carte.querySelectorAll('.switch > .btn');
		var premier = boutons[0];
		var dernier = boutons[boutons.length - 1];
		var etat    = ferme ? 'off' : 'on';

		if (premier){
			premier.classList.remove('btn-success', 'btn-secondary', 'active');
			premier.classList.add(ferme ? 'btn-secondary' : 'btn-success');
			premier.classList.toggle('active', !ferme);
		}

		if (dernier && dernier !== premier){
			dernier.classList.remove('btn-danger', 'btn-secondary', 'active');
			dernier.classList.add(ferme ? 'btn-danger' : 'btn-secondary');
			dernier.classList.toggle('active', ferme);
		}

		carte.classList.toggle('is-off', ferme);
		carte.classList.toggle('is-on', !ferme);

		var icone = carte.querySelector('.nf-status-icon i');
		var titre = carte.querySelector('.nf-status-title');
		var desc  = carte.querySelector('.nf-status-desc');

		if (icone && carte.hasAttribute('data-' + etat + '-icon')){
			icone.className = carte.getAttribute('data-' + etat + '-icon');
		}

		if (titre && carte.hasAttribute('data-' + etat + '-title')){
			titre.textContent = carte.getAttribute('data-' + etat + '-title');
		}

		if (desc && carte.hasAttribute('data-' + etat + '-desc')){
			desc.textContent = carte.getAttribute('data-' + etat + '-desc');
		}
	}

	NF.ready(function(){
		document.querySelectorAll('.nf-status-card[data-toggle-endpoint]').forEach(function(carte){
			if (carte._nfStatusBound){
				return;
			}

			carte._nfStatusBound = true;

			var endpoint = carte.getAttribute('data-toggle-endpoint');

			if (!endpoint){
				return;
			}

			carte.querySelectorAll('.switch > .btn').forEach(function(bouton, i){
				bouton.addEventListener('click', function(e){
					e.preventDefault();

					NF.post(endpoint, { closed: i === 0 ? 0 : 1 })
						.then(function(reponse){
							repeindre(carte, !!(reponse && reponse.status));
						})
						.catch(function(){}); // le serveur journalise déjà un refus ([checker]) ; la carte reste telle quelle
				});
			});
		});
	});
})();
