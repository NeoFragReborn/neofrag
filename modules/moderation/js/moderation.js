/**
 * Moderation client-side : ouvre le modal "Signaler" depuis n'importe où.
 *
 * Usage HTML :
 *   <a class="btn btn-sm btn-link" data-moderation-report
 *      data-target-type="forum_message"
 *      data-target-id="123"
 *      data-url="/fr/forum/topic/...">
 *     <i class="fas fa-flag"></i> Signaler
 *   </a>
 *
 * Charge le HTML du modal en AJAX puis l'injecte dans body. Bootstrap 4 modal.
 */
(function(){
	'use strict';

	var MODAL_ID = 'nf-moderation-report-modal';

	function ensureModalShell(){
		if (document.getElementById(MODAL_ID)) return document.getElementById(MODAL_ID);
		var div = document.createElement('div');
		div.id = MODAL_ID;
		div.className = 'modal fade';
		div.setAttribute('tabindex', '-1');
		div.setAttribute('role', 'dialog');
		div.innerHTML = '<div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content" id="nf-moderation-report-content"></div></div>';
		document.body.appendChild(div);
		return div;
	}

	function openReportModal(targetType, targetId, url){
		var $shell = ensureModalShell();
		var $content = document.getElementById('nf-moderation-report-content');
		$content.innerHTML = '<div class="modal-body text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

		// Show modal first (instant feedback)
		jQuery($shell).modal('show');

		var qs = '?type=' + encodeURIComponent(targetType) +
		         '&id='   + encodeURIComponent(targetId)   +
		         '&url='  + encodeURIComponent(url || window.location.pathname + window.location.hash);

		fetch('<?php echo \url('ajax/moderation/report-modal') ?>' + qs, { credentials: 'same-origin' })
			.then(function(r){ return r.text(); })
			.then(function(html){
				$content.innerHTML = html;
				// Re-execute scripts inline (fetch ne le fait pas)
				$content.querySelectorAll('script').forEach(function(s){
					var n = document.createElement('script');
					n.text = s.text;
					s.parentNode.replaceChild(n, s);
				});
			})
			.catch(function(){
				$content.innerHTML = '<div class="modal-body"><div class="alert alert-danger">Erreur de chargement.</div></div>';
			});
	}

	// Délégation : tout élément avec data-moderation-report devient cliquable
	document.addEventListener('click', function(e){
		var target = e.target.closest('[data-moderation-report]');
		if (!target) return;
		e.preventDefault();
		var t  = target.getAttribute('data-target-type');
		var id = target.getAttribute('data-target-id');
		var url = target.getAttribute('data-url') || '';
		if (!t || !id) {
			console.warn('moderation: data-target-type et data-target-id requis');
			return;
		}
		openReportModal(t, id, url);
	});

	// Expose API global pour appel JS direct si besoin
	window.NFModeration = {
		report: openReportModal
	};
})();
