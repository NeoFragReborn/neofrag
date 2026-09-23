// Tri des colonnes de table2 : un clic sur un en-tête (`.table th > a[data-col]`) POSTe la demande de
// tri sur la page courante et remplace le contenu de la table par le fragment rendu par le serveur.
//   - Maj + clic  : ajoute la colonne au tri (tri multi-colonnes) ;
//   - Ctrl + clic : retire la colonne du tri ;
//   - une demande encore en cours sur la même table est annulée par la suivante.
//
// Vanilla, sur les primitives du cœur (NF.ajax, NF.data, NF.setHtml). La version précédente était
// restée en jQuery lors de la sortie de jQuery (juin 2026) : elle levait « $ is not defined » au
// chargement, et le tri par en-tête était mort dans toute l'administration sans qu'aucun contrôle
// ne le voie — tools/check-js-jquery.php refuse désormais ce cas.
(function(){
	var en_cours = {};

	NF.ready(function(){
		document.body.addEventListener('click', function(e){
			var lien = e.target.closest('.table th > a');

			if (!lien){
				return;
			}

			var panneau = lien.closest('.panel-table');

			if (!panneau){
				return;
			}

			e.preventDefault();

			var table_id = NF.data(panneau, 'id');

			if (en_cours[table_id]){
				en_cours[table_id].abort();
			}

			// Même forme que l'ancienne sérialisation jQuery de { table2: { id, sort, action } } :
			// le serveur lit $this->input->post->get('table2') et attend un tableau.
			var data = {
				'table2[id]':   table_id,
				'table2[sort]': NF.data(lien, 'col')
			};

			if (e.shiftKey){
				data['table2[action]'] = 'append';
			}
			else if (e.ctrlKey){
				data['table2[action]'] = 'drop';
			}

			var controleur = new AbortController();

			en_cours[table_id] = controleur;

			NF.ajax({ url: window.location.pathname, method: 'POST', data: data, signal: controleur.signal })
				.then(function(reponse){
					if (en_cours[table_id] === controleur){
						delete en_cours[table_id];
					}

					var table = panneau.querySelector('.table');

					if (table && reponse && typeof reponse.content === 'string'){
						NF.setHtml(table, reponse.content);
					}
				})
				.catch(function(erreur){
					if (erreur && erreur.name === 'AbortError'){
						return; // remplacée par un clic plus récent : rien à faire
					}

					if (en_cours[table_id] === controleur){
						delete en_cours[table_id];
					}
				});
		});
	});
})();
