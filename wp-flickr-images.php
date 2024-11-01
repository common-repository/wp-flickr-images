<?php
/*
Plugin Name: Wp Flickr Images
Plugin URI: http://fabwebstudio.com
Description: Flickr images based on particular keywords.
Version: 1.1
Author: Fab Web Studio
Author URI: http://fabwebstudio.com
*/

function wp_Flickr_keyword_upgrademe() {
}


class wpFlickrKeyword {
	public function __construct()
	{
		# create meta boxes for 'edit post' pages
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		
		# on init to catch POST
		add_action('init', array($this, 'save_data'));
		
		# Adding Menu
		add_action('admin_menu',  array($this, 'flick_settings'));

		# include css
		add_action('admin_print_styles', array($this, 'enqueue_styles'));
	}
	
	# include css
	public function enqueue_styles()
	{
		# include wp-flickr-keyword.css
		wp_enqueue_style('wp-flickr-keyword', plugins_url('/stylesheets/wp-flickr-keyword.css', __FILE__), false, '1.0');
	}
	
	# add meta boxes
	public function add_meta_boxes()
	{
		add_meta_box(
			'wp_Flickr_keyword_meta_box',
			'WP Flickr Images',
			array($this, 'render_wp_flickr_keyword_content'),   
			'post',
			'side',
			'core'
		);
		add_meta_box(
			'wp_Flickr_keyword_meta_box',
			'WP Flickr Images',
			array($this, 'render_wp_flickr_keyword_content'),
			'page',
			'side',
			'core'
		);
	}
	
	# meta box content
	public function render_wp_flickr_keyword_content($post) {
		$flickr_detail = get_post_meta($post->ID,'wp_Flickr_options',true);
		if(is_array($flickr_detail)) {
			extract($flickr_detail);
		}
		
		$flickr_post_apikey = get_option('_wp_flickr_key_api',false);
		$flickr_post_secret = get_option('_wp_flickr_key_secret',false);

		# nonce verification
		wp_nonce_field(plugin_basename(__FILE__), 'wp_Flickr_keyword_nonce');

		# Render Box Output
		echo "<div id='css_wp_flickr'>";
		if(!($flickr_post_apikey && $flickr_post_secret)) {
			echo "<span class='wp_flickr_desc'>Please Enter your flickr API detials <a href='".admin_url()."options-general.php?page=flickr-api-data'>Here!</a></span>";
		}
		else {
			echo "<p class='css_wp_flickr_row'>";
				echo "<label>Image Keyword</label>";
				echo "<input type='text' name='_wp_flickr_key_data' id='_wp_flickr_key_data' value='".$yt_keyword."'/>";
			echo "</p>";
			
			echo "<fieldset class='css_wp_flickr_row'>";
				echo "<legend>Image Dimensions</legend>";
				echo "<label class='small_input'>Width Based</label><input style='width:50px;height:20px;' id='' class='small_input_box' name='_wp_flickr_key_image_width' value='".$yt_width."'/>";
			echo "</fieldset>";

			echo "<fieldset class='css_wp_flickr_row'>";
				echo "<legend>Image Location</legend>";
				echo "<input ".($yt_location=='b_title'?'CHECKED':'')." class='small_input_box' id='_wp_flickr_key_image_below_title' name='_wp_flickr_key_image_location' type='radio' value='b_title' />";
				echo "<label for='_wp_flickr_key_image_below_title' class='radio-button'>Below Title</label><br/>";
				echo "<input ".($yt_location=='b_content'?'CHECKED':'')." class='small_input_box' id='_wp_flickr_key_image_below_content' name='_wp_flickr_key_image_location' type='radio' value='b_content'/>";
				echo "<label for='_wp_flickr_key_image_below_content' class='radio-button'>Below Content</label><br/>";
				echo "<input ".($yt_location=='b_mid'?'CHECKED':'')." class='small_input_box' id='_wp_flickr_key_image_between_content' name='_wp_flickr_key_image_location' type='radio' value='b_mid'/>";
				echo "<label for='_wp_flickr_key_image_between_content' class='radio-button'>Between Content</label><br/>";
			echo "</fieldset>";

			echo "<fieldset class='css_wp_flickr_row'>";
				echo "<legend>Image Alignment</legend>";
				echo "<input ".($yt_align=='a_left'?'CHECKED':'')." class='small_input_box' id='_wp_flickr_key_image_align_left' name='_wp_flickr_key_image_align' type='radio' value='a_left' />";
				echo "<label for='_wp_flickr_key_image_align_left' class='radio-button'>Left</label>";
				echo "<input ".($yt_align=='a_mid'?'CHECKED':'')." class='small_input_box' id='_wp_flickr_key_image_align_mid' name='_wp_flickr_key_image_align' type='radio' value='a_mid'/>";
				echo "<label for='_wp_flickr_key_image_align_mid' class='radio-button'>Center</label>";
				echo "<input ".($yt_align=='a_right'?'CHECKED':'')." class='small_input_box' id='_wp_flickr_key_image_align_right' name='_wp_flickr_key_image_align' type='radio' value='a_right'/>";
				echo "<label for='_wp_flickr_key_image_align_right' class='radio-button'>Right</label>";
			echo "</fieldset>";
		}
		echo "</div>";

	}
	
