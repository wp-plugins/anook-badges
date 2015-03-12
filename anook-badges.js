var $ = jQuery;
$(function(){
	badge_width();
	jQuery(window).resize(function(){
		badge_width();
	})
	$('#widgets-right .anook_admin_show_games input[type=radio]').click(function(){
		games_list();
	})
	$('#widgets-right .anook_admin_parts_list select').on('change', function(){
		games_list();
	})

	var data = '';


	$('#widgets-right .anook_admin_search input').on('input',function(){
		if($('#anook_reload_games_list').length < 1 && $('#widgets-right .anook_admin_parts_list select option:selected').val() == 'user'){
			$(this).after('<span id="anook_reload_games_list" onClick="reload_games_list()" style="cursor:pointer;"> <i class="fa fa-refresh"></i> Reload games list</span>');
		}
	})
})
function reload_games_list(){
	$('#widgets-right .anook_admin_games_list select').html('');
	anook_ajax_fetch('user',$('#widgets-right .anook_admin_search input').val()+'/games',['thumbnail','name','user','url'],true);
	$('#anook_reload_games_list').fadeOut();
}
function badge_width(){
	$.each($('.anook-badge'), function(){
		if($(this).width() < 280){
			if($(this).attr('id')!='anook-user-badge' && $(this).width() < 180){
				$(this).addClass('nook-ultra-thin');
			}
			$(this).addClass('thin');
		} else {
			$(this).removeClass('thin');
		}
	})
}
function games_list(){
	if($('#widgets-right .anook_admin_parts_list option:selected').val()=='user'){
		$('.anook_admin_show_games').css({'display':'block',});
		if($('#widgets-right .anook_admin_show_games input[type=radio]:checked').val()==1){
			$('.anook_admin_games_list, .anook_admin_games_list_info').css({'display':'block',});
			if($('.anook_admin_games_list select').html()==''){
				console.log('force-change');
				anook_ajax_fetch('user',$('#widgets-right .anook_admin_search input').val()+'/games',['thumbnail','name','user','url'],true);
			}
		} else {
			$('.anook_admin_games_list, .anook_admin_games_list_info').css({'display':'none',});
		}
	} else if($('#widgets-right .anook_admin_parts_list option:selected').val()=='user') {
		$('.anook_admin_show_games').css({'display':'block',});
		$('.anook_admin_games_list, .anook_admin_games_list_info').css({'display':'none',});
	} else {
		$('.anook_admin_games_list, .anook_admin_show_games, .anook_admin_games_list_info').css({'display':'none',});
	}
}
function anook_ajax_fetch(part,search,attr,json){
	$.post(ajaxurl,{'part':part,'search':search,'attr':attr,'action':'anook_ajax','json_encode':json},function(response){
		anook_ajax_build(response);
	});
}
function anook_ajax_build(data){
	var temp = JSON.parse($('#widgets-right .anook_admin_games_list input[type=hidden]').val());
	$('#widgets-right .anook_admin_games_list select').html('');
	if(temp == '0')
		$('#widgets-right .anook_admin_games_list select').append('<option name="'+$('#widgets-right .anook_admin_games_list select').attr('name')+'" value="0" selected="selected">None</option>');
	else
		$('#widgets-right .anook_admin_games_list select').append('<option name="'+$('#widgets-right .anook_admin_games_list select').attr('name')+'" value="0">None</option>');
	$.each(JSON.parse(data),function(k,v){
		if(v['name'] !== undefined && $.inArray(v['name'],temp)==-1){
			$('#widgets-right .anook_admin_games_list select').append('<option name="'+$('#widgets-right .anook_admin_games_list select').attr('name')+'" value="'+v['name']+'">'+v['name']+'</option>');
		} else if(v['name'] !== undefined && $.inArray(v['name'],temp)==0) {
			$('#widgets-right .anook_admin_games_list select').append('<option name="'+$('#widgets-right .anook_admin_games_list select').attr('name')+'" value="'+v['name']+'" selected="selected">'+v['name']+'</option>');
		}
	})
}