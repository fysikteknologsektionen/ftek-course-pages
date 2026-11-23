<?php


add_action( 'widgets_init', 'register_ftek_course_widget' );
function register_ftek_course_widget() {
	register_widget( 'FtekCourseWidget' );
}

class FtekCourseWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'ftek_course_widget',
			__( 'Ftek Course Widget', 'ftekcp' ),
			array(
				'description' => __( 'Shows current courses for F and TM at Chalmers University', 'ftekcp' ),
				'classname'   => 'ftek_course_widget',
			)
		);
	}

	public function widget( $args, $instance ) {
		// This widget is only useful in Swedish
		// as it only shows first three years
		if ( ! function_exists( 'qtrans_getLanguage' ) || 'sv' !== qtrans_getLanguage() ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		$title = apply_filters( 'widget_title', __( 'Courses', 'ftekcp' ), $instance, $this->id_base );

		if ( $title ) {
			$title_url = home_url( '/kurser' );

			echo $args['before_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf(
				'<a href="%1$s" title="%2$s">%3$s</a>',
				esc_url( $title_url ),
				esc_attr__( 'More courses', 'ftekcp' ),
				esc_html( $title )
			);
			echo $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$current_study_period = current_study_period();

		for ( $year = 1; $year <= 3; $year++ ) {
			$query = course_query( $year );

			echo '<h3>';
			echo esc_html( __( 'Year', 'ftekcp' ) . ' ' . $year . ' (' . __( 'Schedule', 'ftekcp' ) . ' ' );
			echo wp_kses(
				implode( ', ', timeEdit_links( $year ) ),
				array( 'a' => array( 'href' => array() ) )
			);
			echo ')</h3>';

			if ( $query->have_posts() ) {
				echo '<ul>';

				while ( $query->have_posts() ) {
					$query->the_post();

					if ( ! in_array( $current_study_period, course_study_periods(), true ) ) {
						continue;
					}

					printf(
						'<li><a href="%1$s">%2$s</a></li>',
						esc_url( get_permalink() ),
						esc_html( get_the_title() )
					);
				}

				echo '</ul>';
			}

			wp_reset_postdata();
		}

		$course_page = get_page_by_path( 'kurser' );

		if ( $course_page ) {
			printf(
				'<a class="more-link more-courses-link" href="%1$s">%2$s &#8594;</a>',
				esc_url( get_permalink( $course_page->ID ) ),
				esc_html__( 'More courses', 'ftekcp' )
			);
		}

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function update( $new_instance, $old_instance ) {
		// This widget has no configurable settings.
		return is_array( $old_instance ) ? $old_instance : array();
	}

	public function form( $instance ) {
		// This widget has no configurable settings.
	}
}

function course_query( $programme_year ) {
	$args = array(
		'post_type'      => 'ftek_course_page',
		'posts_per_page' => -1,
		'meta_key'       => FTEK_COURSE_PREFIX . 'attendants',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'   => FTEK_COURSE_PREFIX . 'programme_year',
				'value' => (string) $programme_year,
			),
			array(
				'key'     => FTEK_COURSE_PREFIX . 'attendants',
				'value'   => 10,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			),
		),
	);

	return new WP_Query( $args );
}

function timeEdit_links( $programme_year ) {
	$programmes = array( 'F', 'TM' );

	return array_map(
		function ( $programme ) use ( $programme_year ) {
			$url = timeEdit_url( $programme, $programme_year );
			return sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html( $programme )
			);
		},
		$programmes
	);
}
