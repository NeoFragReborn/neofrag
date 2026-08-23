var form = new function(){
	var _forms = [];
	var _load  = {};

	this.load = function(formEl, force){
		var doLoad = false;

		if (typeof force !== 'undefined' && force){
			doLoad = true;
		} else if (_forms.indexOf(formEl) === -1){
			_forms.push(formEl);
			doLoad = true;
		}

		if (doLoad){
			Object.keys(_load).forEach(function(find){
				formEl.querySelectorAll(find).forEach(function(el){
					_load[find].apply(el, [formEl]);
				});
			});
		}
	};

	this.submit = function(formEl){
		return NF.ajax({
			url: formEl.action,
			method: formEl.method,
			body: new FormData(formEl)
		}).then(modal.exec(function(data){
			if (typeof data.form !== 'undefined'){
				var body = formEl.querySelector('.modal-body');
				if (body){ NF.setHtml(body, data.form); }
				form.load(formEl, true);
			}

			return data;
		}));
	};

	this.find = function(find, callback){
		_load[find] = callback;
	};

	return this;
};

NF.ready(function(){
	document.querySelectorAll('form').forEach(function(formEl){
		form.load(formEl);
	});
});
