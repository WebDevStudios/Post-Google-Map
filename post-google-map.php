<?php
/*
Plugin Name: Post Google Map
Plugin URI: http://webdevstudios.com/support/wordpress-plugins/
Description: Plugin allows posts to be linked to specific addresses and coordinates and display plotted on a Google Map.  Use shortcode [google-map] to display map directly in your post/page.  Map shows plots for each address added to the post you are viewing.
Version: 1.6.3
Author: WebDevStudios.com
Author URI: http://webdevstudios.com
License: GPLv2
*/

$gmp_version = '1.6.3';

//hook for adding admin menus
add_action( 'admin_menu', 'gmp_menu' );

//hook for adding a new address
add_action( 'admin_menu', 'gmp_add_new_address' );

//hook for post/page custom meta box
add_action( 'admin_menu', 'gmp_meta_box_add' );

//hook to initialize the widget
add_action( 'widgets_init', 'gmp_register_widget' );

//register the [google-map] shortcode
add_shortcode( 'google-map', 'gmp_register_shortcode' );

//Load our textdomain
add_action('plugins_loaded', 'gmp_textdomain');


function gmp_textdomain() {
  load_plugin_textdomain( 'gmp-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function gmp_register_widget() {

	//register the map widget
	register_widget( 'gmp_map_widget' );

}

//shortcode function
function gmp_register_shortcode() {
	global $gmp_display;

	$gmp_display = 'return';

	//generate map for shortcode
	$map = gmp_generate_map( '650', 'map_canvas_shortcode');

	echo $map;

}

//save/update post/page address fields from meta box
function gmp_add_new_address() {

	if ( isset( $_POST['gmp_submit'] ) && ! empty( $_POST['gmp_submit'] ) ) {
		gmp_post_meta_tags();
	}

}

function gmp_generate_map( $height='650', $id='map_canvas' ) {

	//include the Google Map Class
	include_once( plugin_dir_path( __FILE__ ) .'map-lib/Google-Map-Class.php' );

	?><div id="google_map" class="paper shadow"><?php
	$gmp_google_map = new GMP_Google_Map();
	$gmp_google_map->run( absint( $height ), $id );
	?></div><?php

}

function gmp_meta_box_add() {

	//register custom meta boxes for addresses
	add_meta_box( 'gmp', __( 'Post Google Map', 'gmp-plugin' ), 'gmp_meta_box', 'post' );
	add_meta_box( 'gmp', __( 'Post Google Map', 'gmp-plugin' ), 'gmp_meta_box', 'page' );

}

function del_gmp_address( $deladdy ) {

	//delete address from a post
	if ( is_numeric( $deladdy ) ) {

		$id = $_GET['post'];
		$gmp_arr = get_post_meta( absint( $id ), 'gmp_arr', false );

		if ( is_array( $gmp_arr ) ) {

			delete_post_meta( absint( $id ), 'gmp_arr' );

			unset( $gmp_arr[$deladdy] );

			for ( $row = 0; $row <= count( $gmp_arr ); $row++ ) {

				if ( ! empty( $gmp_arr[$row] ) && is_array( $gmp_arr[$row] ) ) {

					add_post_meta( absint( $id ), 'gmp_arr', $gmp_arr[$row] );

				}

			}

			//echo "<div id=message class=updated fade>Address deleted successfully.</div>";
			$isdeleted = true;

		}
	}
}

function gmp_post_meta_tags() {

	//verify user is on the admin dashboard and at least a contributor
	if ( is_admin() && current_user_can( 'edit_posts' ) ) {
		global $alreadyran;

		$gmp_id = $_POST["gmp_id"];

		//if post is not created yet, bail out
		if ( ! is_numeric( $gmp_id ) )
			return;

		//save the form data from the post/page meta box
		if ( isset( $gmp_id ) && ! empty( $gmp_id ) && $alreadyran != '1' ) {
			$id = $gmp_id;
			$alreadyran = "1";

			//get post data
			$gmp_long           = esc_attr( $_POST["gmp_long"] );
			$gmp_lat            = esc_attr( $_POST["gmp_lat"] );
			$gmp_address1       = esc_attr( $_POST["gmp_address1"] );
			$gmp_address2       = esc_attr( $_POST["gmp_address2"] );
			$gmp_city           = esc_attr( $_POST["gmp_city"] );
			$gmp_state          = esc_attr( $_POST["gmp_state"] );
			$gmp_zip            = esc_attr( $_POST["gmp_zip"] );
			$gmp_marker         = esc_attr( $_POST["gmp_marker"] );
			$gmp_title          = esc_attr( $_POST["gmp_title"] );
			$gmp_description    = esc_attr( $_POST["gmp_description"] );
			$gmp_desc_show      = esc_attr( $_POST["gmp_desc_show"] );

			//get long & lat
			if ( isset( $gmp_long ) && ! empty( $gmp_long ) && isset( $gmp_lat ) && ! empty( $gmp_lat ) ) {
				$coords = implode( ',', array( $gmp_lat, $gmp_long ) );
				$iaddress = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&latlng=' . $coords;
				$result = wp_remote_get( $iaddress );

				if ( ! is_wp_error( $result ) ) {
					$address = json_decode( $result['body'] );

					$lat = $gmp_lat;
					$lng = $gmp_long;
					$gmp_address1 = $address->results[0]->address_components[0]->long_name . $address->results[0]->address_components[1]->short_name;
					$gmp_city = $address->results[0]->address_components[3]->long_name;
					$gmp_state = $address->results[0]->address_components[6]->short_name;
					$gmp_zip = $address->results[0]->address_components[8]->long_name;
				}

			} elseif ( isset( $gmp_address1 ) && ! empty( $gmp_address1 ) ) {

				$addressarr = array( $gmp_address1, $gmp_city, $gmp_state, $gmp_zip );
				$address = implode( ',', $addressarr );
				$iaddress = 'http://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=' . urlencode( $address );

				//use the WordPress HTTP API to call the Google Maps API and get coordinates
				$result = wp_remote_get( $iaddress );
				if( ! is_wp_error( $result ) ) {

					$json = json_decode( $result['body'] );
					//set lat/long for address from JSON response
					$lat = $json->results[0]->geometry->location->lat;
					$lng = $json->results[0]->geometry->location->lng;

				}

			}

			//create an array from the post data and long/lat from Google
			$gmp_arr=array(
				"gmp_long"			=>	$lng,
				"gmp_lat"			=>	$lat,
				"gmp_address1"		=>	$gmp_address1,
				"gmp_address2"		=>	$gmp_address2,
				"gmp_city"			=>	$gmp_city,
				"gmp_state"			=>	$gmp_state,
				"gmp_zip"			=>	$gmp_zip,
				"gmp_marker"		=>	$gmp_marker,
				"gmp_title"			=>	$gmp_title,
				"gmp_description"	=>	$gmp_description,
				"gmp_desc_show"		=>	$gmp_desc_show,
				);

			//sanitize the data
			$gmp_arr = array_map( 'strip_tags', $gmp_arr );

			//save address array as option gmp_arr
			add_post_meta( absint( $id ), 'gmp_arr', $gmp_arr );

		}
	}
}

function gmp_meta_box() {
	global $post, $isdeleted;

	//call function to delete an address
	if ( isset( $_GET['deladdy'] ) && $_GET['deladdy'] != "" && $isdeleted != true ) {

		//verify user is at least a contributor and on the WP dashboard to allow deleting an address
		if ( is_admin() && current_user_can( 'edit_posts' ) ) {

			//check nonce for security
			check_admin_referer( 'delete-address' );
			$deladdy = $_GET['deladdy'];

			del_gmp_address( absint( $deladdy ) );

		}
	}

	//load saved addresses if any exist
	$gmp_arr = get_post_meta( $post->ID, 'gmp_arr', false );

	$imgpath = plugin_dir_url( __FILE__ ) . '/markers/';
	?>
	<form method="post">
		<input value="<?php echo absint( $post->ID ); ?>" type="hidden" name="gmp_id" />
		<div style="padding-bottom:10px;"><?php _e( 'To display in your post, add this shortcode', 'gmp-plugin' ); ?>: [google-map]</div>
		<div style="padding-bottom:10px;"><?php _e( 'Current Saved Addresses', 'gmp-plugin' ); ?>:</div>
		<table cellspacing="0" cellpadding="3" width="100%" style="margin-bottom:20px">
			<tr>
				<td colspan="2"></td>
				<td><strong><?php _e( 'Address 1', 'gmp-plugin' ); ?></strong></td>
				<td><strong><?php _e( 'Address 2', 'gmp-plugin' ); ?></strong></td>
				<td><strong><?php _e( 'City', 'gmp-plugin' ); ?></strong></td>
				<td><strong><?php _e( 'State', 'gmp-plugin' ); ?></strong></td>
				<td><strong><?php _e( 'Zip', 'gmp-plugin' ); ?></strong></td>
			</tr>
			<?php if ( is_array( $gmp_arr ) ) {
				//List all of our saved addresses.
				$bgc = "";
				for ( $row = 0; $row < count( $gmp_arr ); $row++ ) {

					if( $bgc == "" ) {
						$bgc = "#eeeeee";
					} else {
						$bgc = "";
					}

					$gmp_action = "delete-address";
					?>
					<tr style="background:<?php echo esc_attr( $bgc );?> !important;" bgcolor="<?php echo esc_attr( $bgc );?>">
						<td><a title="<?php esc_attr_e( 'Delete Address', 'gmp-plugin' ); ?>" href="<?php echo wp_nonce_url( add_query_arg ( 'deladdy', $row ), $gmp_action ); ?>"><img width="15px" border="0" src="<?php echo WP_PLUGIN_URL . '/post-google-map/delete.png';?>"></a></td>
						<td><img width="25px" src="<?php echo esc_attr( $imgpath.$gmp_arr[$row]["gmp_marker"] ); ?>"></td>
						<td><?php echo esc_html( $gmp_arr[$row]["gmp_address1"] ); ?></td>
						<td><?php echo esc_html( $gmp_arr[$row]["gmp_address2"] ); ?></td>
						<td><?php echo esc_html( $gmp_arr[$row]["gmp_city"] ); ?></td>
						<td><?php echo esc_html( $gmp_arr[$row]["gmp_state"] ); ?></td>
						<td><?php echo esc_html( $gmp_arr[$row]["gmp_zip"] ); ?></td>
					</tr>
					<tr style="background:<?php echo esc_attr( $bgc );?> !important;" bgcolor="<?php echo esc_attr( $bgc );?>">
						<td colspan="2"></td>
						<td colspan="5">
							<?php echo $gmp_arr[$row]["gmp_title"];
							if ( $gmp_arr[$row]["gmp_description"] != "" ){
								echo " - ";
							}
							echo esc_html( $gmp_arr[$row]["gmp_description"] ); ?>
						</td>
					</tr>
					<?php
					}
			} else {
				?><tr><td colspan="6" align="center"><i>no addresses saved</i></td></tr><?php
			}
			?>
		</table>
		<div style="padding-bottom:10px;"><?php _e( 'Enter an address or coordinates to plot this post/page on a Google Map.  You can enter multiple addresses', 'gmp-plugin' ); ?></div>
		<table style="margin-bottom:20px">
			<tr>
			<th style="text-align:right;" colspan="2">
			</th>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><?php _e( 'Marker', 'gmp-plugin' ) ?></th>
			<td>
				<select name="gmp_marker">
					<?php
					//create our dropdown based on icons in the markers directory
					//allow extensions
					$allowed_exts = apply_filters( 'gmp_allowed_exts', array( 'jpg', 'jpeg', 'gif', 'png' ) );

					$dir = plugin_dir_path( __FILE__ ) . '/markers/';
					$x = 0;
					if ( is_dir( $dir ) ) {
						if ( $handle = opendir( $dir ) ) {
							while ( false !== ( $file = readdir( $handle ) ) ) {
								$ext = pathinfo( $file , PATHINFO_EXTENSION);
								if ( $file != "." && $file != ".." && in_array( $ext, $allowed_exts ) ) {
									$x=1;
									echo "<option value='" . esc_attr( $file ) . "' style='background: url(" . $imgpath . $file . ")no-repeat;text-indent: 30px;height:25px;'>" . $file;
								}
							}
							closedir( $handle );
						}
					}
					?>
				</select>
			</td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_title"><?php _e( 'Title', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_title" name="gmp_title" size="25" tabindex="91" /> <?php _e( '*If blank will use post title.', 'gmp-plugin' ); ?></td>
			</tr>
			<tr>
			<th valign="top" scope="row" style="text-align:right;"><label for="gmp_description"><?php _e( 'Description', 'gmp-plugin' ) ?></label></th>
			<td><textarea id="gmp_description" name="gmp_description" style="width:300px;" tabindex="92" ></textarea><br>
			<input checked type="checkbox" id="gmp_desc_show" name="gmp_desc_show"><label for="gmp_desc_show"><?php _e( 'Use excerpt or first ten words of post if excerpt is blank.', 'gmp-plugin' ); ?></label>
			</td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_address1"><?php _e( 'Address 1', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_address1" name="gmp_address1" size="25" tabindex="93" /></td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_address2"><?php _e( 'Address 2', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_address2" name="gmp_address2" size="25" tabindex="94" /></td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_city"><?php _e( 'City', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_city" name="gmp_city" size="25" tabindex="95" /></td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_state"><?php _e( 'State', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_state" name="gmp_state" size="15" tabindex="96" /></td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_zip"><?php _e( 'Zip Code', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_zip" name="gmp_zip" size="10" tabindex="97" /></td>
			</tr>
			<tr>
				<th scope="row" style="text-align:right;"></th>
				<td>OR</td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_lat"><?php _e( 'Latitude', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_lat" name="gmp_lat" size="20" tabindex="98" /></td>
			</tr>
			<tr>
			<th scope="row" style="text-align:right;"><label for="gmp_long"><?php _e( 'Longitude', 'gmp-plugin' ) ?></label></th>
			<td><input value="" type="text" id="gmp_long" name="gmp_long" size="20" tabindex="99" /></td>
			</tr>
			<tr>
			<th scope="row"></th>
			<td>
					<input type="submit" class="button button-secondary" name="gmp_submit" value="<?php _e( 'Add Address', 'gmp-plugin' ); ?>" tabindex="100" />
			</td>
			</tr>
		</table>
		</form>
	<?php
}

function gmp_menu() {

	add_options_page( __( 'Post Google Map Options', 'gmp-plugin' ), __( 'Post Google Map', 'gmp-plugin' ), 'manage_options', __FILE__, 'gmp_options' );

}

//Function to save the plugin settings
function gmp_update_options() {

	//nonce check for security
	check_admin_referer( 'gmp_check' );

	//create array for storing option values. Sanitized later
	$wds_gmp_arr = array(
		"post_gmp_map_type"	=>	$_POST['gmp_map_type'],
		"gmp_marker_max"	=>	$_POST['gmp_marker_max'],
	);

	//sanitize the values
	$wds_gmp_arr = array_map( 'strip_tags', $wds_gmp_arr );

	//save array as option
	update_option( 'gmp_params', $wds_gmp_arr );

} # gmp_update_options()


function gmp_options() {
	global $gmp_version;

	if ( isset( $_POST['update_gmp_options'] ) && $_POST['update_gmp_options'] ) {

		//update plugin options
		gmp_update_options();

		echo '<div class="updated"><p><strong>' . __( 'Settings saved.', 'gmp-plugin' ) . '</strong></p></div>';
	}

	//load plugin settings
	$options_arr = get_option( 'gmp_params' );

	$options_map_type = $options_arr["post_gmp_map_type"];
	$gmp_marker_max = $options_arr["gmp_marker_max"];

	echo '<div class="wrap">';
	echo '<h2>' . __( 'Post Google Map Settings', 'gmp-plugin' ) . '</h2>';
	echo '<form method="post" action="">';

	if ( function_exists( 'wp_nonce_field' ) )
		wp_nonce_field( 'gmp_check' );

	echo '<input type="hidden" name="update_gmp_options" value="1">';
	?>
	<strong><?php _e( 'Default Map Settings', 'gmp-plugin' ); ?></strong>
	<table>
	<tr>
	<td align=right><?php _e( 'Map Type', 'gmp-plugin' ); ?>:</td>
	<td>
		<select name="gmp_map_type">
			<option value="ROADMAP" <?php selected( $options_map_type, 'ROADMAP' ); ?> ><?php _e( 'Road', 'gmp-plugin' ); ?>
			<option value="SATELLITE" <?php selected( $options_map_type, 'SATELLITE' ); ?> ><?php _e( 'Satellite', 'gmp-plugin' ); ?>
			<option value="HYBRID" <?php selected( $options_map_type, 'HYBRID' ); ?> ><?php _e( 'Hybrid', 'gmp-plugin' ); ?>
			<option value="TERRAIN" <?php selected( $options_map_type, 'TERRAIN' ); ?> ><?php _e( 'Terrain', 'gmp-plugin' ); ?>
		</select>
	</td>
	</tr>
	<tr>
			<td align=right><?php _e( 'Marker Plot Max', 'gmp-plugin' ); ?>:</td>
			<td>
				<select name="gmp_marker_max">
					<?php for ( $x = 0; $x < 50; ){
						$x = $x + 5;
						?>
						<option value='<?php echo esc_attr( $x ); ?>' <?php selected( $gmp_marker_max, $x ); ?>><?php echo esc_html( $x ); ?>
						<?php
					}
					?>
				</select>
				<?php _e( '*per page load', 'gmp-plugin' ); ?>
			</td>
	</tr>
	</table>

	<p><input type="submit" value="<?php esc_attr_e( 'Save Changes', 'gmp-plugin' ); ?>" class="button-primary" /></p>
	</form>

	<h3><?php _e( 'How do I add custom icons to be used for map markers', 'gmp-plugin' ); ?></h3>
	<p><?php _e( 'Create a folder inside your wp-content folder named "markers", add your custom markers to the folder, and we will do the rest.', 'gmp-plugin' ); ?></p>
	<?php
	echo '<p>' . sprintf( __( 'For support please visit our %s Support Forum %s. Please file bugs %s GitHub %s Version '. $gmp_version .' by %s | %s ' ), '<a href="http://wordpress.org/support/plugin/post-google-map" target="_blank">', '</a>', '<a href="https://github.com/WebDevStudios/Post-Google-Map">', '</a><br/>','<a href="http://webdevstudios.com/" target="_blank">WebDevStudios.com</a>', '<a href="http://twitter.com/webdevstudios" target="_blank">@WebDevStudios</a>' ) . '</p></div>';
}



//post google map widget
class gmp_map_widget extends WP_Widget {

	//process the new widget
	function __construct() {
		$widget_ops = array(
			'classname' => 'gmp_map_widget',
			'description' => __( 'Widget to show Post Google Map plots', 'gmp-plugin' )
			);
		parent::__construct( 'gmp_map_widget', __( 'Post Google Map Widget', 'gmp-plugin' ), $widget_ops );
	}

	 //build the widget settings form
	function form($instance) {
		$defaults = array( 'title' => 'Google Map' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		?>
			<p><?php _e( 'Title', 'gmp-plugin' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<?php
	}

	//save the widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {

		extract( $args );

		echo $before_widget;
		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( !empty( $title ) ) { echo $before_title . esc_html( $title ) . $after_title; };

		//generate the map
		gmp_generate_map( '200' );

		// Reset Post Data
		wp_reset_postdata();

		echo $after_widget;
	}
}
