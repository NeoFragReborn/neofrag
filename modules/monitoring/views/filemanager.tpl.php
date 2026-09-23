<?php
/**
 * Gestionnaire de fichiers webmaster (Monitoring). Arbre jaillé + éditeur CodeMirror (auto-hébergé,
 * fallback textarea). Toute écriture passe par le sudo webmaster (modale) côté serveur. $csrf, $wm_configured.
 *
 * CodeMirror 5.65.16 est vendorisé (js/codemirror, servi en statique via .htaccess). S'il ne charge pas,
 * l'éditeur retombe sur un <textarea> simple (l'outil reste fonctionnel, sans coloration syntaxique).
 */
$CM = dirname(js('codemirror/5.65.16/codemirror.min.js'));
?>
<link rel="stylesheet" href="<?php echo $CM ?>/codemirror.min.css">
<link rel="stylesheet" href="<?php echo $CM ?>/theme/material-darker.min.css">

<div class="nf-fm">
	<div class="alert alert-warning nf-fm-warn">
		<i class="fas fa-code-branch"></i>
		<?php echo $this->lang('Les modifications faites ici sont écrites directement sur le serveur — donc <b>hors Git</b>, et seront écrasées au prochain déploiement. Idéal pour un correctif rapide, pas pour du gros développement.') ?>
	</div>

	<?php if (!$wm_configured): ?>
	<div class="alert alert-danger">
		<i class="fas fa-user-shield"></i>
		<?php echo $this->lang('Aucun mot de passe webmaster défini : les écritures seront refusées. Définis-en un depuis la page Monitoring.') ?>
		<a href="<?php echo url('admin/monitoring') ?>"><?php echo $this->lang('Y aller') ?></a>
	</div>
	<?php endif ?>

	<div class="nf-fm-toolbar">
		<span id="fm-current" class="nf-fm-current text-muted"><?php echo $this->lang('Aucun fichier ouvert') ?></span>
		<span class="nf-fm-actions">
			<button type="button" class="btn btn-primary btn-sm" id="fm-save" disabled><i class="fas fa-save"></i> <?php echo $this->lang('Enregistrer') ?></button>
			<button type="button" class="btn btn-secondary btn-sm" id="fm-newfile"><i class="far fa-file"></i></button>
			<button type="button" class="btn btn-secondary btn-sm" id="fm-newdir"><i class="fas fa-folder-plus"></i></button>
			<button type="button" class="btn btn-secondary btn-sm" id="fm-rename" disabled><i class="fas fa-i-cursor"></i></button>
			<button type="button" class="btn btn-danger btn-sm" id="fm-delete" disabled><i class="far fa-trash-alt"></i></button>
			<button type="button" class="btn btn-secondary btn-sm" id="fm-refresh"><i class="fas fa-sync"></i></button>
		</span>
	</div>

	<div class="nf-fm-body">
		<div class="nf-fm-tree"><ul id="fm-tree" class="nf-fm-ul"></ul></div>
		<div class="nf-fm-editor">
			<textarea id="fm-editor" spellcheck="false" placeholder="<?php echo htmlspecialchars($this->lang('Sélectionne un fichier dans l\'arbre.'), ENT_QUOTES) ?>"></textarea>
		</div>
	</div>
</div>

<!-- Modale sudo webmaster -->
<div class="modal fade" id="fm-sudo-modal" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-sm" role="document"><div class="modal-content">
		<div class="modal-header"><h5 class="modal-title"><i class="fas fa-user-shield"></i> <?php echo $this->lang('Confirmation webmaster') ?></h5></div>
		<div class="modal-body">
			<p class="text-muted small mb-2"><?php echo $this->lang('Action sensible : saisis ton mot de passe webmaster.') ?></p>
			<input type="password" class="form-control" id="fm-sudo-pass" autocomplete="off">
			<div class="text-danger small mt-2" id="fm-sudo-err" style="display:none;"></div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?php echo $this->lang('Annuler') ?></button>
			<button type="button" class="btn btn-primary btn-sm" id="fm-sudo-ok"><?php echo $this->lang('Valider') ?></button>
		</div>
	</div></div>
</div>

