<?php
/*
Plugin Name: THM Overlay
Version: 1.1
 */
  
if (!class_exists('THM_VIDEO_OVERLAY')) { 
    /**
     * Class of THM Overlay
     */
    class THM_VIDEO_OVERLAY {

        var $plugin_version = '1.1';
		var $plugin_base;

        function __construct() {
            define('THM_VIDEO_OVERLAY_VERSION', $this->plugin_version);
			$this->plugin_base = plugin_basename(__FILE__);
            $this->plugin_includes();
        }

        /**
         * sets up all wordpress actions and shortcode handling
         */
        function plugin_includes() {
            if (is_admin()) {
                add_action('admin_menu', array($this, 'add_options_menu'));
            }
            add_action('wp_enqueue_scripts', 'THMOverlay_get_scripts');
			add_shortcode('THMOverlay', 'THM_Overlay_Video_Handler');
        }

        /**
         * adds THM Overlay Settings link to 'Settings' Tab
         */
        function add_options_menu() {
            if (is_admin()) {
                add_options_page(__('THM Overlay Settings', '--'), __('THM Overlay Settings', '--2'), 'manage_options', 'THM_Overlay_Settings', array($this, 'options_page'));
            }
        }

        /**
         * Settings page of THM Overlay
         */
        function options_page() {
			include("settings.php");
        }
    }
	
    $GLOBALS['THM_Overlay_for_wordpress'] = new THM_VIDEO_OVERLAY();
} 

/**
 * sets up all scripts files needed for Overlay
 */
function THMOverlay_get_scripts() {
    if (!is_admin()) {
        $plugin_url = plugins_url('', __FILE__);
        wp_register_script('THMOverlay-main', $plugin_url . '/THMOverlay-main.js', array(), THM_VIDEO_OVERLAY_VERSION, 'all');
        wp_enqueue_script('THMOverlay-main');
        wp_register_script('THMOverlay-ResizeSensor', $plugin_url . '/ResizeSensor.js', array(), THM_VIDEO_OVERLAY_VERSION, 'all');
        wp_enqueue_script('THMOverlay-ResizeSensor');
        wp_register_style('THMOverlay-style', $plugin_url . '/THMOverlay.css', array(), THM_VIDEO_OVERLAY_VERSION, 'all');
        wp_enqueue_style('THMOverlay-style');
    }
}

$n; //counter for multiple overlays

/**
 * proccesses user shortCode into Overlay Div
 * and adds it to first player on the page
 * 
 * @param string[] $atts Attributes of shortCode
 */
