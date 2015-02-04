<?php

class GO_Author_Bio
{
	private $user_meta_key = 'go-author-bio-last-post';
	private $ttl = 933120000; //3 days
	private $not_current = 15552000;//6 30-day months
	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		add_filter( 'go_metadata_description', array( $this, 'go_metadata_description' ) );
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

		$data['show_email'] = ! empty( $data['email'] ) && ( time() - $this->not_current ) < $this->last_post_date( $user_id );

		return $data;
	}//end author_data

	/**
	 * Get the last post date for an author
	 * @param  (int) $user_id The author's user ID
	 * @return (int) timestamp of last post date
	 */
	private function last_post_date( $user_id )
	{
		$last_post_info = get_the_author_meta( $this->user_meta_key, $user_id );

		//if we have timely data stored, use it
		if ( ! empty( $last_post_info ) && ( time() - $this->ttl ) < $last_post_info['date_checked'] )
		{
			return (int) $last_post_info['post_date'];
		}//end if

		//WP_Query for last post
		$last_post_query = new WP_Query(
			array(
				'author'           => $user_id,
				'posts_per_page'   => 1,
				'post_status'      => 'publish',
				'post_type'        => 'post',
			)
		);

		if ( ! isset( $last_post_query->posts[0]->post_date ) )
		{
			$last_date = 0;
		}//end if
		else
		{
			$last_date = $last_post_query->posts[0]->post_date;
		}//end else

		//cache this in user meta for faster checking next time
		update_user_meta(
			$user_id,
			$this->user_meta_key,
			array(
				'post_date'    => strtotime( $last_date ),
				'date_checked' => time(),
			)
		);

		return (int) $last_date;
	}//end last_post_date

	/**
	 * Checks if the current query is for an author and returns the author if possible
	 */
	public function get_author()
	{
		global $wp_query;

		// bail if we aren't looking at the author taxonomy
		if ( ! $wp_query->is_author )
		{
			return FALSE;
		}//end if

		$author = ! empty( $wp_query->query['author_name'] ) ? $wp_query->query['author_name'] : apply_filters( 'go_author_bio_author', NULL );

		if ( ! $author )
		{
			return FALSE;
		}//end if

		$author = get_user_by( 'slug', $author );

		// bail if we couldn't find an author by the provided slug
		if ( ! $author )
		{
			return FALSE;
		}//end if

		return $author;
	} // END get_author

	/**
	 * Filter go_metadata_description and return a description if possible
	 */
	public function go_metadata_description( $description )
	{
		// Is this a query for an author?
		if ( ! $author = $this->get_author() )
		{
			return $description;
		} // END if

		// Do we have any author data?
		if ( ! $data = go_author_bio()->author_data( $author->ID ) )
		{
			return $description;
		} // END if

		// Do we have a bio?
		if ( '' == $data['bio'] )
		{
			return $description;
		} // END if

		return $data['bio'];
	} // END go_metadata_description
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