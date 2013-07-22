<?php
class GMP_Google_Map {

    public function __construct() {
    }

    public function run( $height, $id ) {
		$this->element_id = $id;
		$this->display_map( $height );
        add_action( 'wp_footer', array( $this, 'javascript_include' ) );
    }

    public function display_map( $height='650', $id='map_canvas' ) {

		$map_div = '<div id="'.esc_attr( $this->element_id ).'" style="height:' .absint( $height ) .'px;"></div>';

		echo $map_div;

    }

    public function javascript_include() {
		global $map_included;

		//load plugin options
		$options_arr = get_option( 'gmp_params' );

		//get map type setting
		$display_type = ( $options_arr["post_gmp_map_type"] ) ? $options_arr["post_gmp_map_type"] : 'ROADMAP';

		$javascript = '';
		$js_footer = '';

		$javascript = $this->build_marker_javascript();

		if ( ! $map_included ) {
			$js_footer .= '<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>';
		}

		if ( $this->element_id == 'map_canvas' ) {
			$js_footer .= '<script type="text/javascript">';
			$js_footer .= 'function wds_map_markers_initialize() {';
			$js_footer .= ' var coords = new google.maps.LatLng( \'0\', \'0\' );';
			$js_footer .= '	var mapOptions = {';
			$js_footer .= '	  zoom: 10,';
			$js_footer .= '	  center: coords,';
			$js_footer .= '	  mapTypeId: google.maps.MapTypeId.' .esc_js( $display_type );
			$js_footer .= '	};';
			$js_footer .= '    var map = new google.maps.Map( document.getElementById( "map_canvas" ), mapOptions );';
			$js_footer .= '    var bounds = new google.maps.LatLngBounds();';
			$js_footer .= '    var infowindow = new google.maps.InfoWindow();';
			$js_footer .= $javascript;
			$js_footer .= '}';
			$js_footer .= 'setTimeout( "wds_map_markers_initialize()", 10 );';
			$js_footer .= '</script>';
		}elseif ( $this->element_id == 'map_canvas_shortcode' ) {
			$js_footer .= '<script type="text/javascript">';
			$js_footer .= 'function wds_map_markers_initialize_shortcode() {';
			$js_footer .= '    var coords = new google.maps.LatLng( \'0\', \'0\' );';
			$js_footer .= '	var mapOptions = {';
			$js_footer .= '	  zoom: 10,';
			$js_footer .= '	  center: coords,';
			$js_footer .= '	  mapTypeId: google.maps.MapTypeId.' .esc_js( $display_type );
			$js_footer .= '	};';
			$js_footer .= '    var map = new google.maps.Map( document.getElementById( "map_canvas_shortcode" ), mapOptions );';
			$js_footer .= '    var bounds = new google.maps.LatLngBounds();';
			$js_footer .= '    var infowindow = new google.maps.InfoWindow();';
			$js_footer .= $javascript;
			$js_footer .= '}';
			$js_footer .= 'setTimeout( "wds_map_markers_initialize_shortcode()", 10 );';
			$js_footer .= '</script>';
		}

		$map_included = true;

		echo $js_footer;

    }

    public function build_marker_javascript( $class = '' ) {
        global $post, $wpdb;

		$gmp_arr = get_post_meta( $post->ID, 'gmp_arr', false );

        for ( $row = 0; $row < count( $gmp_arr ); $row++ ) {

			$title = $gmp_arr[$row]["gmp_title"];
			$desc = $gmp_arr[$row]["gmp_description"];
			$lat = $gmp_arr[$row]["gmp_lat"];
			$lng =  $gmp_arr[$row]["gmp_long"];
			$address = $gmp_arr[$row]["gmp_address1"];

            $location_id=$post->ID;
            $featimg = $this->get_listing_thumbnail( NULL, $post->ID );
            $entry_url = get_permalink( $post->ID );
            $post_type = get_post_type( $post );
            $html = $post->post_content;

            if ( $lat && $lng ) {

                $args[$row]=array(
                    'post_id'	=> $post->ID,
                    'post_type' => get_post_type( $post ),
                    'address'	=> $address,
                    'lat'		=> $lat,
                    'lng'		=> $lng,
                    'url'		=> $entry_url,
                    'img'		=> $featimg,
                    'title'		=> htmlentities( $post->post_title, ENT_QUOTES ),
                    'html'		=> $html,
                    'class'		=> $class
                );

            }
        }

        return $this->EL_wds_map_load_markers( $args,'200px','100%','no' );
    }

    public function get_listing_thumbnail( $listing_post_type='', $post_id ) {

		//future feature
		$feat_image = '';

        return $feat_image;

    }

    public function EL_wds_map_load_markers( $args_arr=array(), $map_height="400px", $map_width="100%", $echo="yes" ) {
        global $gmp_display;

		$markers = 0;
		$return = '';

        if ( empty( $args_arr ) ) {
            return $return;
        }

        foreach ( $args_arr as $args ) {

			$markers++;

            extract( $args, EXTR_OVERWRITE );

			$gmp_arr = get_post_meta( $post_id, 'gmp_arr', true );

			$gmp_marker = ( is_array( $gmp_arr ) ) ? $gmp_arr["gmp_marker"] : 'blue-dot.png';

			$return .= 'var icon = new google.maps.MarkerImage( "'.plugins_url( '/markers/' .$gmp_marker, dirname( __FILE__ ) ).'")';

            $address = esc_js( $address );
            $content = $img.$title;
            $return.='
                var myLatLng = new google.maps.LatLng('.esc_js( $lat ).','.esc_js( $lng ).');
                bounds.extend(myLatLng);
                var marker'.$post_id.' = new google.maps.Marker({
                    map: map, icon: icon, position:
                    new google.maps.LatLng('.esc_js( $lat ).','.esc_js( $lng ).')
                });

                google.maps.event.addListener(marker'.absint( $post_id ).', "click", function() {
                    infowindow.open(map, marker'.absint( $post_id ).');
                });
            ';
        }

        if ( $markers == 1 ) {
        	$return .= 'map.setCenter(bounds.getCenter());'; // Set center and zoom out/in from here.
        } else {
        	// If more than one marker we want to fit all markers in a bound area. No Zoom.
        	$return .= 'map.fitBounds(bounds);';
		}

		return $return;

    }

    public static function htmlentitiesCallback( &$string, $key = null ) {
        $string = htmlentities( $string );
    }
}