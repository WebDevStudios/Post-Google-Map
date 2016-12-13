<?php

class gmp_map_widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname'   => 'gmp_map_widget',
			'description' => __( 'Widget to show Post Google Map plots', 'gmp-plugin' ),
		);
		parent::__construct( 'gmp_map_widget', __( 'Post Google Map Widget', 'gmp-plugin' ), $widget_ops );
	}

	function form( $instance ) {
		$defaults = array( 'title' => 'Google Map' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title    = $instance['title'];
		?>
		<p><?php esc_html_e( 'Title', 'gmp-plugin' ); ?>:
			<input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {

		echo $args['before_widget'];
		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		gmp_generate_map( '200' );

		wp_reset_postdata();

		echo $args['after_widget'];
	}
}
