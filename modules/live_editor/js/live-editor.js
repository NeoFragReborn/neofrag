if (document && document.body){ document.body.classList.add('nf-le-chrome'); }

// POST du Live Editor : réponse lue en TEXTE, jamais en JSON.
// NF.post() parse en JSON (NF.ajax fait response.json() sauf si dataType vaut 'text'), or TOUS les
// endpoints admin/ajax/live-editor/* répondent en text/html : un fragment de disposition, ou un
// corps vide pour les mutations. response.json() lève alors sur '<' (ou sur le corps vide), la
// promesse est rejetée, et les .then() qui mettent le DOM à jour ne tournent jamais — alors que le
// serveur, lui, a bien enregistré : la modification n'apparaissait qu'au rechargement de la page.
// jQuery devinait le type de réponse ; la conversion vanilla (ee4300a) a perdu ce comportement.
// On force donc 'text', comme le font déjà js/delete.js et js/popover.js.
var nfLePost = function(url, data){
	return NF.ajax({ url: url, method: 'POST', data: data, dataType: 'text' })
		.then(function(reponse){
			// Renumerotage APRES que l'appelant a mis le DOM a jour. `setTimeout(…, 0)` et non un
			// `.then()` : les callbacks de promesse sont des micro-taches, et celle-ci serait donc
			// executee AVANT celle de l'appelant — donc avant l'insertion ou la suppression du
			// noeud. Une macro-tache passe apres toute la chaine, ce qui est precisement voulu.
			setTimeout(nfLeRenumeroter, 0);

			return reponse;
		})
		.catch(function(erreur){
			// Une mutation qui ÉCHOUE ne doit jamais passer inaperçue. Le Live Editor a déjà
			// déplacé, supprimé ou ajouté l'élément à l'écran : sans signal, l'utilisateur croit
			// que c'est enregistré et ne découvre le contraire qu'en rechargeant la page — ou
			// jamais. Les treize appels de ce fichier faisaient `.catch(function(){})`, un silence
			// complet, et c'est ce silence qui rendait le défaut impossible à signaler.
			//
			// Le message est posé ICI, au point de passage unique, plutôt qu'aux treize endroits.
			nfLeSignalerEchec(url, erreur);

			throw erreur; // les appelants gardent leur propre catch : ils n'ont plus rien à dire
		});
};

/** Ce que chaque point d'accès modifie, dit en clair. La clé est le dernier segment de l'URL. */
var NF_LE_OPERATIONS = {
	'zone-fork':      '<?php echo addslashes($this->lang('La zone n\'a pas pu être détachée de la disposition commune')) ?>',
	'row-add':        '<?php echo addslashes($this->lang('La ligne n\'a pas pu être ajoutée')) ?>',
	'row-move':       '<?php echo addslashes($this->lang('Le déplacement de la ligne n\'a pas été enregistré')) ?>',
	'row-style':      '<?php echo addslashes($this->lang('L\'apparence de la ligne n\'a pas été enregistrée')) ?>',
	'row-delete':     '<?php echo addslashes($this->lang('La ligne n\'a pas pu être supprimée')) ?>',
	'col-add':        '<?php echo addslashes($this->lang('La colonne n\'a pas pu être ajoutée')) ?>',
	'col-move':       '<?php echo addslashes($this->lang('Le déplacement de la colonne n\'a pas été enregistré')) ?>',
	'col-size':       '<?php echo addslashes($this->lang('La largeur de la colonne n\'a pas été enregistrée')) ?>',
	'col-delete':     '<?php echo addslashes($this->lang('La colonne n\'a pas pu être supprimée')) ?>',
	'widget-add':     '<?php echo addslashes($this->lang('Le widget n\'a pas pu être ajouté')) ?>',
	'widget-move':    '<?php echo addslashes($this->lang('Le déplacement du widget n\'a pas été enregistré')) ?>',
	'widget-update':  '<?php echo addslashes($this->lang('Les réglages du widget n\'ont pas été enregistrés')) ?>',
	'widget-style':   '<?php echo addslashes($this->lang('L\'apparence du widget n\'a pas été enregistrée')) ?>',
	'widget-delete':  '<?php echo addslashes($this->lang('Le widget n\'a pas pu être supprimé')) ?>'
};

/**
 * Signale un échec de mutation : un message lisible à l'écran, et le détail technique en console.
 *
 * L'écran et la base ne sont plus d'accord à ce stade — on le dit, et on invite à recharger plutôt
 * que de laisser l'utilisateur travailler sur un affichage qui ment.
 */
var nfLeSignalerEchec = function(url, erreur){
	var segment = String(url).split('?')[0].replace(/\/+$/, '').split('/').pop();
	var quoi    = NF_LE_OPERATIONS[segment] || '<?php echo addslashes($this->lang('La modification n\'a pas été enregistrée')) ?>';
	var statut  = erreur && erreur.status ? ' (<?php echo addslashes($this->lang('erreur')) ?> ' + erreur.status + ')' : '';

	if (typeof notify === 'function'){
		notify(quoi + statut + '.<br><?php echo addslashes($this->lang('L\'affichage ne correspond plus à ce qui est enregistré : rechargez la page.')) ?>', 'danger');
	}

	if (window.console && console.error){
		console.error('[live-editor] ' + segment + ' : ' + (erreur && erreur.message ? erreur.message : erreur));
	}
};

// switchClass de jQuery UI (retiré) -> bascule de classe simple (la transition est cosmétique).
var nfLeSwitchClass = function(el, oldClass, newClass){
	if (!el){ return; }
	if (oldClass){ el.classList.remove(oldClass); }
	if (newClass){ el.classList.add(newClass); }
};

