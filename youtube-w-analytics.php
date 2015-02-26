<?php

/*
Plugin Name: YouTube with Universal Analytics Tracking
Plugin URI: http://wpcms.ninja
Description: YouTube video embed system that adds tracking for Universal Analytics Events to the start, pause, and completion of a video. 
Author: Greg Whitehead
Version: 1.2
Author URI: http://www.gregwhitehead.us/

*/


$plugin_directory 	= "youtube-w-analytics"; 	//For use in definitions
$plugin_prefix		= "YWA";			//For use in definitions names

define( $plugin_prefix.'_URL',plugins_url($plugin_directory) . "/");
define( $plugin_prefix.'_PATH', plugin_dir_path( __FILE__) ); 

include("class/class.youtube-w-analytics.php");

if (class_exists('youtube_w_analytics')) {

	$youtube_w_analytics = new youtube_w_analytics( __FILE__ );	

}