function THM_Overlay_Video_Handler($atts) {
	
	$output;
    $string;
    $PicScript = '';
    $VidScript = '';
    global $n;
    $n = $n+1;
	
    $atts = shortcode_atts(
			array(
                'content'   =>  '',
                'link'      =>  '',
                'pic'       =>  '',
                'pWidth'    =>  '100%',
                'pHeight'   =>  '100%',
                'video'       =>  '',
                'vWidth'    =>  '100%',
                'vHeight'   =>  '100%',
				'position'  =>  'top',
                'ticker'    =>  'false',
                'start' =>  '0',
                'end' =>  '5'
			), $atts, 'THMOverlay' );

    //check if there is any content
    if(empty($atts['content']) && empty($atts['pic']) && empty($atts['video'])){
		return __("[THMOverlay-Error] Content, Video or Picture has to be set!", '--');
	}


    $timeStart  = $atts['start'];
    $timeEnd    = $atts['end'];
    $position   = 'THM' . $atts['position'];

    $contentString = THM_Overlay_Content_Handler($atts);
	
	$output .=  "<!-- Start THM_Overlay -->";
			
	$output .=  "<script>
					$(document).ready(function() {
                        //getting all Clappr Videos
                        var matches = [].slice.call(
                            document.querySelector('div').querySelectorAll('[id^=clappr-]')
                        );
                        //creating Overlay Div Container
                        var OverlayDiv = document.createElement('div');
                        OverlayDiv.id = 'THMOverlay-' + matches[0].getAttribute('id').split('-')[1] + '$n';
                        OverlayDiv.className = 'THMOverlay ' + '$position';
                        OverlayDiv.innerHTML = '$contentString';

                        // different width settings for top/Bottom and left/right Overlays
                        if('$position'.includes('top') || '$position'.includes('bottom')) {
                            OverlayDiv.style.width = matches[0].offsetWidth+'px';
                        } else {
                            OverlayDiv.style.width = (matches[0].offsetWidth/4)+'px';
                            if('$position'.includes('right')) {
                                //OverlayDiv.style.marginLeft = (matches[0].offsetWidth)-(OverlayDiv.style.width.split('p')[0])+'px';
                            }
                        }
                        matches[0].appendChild(OverlayDiv);
                        // function for Visibility of Overlay
                        player.listenTo(player, Clappr.Events.PLAYER_TIMEUPDATE, function() { checkAndToggleOverlay(OverlayDiv.id, $timeStart, $timeEnd) })

                        new ResizeSensor(matches[0], function() {
                            var layer = document.getElementById(OverlayDiv.id);
                            if(layer.className.includes('THMtop') || layer.className.includes('THMbottom')) {
                                layer.style.width = matches[0].clientWidth + 'px';
                            } else {
                                layer.style.width = matches[0].clientWidth/4 + 'px';
                            }
                        });";

                        //add Picture script
                        $PicScript = buildPictureSizeScript($atts['pWidth'], $atts['pHeight']);
                        $output .= $PicScript;
                        //add Video script
                        $VidScript = buildVideoSizeScript($atts['vWidth'], $atts['vHeight']);
                        $output .= $VidScript;

	$output .=  "});
			</script>";

	$output .= "<!-- End THM_Overlay -->";
	
	return $output;
}


/**
 * gets javascript for resizing picture
 *
 * @param number $width desired width of picture
 * @param number $height desired height of picture
 */
function buildPictureSizeScript($width, $height) {

    $script = "
        var picture = document.getElementById('OverlayPic')
        if(picture) {
            picture.style.width = '$width';
            picture.style.height = '$height';
        }
    ";

    return $script;
}

/**
 * gets javascript for resizing video
 *
 * @param number $width desired width of video
 * @param number $height desired height of video
 */
function buildVideoSizeScript($width, $height) {

    $script = "
        var video = document.getElementById('OverlayVid')
        if(video) {
            video.style.width = '$width';
            video.style.height = '$height';
        }
    ";

    return $script;
}

/**
 * Builds the Content String with specified attributes
 * which will be in the Overlay <div>
 * 
 * @param string[] $atts Attributes of shortCode
 */
function THM_Overlay_Content_Handler($atts) {

    if(!empty($atts['pic'])){
        $content .= "<div>";
        if(!empty($atts['link'])) $content .= '<a target="_blank" href="' . $atts['link'] . '">'; 
        $content .= '<img id="OverlayPic" src="'. $atts['pic'] . '">';
        if(!empty($atts['link'])) $content .= '</a>';
        $content .= "</div>";
	} if(!empty($atts['video'])){
        $content .= "<div>";
        if(!empty($atts['link'])) $content .= '<a target="_blank" href="' . $atts['link'] . '">'; 
        $content .= '<video id="OverlayVid" src="'. $atts['video'] . '" autoplay muted>';
        if(!empty($atts['link'])) $content .= '</a>';
        $content .= "</div>";
    } if(!empty($atts['content'])) {
        $content .= "<div>";
        if(strcmp($atts['ticker'],"true") == 0) $content .= "<marquee>";
        if(!empty($atts['link'])) $content .= '<a target="_blank" href="' . $atts['link'] . '">'; 
        $content .= $atts['content'];
        if(!empty($atts['link'])) $content .= '</a>';
        if(strcmp($atts['ticker'],"true") == 0) $content .= "</marquee>";
        $content .= "</div>";
    }
    return $content;
}