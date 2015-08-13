	
jQuery( function($){

	//move to after apply button.
	$('.tablenav-pages').before($('#parent_search_wrap').clone());

	//show new filter form.
	$('#parent_search_wrap').show();

	//submit when dropdown changed.
	$('#parent_search').change(function(){
		$('#parent_search_form').submit();
	});
});
