var modal = new function(){
	var _modals = {};
	var _scripts;

	// Interprète une réponse AJAX standard (refresh / redirect / css / js / notify) puis appelle `callback`.
	// Renvoie une Promise qui se résout (avec la valeur de callback) APRÈS chargement des scripts data.js —
	// la sémantique attendue par form.submit().then(...).
	this.exec = function(callback){
		return function(data){
			if (typeof data.success !== 'undefined' && data.success === 'refresh'){
				location.reload();
				return new Promise(function(){}); // la page se recharge : ne résout jamais
			}

			if (typeof data.redirect !== 'undefined'){
				window.location.href = data.redirect;
				return new Promise(function(){});
			}

			if (typeof data.css !== 'undefined'){
				var holder = document.createElement('div');
				holder.innerHTML = data.css;
				while (holder.firstChild){ document.head.appendChild(holder.firstChild); }
			}

			var chain = Promise.resolve();

			if (typeof data.js !== 'undefined'){
				if (typeof _scripts === 'undefined'){
					_scripts = [];
					document.querySelectorAll('script').forEach(function(s){
						if (s.src){ _scripts.push(s.src); }
					});
				}

				data.js.forEach(function(js){
					if (_scripts.indexOf(js) === -1){
						chain = chain.then(function(){
							return NF.loadScript(js).then(function(){ _scripts.push(js); });
						});
					}
				});
			}

			if (typeof data.notify !== 'undefined'){
				data.notify.forEach(function(n){ notify(n.message, n.type); });
			}

			return chain.then(function(){ return callback(data); });
		};
	};

	this.load = function(url){
		var show = function(){
			document.querySelectorAll('.modal.show').forEach(function(m){
				bootstrap.Modal.getOrCreateInstance(m).hide();
			});
			bootstrap.Modal.getOrCreateInstance(_modals[url]).show();
		};

		if (typeof _modals[url] === 'undefined'){
			NF.ajax({ url: url }).then(this.exec(function(data){
				if (typeof data.content === 'undefined'){ return data; }

				// Insère le HTML de la modale puis ré-exécute ses <script> avec le nonce CSP.
				var holder   = document.createElement('div');
				holder.innerHTML = data.content;

				var inserted = [];
				while (holder.firstChild){
					var node = holder.firstChild;
					holder.removeChild(node);
					document.body.appendChild(node);
					inserted.push(node);
				}

				var modalEl = null;
				inserted.forEach(function(node){
					if (node.nodeType !== 1){ return; }
					NF.runScripts(node);
					if (!modalEl){
						modalEl = node.matches('.modal') ? node : node.querySelector('.modal');
					}
				});

				_modals[url] = modalEl;

				document.body.dispatchEvent(new CustomEvent('nf.load', { bubbles: true }));

				var formEl = modalEl ? modalEl.querySelector('form') : null;

				if (typeof form !== 'undefined' && formEl){
					modalEl.addEventListener('submit', function(e){
						e.preventDefault();

						var submitBtn = modalEl.querySelector('[type="submit"]');
						if (submitBtn && submitBtn.classList.contains('disabled')){ return; }
						if (submitBtn){ submitBtn.classList.add('disabled'); }

						form.submit(e.target).then(function(data){
							if (submitBtn){ submitBtn.classList.remove('disabled'); }

							if (typeof data.modal !== 'undefined' && data.modal === 'dispose'){
								modalEl.addEventListener('hidden.bs.modal', function(){
									modalEl.remove();
									delete _modals[url];
								});
								bootstrap.Modal.getOrCreateInstance(modalEl).hide();
							}
						}).catch(function(e){
							// L'envoi a échoué : le bouton redevient cliquable, et le message part de
							// NF (l'erreur est relancée pour lui).
							if (submitBtn){ submitBtn.classList.remove('disabled'); }
							throw e;
						});
					});

					form.load(formEl);
				}

				show();

				return data;
			}));
		}
		else {
			show();
		}
	};

	return this;
};

NF.ready(function(){
	document.addEventListener('click', function(e){
		var trigger = e.target.closest('[data-modal-ajax]');
		if (!trigger){ return; }
		e.preventDefault();
		modal.load(NF.data(trigger, 'modal-ajax'));
	});
});
