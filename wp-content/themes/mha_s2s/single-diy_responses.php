<?php
/**
 * DIY Tool Container
 */

get_header();

// Hide from public by default if crowdsource is hidden
$can_view = get_field('crowdsource_hidden') ? false : true;

if( get_the_author_meta('ID') == get_current_user_id() ){
    // If user associated with it is viewing they can always view it
    $can_view = true;
} else if( !get_current_user_id() && !get_field('crowdsource_hidden') || get_current_user_id() == 4 && !get_field('crowdsource_hidden') ){
    // Show if submission is anonymous
    $can_view = true;
} 

if( current_user_can('edit_posts') ){
    // Allow admins to view
    $can_view = true;
}

if( !$can_view ):    
    echo '<p class="text-center">You are not authorized to view this page. Please <a href="/log-in">log in</a>.</p>';
else:
    get_template_part( 'templates/diy-tools/cta', 'login', array( 'id' => get_the_ID()) ); 
    get_template_part( 'templates/diy-tools/page', 'confirmation', array( 'id' => get_the_ID()) );	
endif;

get_footer();
