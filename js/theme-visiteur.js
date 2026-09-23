/*
 * Le choix de thème du visiteur : le menu du pied de page (cf. nf_selecteur_theme(), helpers/theme.php).
 *
 * Le nom du cookie, son chemin et l'« époque » viennent du serveur, posés sur le menu lui-même. Le
 * cookie est propre à CE site : la démonstration est servie depuis un sous-dossier du site vitrine,
 * et un choix fait sur l'une ne doit pas suivre le visiteur sur l'autre. L'époque est celle du thème
 * par défaut au moment du choix : quand l'administrateur en change, les choix anciens sont oubliés.
 */
NF.ready(function(){
	document.querySelectorAll('.nf-theme-switch[data-nf-cookie]').forEach(function(menu){
		var nom    = menu.getAttribute('data-nf-cookie');
		var fin    = ';path=' + (menu.getAttribute('data-nf-chemin') || '/') + ';max-age=31536000;samesite=lax';
		var epoque = menu.getAttribute('data-nf-epoque') || '0';

		menu.querySelectorAll('[data-theme-pick]').forEach(function(entree){
			entree.addEventListener('click', function(){
				document.cookie = nom + '=' + encodeURIComponent(entree.getAttribute('data-theme-pick')) + fin;
				document.cookie = nom + '_epoch=' + epoque + fin;
				location.reload();
			});
		});
	});
});
