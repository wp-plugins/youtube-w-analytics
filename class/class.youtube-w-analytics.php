<?php

if (!class_exists('youtube_w_analytics')) :

	class youtube_w_analytics {
		
		//declaring different variables needed for class
		var $sessions_needed = "";
		var $definitions = array();
		var $error = array();
		var $access_level = "";
		var $uatag = "";
		var $pvparams = array();
		var $videos = array();
		var $variables = array();
		var $video_table_name = "";
		//var $self = array();
		
		function __construct($file = __FILE__) {
			global $wpdb;
			//place to set initial settings for plugin
			$this->sessions_needed = false;	
			$this->access_level = "manage_options";
			$this->variables['video_table_name'] ='youtube_w_analytics';
			
			$this->video_table_name = $wpdb->prefix . 'youtube_w_analytics';
			
			define('YTVTABLE',$this->video_table_name);
			// https://developers.google.com/youtube/player_parameters
			// https://developers.google.com/youtube/js_api_reference
			$this->pvparams = array ( 
									// 'autohide' => '',				
									// 'autoplay' => '',		
									// 'cc_load_policy' => '',	
									// 'color' => '',			
									// 'controls' => '',		
									// 'disablekb' => '',
									// 'enablejsapi' => '',
									// 'end' => '',
									// 'fs' => '',
									// 'hl' => '',
									// 'iv_load_policy' => '',
									// 'list' => '',
									// 'listType' => '',
									// 'loop' => '',
									'modestbranding' => false,	
									// 'origin' => '',
									// 'playerapiid' => '',		
									// 'playlist' => '',			
									'rel' => '0',				
									// 'showinfo' => '',			
									// 'start' => '',				
									'theme' => 'dark',			
									);
			
			register_activation_hook($file, array(&$this,'activate'));
			register_deactivation_hook($file,array(&$this,'deactivate'));
			add_action('init', array(&$this, 'init'));
			
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
			add_shortcode('ytwa_video',array(&$this, 'display_video') );
		}
		function activate() {
			$youtube_w_analytics = new youtube_w_analytics();
			$tableName = $youtube_w_analytics->video_table_name;
			$tableSql = "
						`id` int(11) NOT NULL AUTO_INCREMENT,
					 	`youtubeid` varchar(64) NOT NULL,
					 	`videovariables` text NOT NULL,
					 	`datetimeadded` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					 	UNIQUE id ( `id` )
						";
			self::create_table($tableName,$tableSql);
			
			update_option('ytwa_options', array('objectname'=>'ga', 'width'=> '500', 'height'=> '350') );
			
		}
		
		function deactivate() {
			$youtube_w_analytics = new youtube_w_analytics();
			$tableName = $youtube_w_analytics->video_table_name;
			
			self::drop_table($tableName);
			
			delete_option('ytwa_options');
		}
		
		function init() {
			if ($this->sessions_needed) :
				if (!session_id()) :
					session_start();
				endif;
			endif;
			
			$this->init_settings();	
			
		}
		
		function define_variable($variablename, $variabledata) {
			define($variablename, $variabledata);
			$this->definitions[$variablename] = $variabledata;
			return true;
		}
		
		function admin_init() {
			//customized initilization for the admin area only
			$this->init_settings();
			
			add_action( 'wp_ajax_updatevideo', array(&$this, 'tywa_callback') );
			add_action( 'wp_ajax_deletevideo', array(&$this, 'tywa_callback') );
			
		}
		function tywa_callback() {
				$action = strip_tags( $_POST['action'] );
				
				switch ($action) {
					case 'updatevideo':
						global $wpdb; // this is how you get access to the database
						
						$video_id = strip_tags($_POST['videoid']);
						if (check_admin_referer("update_video_" . $video_id)) {
							$vars['ytvtitle'] = strip_tags($_POST['ytvtitle_'.$video_id]);
							$vars['ytvid'] = strip_tags($_POST['youtubeid_'.$video_id]);
							$vars['ytvheight'] = strip_tags($_POST['ytvheight_'.$video_id]);
							$vars['ytvwidth'] = strip_tags($_POST['ytvwidth_'.$video_id]);
							$vars['ytmodbrand'] = strip_tags($_POST['ytmodbrand_'.$video_id]);
							$vars['ytrel'] = strip_tags($_POST['ytrel_'.$video_id]);
							$vars['yttheme'] = strip_tags($_POST['yttheme_'.$video_id]);
							
							$updateValues = array(
												'youtubeid' => $vars['ytvid'],
												'videovariables' => serialize($vars),
												);
							$updateTypes = array(
												'%s',
												'%s',
												);
						
							if ( $wpdb->update(YTVTABLE, $updateValues, array( 'id' => $video_id ), $updateTypes, array('%d') )) {
								echo "true";
							} else {
								echo "false";
							}
							
						} else {
							echo "false";
						}
					
					break;
					case 'deletevideo':
						global $wpdb; // this is how you get access to the database
						
						$vidid = $_POST['videoid'];
						$verifyId = base64_decode($_POST['hashtag']);
						
						if ($vidid == $verifyId) {
							if ( $wpdb->delete(YTVTABLE, array('id' => $vidid) ) ) {
								echo "true";
							} else {
								echo "false";
							}
						} else {
							echo "false";
						}
					break;	
				}
			
				wp_die(); // this is required to terminate immediately and return a proper response
			}
		
		function init_settings() {
			//customized init settings for entire program backend and front end
			wp_enqueue_script('jquery');
			add_action('wp_head', array(&$this, 'header') );
			add_action('wp_footer', array(&$this, 'footer') );
		}
		function footer() {
			echo "<!--Doing Footer-->\n";
		}
		function header() {
			echo "<!--Doing Header-->\n";
			$headYtwa = new youtube_w_analytics();
			$headYtwa->display_header_code();
		}
		
		function update_options() {
			
		}
		
		function add_menu() { 
			/* This menu choice adds the menu choice under the main menu settings */
			//add_options_page('Plugin Ba Settings', 'Incon Tracking', 'manage_options', 'incon_tracking_settings', array(&$this, 'incon_tracking_settings_page'));
			
			/* This menu choice adds it as a main menu allowing for sub pages */
			// Add the top-level admin menu to backend of WordPress
			$page_title = 'YouTube with Analytics Tracking Settings';
			$menu_title = 'YouTube w/ UAT';
			$capability = $this->access_level; //'manage_options';
			$menu_slug = 'ywa-settings';
			$menu_function = array(&$this, 'settings_page');
			add_menu_page($page_title, $menu_title, $capability, $menu_slug, $menu_function);
		 
			// Add submenu page with same slug as parent to ensure no duplicates
			$sub_menu_title = 'Settings';
			add_submenu_page($menu_slug, $page_title, $sub_menu_title, $capability, $menu_slug, $menu_function);
		 
			// Now add the submenu page for Help
			$submenu_page_title = 'YouTube Videos Page';
			$submenu_title = 'YouTube Videos Page';
			$submenu_slug = 'ywa-videos';
			$submenu_function = array(&$this, 'videos_page');
			add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
			
			// Now add the submenu page for Help
			$submenu_page_title = 'YouTube Parameters Help Page';
			$submenu_title = 'YouTube Parameters Help';
			$submenu_slug = 'ywa-second';
			$submenu_function = array(&$this, 'ytp_help_page');
			add_submenu_page($menu_slug, $submenu_page_title, $submenu_title, $capability, $submenu_slug, $submenu_function);
			
		}
		function add_video_form( $variables = array() ) {
			$ytwa = get_option('ytwa_options');
			if ($variables['ytvwidth'] == '') {
				$variables['ytvwidth'] = $ytwa['width'];	
			}
			if ($variables['ytvheight'] == '') {
				$variables['ytvheight'] = $ytwa['height'];	
			}
			?>
			<form method="post">
			<?php wp_nonce_field("add_video_form"); ?>
            YouTube Video Title: <input type="text" name="ytvtitle" value="<?php if (isset($variables['ytvtitle'])) echo $variables['ytvtitle']; ?>" /><br />
            YouTube Video ID: <input type="text" name="ytvid" value="<?php if (isset($variables['ytvid'])) echo $variables['ytvid']; ?>" /><br />
            Width: <input type="text" name="ytvwidth" value="<?php if (isset($variables['ytvwidth'])) echo $variables['ytvwidth']; ?>" size="4" maxlength="4" />px | Height: <input type="text" name="ytvheight" value="<?php if (isset($variables['ytvheight'])) echo $variables['ytvheight']; ?>" size="4" maxlength="4" />px<br />
            Modest Branding: <select name="ytmodbrand">
            <option value="false" <?php if (isset($variables['ytmodbrand']) && $variables['ytmodbrand'] == 'false') echo ' selected="selected" '; ?>>False</option>
            <option value="true" <?php if (isset($variables['ytmodbrand']) && $variables['ytmodbrand'] == 'true') echo ' selected="selected" '; ?>>True</option>
            </select> | Relationships: <select name="ytrel">
            <option value="0" <?php if (isset($variables['ytrel']) && $variables['ytrel'] == '0') echo ' selected="selected" '; ?>>No</option>
            <option value="1" <?php if (isset($variables['ytrel']) && $variables['ytrel'] == '1') echo ' selected="selected" '; ?>>Yes</option>
            </select> | Theme: <select name="yttheme">
            <option value="dark" <?php if (isset($variables['yttheme']) && $variables['yttheme'] == 'dark') echo ' selected="selected" '; ?>>Dark</option>
            <option value="light" <?php if (isset($variables['yttheme']) && $variables['yttheme'] == 'light') echo ' selected="selected" '; ?>>Light</option></select><br />
			<input type="submit" name="ytwa_submit" value="Add Video">
			</form>
			<?php
		}
		function settings_page() {
			$this->check_user();
			global $wpdb;
			if (isset($_POST['update_settings']) && check_admin_referer( 'ytwa_settings' ) ) {
				$tmpPost = $_POST;
				$newytwa = $tmpPost['ytwa'];
				update_option('ytwa_options',$newytwa);
				$this->add_error_msg("Updated Options.");
			} else if (isset($_POST['update_settings']) ) {
				$this->add_error_msg("Error Updating Options.");
			}
			$ytwa = get_option('ytwa_options');
			
			?>
		    <div class="wrapper">
			<?php			
			//$this->add_error_msg("Test Error Message");
			echo "<h1>YouTube with Analytics Settings Page</h2>";
			$this->disp_errors();
			?>
			<form method="post">
            <style>
			.indent {
				display:inline-block;
				width:50px;
			}
			.objectname {
				color:#ff0000;	 
				font-weight:bold; 
				font-size:24px	
			}
			</style>
<div style="margin-left:20px;">
&lt;script&gt;<br />
<span class="indent">&nbsp;</span>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){<br />
<span class="indent">&nbsp;</span>(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),<br />
<span class="indent">&nbsp;</span>m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)<br />
<span class="indent">&nbsp;</span>})(window,document,'script','//www.google-analytics.com/analytics.js','ga');<br /><br />
<span class="indent">&nbsp;</span><strong class="objectname">ga</strong>('create', 'UA-XXXXXXXX-X', 'auto');<br />
<span class="indent">&nbsp;</span><strong class="objectname">ga</strong>('require', 'displayfeatures');<br />
<span class="indent">&nbsp;</span><strong class="objectname">ga</strong>('send', 'pageview');<br />
&lt;/script&gt;
</div>
					<?php wp_nonce_field("ytwa_settings" ); ?>
				Universal Analytics Object Name: <input type="text" name="ytwa[objectname]" value="<?php echo $ytwa['objectname']; ?>" /><br />
