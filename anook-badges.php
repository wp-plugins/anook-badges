<?php
/**
 * Plugin Name: Anook Badges
 * Plugin URI: http://github.com/surdaft/wp-anook-badge
 * Description: Insert a badge of a user / nook into your post
 * Version: 1.0.3
 * Author: SurDaft Jack
 * Author URI: http://surdaft.me
 * License: GPL3
 * Copyright 2015  Jack Stupple  (email : jacktstupple@gmail.com)
**/

$enable_js_responsive = true;

function anook_fetch($part, $search, $params){
	$json_decode = 'true'; // just-incase
	$url = 'http://www.anook.com/api'."/".$part."/".$search;
	$i = 0; // if the first parameter
	// add url attributes
	if($params){
		foreach($params as $key => $param){
			if($i == 0) $prefix = '?attributes='; // add the url prefix
			else $prefix = ',';
			$i = 1;

			$url = $url.$prefix.$param;
		}
	}
	// adds the empty so that 0's are returned
	$url = $url.'&empty=1';
	$c = curl_init($url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true); // hides preemptive output
	if($json_decode == true){
		$output = json_decode(curl_exec($c),true);
		$output = $output['data'];
		$output = $output[0];
		return $output;
	}
	else
		return curl_exec($c);
}

// widget
class anook_widget extends WP_Widget{
	// tell wordpress about me!
	function __construct(){
		parent::__construct('anook_widget','Anook Badge',array('description'=>'Display an anook badge in your sidebar'));
	}
	// this is what is shown
	public function widget($args,$instance){
		$title = apply_filters('widget_title',$instance['title']);
		echo $args['before_widget'];
		if(!empty($title))
			echo $args['before_title'].$title.$args['after_title'];
		echo anook_show(array($instance['part']=>$instance['search']));
		echo $args['after_widget'];
	}
	// backend options stuff
	public function form($instance){

		if(isset($instance['title']))
			$title = $instance['title'];
		if(isset($instance['search']))
			$search = $instance['search'];
		else 
			$search = 'SurDaft';

		$options = array('user','nook','game');
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if(isset($title)) echo $title; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('part'); ?>">Part:</label>
			<select name="<?php echo $this->get_field_name('part');?>" id="<?php echo $this->get_field_id('part');?>">
				<?php foreach($options as $option){
					echo '<option value="'.$option.'" id="'.$option.'" '.(($instance['part']==$option)?'selected':'').'>'.ucfirst($option).'</option>';
				}?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('search'); ?>">Search:</label>
			<input type="text" id="<?php echo $this->get_field_id('search'); ?>" name="<?php echo $this->get_field_name('search'); ?>" value="<?php if(isset($search)) echo $search; else echo 'SurDaft';?>">
		</p>	
	<?php }
	// save the options!
	public function update($new_instance, $old_instance){
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['part'] = strip_tags($new_instance['part']);
		$instance['search'] = (!empty($new_instance['search']))?strip_tags($new_instance['search']):'surdaft';
		return $instance;
	}
}

// build
function anook_show($atts){
	$html = '';
	foreach($atts as $key => $attr){
		if($key=='title') continue;
		// define attributes (params) to make bandwidth less an issue and specify data return
		if($key == 'user'){
			$params = array('picture','country','followerCount','url','username');
		} elseif($key == 'game' || $key == 'nook'){
			$params = array('thumbnail','name','userCount','url');
		}
		$return = anook_fetch($key,$attr,$params);
		if($key == 'user')
			$html .= '<div class="anook-badge anook-user" id="anook-user-badge">
						<span class="logo-container"><img class="logo" src="'.plugins_url('assets/anook_logo_dark.png',__FILE__).'"></span>
						<div id="img-container"><a href="'.$return['url'].'" target="_blank"><img src="http://anook.com/'.$return['picture'].'" /></a></div>
						<div class="right">
							<span id="username"><a href="'.$return['url'].'" target="_blank">'.$return['username'].'</a></span>
							<span id="country">'.$return['country'].'</span>
							<span id="followers">Followers: '.$return['followerCount'].'</span>
							<a id="follow-button" href="'.$return['url'].'" target="_blank">follow user</a>
						</div>
					</div>';
		elseif($key == 'nook' || $key == 'game')
			$html .= '<div class="anook-badge anook-'.$key.'" id="anook-'.$key.'-badge">
						<img class="logo" src="'.plugins_url('assets/anook_icon_dark.png',__FILE__).'">
						<div id="img-container-nook"><a href="'.$return['url'].'" target="_blank"><img src="http://anook.com/'.$return['thumbnail'].'" /></a></div>
						<div class="right">
							<span id="username"><a href="'.$return['url'].'" target="_blank">'.$return['name'].'</a></span>
							<span id="country-empty"> </span>
							<span id="followers">Followers: '.$return['userCount'].'</span>
							<a id="follow-button" href="'.$return['url'].'" target="_blank">follow '.$key.'</a>
						</div>
					</div>';
	}
	// return the end html result, all the built bits of code
	return $html;
}

// init widget
function widget_load(){
	register_widget('anook_widget');
}

// shortcode
add_shortcode('anook','anook_show');
// widget
add_action('widgets_init', 'widget_load');

// add extra stylesheet
wp_enqueue_style('anook-badge', plugins_url('style.css',__FILE__));
// enqueue js
if($enable_js_responsive)
	wp_enqueue_script('anook-badge', plugins_url('anook-badges.js',__FILE__), array('jquery'));
?>