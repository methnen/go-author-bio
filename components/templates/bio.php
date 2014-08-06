<section class="boxed">
	<a href="<?php echo esc_url( $data['url'] ); ?>">
		<?php echo $data['avatar']; ?>
	</a>
	<div class="team-top">
		<h2><?php echo esc_html( $data['name'] ); ?></h2>
		<?php

		if ( ! empty( $data['title'] ) )
		{
			?>
			<h3><?php echo esc_html( $data['title'] ); ?></h3>
			<?php
		}//end if
		?>
	</div>
	<div class="social">
		<?php
		//Let's build some shortcodes! Conditionally, of course.
		$shortcodes = '';

		//twitter icon
		if ( ! empty( $data['twitter'] ) )
		{
			$shortcodes .= '[social-icon service="twitter" dest="@' . esc_html( $data['twitter'] ) . '" round="true"]';
		}//end if

		//RSS icon
		$shortcodes .= '<a class="goicon icon-rss-circled" itemprop="url" href="' . esc_html( $data['feed'] ) . '"></a>';

		if ( ! empty( $data['show_email'] ) )
		{
			//email icon/contact form
			$shortcodes .= '[go_contact email="' . esc_html( $data['email'] ) . '" form="about" submit="Continue"]';
		}//end if

		if ( ! empty( $shortcodes ) )
		{
			echo do_shortcode( $shortcodes );
		}//end if
		?>
	</div>
	<?php
	if ( ! empty( $data['bio'] ) )
	{
		echo '<p>' . wp_kses_post( $data['bio'] ) . '</p>';
	}//end if
	?>
</section>