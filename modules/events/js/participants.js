NF.ready(function(){
	var modal = document.getElementById('c2dac90bb0731401a293d27ee036757a');
	if (!modal){ return; }

	// Case « tout sélectionner » d'un groupe : (dé)coche tous les enfants qui diffèrent de l'état cible,
	// via un vrai click (pour déclencher leur propre handler de synchronisation ci-dessous).
	modal.addEventListener('click', function(e){
		var selectAll = e.target.closest('.accordion .list-group-item input[name="select-all"]');
		if (!selectAll){ return; }

		var checked = selectAll.checked || selectAll.indeterminate;
		var group   = selectAll.closest('.list-group-item');
		if (!group){ return; }

		group.querySelectorAll('.collapse input[type="checkbox"]').forEach(function(cb){
			if (checked !== cb.checked){ cb.click(); }
		});
	});

	// Case d'un participant : synchronise les doublons (même valeur dans plusieurs groupes) et recalcule
	// l'état (coché / indéterminé) de la case « tout sélectionner » du groupe.
	modal.addEventListener('click', function(e){
		var checkbox = e.target.closest('.collapse input[type="checkbox"]');
		if (!checkbox){ return; }

		modal.querySelectorAll('.collapse input[type="checkbox"][value="' + checkbox.value + '"]').forEach(function(cb){
			if (cb !== checkbox){
				cb.checked = checkbox.checked;
			}

			var group = cb.closest('.list-group-item');
			if (!group){ return; }

			var checked = 0, total = 0;
			group.querySelectorAll('.collapse input[type="checkbox"]').forEach(function(c){
				total++;
				if (c.checked){ checked++; }
			});

			var title = group.querySelector('.list-group-item input[name="select-all"]');
			if (!title){ return; }

			if (!checked){
				title.checked = false;
				title.indeterminate = false;
			}
			else if (checked === total){
				title.checked = true;
				title.indeterminate = false;
			}
			else {
				title.checked = false;
				title.indeterminate = true;
			}
		});
	});
});
