NF.ready(function(){
	document.body.addEventListener('click', function(e){
		var btn = e.target.closest('#modal-update .btn-primary');
		if (!btn){ return; }
		e.preventDefault();

		var progressBar = [];
		btn.disabled = true;

		var modalEl = document.getElementById('modal-update');
		var steps   = modalEl.querySelectorAll('.step');
		if (steps[0]){ steps[0].classList.add('active'); }

		modalEl.querySelectorAll('.progress-bar').forEach(function(bar, i){
			var start = 0;
			String(NF.data(bar, 'step')).split(',').forEach(function(value){
				value = parseInt(value, 10);
				bar.setAttribute('data-index', i);
				progressBar.push([bar, start, value]);
				start += value;
			});
		});

		modalEl.addEventListener('hidden.bs.modal', function(){
			btn.disabled = false;
			modalEl.querySelectorAll('.step').forEach(function(s){ s.classList.remove('active'); });
			modalEl.querySelectorAll('.progress-bar').forEach(function(b){
				b.setAttribute('data-value', 0);
				b.style.width = 0;
			});
		});

		fetch('<?php echo url('admin/ajax/monitoring/update.json') ?>', {
			method: 'POST',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			credentials: 'same-origin',
			cache: 'no-store'
		}).then(function(response){
			var reader  = response.body.getReader();
			var decoder = new TextDecoder();
			var buffer  = '';

			// Réponse streamée = suite de fragments JSON séparés par ';'. On re-parse tout le buffer à
			// chaque chunk (idempotent grâce au garde `value < pourcent`) ; un fragment incomplet en fin
			// de buffer échoue silencieusement et sera complété au chunk suivant.
			var fin = null;   // [100, 'OK'] ou [99, message, référence] : comment le serveur dit avoir fini

			function processBuffer(){
				buffer.split(';').forEach(function(chunk){
					chunk = chunk.trim();
					if (!chunk){ return; }

					var d;
					try { d = JSON.parse(chunk); } catch (err){ return; }

					if (d[0] === 100 || d[0] === 99){ fin = d; return; }

					var entry    = progressBar[d[0]];
					if (!entry){ return; }
					var bar      = entry[0];
					var value    = parseFloat(bar.getAttribute('data-value')) || 0;
					var pourcent = Math.ceil(entry[1] + (d[1] * entry[2] / 100));

					if (value < pourcent){
						bar.classList.add('progress-bar-striped', 'active');
						bar.setAttribute('data-value', pourcent);
						bar.style.width = pourcent + '%';

						if (pourcent === 100){
							bar.classList.remove('progress-bar-striped', 'active');
							var nextStep = modalEl.querySelectorAll('.step')[parseInt(bar.getAttribute('data-index'), 10) + 1];
							if (nextStep){ nextStep.classList.add('active'); }
						}
					}
				});
			}

			function pump(){
				return reader.read().then(function(result){
					if (result.done){
						bootstrap.Modal.getOrCreateInstance(modalEl).hide();

						// Le succès ne s'annonce que si le serveur l'a dit. « Mise à jour effectuée avec
						// succès » s'affichait dès que le flux s'arrêtait, échec compris (relevé le 2026-10-02).
						if (fin && fin[0] === 100){
							var refresh = document.querySelector('.module-monitoring .refresh');
							if (refresh){ refresh.click(); }
							notify('<?php echo addslashes($this->lang('Mise à jour effectuée avec succès')) ?>');
							setTimeout(function(){ window.location.reload(); }, 2000);
						}
						else {
							nfEchecDuFlux('<?php echo addslashes($this->lang('La mise à jour a échoué : %s')) ?>', fin, buffer);
						}
						return;
					}

					buffer += decoder.decode(result.value, { stream: true });
					processBuffer();
					return pump();
				});
			}

			return pump();
		}).catch(function(){
			bootstrap.Modal.getOrCreateInstance(modalEl).hide();
			nfEchecDuFlux('<?php echo addslashes($this->lang('La mise à jour a échoué : %s')) ?>', null, '');
		});
	});
});

/**
 * L'échec d'une opération en flux (sauvegarde, mise à jour), dit à l'administrateur : le message du
 * serveur et la référence que porte le journal ; à défaut, le texte brut que le serveur a renvoyé ;
 * à défaut, qu'elle s'est arrêtée sans confirmation.
 */
function nfEchecDuFlux(modele, fin, brut){
	var echapper = function(t){ return String(t).replace(/[&<>"']/g, function(c){ return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
	var texte    = fin && fin[0] === 99 ? fin[1] : String(brut || '').split(';').filter(function(c){ c = c.trim(); if (!c){ return false; } try { JSON.parse(c); return false; } catch (e){ return true; } }).join(' ').trim();
	texte        = String(texte).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 300);
	var message  = modele.replace('%s', echapper(texte || '<?php echo addslashes($this->lang('elle s’est arrêtée sans confirmation du serveur.')) ?>'));

	if (fin && /^[0-9A-F]{8}$/.test(fin[2] || '')){
		message += ' <?php echo addslashes($this->lang('Référence : %s')) ?>'.replace('%s', fin[2]);
	}

	notify(message, 'danger');
}