/**
 * Position d'un element deplace, comptee parmi SES SEULS FRERES DEPLACABLES.
 *
 * `evt.newIndex` de SortableJS compte TOUS les enfants du conteneur, en-tetes compris. Or deux des
 * trois conteneurs du Live Editor commencent par un en-tete :
 *
 *   <section data-disposition-id>   -> <header class="nf-le-zone-header">, puis les lignes
 *   <div class="live-editor-col">   -> <header class="nf-le-col-header">,  puis les widgets
 *
 * L'indice envoye au serveur etait donc decale de 1 : deposer une ligne tout en haut demandait la
 * position 1, et le serveur la remettait exactement ou elle etait. Rien ne le signalait, puisque
 * l'appel REUSSISSAIT — il faisait simplement le mauvais deplacement. C'est le defaut signale le
 * 2026-09-15 (« le changement de position d'un row ne se sauvegarde pas »).
 *
 * Compter les freres deplacables est juste quel que soit le nombre d'en-tetes, aujourd'hui et
 * apres un remaniement du balisage.
 */
var nfLePosition = function(evt, selecteur){
	var freres = Array.prototype.filter.call(evt.to.children, function(enfant){
		return enfant.matches && enfant.matches(selecteur);
	});

	var i = freres.indexOf(evt.item);

	return i === -1 ? evt.newIndex : i; // repli defensif : jamais pire qu'avant
};

/** Enfants DIRECTS correspondant a un selecteur. */
var nfLeEnfants = function(parent, selecteur){
	return parent ? Array.prototype.filter.call(parent.children, function(enfant){
		return enfant.matches && enfant.matches(selecteur);
	}) : [];
};

/**
 * Remet les identifiants du DOM en accord avec ce que le serveur vient d'enregistrer.
 *
 * C'EST LE POINT LE PLUS SUBTIL DE CE FICHIER. Les identifiants de ligne, de colonne et de widget
 * ne sont pas des cles stables : ce sont des POSITIONS, que le serveur recalcule 0,1,2… a chaque
 * rendu (cf. `$child->id($i)` dans neofrag/displayables/{zone,row,col}.php). Le client, lui,
 * deplaçait et supprimait dans le DOM sans jamais les rafraichir.
 *
 * Consequence, constatee le 2026-09-15 : la PREMIERE operation reussit, et toutes les suivantes
 * visent le mauvais element — ou aucun. Les journaux le disaient sans ambiguite :
 *
 *     [output] admin/ajax/live-editor/col-size   : Call to a member function size() on null
 *     [output] admin/ajax/live-editor/col-delete : Call to a member function each() on null
 *
 * `$disposition[$row_id][$col_id]` etait NULL parce que le client envoyait une position perimee.
 * D'ou l'experience decrite : des alertes rouges, des colonnes atterries dans la mauvaise ligne,
 * et « ca finit par marcher a la troisieme tentative » — c'est-a-dire apres un rechargement, qui
 * rend des identifiants frais.
 *
 * Apres une mutation reussie, l'ordre du DOM EST celui du serveur : renumeroter 0..n-1 dans l'ordre
 * du document suffit donc a retablir l'accord, sans recharger l'iframe.
 */
var nfLeRenumeroter = function(){
	var cadre = document.querySelector('.live-editor-iframe iframe');
	var doc   = cadre && cadre.contentDocument;

	if (!doc){ return; }

	doc.querySelectorAll('[data-disposition-id]').forEach(function(zone){
		// Les lignes ne s'imbriquent pas : l'ordre du document suffit.
		zone.querySelectorAll('[data-row-id]').forEach(function(ligne, i){
			ligne.setAttribute('data-row-id', i);

			nfLeEnfants(ligne, '[data-col-id]').forEach(function(colonne, j){
				colonne.setAttribute('data-col-id', j);

				// Le conteneur des widgets est `.live-editor-col` quand le mode Colonnes est actif,
				// la colonne elle-meme sinon — exactement ce que vise le Sortable des widgets.
				var hote = colonne.querySelector('.live-editor-col') || colonne;

				nfLeEnfants(hote, '[data-widget-id]').forEach(function(widget, k){
					widget.setAttribute('data-widget-id', k);
				});
			});
		});
	});
};

// Style « courant » d'un widget : data-widget-style (attribut serveur) puis suivi en mémoire.
var nfLeWidgetStyle = function(widget, value){
	if (arguments.length > 1){ widget._nfWidgetStyle = value; return value; }
	return widget._nfWidgetStyle !== undefined ? widget._nfWidgetStyle : NF.data(widget, 'widget-style');
};

var nfLeCloneModal = function(templateId){
	var tpl = document.getElementById(templateId);
	if (!tpl || !tpl.content){
		return null;
	}
	return tpl.content.cloneNode(true).querySelector('.modal');
};

var nfLeOpenModal = function(templateId, title){
	if (document.querySelector('.live-editor-modal')){
		return null;
	}
	var modal = nfLeCloneModal(templateId);
	if (!modal){
		return null;
	}
	if (title){
		var titleEl = modal.querySelector('.nf-le-modal-title-text');
		if (titleEl){ titleEl.textContent = title; }
	}
	document.body.appendChild(modal);
	return modal;
};

var modal_style = function(title, element, styles, callback){
	var modal = nfLeOpenModal('nf-le-tpl-modal-style', title);
	if (!modal){ return; }

	var stylesEl = document.querySelector(styles);
	modal.querySelector('.modal-body').innerHTML = stylesEl ? stylesEl.innerHTML : '';
	modal._nfElement = element;
	bootstrap.Modal.getOrCreateInstance(modal).show();

	var widget = element.closest('.widget');

	element._nfPreviousStyle = nfLeWidgetStyle(widget);

	var entries = modal.querySelectorAll('[data-style]');
	for (var i = 0; i < entries.length; i++){
		if (NF.data(entries[i], 'style') == nfLeWidgetStyle(widget)){
			entries[i].classList.add('active');
			break;
		}
	}

	modal.addEventListener('hidden.bs.modal', function(){
		if (element._nfPreviousStyle != nfLeWidgetStyle(widget)){
			nfLeSwitchClass(element, element._nfPreviousStyle, nfLeWidgetStyle(widget));
		}
		modal.remove();
	});

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		var style = element._nfPreviousStyle;
		nfLeWidgetStyle(widget, style);
		bootstrap.Modal.getOrCreateInstance(modal).hide();
		callback(style);
	});
};

