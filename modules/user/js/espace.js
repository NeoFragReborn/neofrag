/*
 * L'espace membre au téléphone : son menu est une bande d'onglets qui défile de côté (css/user-space.css).
 * La page courante peut être hors de l'écran — « Confidentialité et données » est loin à droite — : on la
 * ramène vers le centre de la bande à l'ouverture de la page.
 *
 * Le bord gauche de la bande tombe au DÉBUT d'un onglet : centrée au pixel près, la page courante laissait
 * l'onglet précédent amputé du début de son nom (« …fications »), dans tous les thèmes (check-mise-en-page,
 * 2026-10-07). La page courante reste entière à l'écran.
 */
document.addEventListener('DOMContentLoaded', function () {
	var actif = document.querySelector('.nf-espace-menu .nf-espace-lien.actif');
	var bande = actif ? actif.closest('.nf-espace-menu') : null;

	if (bande && bande.scrollWidth > bande.clientWidth) {
		// Mesuré à l'écran : la bande n'est pas positionnée au téléphone, offsetLeft partirait d'un autre parent.
		var origine = bande.getBoundingClientRect().left - bande.scrollLeft;
		var marge   = parseFloat(window.getComputedStyle(bande).paddingLeft) || 0;
		var gauche  = function (lien) { return lien.getBoundingClientRect().left - origine - marge; };
		var cible   = gauche(actif) - (bande.clientWidth - actif.offsetWidth) / 2;
		var debut   = 0;

		Array.prototype.forEach.call(bande.querySelectorAll('.nf-espace-lien'), function (lien) {
			if (gauche(lien) <= cible) {
				debut = gauche(lien);
			}
		});

		// Des onglets larges : caler au début du précédent pourrait pousser la page courante hors de la bande.
		if (gauche(actif) + actif.offsetWidth - debut > bande.clientWidth - 2 * marge) {
			debut = gauche(actif);
		}

		bande.scrollLeft = Math.max(0, debut);
	}
});