<script src="<?php echo $CM ?>/codemirror.min.js"></script>
<?php foreach (['xml','javascript','css','clike','php','htmlmixed','sql','markdown','yaml'] as $mode): ?>
<script src="<?php echo $CM ?>/mode/<?php echo $mode ?>/<?php echo $mode ?>.min.js"></script>
<?php endforeach ?>
<script>
(function(){
	"use strict";

	// Différé jusqu'au DOMContentLoaded : le helper NF (et CodeMirror) sont prêts à ce moment.
	function init(){
		var CSRF = <?php echo json_encode($csrf) ?>;
		var U = {
			list:'<?php echo url('admin/ajax/monitoring/fs_list') ?>', read:'<?php echo url('admin/ajax/monitoring/fs_read') ?>',
			save:'<?php echo url('admin/ajax/monitoring/fs_save') ?>', mkdir:'<?php echo url('admin/ajax/monitoring/fs_mkdir') ?>',
			rename:'<?php echo url('admin/ajax/monitoring/fs_rename') ?>', del:'<?php echo url('admin/ajax/monitoring/fs_delete') ?>',
			sudo:'<?php echo url('admin/ajax/monitoring/sudo') ?>'
		};
		var L = {
			newfile:<?php echo json_encode((string) $this->lang('Nom du nouveau fichier (chemin relatif au dossier courant) :')) ?>,
			newdir:<?php echo json_encode((string) $this->lang('Nom du nouveau dossier :')) ?>,
			rename:<?php echo json_encode((string) $this->lang('Nouveau nom :')) ?>,
			del:<?php echo json_encode((string) $this->lang('Supprimer définitivement ?')) ?>,
			saved:<?php echo json_encode((string) $this->lang('Enregistré.')) ?>, done:<?php echo json_encode((string) $this->lang('Fait.')) ?>,
			error:<?php echo json_encode((string) $this->lang('Erreur')) ?>
		};

		var sel = null;          // {path, type, dir}
		var cm = null;
		var ed = document.getElementById('fm-editor');

		function post(url, data){ data = data || {}; data.csrf = CSRF; return NF.post(url, data); }
		function fmNotify(msg, type){ if (typeof window.notify === 'function'){ window.notify(msg, type||'success'); } else if (type === 'danger'){ alert(msg); } }
		function escapeHtml(s){ var span = document.createElement('span'); span.textContent = s == null ? '' : s; return span.innerHTML; }

		// Exécute fn() (-> promise json). Si le serveur réclame le sudo, ouvre la modale puis rejoue fn().
		function withSudo(fn){
			return fn().then(function(r){
				if (r && r.sudo === 'required'){ return askSudo().then(fn); }
				return r;
			});
		}
		function askSudo(){
			return new Promise(function(resolve, reject){
				var modalEl = document.getElementById('fm-sudo-modal');
				var passEl  = document.getElementById('fm-sudo-pass');
				var errEl   = document.getElementById('fm-sudo-err');
				errEl.style.display = 'none'; errEl.textContent = ''; passEl.value = '';
				bootstrap.Modal.getOrCreateInstance(modalEl).show();
				setTimeout(function(){ passEl.focus(); }, 300);

				function submit(){
					post(U.sudo, {password:passEl.value}).then(function(r){
						if (r && r.ok){ bootstrap.Modal.getOrCreateInstance(modalEl).hide(); cleanup(); resolve(); }
						else { errEl.textContent = (r && r.error) || L.error; errEl.style.display = ''; }
					});
				}
				function cleanup(){
					document.getElementById('fm-sudo-ok').removeEventListener('click', submit);
					passEl.removeEventListener('keydown', onKey);
					modalEl.removeEventListener('hidden.bs.modal', onHide);
				}
				function onKey(e){ if (e.key === 'Enter') submit(); }
				function onHide(){ cleanup(); reject(); }

				document.getElementById('fm-sudo-ok').addEventListener('click', submit);
				passEl.addEventListener('keydown', onKey);
				modalEl.addEventListener('hidden.bs.modal', onHide);
			});
		}

		// ----- Arbre -------------------------------------------------------------
		function loadDir(path, ul){
			post(U.list, {dir:path}).then(function(r){
				if (!r || r.error){ fmNotify((r && r.error) || L.error, 'danger'); return; }
				ul.innerHTML = '';
				r.entries.forEach(function(e){
					var li = document.createElement('li');
					li.className = 'nf-fm-node';
					li.setAttribute('data-path', e.path);
					li.setAttribute('data-type', e.type);
					var icon = e.type === 'dir' ? 'fas fa-folder' : 'far fa-file';
					li.innerHTML = '<span class="nf-fm-label"><i class="' + icon + '"></i> ' + escapeHtml(e.name) + '</span>';
					if (e.type === 'dir'){ li.insertAdjacentHTML('beforeend', '<ul class="nf-fm-ul" style="display:none"></ul>'); }
					ul.appendChild(li);
				});
			});
		}

		var tree = document.getElementById('fm-tree');
		if (tree){
			tree.addEventListener('click', function(ev){
				var label = ev.target.closest('.nf-fm-label');
				if (!label || !tree.contains(label)){ return; }
				ev.stopPropagation();

				var li   = label.closest('.nf-fm-node');
				var path = li.getAttribute('data-path');
				var type = li.getAttribute('data-type');

				var active = tree.querySelector('.nf-fm-node.active');
				if (active){ active.classList.remove('active'); }
				li.classList.add('active');

				sel = {path:path, type:type, dir:type === 'dir' ? path : path.replace(/\/[^\/]*$/, '')};
				document.getElementById('fm-rename').disabled = false;
				document.getElementById('fm-delete').disabled = false;

				if (type === 'dir'){
					var ul     = li.querySelector(':scope > ul');
					var iconEl = li.querySelector(':scope > .nf-fm-label i');
					if (ul && ul.style.display !== 'none'){
						ul.style.display = 'none';
						if (iconEl){ iconEl.className = 'fas fa-folder'; }
					}
					else if (ul){
						ul.style.display = '';
						if (iconEl){ iconEl.className = 'fas fa-folder-open'; }
						if (!ul.children.length){ loadDir(path, ul); }
					}
				}
				else { openFile(path); }
			});
		}

		// ----- Éditeur -----------------------------------------------------------
		function openFile(path){
			post(U.read, {path:path}).then(function(r){
				if (!r || r.error){ fmNotify((r && r.error) || L.error, 'danger'); return; }
				setContent(r.content, r.mode);
				var cur = document.getElementById('fm-current');
				if (cur){ cur.textContent = r.path; }
				document.getElementById('fm-save').disabled = false;
			});
		}
		function setContent(text, mode){
			if (cm){ cm.setOption('mode', mode || 'text/plain'); cm.setValue(text); }
			else if (ed){ ed.value = text; }
		}
		function getContent(){ return cm ? cm.getValue() : (ed ? ed.value : ''); }

		function save(){
			if (!sel || sel.type !== 'file') return;
			withSudo(function(){ return post(U.save, {path:sel.path, content:getContent()}); }).then(function(r){
				if (r && r.ok) fmNotify(L.saved); else if (r && r.error) fmNotify(r.error, 'danger');
			}).catch(function(){});
		}

		// ----- Toolbar -----------------------------------------------------------
		var saveBtn = document.getElementById('fm-save');
		if (saveBtn){ saveBtn.addEventListener('click', save); }
		document.addEventListener('keydown', function(e){ if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.which === 83)){ e.preventDefault(); save(); } });

		var newfileBtn = document.getElementById('fm-newfile');
		if (newfileBtn){ newfileBtn.addEventListener('click', function(){
			var name = prompt(L.newfile); if (!name) return;
			var dir = sel ? sel.dir : '.';
			withSudo(function(){ return post(U.save, {path:(dir ? dir + '/' : '') + name, content:''}); }).then(function(r){
				if (r && r.ok){ fmNotify(L.done); refresh(); } else if (r && r.error) fmNotify(r.error, 'danger');
			}).catch(function(){});
		}); }

		var newdirBtn = document.getElementById('fm-newdir');
		if (newdirBtn){ newdirBtn.addEventListener('click', function(){
			var name = prompt(L.newdir); if (!name) return;
			withSudo(function(){ return post(U.mkdir, {dir:(sel ? sel.dir : '.'), name:name}); }).then(function(r){
				if (r && r.ok){ fmNotify(L.done); refresh(); } else if (r && r.error) fmNotify(r.error, 'danger');
			}).catch(function(){});
		}); }

		var renameBtn = document.getElementById('fm-rename');
		if (renameBtn){ renameBtn.addEventListener('click', function(){
			if (!sel) return;
			var name = prompt(L.rename, sel.path.replace(/^.*\//, '')); if (!name) return;
			withSudo(function(){ return post(U.rename, {path:sel.path, name:name}); }).then(function(r){
				if (r && r.ok){ fmNotify(L.done); refresh(); } else if (r && r.error) fmNotify(r.error, 'danger');
			}).catch(function(){});
		}); }

		var deleteBtn = document.getElementById('fm-delete');
		if (deleteBtn){ deleteBtn.addEventListener('click', function(){
			if (!sel || !confirm(L.del + '\n' + sel.path)) return;
			withSudo(function(){ return post(U.del, {path:sel.path}); }).then(function(r){
				if (r && r.ok){ fmNotify(L.done); sel = null; document.getElementById('fm-rename').disabled = true; document.getElementById('fm-delete').disabled = true; refresh(); }
				else if (r && r.error) fmNotify(r.error, 'danger');
			}).catch(function(){});
		}); }

		function refresh(){ loadDir('.', document.getElementById('fm-tree')); }
		var refreshBtn = document.getElementById('fm-refresh');
		if (refreshBtn){ refreshBtn.addEventListener('click', refresh); }

		// ----- Init --------------------------------------------------------------
		if (window.CodeMirror && document.getElementById('fm-editor')){
			cm = CodeMirror.fromTextArea(document.getElementById('fm-editor'), {
				lineNumbers:true, theme:'material-darker', indentUnit:4, indentWithTabs:true,
				lineWrapping:false, autofocus:false
			});
			cm.setSize('100%', '100%');
		}
		refresh();
	}

	if (document.readyState === 'loading'){ document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
</script>