/**
 * Assistant d'ajout / de réglage d'un widget : quatre étapes, sélection par cartes.
 *
 * Autonome par construction — il ne connaît ni la modale ni le serveur. La modale lui passe ses
 * deux boutons de navigation, et le dialogue avec le serveur est un rappel `charger()`. C'est ce
 * qui le rend éprouvable dans un navigateur sans rien monter d'autre :
 * cf. tests/Browser/live-editor-wizard.test.html.
 *
 * @param form    Le formulaire rendu par modules/live_editor/views/widget.tpl.php.
 * @param options { precedent, suivant, charger(widget, type, conteneur, fini) }
 */
window.nfLeWizard = function(form, options){
	// ── Pièces de l'assistant ────────────────────────────────────────────────
	var champWidget = form.querySelector('#live-editor-settings-widget');
	var champType   = form.querySelector('#live-editor-settings-type');
	var champTitre  = form.querySelector('#live-editor-settings-title');
	var settingsEl  = form.querySelector('#live-editor-settings');
	var boutonsEtape = Array.prototype.slice.call(form.querySelectorAll('.nf-le-wiz-step'));
	var precedent   = options.precedent || null;
	var suivant     = options.suivant || null;

	var panneau = function(cle){ return form.querySelector('.nf-le-wiz-panel[data-panel="' + cle + '"]'); };
	var cartes  = function(role){ return Array.prototype.slice.call(form.querySelectorAll('[data-role="' + role + '"] .nf-le-wiz-card')); };

	var ORDRE = ['widget', 'type', 'title', 'settings'];
	var courante = 'widget';
	var reglagesVides = true;   // tant qu'on n'a pas chargé, on suppose l'étape absente

	// ── Quelles étapes ont lieu d'être ───────────────────────────────────────
	// L'étape « Type » n'existe que si le widget en propose ; « Titre » n'a pas de sens pour le
	// widget `module`, qui porte le titre de la page ; « Configuration » n'apparaît que si le
	// serveur a réellement renvoyé un formulaire — c'est le seul signal fiable, et il évite d'avoir
	// à déclarer quelque part de plus quels widgets ont un controllers/admin.php.
	var utile = function(cle){
		if (cle === 'type'){
			return cartes('types').some(function(c){ return NF.data(c, 'widget') === champWidget.value; });
		}
		if (cle === 'title'){ return champWidget.value !== 'module'; }
		if (cle === 'settings'){ return !reglagesVides; }
		return true;
	};

	var etapesUtiles = function(){ return ORDRE.filter(utile); };

	var rafraichirEtapes = function(){
		var utiles = etapesUtiles();

		boutonsEtape.forEach(function(b){
			var cle = NF.data(b, 'step');
			var i   = utiles.indexOf(cle);

			b.hidden = i === -1;
			b.querySelector('.nf-le-wiz-step-n').textContent = i === -1 ? '' : (i + 1);
			b.setAttribute('aria-selected', cle === courante ? 'true' : 'false');
			b.classList.toggle('is-courante', cle === courante);
			b.tabIndex = cle === courante ? 0 : -1;
		});

		var i = utiles.indexOf(courante);
		if (precedent){ precedent.disabled = i <= 0; }
		if (suivant){ suivant.disabled = i === -1 || i >= utiles.length - 1; }
	};

	var aller = function(cle){
		if (!utile(cle)){ return; }

		courante = cle;

		ORDRE.forEach(function(c){
			var p = panneau(c);
			if (p){ p.hidden = c !== cle; }
		});

		rafraichirEtapes();

		// Rendre la main au contenu de l'étape : le filtre sur la première, le champ sur le titre.
		var p = panneau(cle);
		var premier = p && p.querySelector('[data-role="filtre"], input[type="text"], .nf-le-wiz-card[aria-selected="true"]');
		if (premier && premier.focus){ premier.focus(); }
	};

	var decaler = function(pas){
		var utiles = etapesUtiles();
		var i = utiles.indexOf(courante) + pas;
		if (i >= 0 && i < utiles.length){ aller(utiles[i]); }
	};

	/**
	 * L'ensemble des étapes utiles vient de changer : si celle où l'on se trouve a disparu, revenir
	 * à la dernière qui la précède et qui existe encore.
	 *
	 * `decaler(-1)` ne suffisait pas : il cherche l'étape courante DANS la liste des utiles, et ne
	 * la trouve justement plus — il ne faisait donc rien, et un panneau sans objet restait à
	 * l'écran. Constaté par l'épreuve de navigateur, invisible autrement.
	 */
	var replier = function(){
		var utiles = etapesUtiles();

		if (utiles.indexOf(courante) !== -1){ rafraichirEtapes(); return; }

		for (var k = ORDRE.indexOf(courante) - 1; k >= 0; k--){
			if (utiles.indexOf(ORDRE[k]) !== -1){ aller(ORDRE[k]); return; }
		}

		aller(utiles[0] || 'widget');
	};

	// ── Chargement des réglages ──────────────────────────────────────────────
	// Le dialogue avec le serveur est FOURNI par l'appelant : l'assistant n'a pas à le connaître,
	// et une épreuve de navigateur peut le remplacer par une réponse immédiate.
	var load_settings = function(){
		options.charger(champWidget.value, champType.value, settingsEl, function(vide){
			reglagesVides = !!vide;

			replier();
		});
	};

	// ── Sélections ───────────────────────────────────────────────────────────
	var choisirType = function(valeur){
		champType.value = valeur || 'index';

		cartes('types').forEach(function(c){
			var sien = NF.data(c, 'value') === champType.value && NF.data(c, 'widget') === champWidget.value;
			c.setAttribute('aria-selected', sien ? 'true' : 'false');
			c.tabIndex = sien ? 0 : -1;
		});

		load_settings();
	};

	var choisirWidget = function(nom){
		champWidget.value = nom;

		cartes('widgets').forEach(function(c){
			var sien = NF.data(c, 'value') === nom;
			c.setAttribute('aria-selected', sien ? 'true' : 'false');
			c.tabIndex = sien ? 0 : -1;
		});

		// N'exposer que les types de CE widget, et en sélectionner un par défaut.
		var siens = cartes('types').filter(function(c){ return NF.data(c, 'widget') === nom; });
		cartes('types').forEach(function(c){ c.hidden = NF.data(c, 'widget') !== nom; });

		choisirType(siens.length ? NF.data(siens[0], 'value') : 'index');

		// Le widget `module` porte le titre de la page : on met le titre saisi de côté plutôt que de
		// le perdre, et on le restitue si l'utilisateur revient sur un autre widget.
		if (champTitre){
			if (nom === 'module'){
				if (champTitre.value){ champTitre._nfValeur = champTitre.value; }
				champTitre.value = '';
			}
			else if (!champTitre.value && champTitre._nfValeur){
				champTitre.value = champTitre._nfValeur;
			}
		}

		replier();
	};

	// ── Écoute ───────────────────────────────────────────────────────────────
	form.addEventListener('click', function(e){
		var etape = e.target.closest('.nf-le-wiz-step');
		if (etape){ aller(NF.data(etape, 'step')); return; }

		var carte = e.target.closest('.nf-le-wiz-card');
		if (!carte){ return; }

		var liste = carte.closest('[data-role]');
		if (NF.data(liste, 'role') === 'widgets'){ choisirWidget(NF.data(carte, 'value')); }
		else                                     { choisirType(NF.data(carte, 'value')); }
	});

	// Navigation au clavier dans une listbox : flèches pour déplacer, Origine/Fin pour les bouts.
	form.addEventListener('keydown', function(e){
		var carte = e.target.closest('.nf-le-wiz-card');
		if (!carte){ return; }

		var pas = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
		if (!pas && e.key !== 'Home' && e.key !== 'End'){ return; }

		var visibles = Array.prototype.slice.call(carte.closest('[data-role]').querySelectorAll('.nf-le-wiz-card'))
			.filter(function(c){ return !c.hidden && c.style.display !== 'none'; });

		var i = visibles.indexOf(carte);
		var cible = e.key === 'Home' ? visibles[0]
		          : e.key === 'End'  ? visibles[visibles.length - 1]
		          : visibles[i + pas];

		if (cible){ e.preventDefault(); cible.focus(); }
	});

	// Filtre : 38 widgets, c'est trop pour être parcouru du regard sans aide.
	var filtre = form.querySelector('[data-role="filtre"]');
	var vide   = form.querySelector('[data-role="vide"]');

	if (filtre){
		filtre.addEventListener('input', function(){
			var q = filtre.value.trim().toLowerCase();
			var n = 0;

			cartes('widgets').forEach(function(c){
				var montre = !q || (NF.data(c, 'recherche') || '').indexOf(q) !== -1;
				c.style.display = montre ? '' : 'none';
				if (montre){ n++; }
			});

			if (vide){
				vide.textContent = NF.data(form, 'lang-aucun-resultat') || '';
				vide.hidden = n !== 0;
			}
		});
	}

	if (precedent){ precedent.addEventListener('click', function(){ decaler(-1); }); }
	if (suivant){ suivant.addEventListener('click', function(){ decaler(1); }); }

	// Démarrage : la sélection courante pilote l'affichage, puis on ouvre sur la première étape.
	choisirWidget(champWidget.value);
	aller('widget');

	return { aller: aller, etapesUtiles: etapesUtiles, courante: function(){ return courante; } };
};

