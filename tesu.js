var tesu={
	init:function(){
		jQuery('.tesu-button').on('click',function(){
			var tesumain = jQuery(this).closest('div.tesu-main');
			tesumain.find('> form > .tesu-loading').fadeIn();
			tesumain.find('> form > .tesu-error').removeClass('tesu-error');
			tesu.launch(tesumain);
			
		});
	},
	launch:function(tesumain){
		var error=0;
		var form = tesumain.find('> form');
		var divcorreo = form.find('> .div_tesu-email');
		var correo = divcorreo.find('> .tesu-email');
		var divcon = form.find('> .div_tesu-con');
		var condiciones=divcon.find(" > .tesu-con");
		
		if ( correo.val().length == 0){
			divcorreo.addClass('tesu-error');
			error=1;
		}else if ( correo.val().trim().match(/^[\w-_.]{1,}@[\w-_.]{1,}(\.\w{2,})+$/)==null){
			divcorreo.addClass('tesu-error');
			error=1;
		}
		
		if(!condiciones.is(':checked')){
			divcon.addClass('tesu-error');
			error=1;
		}
		
		if (error<=0){
			var data=form.serialize();

			jQuery.post(tesuAjax.url, data, function(response) {
			    form.find('> .tesu-loading').fadeOut();
				if(response!="Error"){
					form.fadeOut();
					tesumain.find('> .tesu-msg').fadeIn();
				}
				
			});	
		}else{
				form.find('> .tesu-loading').fadeOut();
		}
		
	}
};

if (jQuery){
	jQuery(document).ready(function(){
		tesu.init();
	});
}