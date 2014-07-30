<?php

class GO_Author_Bio
{
	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
	}//end __construct

	/**
	 * initializes widgets
	 */
	public function widgets_init()
	{
		require_once __DIR__ . '/class-go-author-bio-widget.php';
		register_widget( 'GO_Author_Bio_Widget' );
	}//end widgets_init

	/**
	 * fetches and organizes the author information used in the bio
	 */
	public function author_data( $user )
	{
		if ( is_object( $user ) )
		{
			$user_id = $user->ID;
		}//end if
		else
		{
			$user_id = $user;
		}//end else

		$data = array(
			'avatar' => NULL,
			'bio' => get_the_author_meta( 'description', $user_id ),
			'email' => get_the_author_meta( 'user_email', $user_id ),
			'feed' => add_query_arg( array( 'feed' => 'atom' ), get_author_posts_url( $user_id ) ),
			'name' => get_the_author_meta( 'display_name', $user_id ),
			'title' => apply_filters( 'go_user_profile_get_meta', NULL, 'title' ),
			'twitter' => NULL,
		);

		$data['avatar'] = get_avatar( $data['email'], 96, '', $data['name'] );

		if ( function_exists( 'go_local_keyring' ) )
		{
			$args = array(
				'id' => "{$user_id}-twitter-access",
				'user_id' => $user_id,
				'service' => 'twitter',
			);

			$twitter = go_local_keyring()->keyring()->get_token_store()->get_token( $args );

			if ( $twitter )
			{
				$data['twitter'] = $twitter->meta['username'];
			}//end if
		}//end if

		return $data;
	}//end author_data
}//end class

function go_author_bio()
{
	global $go_author_bio;

	if ( ! $go_author_bio )
	{
		$go_author_bio = new GO_Author_Bio;
	}//end if

	return $go_author_bio;
}//end go_author_bio
