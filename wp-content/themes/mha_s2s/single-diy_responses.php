<?php
/**
 * DIY Tool Container
 */

get_header();

// General Vars
$activity_id = get_field('activity_id');
$questions = get_field('questions', $activity_id);
$crowdsource_heading = get_field('crowdsource_heading', $activity_id);
$allow_crowdsource_viewing = get_field('allow_crowdsource_viewing', $activity_id) ? get_field('allow_crowdsource_viewing', $activity_id) : 0;
$crowdsource_button_label = get_field('crowdsource_button_label', $activity_id);
$crowdsource_default_visible = get_field('crowdsource_default_visible', $activity_id);

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

// Login/Register Prompt
get_template_part( 'templates/diy-tools/cta', 'login', array( 'id' => get_the_ID()) ); 
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="page-heading plain">	
    <div class="wrap normal">				
        <h1 class="entry-title"><?php echo get_the_title($activity_id); ?></h1>
        <div class="page-intro mx-auto">
            <?php 
                $activity = get_post($activity_id);
                echo apply_filters('the_content', $activity->post_content);
            ?>	
            
            <p class="bold text-blue text-center">
                Submitted on <?php echo get_the_date('F j, Y'); ?><br />
                <a class="button cerulean round-small-tl mt-3" href="<?php echo get_the_permalink($activity_id); ?>">View <?php echo get_the_title($activity_id); ?> Activity</a>
            </p>
        </div>
    </div>
    </div>
</article>

<div class="wrap medium">	
    <div class="question bubble light-blue round-bl mb-4">
    <div class="inner">

        <?php
            if( have_rows('response') ):
            while( have_rows('response') ) : the_row();
                $key = get_sub_field('id');
                ?>
                    <div class="label bold"><?php echo $questions[$key]['question']; ?></div>
                    <p class="mb-1"><?php echo $questions[$key]['description']; ?></p>
                    <p class="px-4 mb-4 text-bright-blue large">
                        <?php 
                            if(get_sub_field('answer')){
                                the_sub_field('answer'); 
                            } else {
                                echo '&mdash;';
                            }
                        ?>
                    </p>
                <?php
            endwhile;
            endif;
        ?>

    </div>
    </div>
</div>


<?php 
    if($allow_crowdsource_viewing): 
        $crowdsource_expanded = $crowdsource_default_visible ? 'true' : 'false';
        $crowdsource_classes[] = $crowdsource_default_visible ? 'collapse show' : 'collapse';  
    ?>
        
        <div class="wrap wide no-margin-mobile">
            <div class="text-center">
                <button class="bar toggle-crowdthoughts" data-toggle="collapse" href="#crowdthoughtsAll" role="button" aria-expanded="<?php echo $crowdsource_expanded; ?>" aria-controls="crowdthoughtsAll" tabindex="-1">
                    <?php echo $crowdsource_button_label; ?> &raquo;
                </button>
                <input type="hidden" name="current_url" value="<?php echo sanitize_url("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" />
            </div>

            <div id="crowdthoughtsAll" class="pt-5 <?php echo implode(' ', $crowdsource_classes); ?>">
                <div class="pb-2">
                    <h3 class="text-center text-blue mb-0 pb-3"><?php echo $crowdsource_heading; ?></h3>
                </div>
                <div id="crowdthoughtsContent" class="carousel" data-carousel="1" data-question="" data-activity="<?php echo $activity_id->ID; ?>" data-current="<?php echo get_the_ID(); ?>"></div>
            </div>
        </div>

    <?php 
    endif; 
?>


<?php		
endif;
get_footer();
