<?php
/**
 * Shown when the site is embedded with ?iframe=true and the partner
 * is missing or not listed in mha_approved_partners().
 */

get_header();

$iframe_embed_error_message = get_field( 'iframe_embed_error_message', 'options' );
if ( $iframe_embed_error_message ) {
	echo '<div class="wrap narrow pt-5 mb-5"><div class="bubble round-br teal"><div class="inner">' . $iframe_embed_error_message . '</div></div></div>';
}

echo '
<style>
body { background: #fff !important; }
#header,
#footer,
#cookie-consent-banner,
#supporters { display: none !important; }
</style>
';

get_footer();
