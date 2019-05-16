require(['jquery'], function($){ 
	jQuery(document).on('click', '.section__tabs .section__tabs--tabnav label.tabnav-label', function(){
		var selected_tab = jQuery(this).attr('for');
			jQuery('.section__tabs .section__tabs--tabcontainer .tabpanel').removeClass('active');
			jQuery('.section__tabs .section__tabs--tabcontainer div[data-panel="'+selected_tab+'"]').addClass('active');
	});
});