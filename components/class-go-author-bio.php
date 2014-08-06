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

		$last_post_info = get_the_author_meta( $this->user_meta_key, $user_id );

		//no meta value, find it and set it
		if ( empty( $last_post_info ) )
		{
			//set the flag for our template and update user meta
			$data['show_email'] = $this->current_post_check( $user_id );
		}//end if
		// our last check was  > 3 days ago, recheck
		elseif ( ( time() - 933120000 ) > $last_post_info['date_checked'] )
		{
			//let's only hit the database if our stored post date is too old
			if ( strtotime( '-6 months' ) < $last_post_info['post_date'] )
			{
				//set the flag for our template
				$data['show_email'] = $last_post_info['is_current'];
			}//end if
			else
			{
				//set the flag for our template and update user meta
				$data['show_email'] = $this->current_post_check( $user_id );
			}//end else
		}//end elseif
		else
		{
			//set the flag for our template
			$data['show_email'] = $last_post_info['is_current'];
		}//end else

		return $data;
	}//end author_data

	/**
	 * Check the author's last post for currency and update the user meta
	 * @param  (int) $user_id The author's user ID
	 * @return (boolean) $current Is the authors' last post current
	 */
	private function current_post_check( $user_id )
	{
		$last_post = $this->get_last_post( $user_id );

		//is post older than 6 moths?
		$current = strtotime( '-6 months' ) < $last_post->post_date;

		//while we're doing this, we need to update the author's metadata
		$this->update_user_meta(
			$user_id,
			array(
				'is_current'   => $current,
				'post_date'    => strtotime( $last_post->post_date ),
			)
		);

		//send back the boolean for our flag
		return $current;
	}//end current_post_check

	/**
	 * Utility function to get the last post by an author by user ID
	 * @param  (int) $user_id The author's user ID
	 * @return (object) $last_post The post object
	 */
	private function get_last_post( $user_id )
	{
		$args = array(
			'author'           => $user_id,
			'date_query'       => array(
				'after' => date( strtotime( '-6 months' ) ),
			),
			'posts_per_page'   => 1,
			'post_status'      => 'publish',
			'post_type'        => 'post',
		);

		$last_post_query = new WP_Query( $args );
		$last_post = $last_post_query->posts[0];

		return $last_post;
	}//end get_last_post

	/**
	 * Utility function to update the author's metadata
	 * @param  (int) $user_id The author's user ID
	 * @param  (array) $meta Array of values to store
	 *                 is_current - is last post less than 6 months old
	 *                 post_date - timestamp of the last post post_date
	 */
	private function update_user_meta( $user_id, $meta )
	{
		//add the date checked
		$meta['date_checked'] = time();

		//update the meta data
		update_user_meta( $user_id, $this->user_meta_key, $meta );
	}//end update_user_meta
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
