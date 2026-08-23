// Arbre repliable vanilla (remplace bootstrap-treeview, jQuery+BS4). Données : [{text, tags:[], nodes:[]}]
// — `text`/`tags` sont déjà échappés côté serveur (utf8_htmlentities), d'où l'usage d'innerHTML.
// Usage : nfTreeview(el, data, {collapseIcon, expandIcon, emptyIcon, showTags, levels}).
function nfTreeview(el, data, opts){
	opts = opts || {};
	var expandIcon   = opts.expandIcon   || 'far fa-folder';      // dossier replié
	var collapseIcon = opts.collapseIcon || 'far fa-folder-open'; // dossier déplié
	var emptyIcon    = opts.emptyIcon    || 'far fa-file';
	var showTags     = opts.showTags !== false;
	var openLevels   = opts.levels != null ? opts.levels : 1;

	el.innerHTML = '';
	el.classList.add('nf-tree');

	function build(nodes, level){
		var ul = document.createElement('ul');
		ul.className = 'nf-tree-ul';

		(nodes || []).forEach(function(node){
			var li = document.createElement('li');
			li.className = 'nf-tree-node';

			var hasChildren = node.nodes && node.nodes.length;
			var open = level < openLevels;

			var label = document.createElement('div');
			label.className = 'nf-tree-label' + (hasChildren ? ' nf-tree-toggle' : '');

			var icon = document.createElement('i');
			icon.className = (hasChildren ? (open ? collapseIcon : expandIcon) : emptyIcon) + ' nf-tree-icon';
			label.appendChild(icon);

			var text = document.createElement('span');
			text.className = 'nf-tree-text';
			text.innerHTML = node.text; // pré-échappé serveur
			label.appendChild(text);

			if (showTags && node.tags && node.tags.length){
				node.tags.forEach(function(tag){
					var b = document.createElement('span');
					b.className = 'badge text-bg-secondary nf-tree-tag';
					b.innerHTML = tag;
					label.appendChild(b);
				});
			}

			li.appendChild(label);

			if (hasChildren){
				var children = build(node.nodes, level + 1);
				if (!open){ children.style.display = 'none'; }
				li.appendChild(children);

				label.addEventListener('click', function(){
					var hidden = children.style.display === 'none';
					children.style.display = hidden ? '' : 'none';
					icon.className = (hidden ? collapseIcon : expandIcon) + ' nf-tree-icon';
				});
			}

			ul.appendChild(li);
		});

		return ul;
	}

	el.appendChild(build(data, 0));
}
