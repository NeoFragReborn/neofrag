// Tri générique des listes via SortableJS (remplace jQuery UI sortable). Chaque `.btn-sortable` est la
// poignée de glissement de son item ; le conteneur est `data-parent`, les items `data-items`, et l'ordre
// est POSTé sur `data-update`.
(function(){
	function sortable(){
		document.querySelectorAll('.btn-sortable').forEach(function(btn){
			var container = btn.closest(btn.dataset.parent);
			if (!container || container._nfSortable){ return; }
			container._nfSortable = true;

			new Sortable(container, {
				draggable: String(btn.dataset.items).replace(/^\s*>\s*/, ''),
				handle: '.btn-sortable',
				animation: 150,
				onEnd: function(evt){
					var handle = evt.item.querySelector('.btn-sortable');
					fetch(btn.dataset.update, {
						method: 'POST',
						headers: {'X-Requested-With': 'XMLHttpRequest'},
						body: new URLSearchParams({
							id: handle ? handle.dataset.id : '',
							position: evt.newIndex
						})
					});
				}
			});
		});
	}

	function init(){
		document.body.addEventListener('nf.load', sortable);
		sortable();
	}

	if (document.readyState !== 'loading'){ init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
