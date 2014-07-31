<?php

class GO_Author_Bio_Widget extends WP_Widget
{
	/**
	 * constructor!
	 */
	public function __construct()
	{
		$widget_ops = array(
			'classname'   => 'widget-go-author-bio',
			'description' => 'Author bio and contact info',
		);

		parent::__construct( 'go-author-bio-widget', 'GO Author Bio', $widget_ops );
	}//end __construct

	/**
	 * Output the widget
	 */
	public function widget( $args, $unused_instance )
	{
		global $wp_query;

		// bail if we aren't looking at the author taxonomy
		if ( empty( $wp_query->query['author_name'] ) )
		{
			return;
		}//end if

		$author = get_user_by( 'slug', $wp_query->query['author_name'] );

		// bail if we couldn't find an author by the provided slug
		if ( ! $author )
		{
			return;
		}//end if

		$data = go_author_bio()->author_data( $author->ID );

		//set some kind of 'sanity' threshold minimum data
		if ( empty( $data['email'] ) || empty( $data['name'] ) )
		{
			return;
		}//end if

		echo $args['before_widget'];
		include __DIR__ . '/templates/bio.php';
		echo $args['after_widget'];
	} // END widget
}//end class