var modal_settings = function(title, settings, callback){
	var settingsKey        = null;
	var settingsController = null;

	var modal = nfLeOpenModal('nf-le-tpl-modal-settings', title);
	if (!modal){ return; }

	NF.setHtml(modal.querySelector('.modal-body'), settings);

	var form = modal.querySelector('#live-editor-settings-form');
	if (!form){ return; }

	nfLeWizard(form, {
		precedent: modal.querySelector('[data-action="wiz-prev"]'),
		suivant:   modal.querySelector('[data-action="wiz-next"]'),

		// Une seule requête de réglages en vol à la fois. Sans ça, deux changements rapides de widget
		// ou de type laissaient la réponse la plus lente écraser la plus récente : le formulaire
		// affiché n'était pas celui du widget sélectionné. On annule la requête dépassée et on ignore
		// sa réponse si elle arrive quand même. La clé widget::type évite en prime de recharger pour
		// rien — et donc de perdre ce que l'utilisateur a déjà saisi — quand rien n'a changé.
		charger: function(widget, type, settingsEl, fini){
			var key = widget + '::' + type;

			if (settingsKey === key){ return; }
			settingsKey = key;

			if (settingsController){ settingsController.abort(); }
			var controller = settingsController = new AbortController();

			var data;
			if (NF.data(settingsEl, 'widget-id') && NF.data(settingsEl, 'original-widget') == widget && NF.data(settingsEl, 'original-type') == type){
				data = { widget_id: NF.data(settingsEl, 'widget-id') };
			}
			else {
				data = { widget: widget, type: type };
			}

			settingsEl.innerHTML = '';

			NF.ajax({
				url: '<?php echo url('admin/ajax/live-editor/widget-admin') ?>',
				method: 'POST',
				data: data,
				dataType: 'text',
				signal: controller.signal
			}).then(function(html){
				if (controller !== settingsController){ return; }

				var vide = !html || !html.trim();
				if (!vide){ NF.setHtml(settingsEl, html); }

				fini(vide);
			}).catch(function(){
				// Annulation volontaire : une requête plus récente a pris la main, rien à faire.
				if (controller !== settingsController){ return; }
				settingsKey = null; // vrai échec réseau : autoriser un nouvel essai.
			});
		}
	});

	// Entrée = valider : le formulaire de réglages d'un widget se remplit souvent au clavier.
	form.addEventListener('submit', function(e){
		e.preventDefault();
		modal.querySelector('[data-action="confirm"]').click();
	});

	bootstrap.Modal.getOrCreateInstance(modal).show();

	modal.addEventListener('hidden.bs.modal', function(){
		modal.remove();
	});

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		form.dispatchEvent(new CustomEvent('nf.live-editor-settings.submit', { bubbles: true }));

		bootstrap.Modal.getOrCreateInstance(modal).hide();

		// Sérialise le form avec ses names natifs (doublons -> tableau ; NF.ajax gère le suffixe []).
		// `settings` DOIT partir sur le fil même vide : le checker serveur le déclare facultatif
		// (`settings?`) depuis le 2026-09-15, mais l'envoyer vide reste le comportement d'avant
		// ee4300a et ne coûte rien. Les widgets qui ont des réglages nomment leurs champs
		// `settings[clé]` ; ceux qui n'ont pas de controllers/admin.php n'envoient rien.
		var settings = { settings: '' };
		new FormData(form).forEach(function(value, name){
			if (settings[name] !== undefined){
				if (!Array.isArray(settings[name])){ settings[name] = [settings[name]]; }
				settings[name].push(value || '');
			}
			else {
				settings[name] = value || '';
			}
		});

		if (typeof settings.title == 'undefined'){
			settings.title = '';
		}

		callback(settings);
	});
};

