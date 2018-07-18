;(function($){

	$(document).ready(function(){

		if( $(".language-switcher").length ){
			
			// add deselect handler
			
			var checkboxes = $('input[type=radio]:checked');
			
			$('input[type=radio]').click(function() {
				
				checkboxes.filter(':not(:checked)').trigger('deselect');
				
				checkboxes = $('input[type=radio]:checked');
			});	
			
			//check main language
			
			$(".language-switcher input:radio").on('change',function(){
				
				$(this).next('input').attr('disabled','disabled').val('default');			
			});
			
			$(".language-switcher input:radio").bind('deselect',function(){
				
				$(this).next('input').removeAttr('disabled').val('');				
			});
		}
	});
		
})(jQuery);