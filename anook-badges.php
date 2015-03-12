<?php
/**
 * Plugin Name: Anook Badges
 * Plugin URI: https://wordpress.org/plugins/anook-badges/
 * Description: Insert a badge of a user / nook into your post
 * Version: 1.0.4
 * Author: SurDaft Jack
 * Author URI: http://surdaft.me
 * License: GPL3
 * Copyright 2015  Jack Stupple  (email : jacktstupple@gmail.com)
**/

$enable_js_responsive = true;

function anook_fetch($part, $search, $params, $json_encode=false){
	$cache = true;
	$hours = 10; // time before fresh data

	$url = 'http://www.anook.com/api'."/".$part."/".$search;
	$i = 0; // if the first parameter
	$search_name = $part.'/'.$search;
	// add url attributes
	if($params){
		foreach($params as $key => $param){
			if($i == 0) $prefix = '?attributes='; // add the url prefix
			else $prefix = ',';
			$i = 1;

			$url = $url.$prefix.$param;
			$search_name = $search_name.$prefix.$param;
		}
	}
	$url = $url.'&empty=1'; // adds the empty so that 0's are returned
	// if to use the cache
	if($cache){
		// obtain the cache file and decode the contents of the file into a json array
		if(file_exists(plugin_dir_path(__FILE__).'/cache.json')) $temp = json_decode(file_get_contents(plugin_dir_path(__FILE__).'/cache.json'), true);
		if(isset($temp[$search_name])&&(strtotime(date('YmdHi'))-(strtotime($temp[$search_name]['timestamp']))<((60*60)*$hours)) && file_exists(plugin_dir_path(__FILE__).'/cache.json')){
			// the row exists for this search and is less than 10 hours old use it
			$output = $temp[$search_name];
			$output['source'] = 'cache';
		} else {
			// else get fresh data
			$c = curl_init($url);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true); // hides pre-emptive output
			$c = json_decode(curl_exec($c),true);
			$output = $c;
			$output = $output['data'];
			// add it to the current file array / replacing or adding a new one
			$temp[$search_name] = $output;
			$temp[$search_name]['timestamp'] = (int)date('YmdHi'); // add the timestamp
			$temp = json_encode($temp);
			// add to cache.json file
			file_put_contents(plugin_dir_path(__FILE__).'/cache.json',$temp);
			$output['source'] = 'fresh';
		}
	} else {
		// not using cache, fresh data
		$c = curl_init($url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true); // hides pre-emptive output
		$c = json_decode(curl_exec($c),true);
		$output = $c;
		$output = $output['data'];
		$output['source'] = 'fresh';
		//$output = $output[0];
	}
	if($json_encode)
		return json_encode($output);
	else
		return $output;
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
		echo anook_show($instance);
		echo $args['after_widget'];
	}
	// backend options stuff
	public function form($instance){
		$options = array('user','nook','game');
		$instance['all_games'] = anook_fetch('user',$instance['search'].'/games',array('thumbnail','name','user','url'));
		?>
		<p class="anook_admin_widget_title">
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if(isset($instance['title'])) echo $instance['title']; ?>"  autocomplete='off'/>
		</p>
		<p class='anook_admin_parts_list'>
			<label for="<?php echo $this->get_field_id('part'); ?>">Part:</label>
			<select name="<?php echo $this->get_field_name('part');?>" id="<?php echo $this->get_field_id('part');?>" autocomplete='off'>
				<?php foreach($options as $option){
					echo '<option value="'.$option.'" id="'.$option.'" '.(($instance['part']==$option)?'selected':'').'>'.ucfirst($option).'</option>';
				}?>
			</select>
		</p>
		<p class='anook_admin_search'>
			<label for="<?php echo $this->get_field_id('search'); ?>">Search:</label>
			<input type="text" id="<?php echo $this->get_field_id('search'); ?>" name="<?php echo $this->get_field_name('search'); ?>" value="<?php if(isset($instance['search'])) echo $instance['search']; ?>" autocomplete='off'>
		</p>
		<p class='anook_admin_show_games' <?php if($instance['part']!='user') echo 'style="display:none;"'; ?>>
			<input type='radio' name="<?php echo $this->get_field_name('show_games'); ?>" value='1' id='show-games' <?php if($instance['show_games'] == 1) echo 'checked'; ?> autocomplete='off'> <label for='show-games'>Show games</label><br />
			<input type='radio' name="<?php echo $this->get_field_name('show_games'); ?>" value='0' id='hide-games' <?php if($instance['show_games'] == 0) echo 'checked'; ?> autocomplete='off'> <label for='hide-games'>Hide games</label>
		</p>
		<p class='anook_admin_games_list_info' <?php if($instance['part']!='user') echo 'style="display:none;"'; ?>><i class="fa fa-info-circle"></i> Hold control and select the games you wish to show on the widget, to show off your fame and favourite games.</p>
		<p class='anook_admin_games_list' <?php if($instance['part']!='user') echo 'style="display:none;"'; ?>>
			<input type='hidden' value='<?php echo json_encode($instance['games_list']); ?>' id="games_selected"  autocomplete='off'/>
			<select class='widefat' id="<?php echo $this->get_field_id('games_list'); ?>" name="<?php echo $this->get_field_name('games_list'); ?>[]" multiple='multiple' style="height:170px" autocomplete='off'>
				<?php
				foreach($instance['all_games'] as $key => $option){
					if($key=='source'||$key=='timestamp') continue;
				?>
					<option name="<?php echo $this->get_field_name('games_list'); ?>[]" value="<?php echo $option['name']; ?>" <?php if(in_array($option['name'], $instance['games_list'])) echo 'selected'; ?>><?php echo $option['name']; ?></option>
				<?php
				}
				?>
			</select>
		</p>
	<?php }
	// save the options!
	public function update($new_instance, $old_instance){
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['part'] = strip_tags($new_instance['part']);
		$instance['search'] = strip_tags($new_instance['search']);
		$instance['show_games'] = $new_instance['show_games'];
		$instance['games_list'] = $new_instance['games_list'];
		return $instance;
	}
}

