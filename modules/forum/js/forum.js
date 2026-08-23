// Tri des forums (admin) via SortableJS (remplace jQuery UI sortable). connectWith -> group partagé ;
// l'update jQuery UI (qui fire 2× source/cible) -> onEnd SortableJS (1×) avec evt.to = conteneur cible.
$(function(){
	var list = document.getElementById('forums-list');
	if (list){
		new Sortable(list, {
			draggable: '.card',
			animation: 150,
			onEnd: function(evt){
				$.post('<?php echo url('admin/ajax/forum/categories/move') ?>', {
					category_id: $(evt.item).find('[data-category-id]:first').data('category-id'),
					position: evt.newIndex
				});
			}
		});
	}

	$('.forum-content').each(function(){
		new Sortable(this, {
			group: 'forum-content',
			draggable: 'tr[data-forum-id]',
			animation: 150,
			onEnd: function(evt){
				$.post('<?php echo url('admin/ajax/forum/move') ?>', {
					parent_id: $(evt.item).parents('[data-category-id]:first').data('category-id'),
					forum_id: $(evt.item).data('forum-id'),
					position: evt.newIndex
				});
			}
		});
	});

	$('.subforums').each(function(){
		new Sortable(this, {
			group: 'subforums',
			draggable: 'li',
			animation: 150,
			onEnd: function(evt){
				$.post('<?php echo url('admin/ajax/forum/move') ?>', {
					parent_id: $(evt.item).parents('[data-forum-id]:first').data('forum-id'),
					forum_id: $(evt.item).data('forum-id'),
					position: evt.newIndex
				});
			}
		});
	});
});
