// Réorganisation par glisser-déposer de l'administration du forum (SortableJS) :
//   - les catégories (cartes de #forums-list), saisies par leur en-tête ;
//   - les forums d'une catégorie, déplaçables d'une catégorie à l'autre (groupe partagé) ;
//   - les sous-forums d'un forum, déplaçables d'un forum à l'autre (groupe partagé).
//
// Le balisage est celui de views/admin.tpl.php. Les adresses des deux points d'entrée sont lues sur
// #forums-list (data-url-categories, data-url-forums) : plus de PHP interpolé dans ce fichier, ce qui
// le rend éprouvable tel quel dans le navigateur (tests/Browser/forum-admin-tri.test.html).
//
// Historique : la version précédente datait de la sortie de jQuery UI (juin 2026) mais gardait `$(…)`
// et `$.post` alors que jQuery n'était plus chargé — elle levait « $ is not defined » avant d'attacher
// quoi que ce soit — et ses sélecteurs (.forum-content, .subforums) ne correspondaient plus au gabarit.
// Les poignées « Glisser-déposer pour réorganiser » étaient donc purement décoratives.
(function(){
	NF.ready(function(){
		var liste = document.getElementById('forums-list');

		if (!liste || typeof Sortable === 'undefined'){
			return;
		}

		var url_categories = liste.getAttribute('data-url-categories');
		var url_forums     = liste.getAttribute('data-url-forums');

		// Le serveur ne répond rien d'utile ici ; un refus est déjà journalisé côté serveur ([checker]).
		function envoyer(url, data){
			return NF.ajax({ url: url, method: 'POST', data: data, dataType: 'text' }).catch(function(){});
		}

		new Sortable(liste, {
			draggable: '.forum-admin-card',
			handle:    '.card-header',
			animation: 150,
			onEnd: function(evt){
				var entete = evt.item.querySelector('[data-category-id]');

				if (!entete){
					return;
				}

				envoyer(url_categories, {
					category_id: NF.data(entete, 'category-id'),
					position:    evt.newIndex
				});
			}
		});

		// parent_id est la catégorie de la liste d'ARRIVÉE (evt.to), pas celle de départ : c'est ce qui
		// permet de changer un forum de catégorie d'un seul geste.
		liste.querySelectorAll('.forum-admin-list').forEach(function(ul){
			new Sortable(ul, {
				group:     'forum-admin-list',
				draggable: '.forum-admin-item',
				handle:    '.forum-admin-row > .forum-admin-handle', // pas la poignée d'un sous-forum
				animation: 150,
				onEnd: function(evt){
					envoyer(url_forums, {
						parent_id: NF.data(evt.to, 'category-id'),
						forum_id:  NF.data(evt.item, 'forum-id'),
						position:  evt.newIndex
					});
				}
			});
		});

		// Le parent d'un sous-forum est le forum qui contient la liste d'arrivée : evt.to est la <ul>,
		// le <li class="forum-admin-item"> au-dessus porte data-forum-id.
		liste.querySelectorAll('.forum-admin-subforums').forEach(function(ul){
			new Sortable(ul, {
				group:     'forum-admin-subforums',
				draggable: '.forum-admin-subforum-item',
				handle:    '.forum-admin-handle',
				animation: 150,
				onEnd: function(evt){
					var forum = evt.to.closest('.forum-admin-item');

					if (!forum){
						return;
					}

					envoyer(url_forums, {
						parent_id: NF.data(forum, 'forum-id'),
						forum_id:  NF.data(evt.item, 'forum-id'),
						position:  evt.newIndex
					});
				}
			});
		});
	});
})();
