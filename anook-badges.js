jQuery(function(){
	badge_width();
	jQuery(window).resize(function(){
		badge_width();
	})
})
function badge_width(){
	jQuery.each(jQuery('.anook-badge'), function(){
		if(jQuery(this).width() < 280){
			if(jQuery(this).attr('id')!='anook-user-badge' && jQuery(this).width() < 180){
				jQuery(this).addClass('nook-ultra-thin');
			}
			jQuery(this).addClass('thin');
		} else {
			jQuery(this).removeClass('thin');
		}
	})
}