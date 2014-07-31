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
		$twitter = esc_html( $data['twitter'] );
		if ( ! empty( $twitter ) )
		{
			$shortcodes .= '[social-icon service="twitter" dest="@' . $twitter . '" round="true"]';
		}//end if

		//RSS icon
		$shortcodes .= '<a class="goicon icon-rss-circled" itemprop="url" href="' . esc_html( $data['feed'] ) . '"></a>';

		//email icon/contact form
		$shortcodes .= '[go_contact email="' . esc_html( $data['email'] ) . '" form="about" submit="Continue"]';

		if ( ! empty( $shortcodes ) )
		{
			echo do_shortcode( $shortcodes );
		}//end if
		?>
	</div>
	<?php
	$bio = wp_kses_post( $data['bio'] );
	if ( ! empty( $bio ) )
	{
		echo '<p>' . $bio . '</p>';
	}//end if
	?>
</section>