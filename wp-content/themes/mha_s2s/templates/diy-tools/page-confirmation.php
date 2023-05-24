<?php
    // General Vars
    $pid = isset($args['id']) ? $args['id'] : get_the_ID();
    $activity_id_raw = get_post( get_field('activity_id', $pid) );
    $activity_id = $activity_id_raw->ID;
    $questions = get_field('questions', $activity_id);
    $crowdsource_heading = get_field('crowdsource_heading', $activity_id);
    $allow_crowdsource_viewing = get_field('allow_crowdsource_viewing', $activity_id) ? get_field('allow_crowdsource_viewing', $activity_id) : 0;
    $crowdsource_button_label = get_field('crowdsource_button_label', $activity_id);
    $crowdsource_default_visible = get_field('crowdsource_default_visible', $activity_id);
    $article_classes = get_post_class( 'article-diy-page-confirmation', $pid );
    $embedded = isset($args['embedded']) ? $args['embedded'] : 0;
?>

<?php if($embedded): ?>
    <div class="embedded-confirmation-container bubble light-blue round-bl mb-4">
    <div class="inner">
<?php endif; ?>

<article id="post-<?php echo $pid; ?>" class="<?php echo esc_attr( implode( ' ', $article_classes ) ); ?>">
    <div class="page-heading plain<?php echo $embedded ? ' mb-0' : ''; ?>">	
    <div class="wrap<?php echo $embedded ? '' : ' normal'; ?>">		

        <?php if(!$embedded): ?>
            <h2 class="entry-title"><?php echo get_the_title($activity_id); ?></h2>
        <?php endif; ?>
        
        <div class="page-intro mx-auto">
            <?php 
                //if(!$embedded):
                    if(get_field('completed_tool_message', $activity_id)){
                        the_field('completed_tool_message', $activity_id);
                    } else {
                        $activity = get_post($activity_id);
                        echo apply_filters('the_content', $activity->post_content);
                    }
                //endif;
            ?>	
            <?php 
                if($embedded){
                    echo '<hr class="mb-0" />';
                }
            ?>            
        </div>
    </div>
    </div>
</article>

<?php 
    if($embedded){
        echo '<h3 class="dark-blue mb-3 text-center">';
    } else {
        echo '<p class="bold text-blue text-center">';
    }
?>            

Submitted on <?php echo get_the_date('F j, Y', $activity_id); ?><br />

<?php 
    if($embedded){
        echo '</h3>';
    } else {
        echo '</p>';
    }
?>

<div class="wrap<?php echo $embedded ? '' : ' medium'; ?>">	
    <div class="question bubble light-blue round-bl <?php if(!$embedded) { echo 'mb-4'; } ?>">
    <div class="inner">

        <?php
            if( have_rows('response', $pid) ):
            while( have_rows('response', $pid) ) : the_row();
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

<div class="wrap <?php echo $embedded ? '' : ' medium'; ?> no-margin-mobile">
<div class="container-fluid">
    <div class="row">

        <div class="col-12 col-md-6 order-md-last order-first text-md-right mb-md-0 mb-4 text-center">
            <?php if(!$embedded): ?>
                <a class="button cerulean round-small-tl" href="<?php echo get_the_permalink($activity_id); ?>">
                    <?php echo get_field('try_again_label', $activity_id) ? get_field('try_again_label', $activity_id) : 'Try this activity again'; ?>
                </a>            
            <?php endif; ?>
        </div>

        <div class="col-12 col-md-6 order-md-first order-last text-center text-md-left">
            <?php 
                $crowdsource_expanded = $crowdsource_default_visible ? 'true' : 'false';
                $crowdsource_classes[] = $crowdsource_default_visible ? 'collapse show' : 'collapse';  
                if($embedded) { 
                    $crowdsource_classes[] = 'embedded-diy'; 
                }
                if($allow_crowdsource_viewing): 
                ?>                    
                    <button class="bar toggle-crowdthoughts" data-toggle="collapse" href="#crowdthoughtsAll" role="button" aria-expanded="<?php echo $crowdsource_expanded; ?>" aria-controls="crowdthoughtsAll" tabindex="-1">
                        <?php echo $crowdsource_button_label; ?> &raquo;
                    </button>
                    <input type="hidden" name="current_url" value="<?php echo sanitize_url("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" />
                <?php 
                endif; 
            ?>            
        </div>
        
    </div>
</div>
</div>

<?php if($embedded): ?>
    </div>
    </div>
    <?php get_template_part( 'templates/diy-tools/cta', 'login', array( 'id' => $args['id'], 'embedded' => $args['embedded'] ) ); ?> 
<?php endif; ?>

<div class="wrap <?php echo $embedded ? '' : ' wide'; ?> no-margin-mobile">
    <div id="crowdthoughtsAll" class="pt-5 <?php echo implode(' ', $crowdsource_classes); ?>">
        <div class="pb-2">
            <h3 class="text-center text-blue mb-0 pb-3"><?php echo $crowdsource_heading; ?></h3>
        </div>
        <div id="crowdthoughtsContent" class="carousel" data-carousel="1" data-question="0" data-activity="<?php echo $activity_id; ?>" data-current="<?php echo $pid; ?>" data-page="1"></div>
    </div>
</div>