var modal_fork = function(callback){
	var modal = nfLeOpenModal('nf-le-tpl-modal-fork');
	if (!modal){ return; }

	bootstrap.Modal.getOrCreateInstance(modal).show();

	modal.addEventListener('hidden.bs.modal', function(){ modal.remove(); });

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		bootstrap.Modal.getOrCreateInstance(modal).hide();
		callback();
	});
};

var modal_delete = function(message, callback){
	var modal = nfLeOpenModal('nf-le-tpl-modal-delete');
	if (!modal){ return; }

	modal.querySelector('.modal-body').innerHTML = message;
	bootstrap.Modal.getOrCreateInstance(modal).show();

	modal.addEventListener('hidden.bs.modal', function(){ modal.remove(); });

	modal.querySelector('[data-action="confirm"]').addEventListener('click', function(){
		bootstrap.Modal.getOrCreateInstance(modal).hide();
		callback();
	});
};

NF.ready(function(){
	var widgetsMode = document.querySelector('[data-mode="<?php echo \NF\NeoFrag\Core\Output::WIDGETS ?>"]');

	var liveEditorForm = function(){ return document.querySelector('form[target="live-editor-iframe"]'); };
	var liveEditorValue = function(){ var i = document.querySelector('input[type="hidden"][name="live_editor"]'); return i ? i.value : ''; };
	var showSave = function(){ document.querySelectorAll('.live-editor-save').forEach(function(s){ s.style.display = ''; }); };
	var hideSave = function(){ document.querySelectorAll('.live-editor-save').forEach(function(s){ s.style.display = 'none'; }); };

	var initialForm = liveEditorForm();
	if (initialForm){ initialForm.submit(); }

	document.querySelectorAll('.live-editor-screen[data-width]').forEach(function(screen){
		screen.addEventListener('click', function(){
			var width = NF.data(this, 'width');
			var size;

			if (width == '100%'){
				size = '20px';
				width = 'calc(' + width + ' - 40px)';
			}
			else {
				size = 'calc(50% - ' + width + ' / 2)';
			}

			document.querySelectorAll('.live-editor-iframe').forEach(function(f){ f.style.width = width; f.style.left = size; });
			document.querySelectorAll('.live-editor-screen').forEach(function(s){ s.classList.remove('active'); });
			this.classList.add('active');
			var dropdown = document.getElementById('navbarDropdownScreen');
			if (dropdown){ dropdown.innerHTML = this.innerHTML + ' <?php echo icon('fas fa-angle-down') ?>'; }
		});
	});

	document.querySelectorAll('.live-editor-mode').forEach(function(modeBtn){
		modeBtn.addEventListener('click', function(){
			this.classList.toggle('active');

			if (NF.data(this, 'mode') == <?php echo \NF\NeoFrag\Core\Output::WIDGETS ?>){
				return;
			}

			var mode = <?php echo $this->output->live_editor() ?>;
			document.querySelectorAll('.live-editor-mode.active').forEach(function(m){
				mode += NF.data(m, 'mode');
			});

			var hidden = document.querySelector('input[type="hidden"][name="live_editor"]');
			if (hidden){ hidden.value = mode; }
			var form = liveEditorForm();
			if (form){ form.submit(); }
		});
	});

	var modulesLinks = document.getElementById('modules-links-collapse');
	if (modulesLinks){
		modulesLinks.addEventListener('click', function(e){
			// Les pages sont rangées en sous-menus (`.nf-le-nav`) : le lien n'est plus un enfant direct du menu.
			var link = e.target.closest('.dropdown-menu > a, .nf-le-nav a.dropdown-item');
			if (!link){ return; }
			e.preventDefault();

			var map = document.getElementById('live-editor-map');
			if (map){ map.innerHTML = '<?php echo icon('fas fa-spinner fa-spin').' '.addslashes($this->lang('Chargement en cours...')) ?>'; }
			var form = liveEditorForm();
			if (form){ form.action = link.getAttribute('href'); form.submit(); }
			document.querySelectorAll('.dropdown-menu').forEach(function(m){ m.classList.remove('show'); });
			document.querySelectorAll('.nav-item.dropdown').forEach(function(m){ m.classList.remove('show'); });
		});
	}

	/* La recherche du menu « Navigation » : elle ouvre les sous-menus qui ont une page correspondante, et
	   cache les autres ; vidée, chaque sous-menu retrouve son état d'origine. */
	var navFiltre = document.querySelector('.nf-le-nav-filtre input');
	if (navFiltre){
		var navMenu = navFiltre.closest('.nf-le-nav');
		navFiltre.addEventListener('input', function(){
			var q = navFiltre.value.trim().toLowerCase();
			var trouves = 0;
			navMenu.querySelectorAll('.nf-le-nav-groupe').forEach(function(groupe){
				var visibles = 0;
				groupe.querySelectorAll('a.dropdown-item').forEach(function(lien){
					var garde = q === '' || lien.textContent.toLowerCase().indexOf(q) !== -1;
					lien.hidden = !garde;
					visibles += garde ? 1 : 0;
				});
				groupe.hidden = visibles === 0;
				groupe.open = q !== '' ? visibles > 0 : groupe.hasAttribute('data-ouvert');
				trouves += visibles;
			});
			var vide = navMenu.querySelector('.nf-le-nav-vide');
			if (vide){ vide.hidden = trouves > 0; }
		});
		// Le menu s'ouvre sur la recherche : on tape tout de suite.
		var navToggle = document.getElementById('navbarDropdownModules');
		if (navToggle){
			navToggle.addEventListener('shown.bs.dropdown', function(){ navFiltre.focus(); });
		}
	}

	/* Styles Overview */
	document.body.addEventListener('click', function(e){
		var overview = e.target.closest('.live-editor-overview:not(.active)');
		if (!overview){ return; }

		var modal = overview.closest('.modal');
		var element = modal ? modal._nfElement : null;
		if (!element){ return; }

		nfLeSwitchClass(element, element._nfPreviousStyle, NF.data(overview, 'style'));
		element._nfPreviousStyle = NF.data(overview, 'style');
		document.querySelectorAll('.live-editor-overview').forEach(function(o){ o.classList.remove('active'); });
		overview.classList.add('active');
	});

	document.querySelectorAll('.live-editor-iframe iframe').forEach(function(iframe){
		iframe.addEventListener('load', function(){
			var doc = iframe.contentDocument || iframe.contentWindow.document;

			var liveEditorEl = doc.querySelector('#live_editor');
			var map = document.getElementById('live-editor-map');
			if (map && liveEditorEl){ map.innerHTML = NF.data(liveEditorEl, 'module-title'); }

			doc.addEventListener('mouseover', function(e){
				var el = e.target.closest('.widget, .module');
				if (!el){ return; }

				if (widgetsMode && widgetsMode.classList.contains('active') && !el.querySelector('.widget-hover')){
					doc.querySelectorAll('.widget-hover').forEach(function(h){ h.remove(); });
					if (getComputedStyle(el).position === 'static'){
						el.style.position = 'relative';
					}
					var isModule  = el.classList.contains('module');
					var typeLabel = isModule ? '<?php echo addslashes($this->lang('Module')) ?>' : '<?php echo addslashes($this->lang('Widget')) ?>';
					var title     = NF.data(el, 'title') || '';
					var styleBtn  = isModule ? '' : '<button type="button" class="nf-le-btn live-editor-style" title="<?php echo addslashes($this->lang('Apparence')) ?>" aria-label="<?php echo addslashes($this->lang('Apparence')) ?>"><?php echo icon('fas fa-paint-brush') ?></button>';

					var hover = document.createElement('div');
					hover.className = 'widget-hover nf-le-widget-hover';
					hover.innerHTML = '<div class="nf-le-widget-hover-card">' +
							'<span class="nf-le-widget-hover-type">' + typeLabel + '</span>' +
							'<span class="nf-le-widget-hover-title">' + title + '</span>' +
							'<div class="nf-le-toolbar" role="toolbar">' +
								styleBtn +
								'<button type="button" class="nf-le-btn live-editor-setting" title="<?php echo addslashes($this->lang('Configurer')) ?>" aria-label="<?php echo addslashes($this->lang('Configurer')) ?>"><?php echo icon('fas fa-cog') ?></button>' +
								'<button type="button" class="nf-le-btn nf-le-btn-danger live-editor-delete" title="<?php echo addslashes($this->lang('Supprimer')) ?>" aria-label="<?php echo addslashes($this->lang('Supprimer')) ?>"><?php echo icon('far fa-trash-alt') ?></button>' +
							'</div>' +
						'</div>';
					hover.style.opacity = '0';
					hover.style.transition = 'opacity .2s';
					hover.addEventListener('mouseleave', function(){ hover.remove(); });
					el.insertBefore(hover, el.firstChild);
					requestAnimationFrame(function(){ hover.style.opacity = '1'; });
				}
			});

			doc.addEventListener('click', function(e){
				var link = e.target.closest('a');
				if (!link || !doc.contains(link)){ return; }
				// Délégué plus bas pour les boutons spécifiques ; ici uniquement les liens de navigation.
				if (e.target.closest('.live-editor-fork, .live-editor-add-row, .live-editor-add-col, .live-editor-add-widget, .live-editor-style, .live-editor-setting, .live-editor-delete, .live-editor-size')){ return; }

				var href = link.getAttribute('href');
				if (href && href.match(/<?php echo str_replace('/', '\/', url()) ?>(?!(admin|live-editor|#))/)){
					if (map){ map.innerHTML = '<?php echo icon('fas fa-spinner fa-spin').' '.addslashes($this->lang('Chargement en cours...')) ?>'; }
					var form = liveEditorForm();
					if (form){ form.action = href; form.submit(); }
				}

				e.preventDefault();
			});

			/* Zone Fork */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-zone .live-editor-fork');
				if (!btn){ return; }

				var fork = function(){
					showSave();
					var zone = btn.closest('[data-disposition-id]');

					nfLePost('<?php echo url('admin/ajax/live-editor/zone-fork') ?>', {
						disposition_id: NF.data(zone, 'disposition-id'),
						url: doc.location.pathname,
						live_editor: liveEditorValue()
					}).then(function(data){
						var tmp = document.createElement('div');
						tmp.innerHTML = data;
						if (tmp.querySelector('.live-editor-widget.module')){
							var form = liveEditorForm();
							if (form){ form.submit(); }
						}
						else {
							NF.replaceHtml(zone, data);
						}
					}).catch(function(){}).finally(hideSave);
				};

				if (NF.data(btn, 'enabled')){ modal_fork(fork); }
				else { fork(); }
			});

			/* Row Add */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-add-row');
				if (!btn){ return; }

				var disposition = btn.closest('[data-disposition-id]');
				showSave();

				nfLePost('<?php echo url('admin/ajax/live-editor/row-add') ?>', {
					disposition_id: NF.data(disposition, 'disposition-id'),
					live_editor: liveEditorValue()
				}).then(function(data){
					var rowsButton = document.querySelector('.live-editor-mode[data-mode="<?php echo \NF\NeoFrag\Core\Output::ROWS ?>"]');
					if (rowsButton && !rowsButton.classList.contains('active')){
						rowsButton.click();
					}
					else {
						NF.insertHtml(disposition, 'beforeend', data);
					}
				}).catch(function(){}).finally(hideSave);
			});

			/* Row Move */
			doc.querySelectorAll('[data-disposition-id]').forEach(function(el){
				new Sortable(el, {
					draggable: '.live-editor-row',
					animation: 150,
					ghostClass: 'live-editor-placeholder',
					onEnd: function(evt){
						showSave();
						var handle = evt.item.querySelector('.row');
						nfLePost('<?php echo url('admin/ajax/live-editor/row-move') ?>', {
							disposition_id: NF.data(evt.to, 'disposition-id'),
							row_id: handle ? NF.data(handle, 'row-id') : '',
							position: nfLePosition(evt, '.live-editor-row')
						}).catch(function(){}).finally(hideSave);
					}
				});
			});

			/* Row Style */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-row-header .live-editor-style');
				if (!btn){ return; }

				var header = btn.closest('.live-editor-row-header');
				var row    = header ? header.nextElementSibling : null;

				modal_style('<?php echo addslashes($this->lang('Apparence de la ligne')) ?>', row, '.live-editor-styles-row', function(style){
					showSave();
					nfLePost('<?php echo url('admin/ajax/live-editor/row-style') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id: NF.data(row, 'row-id'),
						style: style
					}).catch(function(){}).finally(hideSave);
				});
			});

			/* Row Delete */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-row-header .live-editor-delete');
				if (!btn){ return; }

				modal_delete('<?php echo addslashes($this->lang('Êtes-vous sûr(e) de vouloir supprimer cette <b>ligne</b> ?<br />Toutes les <b>colonnes</b> et <b>widgets</b> contenus seront également supprimés.')) ?>', function(){
					var header = btn.closest('.live-editor-row-header');
					var row    = header ? header.nextElementSibling : null;
					showSave();

					nfLePost('<?php echo url('admin/ajax/live-editor/row-delete') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id: NF.data(row, 'row-id')
					}).then(function(){
						var wrapper = row ? row.closest('.live-editor-row') : null;
						if (wrapper){ wrapper.remove(); }
					}).catch(function(){}).finally(hideSave);
				});
			});

			/* Col Add */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-add-col');
				if (!btn){ return; }

				var header = btn.closest('.live-editor-row-header');
				var row    = header ? header.nextElementSibling : null;
				showSave();

				nfLePost('<?php echo url('admin/ajax/live-editor/col-add') ?>', {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(row, 'row-id'),
					live_editor: liveEditorValue()
				}).then(function(data){
					var colsButton = document.querySelector('.live-editor-mode[data-mode="<?php echo \NF\NeoFrag\Core\Output::COLS ?>"]');
					if (colsButton && !colsButton.classList.contains('active')){
						colsButton.click();
					}
					else if (row){
						NF.insertHtml(row, 'beforeend', data);
					}
				}).catch(function(){}).finally(hideSave);
			});

			/* Col Move */
			doc.querySelectorAll('[data-row-id]').forEach(function(el){
				new Sortable(el, {
					draggable: '[data-col-id]',
					animation: 150,
					ghostClass: 'live-editor-placeholder',
					onEnd: function(evt){
						showSave();
						nfLePost('<?php echo url('admin/ajax/live-editor/col-move') ?>', {
							disposition_id: NF.data(evt.to.closest('[data-disposition-id]'), 'disposition-id'),
							row_id: NF.data(evt.to, 'row-id'),
							col_id: NF.data(evt.item, 'col-id'),
							position: nfLePosition(evt, '[data-col-id]')
						}).catch(function(){}).finally(hideSave);
					}
				});
			});

			/* Col Size */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-col .live-editor-size');
				if (!btn){ return; }

				var col       = btn.closest('[data-col-id]');
				var classList = col.getAttribute('class') || '';
				var oldSize   = 12;
				var prefix    = 'col-lg-';
				var match     = classList.match(/\bcol-lg-(\d{1,2})\b/);

				if (match){
					oldSize = parseInt(match[1], 10);
				}
				else if ((match = classList.match(/\bcol-(\d{1,2})\b/))){
					oldSize = parseInt(match[1], 10);
					prefix  = 'col-';
				}

				var newSize = Math.max(1, Math.min(12, oldSize + parseInt(NF.data(btn, 'size'), 10)));

				if (newSize !== oldSize){
					col.classList.remove(prefix + oldSize);
					col.classList.add(prefix + newSize);
					showSave();

					nfLePost('<?php echo url('admin/ajax/live-editor/col-size') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id:         NF.data(btn.closest('[data-row-id]'), 'row-id'),
						col_id:         NF.data(col, 'col-id'),
						size:           newSize
					}).catch(function(){}).finally(hideSave);
				}
			});

			/* Col Delete */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-col > .nf-le-col-header .live-editor-delete');
				if (!btn){ return; }

				var col = btn.closest('[data-col-id]');

				modal_delete('<?php echo addslashes($this->lang('Êtes-vous sûr(e) de vouloir supprimer cette <b>colonne</b> ?<br />Tous les <b>widgets</b> contenus seront également supprimés.')) ?>', function(){
					showSave();
					nfLePost('<?php echo url('admin/ajax/live-editor/col-delete') ?>', {
						disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
						row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
						col_id: NF.data(col, 'col-id')
					}).then(function(){
						if (col){ col.remove(); }
					}).catch(function(){}).finally(hideSave);
				});
			});

			/* Widget Add */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-add-widget');
				if (!btn){ return; }

				var col  = btn.closest('[data-col-id]');
				var data = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(col, 'col-id'),
					widget_id: -1
				};

				nfLePost('<?php echo url('admin/ajax/live-editor/widget-settings') ?>', data).then(function(html){
					modal_settings('<?php echo addslashes($this->lang('Nouveau Widget')) ?>', html, function(settings){
						Object.assign(data, settings, { live_editor: liveEditorValue() });
						showSave();

						nfLePost('<?php echo url('admin/ajax/live-editor/widget-add') ?>', data).then(function(result){
							if (settings.widget == 'module'){
								var form = liveEditorForm();
								if (form){ form.submit(); }
							}
							else {
								var target = col.querySelector('.live-editor-col');
								if (target){ NF.insertHtml(target, 'beforeend', result); }
							}
						}).catch(function(){}).finally(hideSave);
					});
				});
			});

			/* Widget Move */
			// SortableJS ne déplace que les ENFANTS DIRECTS de son conteneur, là où jQuery UI acceptait
			// un sélecteur de descendants (`items: '[data-widget-id]'`). Or les widgets ne sont pas
			// enfants de [data-col-id] : quand le mode Colonnes est actif, col.php les enveloppe dans
			// un `.live-editor-col` (avec l'en-tête de colonne). Viser [data-col-id] rendait donc le
			// glisser-déposer de widgets totalement inopérant — impossible, par exemple, de remonter un
			// widget au-dessus du module de la page. On vise le vrai parent, qui est `.live-editor-col`
			// s'il existe et [data-col-id] sinon (mode Colonnes éteint = pas de wrapper).
			doc.querySelectorAll('[data-col-id]').forEach(function(el){
				new Sortable(el.querySelector('.live-editor-col') || el, {
					draggable: '[data-widget-id]',
					animation: 150,
					ghostClass: 'live-editor-placeholder',
					onEnd: function(evt){
						showSave();
						nfLePost('<?php echo url('admin/ajax/live-editor/widget-move') ?>', {
							disposition_id: NF.data(evt.to.closest('[data-disposition-id]'), 'disposition-id'),
							row_id: NF.data(evt.to.closest('[data-row-id]'), 'row-id'),
							col_id: NF.data(evt.to.closest('[data-col-id]'), 'col-id'),
							widget_id: NF.data(evt.item, 'widget-id'),
							position: nfLePosition(evt, '[data-widget-id]')
						}).catch(function(){}).finally(hideSave);
					}
				});
			});

			/* Widget Style */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-widget .live-editor-style');
				if (!btn){ return; }

				var widget = btn.closest('[data-widget-id]');
				var data   = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(btn.closest('[data-col-id]'), 'col-id'),
					widget_id: NF.data(widget, 'widget-id')
				};

				modal_style('<?php echo addslashes($this->lang('Apparence du Widget')) ?>', widget.querySelector('.card'), '.live-editor-styles-widget', function(style){
					Object.assign(data, { style: style });
					showSave();
					nfLePost('<?php echo url('admin/ajax/live-editor/widget-style') ?>', data).catch(function(){}).finally(hideSave);
				});
			});

			/* Widget Settings */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-widget .live-editor-setting');
				if (!btn){ return; }

				var widget = btn.closest('[data-widget-id]');
				var data   = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(btn.closest('[data-col-id]'), 'col-id'),
					widget_id: NF.data(widget, 'widget-id')
				};

				nfLePost('<?php echo url('admin/ajax/live-editor/widget-settings') ?>', data).then(function(html){
					modal_settings('<?php echo addslashes($this->lang('Configuration du Widget')) ?>', html, function(settings){
						Object.assign(data, settings);
						showSave();

						nfLePost('<?php echo url('admin/ajax/live-editor/widget-update') ?>', data).then(function(result){
							if (settings.widget == 'module'){
								var form = liveEditorForm();
								if (form){ form.submit(); }
							}
							else {
								NF.replaceHtml(widget, result);
							}
						}).catch(function(){}).finally(hideSave);
					});
				});
			});

			/* Widget Delete */
			doc.addEventListener('click', function(e){
				var btn = e.target.closest('.live-editor-widget .live-editor-delete');
				if (!btn){ return; }

				var widget = btn.closest('[data-widget-id]');
				var data   = {
					disposition_id: NF.data(btn.closest('[data-disposition-id]'), 'disposition-id'),
					row_id: NF.data(btn.closest('[data-row-id]'), 'row-id'),
					col_id: NF.data(btn.closest('[data-col-id]'), 'col-id'),
					widget_id: NF.data(widget, 'widget-id')
				};

				modal_delete('<?php echo addslashes($this->lang('Êtes-vous sûr(e) de vouloir supprimer ce <b>widget</b> ?')) ?>', function(){
					showSave();
					nfLePost('<?php echo url('admin/ajax/live-editor/widget-delete') ?>', data).then(function(){
						if (widget){ widget.remove(); }
						hideSave();
					}).catch(hideSave);
				});
			});
		});
	});
});

document.querySelectorAll('[data-typer]').forEach(function(typer){
	var txt      = typer.getAttribute('data-typer');
	var tot      = txt.length;
	var pauseMax = 300;
	var pauseMin = 60;
	var ch       = 0;

	(function typeIt(){
		if (ch > tot){ return; }
		typer.textContent = txt.substring(0, ch++);
		setTimeout(typeIt, ~~(Math.random() * (pauseMax - pauseMin + 1) + pauseMin));
	}());
});
