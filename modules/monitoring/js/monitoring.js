NF.ready(function(){
	var printSize = function(bytes, decimals){
		var sz = ('KMGTP').split('');
		var factor = Math.floor((String(bytes).length - 1) / 3);
		// Le symbole de l'octet : « o » en français (Ko, Mo), « B » ailleurs (KB, MB).
		var unit = (typeof sz[factor - 1] !== 'undefined' ? sz[factor - 1] : '') + '<?php echo addslashes($this->lang('o')) ?>';
		return (bytes / Math.pow(1024, factor)).toFixed(typeof decimals !== 'undefined' ? decimals : 2) + '<small>' + unit + '</small>';
	};

	function setHtmlAll(selector, html){
		document.querySelectorAll(selector).forEach(function(el){ el.innerHTML = html; });
	}

	var loading = false;

	var refresh = function(forceRefresh){
		if (loading){ return false; }
		loading = true;

		document.querySelectorAll('.knob').forEach(function(k){ if (k._nfKnobUpdate){ k._nfKnobUpdate(0); } });
		document.querySelectorAll('.module-monitoring .refresh > i').forEach(function(i){ i.classList.add('fa-spin'); });
		setHtmlAll('#storage-pourcent, #monitoring-text', '&nbsp');
		['storage-total', 'storage-free', 'storage-database', 'storage-files', 'storage-used', 'monitoring-danger', 'monitoring-warning', 'monitoring-info'].forEach(function(id){
			var el = document.getElementById(id);
			if (el){ el.innerHTML = '<?php echo icon('fas fa-spinner fa-spin') ?>'; }
		});
		setHtmlAll('.table-notifications', '');
		document.querySelectorAll('.panel-infos i.text-success, .panel-infos i.text-danger').forEach(function(el){
			el.classList.add('fas', 'fa-spinner', 'fa-spin');
			el.classList.remove('fa-check-square', 'text-success', 'fa-exclamation-triangle', 'text-danger');
		});
		document.querySelectorAll('.panel-infos [data-label]').forEach(function(el){ el.innerHTML = NF.data(el, 'label'); });
		document.querySelectorAll('.panel-monitoring').forEach(function(el){ el.classList.add('nf-sante-inconnue'); el.classList.remove('nf-sante-erreur', 'nf-sante-alerte', 'nf-sante-ok'); });
		document.querySelectorAll('.monitoring-icon-status').forEach(function(el){ el.classList.remove('beat-fast', 'beat-medium', 'beat-slow'); });

		NF.post('<?php echo url('admin/ajax/monitoring.json') ?>', { refresh: (typeof forceRefresh !== 'undefined' && forceRefresh) ? forceRefresh : 0 }).then(function(data){
			var used     = data.storage.total - data.storage.free;
			var pourcent = Math.ceil(used / data.storage.total * 100);

			var knobColor = pourcent >= 90 ? '#d9534f' : (pourcent >= 75 ? '#f0ad4e' : '#25C7F0');
			document.querySelectorAll('.knob').forEach(function(k){ if (k._nfKnobUpdate){ k._nfKnobUpdate(pourcent, knobColor); } });

			Object.keys(data.storage).forEach(function(key){
				var el = document.getElementById('storage-' + key);
				if (el){ el.innerHTML = printSize(data.storage[key]); }
			});

			var usedEl = document.getElementById('storage-used');
			if (usedEl){ usedEl.innerHTML = printSize(used); }
			var pctEl = document.getElementById('storage-pourcent');
			if (pctEl){ pctEl.innerHTML = '<?php echo addslashes($this->lang('Utilisé')) ?> (' + pourcent + ' %)'; }

			var notifications = '';
			var count = { danger: 0, warning: 0, info: 0 };

			data.notifications.forEach(function(notification){
				notifications += '<tr>'
					+ '<td class="text-nowrap"><span class="badge bg-' + notification[1] + '-subtle text-' + notification[1] + '-emphasis">'
					+ (notification[1] === 'danger' ? '<?php echo icon('fas fa-bug') ?> <?php echo addslashes($this->lang('Erreur')) ?>' : (notification[1] === 'warning' ? '<?php echo icon('fas fa-bolt') ?> <?php echo addslashes($this->lang('Anomalie')) ?>' : '<?php echo icon('fas fa-exclamation-circle') ?> <?php echo addslashes($this->lang('Conseil')) ?>'))
					+ '</span></td>'
					+ '<td class="align-middle">' + notification[0] + '</td>'
					+ '</tr>';
				count[notification[1]]++;
			});

			setHtmlAll('.table-notifications', '<tbody>' + notifications + '</tbody>');

			Object.keys(count).forEach(function(key){
				var el = document.getElementById('monitoring-' + key);
				if (el){ el.innerHTML = count[key]; }
			});

			var textEl = document.getElementById('monitoring-text');
			if (textEl){ textEl.innerHTML = count.danger ? '<?php echo addslashes($this->lang('Le navire coule !')) ?>' : (count.warning ? '<?php echo addslashes($this->lang('Iceberg droit devant !')) ?>' : '<?php echo addslashes($this->lang('Tout est en ordre, capitaine !')) ?>'); }
			document.querySelectorAll('.panel-monitoring').forEach(function(el){ el.classList.remove('nf-sante-inconnue'); el.classList.add(count.danger ? 'nf-sante-erreur' : (count.warning ? 'nf-sante-alerte' : 'nf-sante-ok')); });
			document.querySelectorAll('.monitoring-icon-status').forEach(function(el){ el.classList.add(count.danger ? 'beat-fast' : (count.warning ? 'beat-medium' : 'beat-slow')); });

			nfTreeview(document.getElementById('tree'), data.files, {
				collapseIcon: 'far fa-folder-open',
				expandIcon: 'far fa-folder',
				emptyIcon: 'far fa-file',
				showTags: true,
				levels: 1
			});

			Object.keys(data.server).forEach(function(key){
				var value  = data.server[key];
				var result = value;
				var span   = document.querySelector('#server-' + key + ' > span');

				if (Array.isArray(value)){
					result = value[0];
					if (span){ span.setAttribute('data-label', span.innerHTML); span.innerHTML = value[1]; }
				}

				var icon = document.querySelector('#server-' + key + ' > i');
				if (icon){
					icon.classList.remove('fas', 'fa-spinner', 'fa-spin');
					icon.classList.add('fas');
					if (result){ icon.classList.add('fa-check-square', 'text-success'); }
					else { icon.classList.add('fa-exclamation-triangle', 'text-danger'); }
				}
			});

			loading = false;
			document.querySelectorAll('.module-monitoring .refresh > i').forEach(function(i){ i.classList.remove('fa-spin'); });
		});
	};

	document.querySelectorAll('.module-monitoring .refresh').forEach(function(el){
		el.addEventListener('click', function(e){ e.preventDefault(); refresh(true); });
	});

	var backupBtn = document.querySelector('#modal-backup .btn-primary');
	if (backupBtn){
		backupBtn.addEventListener('click', function(e){
			e.preventDefault();

			var origHtml = backupBtn.innerHTML;
			backupBtn.innerHTML = '<?php echo icon('fas fa-spinner fa-spin').' '.addslashes($this->lang('Sauvegarde en cours...')) ?>';
			backupBtn.classList.add('disabled');

			var modalEl = document.getElementById('modal-backup');
			var steps   = modalEl.querySelectorAll('.step');
			if (steps[0]){ steps[0].classList.add('active'); }

			modalEl.addEventListener('hidden.bs.modal', function(){
				backupBtn.innerHTML = origHtml;
				backupBtn.classList.remove('disabled');
				modalEl.querySelectorAll('.step').forEach(function(s){ s.classList.remove('active'); });
				modalEl.querySelectorAll('.progress-bar').forEach(function(b){ b.setAttribute('data-value', 0); b.style.width = 0; });
			});

			fetch('<?php echo url('admin/ajax/monitoring/backup') ?>', {
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				credentials: 'same-origin',
				cache: 'no-store'
			}).then(function(response){
				var reader  = response.body.getReader();
				var decoder = new TextDecoder();
				var buffer  = '';

				function processBuffer(){
					buffer.split(';').forEach(function(chunk){
						chunk = chunk.trim();
						if (!chunk){ return; }
						var d;
						try { d = JSON.parse(chunk); } catch (err){ return; }

						var bar = modalEl.querySelectorAll('.progress-bar')[d[0]];
						if (!bar){ return; }
						var value = parseFloat(bar.getAttribute('data-value'));

						if (isNaN(value) || value < d[1]){
							bar.classList.add('progress-bar-striped', 'active');
							bar.setAttribute('data-value', d[1]);
							bar.style.width = d[1] + '%';

							if (d[1] === 100){
								bar.classList.remove('progress-bar-striped', 'active');
								var nextStep = modalEl.querySelectorAll('.step')[d[0] + 1];
								if (nextStep){ nextStep.classList.add('active'); }
							}
						}
					});
				}

				function pump(){
					return reader.read().then(function(result){
						if (result.done){
							setTimeout(function(){
								bootstrap.Modal.getOrCreateInstance(modalEl).hide();
								notify('<?php echo addslashes($this->lang('Sauvegarde réalisée dans le dossier <b>backups</b> de votre FTP')) ?>');
							}, 1000);
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
	}

	refresh();
});
