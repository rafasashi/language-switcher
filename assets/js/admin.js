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

				var lang = $(this).val();
			
				$('#language_switcher_url_' + lang).attr('data-url',$('#language_switcher_url_' + lang).val());
			
				$('#language_switcher_url_' + lang).attr('disabled','disabled').val('default');			
			});
			
			$(".language-switcher input:radio").bind('deselect',function(){
				
				var lang = $(this).val();
				var langUrl = '';
				
				if( $('#language_switcher_url_' + lang).attr('data-url') ){
					
					langUrl = $('#language_switcher_url_' + lang).attr('data-url');
				}
				
				$('#language_switcher_url_' + lang).removeAttr('disabled').val(langUrl);				
			});
		}
	});
		
})(jQuery);