	/* Save Data on post save*/
	public function save_data() {

		# if there is no data
		if (empty($_POST))
			return false;
		
		# check nonce is set and verify it
		if (!isset($_POST['wp_Flickr_keyword_nonce']) || !wp_verify_nonce($_POST['wp_Flickr_keyword_nonce'], plugin_basename(__FILE__)))
			return false;

		# get post id
		$post_id = (int) $_POST['post_ID'];
		
		# check permissions
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id))
				return false;
		}
		else {
			if (!current_user_can('edit_post', $post_id))
				return false;
		}
		
		# get data from post meta for youtube
		$flickr_detail = array(
			'yt_keyword'=>$_POST['_wp_flickr_key_data'],
			'yt_neg_keyword'=>$_POST['_wp_flickr_key_neg_data'],
			'yt_width'=>$_POST['_wp_flickr_key_image_width'],
			'yt_height'=>$_POST['_wp_flickr_key_image_height'],
			'yt_location'=>$_POST['_wp_flickr_key_image_location'], 
			'yt_align'=>$_POST['_wp_flickr_key_image_align'],
		);
				
		update_post_meta($post_id,'wp_Flickr_options',$flickr_detail);
	}

	public function flickr_api_data () {
		
		$flickr_api = $flickr_secret = '';

		if(get_option('_wp_flickr_key_api',false)) {
			$flickr_api = get_option('_wp_flickr_key_api',false);
		}

		if(get_option('_wp_flickr_key_secret',false)) {
			$flickr_secret = get_option('_wp_flickr_key_secret',false);
		}
		
		if(isset($_POST['submit_flickr_api']) && $_POST['submit_flickr_api']=='Save Changes') {
			update_option('_wp_flickr_key_api',$_POST['_wp_flickr_key_api']);
			$flickr_api = $_POST['_wp_flickr_key_api'];
			update_option('_wp_flickr_key_secret',$_POST['_wp_flickr_key_secret']);
			$flickr_secret = $_POST['_wp_flickr_key_secret'];
		}

		echo "<div class='wrap'>";
			echo "<h2>Flickr Settings</h2>";
			echo "<span class='description'>If you do not have an api key please apply for one <a href='http://www.flickr.com/services/apps/create/apply/'>Here !</a></span>";
			echo '<form action="" method="post">';
				echo "<table class='form-table'><tr><td>";
					echo "<label for='_wp_flickr_key_api' class='api-button'>Flickr API</label>";
				echo "</td><td>";
					echo "<input id='_wp_flickr_key_api' name='_wp_flickr_key_api' type='text' value='".$flickr_api."' />";
				echo "</td></tr><tr><td>";
					echo "<label for='_wp_flickr_key_secret' class='api-button'>Flickr Secret</label>";
				echo "</td><td>";
					echo "<input id='_wp_flickr_key_secret' name='_wp_flickr_key_secret' type='text' value='".$flickr_secret."' />";
				echo "</td></tr><tr><td colspan='2'>";
				echo "</td></tr></table>";
				echo '<p class="submit"><input type="submit" value="Save Changes" class="button-primary" id="submit" name="submit_flickr_api"></p>';
			echo "</form>";	
		echo "</div>";
	}

	public function flick_settings() {
		add_options_page('Wp Flickr Images', 'Wp Flickr Images', 'manage_options', 'flickr-api-data', array($this,'flickr_api_data'));
	}


}

# if loaded in wordpress and we are in /wp-admin
if (function_exists('is_admin') && is_admin()) {
	# create object
	new wpFlickrKeyword();
} 