(in above code the red and bold ga text that you are looking for to enter in here)<br>
				Default width: <input type="text" name="ytwa[width]" value="<?php echo $ytwa['width']; ?>" /><br>
				Default height: <input type="text" name="ytwa[height]" value="<?php echo $ytwa['height']; ?>" /><br>
				<input type="submit" name="update_settings" value="Update Settings">
			</form>
			</div>
			<?php
		}
		function videos_page() {
			$this->check_user();
			global $wpdb;
			$vidTableName = $this->video_table_name;
			
			$variables == array();
			
			if (isset($_POST['ytwa_submit']) && check_admin_referer( 'add_video_form' ) ) {
				//$this->add_error_msg("Video Added");
				//echo "<pre>" . print_r($_POST,true) . "</pre>";
				/*
					Array
					(
						[_wpnonce] => f47322f2d8
						[_wp_http_referer] => /wp-admin/admin.php?page=ywa-settings
						[ytvid] => 7gwJ7g5DPlo
						[ytvheight] => 600
						[ytvwidth] => 460
						[ytmodbrand] => false
						[ytrel] => 0
						[yttheme] => dark
						[ytwa_submit] => Add Video
					)
				*/
				$variables['ytvtitle'] = strip_tags($_POST['ytvtitle']);
				$variables['ytvid'] = strip_tags($_POST['ytvid']);
				$variables['ytvheight'] = strip_tags($_POST['ytvheight']);
				$variables['ytvwidth'] = strip_tags($_POST['ytvwidth']);
				$variables['ytmodbrand'] = strip_tags($_POST['ytmodbrand']);
				$variables['ytrel'] = strip_tags($_POST['ytrel']);
				$variables['yttheme'] = strip_tags($_POST['yttheme']);
				
				if ( 
						($variables['ytvtitle'] != '' && $variables['ytvid'] != '' && $variables['ytvheight'] != '' && $variables['ytvwidth'] != '' 
							&& $variables['ytmodbrand'] != '' && $variables['ytrel'] != '' && $variables['yttheme'] != '')
					&& (is_numeric($variables['ytvheight']) && is_numeric($variables['ytvwidth']))) {
						//insert video into database
						$this->add_error_msg("Added Video to syatem");	
						$insertValues = array(
											'youtubeid' => $variables['ytvid'],
											'videovariables' => serialize($variables),
											);
						$insertTypes = array(
											'%s',
											'%s',
											);
						if ($wpdb->insert( $vidTableName, $insertValues, $insertTypes)) {
							//success
							
						} else {
							//failed
							
						}
						
						$variables = array();
					} else {
						$this->add_error_msg("Error Adding Video");	
					}
				
			} else if (isset($_POST['ytwa_submit'])) {
				$this->add_error_msg("Error Adding Video");	
			}
			
			//if passed then display following code to user
?>
    <!-- Create a header in the default WordPress 'wrap' container -->
    <div class="wrapper">
<?php			
			//$this->add_error_msg("Test Error Message");
			echo "<h1>YouTube with Analytics Tracking Videos Page</h2>";
			$this->disp_errors();
			$this->add_video_form($variables);
			$videos = $wpdb->get_results( 'SELECT * FROM ' . $vidTableName . ' ORDER BY id ASC', ARRAY_A);
			$vidcount = $wpdb->num_rows;
			if ($vidcount > 0) {
				?>
                                    <script>
                                    jQuery.fn.multiline = function(text){
									    this.text(text);
									    this.html(this.html().replace(/\n/g,'<br/>'));
									    return this;
									}
					jQuery(document).ready(function(){
						jQuery('button[name="update"]').click( function(e) {
								//console.log(e);
								e.preventDefault();
								var videoid = jQuery(this).val();
								console.log("videoid: " + videoid);
								var ytvideoid = jQuery('input[name="videoid_' + videoid + '"]').val();
						});
						jQuery('.delete').click( function (e) {
							e.preventDefault();
							var vidid = jQuery(this).attr('href');
							var delvideo = confirm("Are you sure you want to delete video #" + vidid + "?");
							if (delvideo == true) {
								//delete video
								
								var tmpData = {
											'action': 'deletevideo',
											'videoid': vidid,
											'hashtag': btoa(vidid),
								};
								
								jQuery.post(ajaxurl,tmpData, function (response) {
											console.log(response);
											if (response == "true") {
												//hide both rows for video
												jQuery("#ytvid_" + vidid).hide();
												jQuery("#edit_ytvid_" + vidid ).hide();
												jQuery('#vuerror').multiline("Video has been deleted.\n");
											} else {
												jQuery('#vuerror').multiline("There was a problem deleting the video.\n");
											}
									
								});
								
								jQuery('#vuerror').multiline("Video has been deleted.\n");
							}
						});
						jQuery('.updatevideo').click( function (e) {
							e.preventDefault();
							var vidid = jQuery(this).attr('value');
							//alert(vidid);
							var ytvtitle = jQuery('#ytvtitle_'+vidid).val();
							var youtubeid = jQuery('#youtubeid_'+vidid).val();
							var ytvheight = jQuery('#ytvheight_'+vidid).val();
							var ytvwidth = jQuery('#ytvwidth_'+vidid).val();
							var ytmodbrand = jQuery('#ytmodbrand_'+vidid).val();
							var ytrel = jQuery('#ytrel_'+vidid).val();
							var yttheme = jQuery('#yttheme_'+vidid).val();
							var errormsg = "";
							jQuery('#vuerror').multiline("\n");
							if (ytvtitle.length == 0) errormsg += "Please enter in a unique video title for tracking.\n";
							if (youtubeid.length == 0) errormsg += "Please enter in a YouTube ID.\n";
							if (Math.floor(ytvheight) != ytvheight || ytvheight.length == 0) errormsg += "Please enter in a proper height.\n";
							if (Math.floor(ytvwidth) != ytvwidth || ytvwidth.length == 0) errormsg += "Please enter in a proper width.\n";
							if (errormsg != "") {
								jQuery('#vuerror').multiline(errormsg);
							} else {
								//process submit
								
								jQuery.post(ajaxurl,jQuery('#video_' + vidid).serialize(), function (response) {
											
											console.log(response);
											
											jQuery('#disp_ytvtitle_' + vidid).text(ytvtitle);
											jQuery('#disp_youtubeid_' + vidid).text(youtubeid);
											jQuery('#disp_ytvheight_' + vidid).text(ytvheight);
											jQuery('#disp_ytvwidth_' + vidid).text(ytvwidth);
											jQuery('#disp_ytmodbrand_' + vidid).text(ytmodbrand);
											jQuery('#disp_ytrel_' + vidid).text(ytrel);
											jQuery('#disp_yttheme_' + vidid).text(yttheme);
											
											jQuery("#ytvid_" + vidid).show();
											jQuery("#edit_ytvid_" + vidid ).hide();
											if (response == "true") {
												jQuery('#vuerror').multiline("Your video has been updated in the database.");
											} else {
												jQuery('#vuerror').multiline("There was a problem updating the video in the database. Please try again.");	
											}
									
								});
							}
						});
					});
					</script>
					<div id="vuerror" style="font-weight:bold; color:#ff0000;">&nbsp;</div>
                <table cellspacing="0" cellpadding="2" border="1">
                <tr><th>Shortcode Usage</th><th>Video Title</th><th>Video ID</th><th>Video Width</th><th>Video Height</th><th>Modest Branding</th><th>Relationships</th><th>Theme</th><th></th><th></th></tr>
                <?php
				foreach ($videos as $video) {
					//echo "<pre>" . print_r($video,true) . "</pre>";
					$vars = unserialize($video['videovariables']);
					?>
                    <script>
					jQuery(document).ready(function(){
						jQuery('.update').click( function (e){
							e.preventDefault();
							//console.log(e);
							var vidid = jQuery(this).attr('href');
							//alert(vidid);
							jQuery("#ytvid_" + vidid).hide();
							jQuery("#edit_ytvid_" + vidid ).show();
						});
						
					});
					</script>
                    <tr id="ytvid_<?php echo $video['id']; ?>">
                    <td style="text-align:center; font-weight:bold">&#91;ytwa_video vid="<?php echo $video['id']; ?>"&#93;</td>
                    <td id="disp_ytvtitle_<?php echo $video['id']; ?>" style="width:140px;"><?php echo $vars['ytvtitle']; ?><?php //echo "<pre>" . print_r($video,true) . "</pre>"; ?></td>
                    <td id="disp_youtubeid_<?php echo $video['id']; ?>" style="width:140px;"><?php echo $video['youtubeid']; ?></td>
                    <td id="disp_ytvwidth_<?php echo $video['id']; ?>" style="width:100px;"><?php echo $vars['ytvwidth']; ?></td>
                    <td id="disp_ytvheight_<?php echo $video['id']; ?>" style="width:100px;"><?php echo $vars['ytvheight']; ?></td>
                    <td id="disp_ytmodbrand_<?php echo $video['id']; ?>"><?php echo $vars['ytmodbrand']; ?></td>
                    <td id="disp_ytrel_<?php echo $video['id']; ?>"><?php echo $vars['ytrel']; ?></td>
                    <td id="disp_yttheme_<?php echo $video['id']; ?>" style="width:70px;"><?php echo $vars['yttheme']; ?></td>
                    <td><a href="<?php echo $video['id']; ?>" class="update">Update</a></td>
                    <td><a href="<?php echo $video['id']; ?>" class="delete">Delete</a></td>
                    </tr>
                    <tr id="edit_ytvid_<?php echo $video['id']; ?>" style="display:none;">
                    <form method="post" name="video_<?php echo $video['id']; ?>" id="video_<?php echo $video['id']; ?>">
					<?php wp_nonce_field("update_video_" . $video['id']); ?>
                    <input type="hidden" name="videoid" value="<?php echo $video['id']; ?>" />
                    <input type="hidden" name="action" value="updatevideo" />
                    <td style="text-align:center; font-weight:bold">&#91;ytwa vid="<?php echo $video['id']; ?>"&#93;</td>
                    <td style="width:140px;"><input type="text" id="ytvtitle_<?php echo $video['id']; ?>" name="ytvtitle_<?php echo $video['id']; ?>" value="<?php echo $vars['ytvtitle']; ?>" style="width:120px;" /></td>
                    <td style="width:140px;"><input type="text" id="youtubeid_<?php echo $video['id']; ?>" name="youtubeid_<?php echo $video['id']; ?>" value="<?php echo $video['youtubeid']; ?>" style="width:120px;" /></td>
                    <td style="width:100px;"><input type="text" id="ytvwidth_<?php echo $video['id']; ?>" name="ytvwidth_<?php echo $video['id']; ?>" value="<?php echo $vars['ytvwidth']; ?>" size="4" maxlength="4" style="width:80px;" /></td>
                    <td style="width:100px;"><input type="text" id="ytvheight_<?php echo $video['id']; ?>" name="ytvheight_<?php echo $video['id']; ?>" value="<?php echo $vars['ytvheight']; ?>" size="4" maxlength="4" style="width:80px;" /></td>
                    <td><select name="ytmodbrand_<?php echo $video['id']; ?>" id="ytmodbrand_<?php echo $video['id']; ?>">
                        <option value="false" <?php if (isset($vars['ytmodbrand']) && $vars['ytmodbrand'] == 'false') echo ' selected="selected" '; ?>>False</option>
                        <option value="true" <?php if (isset($vars['ytmodbrand']) && $vars['ytmodbrand'] == 'true') echo ' selected="selected" '; ?>>True</option>
                        </select></td>
                    <td><select name="ytrel_<?php echo $video['id']; ?>" id="ytrel_<?php echo $video['id']; ?>">
                        <option value="0" <?php if (isset($vars['ytrel']) && $vars['ytrel'] == '0') echo ' selected="selected" '; ?>>No</option>
                        <option value="1" <?php if (isset($vars['ytrel']) && $vars['ytrel'] == '1') echo ' selected="selected" '; ?>>Yes</option>
                        </select></td>
                    <td style="width:70px;"><select name="yttheme_<?php echo $video['id']; ?>" id="yttheme_<?php echo $video['id']; ?>">
                        <option value="dark" <?php if (isset($vars['yttheme']) && $vars['yttheme'] == 'dark') echo ' selected="selected" '; ?>>Dark</option>
                        <option value="light" <?php if (isset($vars['yttheme']) && $vars['yttheme'] == 'light') echo ' selected="selected" '; ?>>Light</option></select></td>
            		<td>
                    <button name="updatevideo" value="<?php echo $video['id']; ?>" class="updatevideo">Update Video</button>
                    </td>
                    <td>
                    </td>
                    </form>
                    </tr>
                    <?php
				}
				?>
                </table>
                <?php
			} else {
				echo "<h2>Currently no videos setup in the system.";
			}
?>
</div>
<?php
			
		}
		
		/* 
		 * The check_user function checks to see if they have sufficient permissions
		 * and if not then displays error message and does a wp_die.
		 */
		function check_user($user_can) {
			if ($user_can == '') $user_can = $this->access_level;
			if (!current_user_can($user_can)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));	
			}
			return true;
		}
		
		function create_table($tableName, $variableSql) {
			/*		Sample $variableSql data
						
					  Automatically adds database prefix to this database table
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `varcharexample` varchar(64) NOT NULL,
					  `textexample` text NOT NULL,
					  `intexample` int(11) NOT NULL DEFAULT '',
					  `timestampexample` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  UNIQUE ( `id` )
			*/
			global $wpdb;
			if ($variableSql != '') {
				$sql = "CREATE TABLE IF NOT EXISTS `". $tableName."` ( ".$variableSql.");";
				$wpdb->query($sql);
				return true;
			}
			
			return false;
		}
		
		function drop_table($tableName) {
			global $wpdb;
			$sql = 	"DROP TABLE IF EXISTS " . $tableName;
			if ($wpdb->query($sql)) 
				return true;			
			return false;	
		}
		function disp_errors() {
			$displayText = '';
			if (count($this->error) > 0 ) {
				foreach ($this->error as $text) {
					$displayText .= $text . "<br>";
				}
				echo "<div style='color:#ff0000;font-weight:bold;'>".$displayText."</div>";
				return true;
			}
			return false;
		}
		
		function add_error_msg($msg) {
			$this->error[] = $msg;
		}
		
		function display_header_code() {
			global $wpdb;
			$videosSql = "SELECT * FROM " . $this->video_table_name;
			$videos = $wpdb->get_results($videosSql, ARRAY_A);
			?>
<script>
	var tag = document.createElement('script');
	tag.src = "http://www.youtube.com/player_api";
	var firstScriptTag = document.getElementsByTagName('script')[0];
	firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
<?php foreach ($videos as $video) { ?>
	var player<?php echo $video['id']; ?>;
	var lastAction<?php echo $video['id']; ?> = '';
	var onPlayerStateChange<?php echo $video['id']; ?>;
<?php }	?>

function cleanTime(playerid){
	var videoid = 'player' + playerid;
    return Math.round(eval(videoid).getCurrentTime())
};

	function onYouTubePlayerAPIReady() {
<?php 	foreach ($videos as $video) { 
					$pvparams = unserialize($video['videovariables']);
?>
		player<?php echo $video['id']; ?> = new YT.Player('player<?php echo $video['id']; ?>', {
			playerVars: {
		    	modestbranding: <?php echo $pvparams['ytmodbrand']; ?>,
		        theme: '<?php echo $pvparams['yttheme']; ?>',
		        rel:  <?php echo $pvparams['ytrel']; ?>,
		},
		height: '<?php echo $pvparams['ytvheight']; ?>',
		width: '<?php echo $pvparams['ytvwidth']; ?>',
		videoId: '<?php echo $video['youtubeid']; ?>',
		events: {
			'onStateChange': onPlayerStateChange<?php echo $video['id']; ?>
		}
	});
					 <?php
				}
				?>
}
</script>
            <?php
		}
		function display_video($atts) {
			global $wpdb;
			$a = shortcode_atts(
				array(
					'vid' => '',
					), $atts, 'ytwa_video');
			$ytwa = new youtube_w_analytics();
			$videoSql = "SELECT * FROM " . $ytwa->video_table_name . " WHERE id=%d";
			$video = $wpdb->get_results($wpdb->prepare($videoSql, $a['vid']), ARRAY_A);
//			echo "<pre>". print_r($video,true) . "</pre>";
//			return;
			//return;
			$videoId = $video[0]['id'];
			$videoVars = unserialize($video[0]['videovariables']);
			
			$ytwa_options = get_option('ytwa_options');
			return $ytwa->display_player_code($videoId,$videoVars['ytvtitle'], $ytwa_options['objectname'],$videoVars['ytvid']);
		}
		function display_player_code($videonum, $videotitle, $uatag, $videoid) {
			//$videotitle = str_replace(" ", "_", $videotitle);
			$returnHtml = '';
			$returnHtml .= '<div id="player'.$videonum. '"></div>' . "\n";
			$returnHtml .= '     <script>' . "\n";
			$returnHtml .= '		function onPlayerStateChange'. $videonum. '(event) {' . "\n";
			$returnHtml .= '            switch (event.data) {' . "\n";
			$returnHtml .= '                case YT.PlayerState.PLAYING:' . "\n";
			$returnHtml .= '						if ( cleanTime("'. $videonum. '") ==  0) {'. "\n";
			$returnHtml .= '							'. $uatag. "('send', 'event', '". $videotitle. "', 'started','vid: ".$videoid."');" . "\n";
			$returnHtml .= '						} else {'. "\n";
			$returnHtml .= '							'. $uatag. "('send', 'event', '". $videotitle. "', 'played','vid: ".$videoid." time: ' + cleanTime('". $videonum. "'));" . "\n";
			$returnHtml .= '						} '. "\n";
			$returnHtml .= '                     break;' . "\n";
			$returnHtml .= '                 case YT.PlayerState.ENDED:' . "\n";
			$returnHtml .= '                		' . "\n";
			$returnHtml .= '						'. $uatag. "('send', 'event', '".$videotitle. "', 'completed','vid: ".$videoid."');" . "\n";
			$returnHtml .= '                     break;' . "\n";
			$returnHtml .= '                 case YT.PlayerState.PAUSED:' . "\n";
			$returnHtml .= '                 	if (player'. $videonum. '.getDuration() - player'. $videonum. '.getCurrentTime() != 0) {' . "\n";
			$returnHtml .= '                    	if (lastAction' . $videonum. " != 'paused') {" . "\n";
			$returnHtml .= '							'. $uatag."('send', 'event', '" . $videotitle. "', 'paused', 'vid: ".$videoid." time: ' + cleanTime('". $videonum. "') );" . "\n";
			$returnHtml .= '                     	} else {' . "\n";
			$returnHtml .= '                         	lastAction'. $videonum ." = 'paused';" . "\n";
			$returnHtml .= '                     	}' . "\n";
			$returnHtml .= '                     }' . "\n";
			$returnHtml .= '                     break;' . "\n";
			$returnHtml .= '             }' . "\n";
			$returnHtml .= '         }' . "\n";
			$returnHtml .= '	</script>' . "\n";
            
            return $returnHtml;
		}
		function ytp_help_page() {
			$this->check_user();
			//if passed then display following code to user
			?>
				<!-- Create a header in the default WordPress 'wrap' container -->
				<div class="wrap">
			<?php			
			
						echo "<h1>YouTube Parameters Help Page</h2>";
						$this->disp_errors();
			?>
            <style>
			dt {
				font-weight:bold;
			}
			</style>
			<h3 id="parameter-subheader">All YouTube player parameters available for plugin</h3>
			<dl>
			  <?php /* ?><?php /* ?><dt id="autohide">autohide (supported players: AS3, HTML5)</dt>
			  <dd id="autohide-definition">Values: 2 (default), 1, and 0. This parameter indicates whether the video controls will automatically hide after a video begins playing. The default behavior (autohide=2) is for the video progress bar to fade out while the player controls (play button, volume control, etc.) remain visible.<br />
				<br />
				<ul>
				  <li>If this parameter is set to 1, then the video progress bar and the player controls will slide out of view a couple of seconds after the video starts playing. They will only reappear if the user moves her mouse over the video player or presses a key on her keyboard.</li>
				  <li>If this parameter is set to 0, the video progress bar and the video player controls will be visible throughout the video and in fullscreen.</li>
				</ul>
			  </dd><?php */ ?>
			  <?php /* ?><dt id="autoplay">autoplay (supported players: AS3, HTML5)</dt>
			  <dd id="autoplay-definition">Values: 0 or 1. Default is 0. Sets whether or not the initial video will autoplay when the player loads.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="cc_load_policy">cc_load_policy (supported players: AS3, HTML5)</dt>
			  <dd id="cc_load_policy-definition">Values: 1. Default is based on user preference. Setting to 1 will cause closed captions to be shown by default, even if the user has turned captions off.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="color">color (supported players: AS3, HTML5)</dt>
			  <dd id="color-definition">This parameter specifies the color that will be used in the player's video progress bar to highlight the amount of the video that the viewer has already seen. Valid parameter values are red and white, and, by default, the player will use the color red in the video progress bar. See the <a target="_blank" href="http://apiblog.youtube.com/2011/08/coming-soon-dark-player-for-embeds.html" spfieldtype="null" spsourceindex="373">YouTube API blog</a> for more information about color options.<br />
				<br />
				<strong>Note:</strong> Setting the color parameter to white will disable the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#modestbranding" spfieldtype="null" spsourceindex="374">modestbranding</a> option.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="controls">controls (supported players: AS3, HTML5)</dt>
			  <dd id="controls-definition">Values: 0, 1, or 2. Default is 1. This parameter indicates whether the video player controls will display. For IFrame embeds that load a Flash player, it also defines when the controls display in the player as well as when the player will load:
				<ul>
				  <li>controls=0 – Player controls do not display in the player. For IFrame embeds, the Flash player loads immediately.</li>
				  <li>controls=1 – Player controls display in the player. For IFrame embeds, the controls display immediately and the Flash player also loads immediately.</li>
				  <li>controls=2 – Player controls display in the player. For IFrame embeds, the controls display and the Flash player loads after the user initiates the video playback.</li>
				</ul>
				<strong>Note:</strong> The parameter values 1 and 2 are intended to provide an identical user experience, but controls=2 provides a performance improvement over controls=1 for IFrame embeds. Currently, the two values still produce some visual differences in the player, such as the video title's font size. However, when the difference between the two values becomes completely transparent to the user, the default parameter value may change from 1 to 2.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="disablekb">disablekb (supported players: AS3, HTML5)</dt>
			  <dd id="disablekb-definition">Values: 0 or 1. Default is 0. Setting to 1 will disable the player keyboard controls. Keyboard controls are as follows: <br />
				Spacebar: Play / Pause <br />
				Arrow Left: Jump back 10% in the current video <br />
				Arrow Right: Jump ahead 10% in the current video <br />
				Arrow Up: Volume up <br />
				Arrow Down: Volume Down <br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="enablejsapi">enablejsapi (supported players: AS3, HTML5)</dt>
			  <dd id="enablejsapi-definition">Values: 0 or 1. Default is 0. Setting this to 1 will enable the Javascript API. For more information on the Javascript API and how to use it, see the <a target="_blank" href="https://developers.google.com/youtube/js_api_reference" spfieldtype="null" spsourceindex="375">JavaScript API documentation</a>.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="end">end (supported players: AS3, HTML5)</dt>
			  <dd id="start-definition">Values: A positive integer. This parameter specifies the time, measured in seconds from the start of the video, when the player should stop playing the video. Note that the time is measured from the beginning of the video and not from either the value of thestart player parameter or the startSeconds parameter, which is used in YouTube Player API functions for loading or queueing a video.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="fs">fs (supported players: AS3, HTML5)</dt>
			  <dd id="fs-definition">Values: 0 or 1. The default value is 1, which causes the fullscreen button to display. Setting this parameter to 0 prevents the fullscreen button from displaying.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="hl">hl (supported players: AS3, HTML5)</dt>
			  <dd id="fs-definition">Sets the player's interface language. The parameter value is an <a target="_blank" href="http://www.loc.gov/standards/iso639-2/php/code_list.php" spfieldtype="null" spsourceindex="376">ISO 639-1 two-letter language code</a>, though other language input codes, such as IETF language tags (BCP 47) may also be handled properly.<br />
				<br />
				The interface language is used for tooltips in the player and also affects the default caption track. Note that YouTube might select a different caption track language for a particular user based on the user's individual language preferences and the availability of caption tracks.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="iv_load_policy">iv_load_policy (supported players: AS3, HTML5)</dt>
			  <dd id="iv_load_policy-definition">Values: 1 or 3. Default is 1. Setting to 1 will cause video annotations to be shown by default, whereas setting to 3 will cause video annotations to not be shown by default.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="list">list (supported players: AS3, HTML5)</dt>
			  <dd id="list-definition">The list parameter, in conjunction with the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="377">listType</a> parameter, identifies the content that will load in the player.<br />
				<ul>
				  <li>If the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="378">listType</a> parameter value is search, then the list parameter value specifies the search query.</li>
				  <li>If the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="379">listType</a> parameter value is user_uploads, then the list parameter value identifies the YouTube channel whose uploaded videos will be loaded.</li>
				  <li>If the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#listType" spfieldtype="null" spsourceindex="380">listType</a> parameter value is playlist, then the list parameter value specifies a YouTube playlist ID. In the parameter value, you need to prepend the playlist ID with the letters PL as shown in the example below.<br />
					<pre>http://www.youtube.com/embed?listType=playlist&amp;list=PLC77007E23FF423C6</pre>
				  </li>
				</ul>
				<strong>Note:</strong> If you specify values for the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#list" spfieldtype="null" spsourceindex="381">list</a> and listType parameters, the IFrame embed URL does not need to specify a video ID.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="listType">listType (supported players: AS3, HTML5)</dt>
			  <dd id="listType-definition">The listType parameter, in conjunction with the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#list" spfieldtype="null" spsourceindex="382">list</a> parameter, identifies the content that will load in the player. Valid parameter values are playlist, search, and user_uploads.<br />
				<br />
				If you specify values for the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#list" spfieldtype="null" spsourceindex="383">list</a> and listType parameters, the IFrame embed URL does not need to specify a video ID.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="loop">loop (supported players: AS3, HTML5)</dt>
			  <dd id="loop-definition">Values: 0 or 1. Default is 0. In the case of a single video player, a setting of 1 will cause the player to play the initial video again and again. In the case of a playlist player (or custom player), the player will play the entire playlist and then start again at the first video.<br />
				<br />
				<strong>Note:</strong> This parameter has limited support in the AS3 player and in IFrame embeds, which could load either the AS3 or HTML5 player. Currently, the loop parameter only works in the AS3 player when used in conjunction with the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#playlist" spfieldtype="null" spsourceindex="384">playlist</a> parameter. To loop a single video, set the loop parameter value to 1 and set the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#playlist" spfieldtype="null" spsourceindex="385">playlist</a> parameter value to the same video ID already specified in the Player API URL:<br />
				<pre>http://www.youtube.com/v/<strong>VIDEO_ID</strong>?version=3&amp;loop=1&amp;playlist=<strong>VIDEO_ID</strong></pre>
			  </dd><?php */ ?>
			  <?php  ?><dt id="modestbranding">modestbranding (supported players: AS3, HTML5)</dt>
			  <dd id="modestbranding-definition">This parameter lets you use a YouTube player that does not show a YouTube logo. Set the parameter value to 1 to prevent the YouTube logo from displaying in the control bar. Note that a small YouTube text label will still display in the upper-right corner of a paused video when the user's mouse pointer hovers over the player.<br />
				<br />
			  </dd><?php  ?>
			  <?php /* ?><dt id="origin">origin (supported players: AS3, HTML5)</dt>
			  <dd id="origin-definition">This parameter provides an extra security measure for the IFrame API and is only supported for IFrame embeds. If you are using the IFrame API, which means you are setting the <a target="_blank" href="https://developers.google.com/youtube/player_parameters#enablejsapi" spfieldtype="null" spsourceindex="386">enablejsapi</a> parameter value to 1, you should always specify your domain as the origin parameter value.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="playerapiid">playerapiid (supported players: AS3)</dt>
			  <dd id="playerapiid-definition">Value can be any alphanumeric string. This setting is used in conjunction with the JavaScript API. See the <a target="_blank" href="https://developers.google.com/youtube/js_api_reference" spfieldtype="null" spsourceindex="387">JavaScript API documentation</a> for details.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="playlist">playlist (supported players: AS3, HTML5)</dt>
			  <dd id="playlist-definition">Value is a comma-separated list of video IDs to play. If you specify a value, the first video that plays will be the VIDEO_ID specified in the URL path, and the videos specified in the playlist parameter will play thereafter.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="playsinline">playsinline (supported players: HTML5)</dt>
			  <dd id="playlist-definition">This parameter controls whether videos play inline or fullscreen in an HTML5 player on iOS. Valid values are:<br />
				<ul>
				  <li>0: This value causes fullscreen playback. This is currently the default value, though the default is subject to change.</li>
				  <li>1: This value causes inline playback for UIWebViews created with the allowsInlineMediaPlayback property set toTRUE.</li>
				</ul>
			  </dd><?php */ ?>
			  <dt id="rel">rel (supported players: AS3, HTML5)</dt>
			  <dd id="rel-definition">Values: 0 or 1. Default is 1. This parameter indicates whether the player should show related videos when playback of the initial video ends.<br />
				<br />
			  </dd>
			  <?php /* ?><dt id="showinfo">showinfo (supported players: AS3, HTML5)</dt>
			  <dd id="showinfo-definition">Values: 0 or 1. The parameter's default value is 1. If you set the parameter value to 0, then the player will not display information like the video title and uploader before the video starts playing.<br />
				<br />
				If the player is loading a playlist, and you explicitly set the parameter value to 1, then, upon loading, the player will also display thumbnail images for the videos in the playlist. Note that this functionality is only supported for the AS3 player since that is the only player that can load a playlist.<br />
				<br />
			  </dd><?php */ ?>
			  <?php /* ?><dt id="start">start (supported players: AS3, HTML5)</dt>
			  <dd id="start-definition">Values: A positive integer. This parameter causes the player to begin playing the video at the given number of seconds from the start of the video. Note that similar to the <a target="_blank" href="https://developers.google.com/youtube/js_api_reference#seekTo" spfieldtype="null" spsourceindex="388">seekTo</a> function, the player will look for the closest keyframe to the time you specify. This means that sometimes the play head may seek to just before the requested time, usually no more than around two seconds.<br />
				<br />
			  </dd><?php */ ?>
			  <dt id="theme">theme (supported players: AS3, HTML5)</dt>
			  <dd id="theme-definition">This parameter indicates whether the embedded player will display player controls (like a play button or volume control) within a dark or light control bar. Valid parameter values are dark and light, and, by default, the player will display player controls using the dark theme. See the <a target="_blank" href="http://apiblog.youtube.com/2011/08/coming-soon-dark-player-for-embeds.html" spfieldtype="null" spsourceindex="389">YouTube API blog</a> for more information about the dark and light themes.</dd>
			</dl>
			
			</div>
			<?php
			
		}
		
		
	}
endif;