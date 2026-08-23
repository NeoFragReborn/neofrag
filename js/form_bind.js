form.find('[data-bind]', function(formEl){
	this.addEventListener('change', function(){
		form.submit(formEl);
	});
});
