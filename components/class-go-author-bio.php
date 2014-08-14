<?php

class GO_Author_Bio
{
	private $user_meta_key = 'go-author-bio-last-post';
	private $ttl = 933120000; //3 days
	private $not_current = 15768000000;//6 months
	private $dependencies = array(
		'go-contact' => 'https://github.com/GigaOM/go-contact',
	);
	private $missing_dependencies = array();
	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		add_action( 'admin_menu', array( $this, 'admin_menu_init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}//end __construct

	public function admin_menu_init()
	{
		$this->check_dependencies();

		if ( $this->missing_dependencies )
		{
			return;
		}//end if
	}//end admin_menu_init

	public function admin_init()
	{
		$this->check_dependencies();

		if ( $this->missing_dependencies )
		{
			return;
		}//end if
	}//end admin_init

	/**
	 * check plugin dependencies
	 */
	public function check_dependencies()
	{
		foreach ( $this->dependencies as $dependency => $url )
		{
			if ( function_exists( str_replace( '-', '_', $dependency ) ) )
			{
				continue;
			}//end if

			$this->missing_dependencies[ $dependency ] = $url;
		}//end foreach

		if ( $this->missing_dependencies )
		{
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}//end if
	}//end check_dependencies

	/**
	 * hooked to the admin_notices action to inject a message if depenencies are not activated
	 */
	public function admin_notices()
	{
		?>
		<div class="error">
			<p>
				You must <a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>">activate</a> the following plugins before using <code>go-author-bio</code> plugin:
			</p>
			<ul>
				<?php
				foreach ( $this->missing_dependencies as $dependency => $url )
				{
					?>
					<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $dependency ); ?></a></li>
					<?php
				}//end foreach
				?>
			</ul>
		</div>
		<?php
	}//end admin_notices

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
