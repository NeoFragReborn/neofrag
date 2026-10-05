/*
 * L'espace membre au téléphone : son menu est une bande d'onglets qui défile de côté (css/user-space.css).
 * La page courante peut être hors de l'écran — « Confidentialité et données » est loin à droite — : on la
 * ramène au centre de la bande à l'ouverture de la page.
 */
document.addEventListener('DOMContentLoaded', function () {
	var actif = document.querySelector('.nf-espace-menu .nf-espace-lien.actif');
	var bande = actif ? actif.closest('.nf-espace-menu') : null;

	if (bande && bande.scrollWidth > bande.clientWidth) {
		// Mesuré à l'écran : la bande n'est pas positionnée au téléphone, offsetLeft partirait d'un autre parent.
		var ecart = actif.getBoundingClientRect().left - bande.getBoundingClientRect().left;
		bande.scrollLeft += ecart - (bande.clientWidth - actif.offsetWidth) / 2;
	}
});