// build
function anook_show($atts){
	$html = '';
	// define attributes (params) to make bandwidth less an issue and specify data return
	if($atts['part'] == 'user'){
		$params = array('picture','country','followerCount','url','username');
	} elseif($atts['part'] == 'game' || $atts['part'] == 'nook'){
		$params = array('thumbnail','name','userCount','url');
	}
	$return = anook_fetch($atts['part'],$atts['search'],$params);
	$source = $return['source'];
	$return = $return[0];
	$html .= '<script>console.log("'.$source.'");</script>';

	if($atts['part'] == 'user')
		$html .= '<div class="anook-badge anook-user" id="anook-user-badge">
					<span class="logo-container"><a href="http://anook.com/" title="Anook" target="_blank"><img class="logo" src="'.plugin_dir_url(__FILE__).'images/anook_logo_dark.png" alt="Anook Logo"></a></span>
					<div class="img-container"><a href="'.$return['url'].'" target="_blank"><img src="http://anook.com/'.$return['picture'].'" alt="'.$return['username'].'\'s profile picture" /></a></div>
					<div class="right">
						<span id="username"><a href="'.$return['url'].'" target="_blank">'.$return['username'].'</a></span>
						<span id="country">'.$return['country'].'</span>
						<small>Followers: '.$return['followerCount'].'</small>
						<a id="follow-button" href="'.$return['url'].'" target="_blank">follow user</a>
					</div>';

	elseif($atts['part'] == 'nook' || $atts['part'] == 'game')
		$html .= '<div class="anook-badge anook-'.$atts['part'].'" id="anook-'.$atts['part'].'-badge">
					<a href="http://anook.com" title="Anook" target="_blank"><img class="logo" src="'.plugin_dir_url(__FILE__).'images/anook_icon_dark.png"></a>
					<div class="img-container-nook"><a href="'.$return['url'].'" target="_blank"><img src="http://anook.com/'.$return['thumbnail'].'" /></a></div>
					<div class="right">
						<span id="username"><a href="'.$return['url'].'" target="_blank">'.$return['name'].'</a></span>
						<span id="country-empty"> </span>
						<small>Followers: '.$return['userCount'].'</small>
						<a id="follow-button" href="'.$return['url'].'" target="_blank">follow '.$atts['part'].'</a>
					</div>';
	// if it has show games
	if($atts['show_games'] && $atts['games_list'][0] != '0' && $atts['part'] == 'user'){
		$games = anook_fetch('user',$atts['search'].'/games',array('thumbnail','name','user','url'),false);
		$html .= '<ul>';
		foreach($atts['games_list'] as $show_game){
			foreach($games as $game){
				if(in_array($show_game,$game)){
					$game['fame'] = $game['user']['fame'];
					$html .= '<li><div class="img-container"><img src="http://anook.com/'.$game['thumbnail'].'"></div><div class="right">'.$game['name'].'<small>Fame: '.$game['fame'].'</small></div></li>';
				}
			}
		}
		$html .= '</ul>';
	}
	// end of anook-badge container		
	$html .='</div>';
	// return the end html result, all the built bits of code
	return $html;
}
function anook_show_shortcode($atts){
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
		$return = $return[0];
		$html .= '<script>console.log("'.$return['source'].'");</script>';
		if($key == 'user')
			$html .= '<div class="anook-badge anook-user" id="anook-user-badge">
						<span class="logo-container"><a href="http://anook.com" title="Anook" target="_blank"><img class="logo" src="'.plugin_dir_url(__FILE__).'images/anook_logo_dark.png"></a></span>
						<div class="img-container"><a href="'.$return['url'].'" target="_blank"><img src="http://anook.com/'.$return['picture'].'" /></a></div>
						<div class="right">
							<span id="username"><a href="'.$return['url'].'" target="_blank">'.$return['username'].'</a></span>
							<span id="country">'.$return['country'].'</span>
							<span id="followers">Followers: '.$return['followerCount'].'</span>
							<a id="follow-button" href="'.$return['url'].'" target="_blank">follow user</a>
						</div>
					</div>';
		elseif($key == 'nook' || $key == 'game')
			$html .= '<div class="anook-badge anook-'.$key.'" id="anook-'.$key.'-badge">
						<a href="http://anook.com" title="Anook" target="_blank"><img class="logo" src="'.plugin_dir_url(__FILE__).'images/anook_icon_dark.png"></a>
						<div class="img-container-nook"><a href="'.$return['url'].'" target="_blank"><img src="http://anook.com/'.$return['thumbnail'].'" /></a></div>
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

function anook_ajax(){
    print_r(anook_fetch($_POST['part'],$_POST['search'],$_POST['attr'],$_POST['json_encode']));
    exit();
}

// load js file to widgets area
function anook_admin_enqueue($hook) {
    if ( 'widgets.php' != $hook ) {
        return;
    }

    wp_enqueue_script( 'anook-badge', plugin_dir_url( __FILE__ ) . 'anook-badges.js', array('jquery') );
}

// init widget
function anook_widget_load(){
	register_widget('anook_widget');
}
function anook_font_awesome() {
   wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'); 
}

// font awesome for better icons
add_action('admin_init', 'anook_font_awesome');

// shortcode
add_shortcode('anook','anook_show_shortcode');

// widget
add_action('widgets_init', 'anook_widget_load');

// add extra stylesheet
wp_enqueue_style('anook-badge', plugins_url('style.css',__FILE__));

// enqueue js
if($enable_js_responsive)
	wp_enqueue_script('anook-badge', plugins_url('anook-badges.js',__FILE__), array('jquery'));

add_action( 'admin_enqueue_scripts', 'anook_admin_enqueue' );

add_action( 'wp_ajax_anook_ajax', 'anook_ajax' );
?>
