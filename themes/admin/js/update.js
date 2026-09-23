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
			function processBuffer(){
				buffer.split(';').forEach(function(chunk){
					chunk = chunk.trim();
					if (!chunk){ return; }

					var d;
					try { d = JSON.parse(chunk); } catch (err){ return; }

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
						var refresh = document.querySelector('.module-monitoring .refresh');
						if (refresh){ refresh.click(); }
						bootstrap.Modal.getOrCreateInstance(modalEl).hide();
						notify('<?php echo addslashes($this->lang('Mise à jour effectuée avec succès')) ?>');
						setTimeout(function(){ window.location.reload(); }, 2000);
						return;
					}

					buffer += decoder.decode(result.value, { stream: true });
					processBuffer();
					return pump();
				});
			}

			return pump();
		});
	});
});