function flickr_post_call($method, $params, $sign = false, $rsp_format = "php_serial") {
	
	$flickr_post_apikey = get_option('_wp_flickr_key_api',true);
	$flickr_post_secret = get_option('_wp_flickr_key_secret',true);
	if(!is_array($params)) $params = array();
	$call_includes = array( 'api_key'	=> $flickr_post_apikey, 'method'	=> $method, 'format'	=> $rsp_format);	
	$params = array_merge($call_includes, $params);	
	if($sign) $params = array_merge($params, array('api_sig' => flickr_post_sig($params)));	
	$url = "http://api.flickr.com/services/rest/?".flickr_post_encode($params);	
    return flickr_post_get_request($url);    
}

function flickr_post_get_request($url) {
	if(function_exists('curl_init')) {
		$session = curl_init($url);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($session);
		curl_close($session);
		$rsp_obj = unserialize($response);
	} 
	else {
		$handle = fopen($url, "rb");
		$contents = '';
		while (!feof($handle)) {
			$contents .= fread($handle, 8192);
		}
		fclose($handle);
		$rsp_obj = unserialize($contents);
	}
	return $rsp_obj;
}

function flickr_post_encode($params) {
	$encoded_params = array();

	foreach ($params as $k => $v){
		$encoded_params[] = urlencode($k).'='.urlencode($v);
	}
	
	return implode('&', $encoded_params);
}

function flickr_post_sig($params) {
	ksort($params);
	

$flickr_post_apikey = get_option('_wp_flickr_key_api',true);
$flickr_post_secret = get_option('_wp_flickr_key_secret',true);
$api_sig = $flickr_post_secret;
	
	foreach ($params as $k => $v){
		$api_sig .= $k . $v;
	}
	
	return md5($api_sig);
}

function flickr_post_auth_url($frob, $perms) {
$flickr_post_apikey = get_option('_wp_flickr_key_api',true);
$flickr_post_secret = get_option('_wp_flickr_key_secret',true);	$params = array('api_key' => $flickr_post_apikey, 'perms' => $perms, 'frob' => $frob);
	$params = array_merge($params, array('api_sig' => flickr_post_sig($params)));	
	$url = 'http://flickr.com/services/auth/?'.flickr_post_encode($params);
	return $url;
}

function flickr_post_photo_url($photo, $size) {
	$sizes = array('square' => '_s', 'thumbnail' => '_t', 'small' => '_m', 'medium' => '', 'large' => '_b', 'original' => '_o');
	if(!isset($photo['originalformat']) && strtolower($size) == "original") $size = 'medium';
	if(($size = strtolower($size)) != 'original') {
		$url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['secret']}{$sizes[$size]}.jpg";
	} else {
		$url = "http://farm{$photo['farm']}.static.flickr.com/{$photo['server']}/{$photo['id']}_{$photo['originalsecret']}{$sizes[$size]}.{$photo['originalformat']}";
	}
	return $url;
}

function flickr_replace($string) {
	return str_replace("&amp;amp;","&amp;",str_replace("&","&amp;",str_replace("'","\'",$string))); 
}


function imageResize($width, $height, $target) {
	if ($width > $height) {
		$percentage = ($target / $width);
	} else {
		$percentage = ($target / $height);
	}
	$width = round($width * $percentage);
	$height = round($height * $percentage);
	return "width=\"$width\" height=\"$height\"";
}

