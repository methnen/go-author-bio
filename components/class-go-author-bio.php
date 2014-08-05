<?php

class GO_Author_Bio
{
	private $user_meta_key = 'go-author-bio-last-post';
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
			'feed' => NULL,
			'name' => get_the_author_meta( 'display_name', $user_id ),
			'title' => NULL,
			'twitter' => NULL,
			'url' => get_author_posts_url( $user_id ),
		);

		$profile_data = apply_filters( 'go_user_profile_get_meta', array(), $user_id );

		$data['title'] = ! empty( $profile_data['title'] ) ? $profile_data['title'] : NULL;

		$data['avatar'] = get_avatar( $data['email'], 96, '', $data['name'] );

		$data['feed'] = "{$data['url']}feed/";

		if ( function_exists( 'go_local_keyring' ) )
		{
			$args = array(
				'id' => "{$user_id}-twitter-access",
				'user_id' => $user_id,
				'service' => 'twitter',
			);

			$twitter = go_local_keyring()->keyring()->get_token_store()->get_token( $args );

			if ( ! empty( $twitter ) )
			{
				$data['twitter'] = $twitter->meta['username'];
			}//end if
		}//end if

		$last_post = get_the_author_meta( $this->user_meta_key, $user_id );

		//no meta value, find it and set it
		if ( empty( $last_post ) )
		{
			//set the flag for our template
			$data['show_email'] = $this->last_post_date( $user_id );
		}//end if
		elseif ( strtotime( '-3 days' ) > strtotime( $last_post['date_checked'] ) )
		{
			//if we've got a post recent enough, let's not hit the database
			if ( strtotime( '-6 months' ) < strtotime( $last_post['post_date'] ) )
			{
				$data['show_email'] = 1;
			}//end if
			else
			{
				$data['show_email'] = $this->last_post_date( $user_id );
			}//end else
		}//end elseif
		else
		{
			//set the flag for our template
			$data['show_email'] = 1;
		}//end else

		return $data;
	}//end author_data

	public function last_post_date( $user_id )
	{
		//get last post
		$args = array(
			'author'           => $user_id,
			'date_query'       => array(
				'after' => date( strtotime( '-6 months' ) ),
			),
			'posts_per_page'   => 1,
			'post_status'      => 'publish',
			'post_type'        => 'post',
		);

		$last_post_query = null;
		$last_post_query = new WP_Query( $args );

		$last_post = $last_post_query->posts[0];

		//count results, use as our flag value ( should be 0 or 1 )
		$current = count( $last_post_query );
		//just in case we don't get back a post
		$post_date = ( ! empty( $last_post_query->posts[0]->post_date ) ) ? $last_post_query->posts[0]->post_date : '';

		$meta_value = array(
			'date_checked' => date( 'Y-m-d H:i:s' ),
			'is_current'   => $current,
			'post_date'    => $post_date,
		);

		//update the meta data
		update_user_meta( $user_id, $this->user_meta_key, $meta_value );

		//send back the boolean for our flag
		return $current;
	}//end last_post_date
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