function add_flickr_content($content) {
	global $post;
	$flickr_opt = get_post_meta($post->ID,'wp_Flickr_options',true);
	if($flickr_opt) {
		extract($flickr_opt);
	}
	if($yt_keyword) {
		$image_out = '';
		$flickr_post_apikey = get_option('_wp_flickr_key_api',true);
		$flickr_post_secret = get_option('_wp_flickr_key_secret',true);
		$per_page = 5;
		$sortbyinteresting = 'interestingness-desc';
		$commercial_only = 1;
		$temp = array();

		$licences = flickr_post_call('flickr.photos.licenses.getInfo',array());

		for($i = 1; $i < count($licences['licenses']['license']); $i++) {
					array_push($temp,$i);
		}
		$licence_search = implode(',',$temp);
		$licences = $licences['licenses']['license'];

		$params = array('api_key'=>$flickr_post_apikey, 'extras' => 'license,o_dims');

		$aTags = explode (" ",$yt_keyword);
		$sTag = implode(',',$aTags);

		$params = array_merge($params,array('tags' => $sTag,'tag_mode' => 'all'));
		$flickr_function = 'flickr.photos.search';

		$params = array_merge($params, array('sort' => $sortbyinteresting)); 
		$params = array_merge($params, array('license' => '4,5,6'));
		$params = array_merge($params,array('per_page' => '10'));
		$photos = flickr_post_call($flickr_function, $params, true);

		if(count($photos['photos']['photo'])>0) {
			$id_win = array_rand($photos['photos']['photo'],1);
			$photo = $photos['photos']['photo'][$id_win];
			$owner = flickr_post_call('flickr.people.getInfo',array('user_id' => $photo['owner']));
			$by = $owner['person']['username']['_content'];
			$img_title = $photo['title'];
			$img_alt = $photo['title'];

			$image_align = '';
			$loc_align = '';
			if($yt_align == "a_left") {
				$image_align="<div style='float:left;padding: 0 15px 10px 0;'>";
				$loc_align ="float:left;";
			}else if($yt_align == "a_mid") {
				$image_align="<div style='float:none;text-align:center;width=100%;'>";
				$loc_align ="float:none;";
			}else if($yt_align == "a_right") {
				$image_align="<div style='float:right;padding: 0 0 10px 15px;'>";
				$loc_align ="float:right;";
			}

				if($image_align!='') {
					$image .= $image_align;
				}		

				$size = "original";
				$image_url = flickr_post_photo_url($photo,$size);

				$image_dim = getimagesize($image_url);

				
				if($yt_location == "b_mid") {
					$image_only = '<img alt="'.$img_alt.'" title="'.$sTag.'" src="'.$image_url.'" '.imageResize($image_dim[0],$image_dim[1], $yt_width).' id="image-'.$photo['id'].'" alt="'.flickr_replace($photo['title']).'" style="vertical-align:top;padding: 10px;'.$loc_align.'"/><br/><span style="clear:both;display:block;'.$loc_align.'text-align:center;padding:0 10px 10px;">by <em>'.$by.'</em> under <em>CC-SA</em></span>';
				}else {
					if($yt_align == "a_left") {
						$image_only = '<img alt="'.$img_alt.'" title="'.$sTag.'" src="'.$image_url.'" '.imageResize($image_dim[0],$image_dim[1], $yt_width).' id="image-'.$photo['id'].'" alt="'.flickr_replace($photo['title']).'"/><br/><span style="clear:both;display:block;float:left;width:250px;padding:0 10px 10px;">by <em>'.$by.'</em> under <em>CC-SA</em></span>';
					}
					else if($yt_align == "a_right") {
						$image_only = '<img alt="'.$img_alt.'" title="'.$sTag.'" src="'.$image_url.'" '.imageResize($image_dim[0],$image_dim[1], $yt_width).' id="image-'.$photo['id'].'" alt="'.flickr_replace($photo['title']).'"/><br/><span style="clear:both;display:block;float:right;text-align:left;width:250px;padding:0 10px 10px;">by <em>'.$by.'</em> under <em>CC-SA</em></span>';					
					}
					else {
						$image_only = '<img alt="'.$img_alt.'" title="'.$sTag.'" src="'.$image_url.'" '.imageResize($image_dim[0],$image_dim[1], $yt_width).' id="image-'.$photo['id'].'" alt="'.flickr_replace($photo['title']).'"/><br/><span style="clear:both;display:block;float:right;text-align:center;width:100%;padding:0 10px 10px;">by <em>'.$by.'</em> under <em>CC-SA</em></span>';
					}
				}
				$image .= $image_only;
		
			if($image_align!='') {
				$image .=  '</div>';
			}

			if($yt_location == "b_title") {
				return $image." ".$content;
			}
			else if($yt_location == "b_content") {
				return $content." ".$image;
			}
			else if($yt_location == "b_mid") {
				$content_len = strlen($post->post_content)/2;
				if($content_len>60) {

					$str_rp = $content;

					$content_dot = explode('.',strip_tags($str_rp));
					
					$content_dot_index = ceil(count($content_dot)/2);
					$content_rep_text = trim($content_dot[$content_dot_index]);
					$content_rep_text_new = "##LINKFIT## ".$content_rep_text;
					$content = str_replace($content_rep_text,$content_rep_text_new,$content);
					$content = str_replace("##LINKFIT##", $image_only, $content);
					return $content;
				}

			}
			else {
				return $image." ".$content;
			}
		}
		else {
			return $content;
		}
	}
	else {
		return $content;
	}
}

if(!is_admin()) {
	add_action('the_content', 'add_flickr_content');
}