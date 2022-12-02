<?php
    $activity_id = get_the_ID();
    $allow_question_skipping = get_field('allow_question_skipping') ? get_field('allow_question_skipping') : 0;
    $show_next_previews = get_field('show_next_previews') ? get_field('show_next_previews') : 0;
    $allow_crowdsource_viewing = get_field('allow_crowdsource_viewing') ? get_field('allow_crowdsource_viewing') : 0;
    $crowdsource_heading = get_field('crowdsource_heading');
    $crowdsource_button_label = get_field('crowdsource_button_label');
    $crowdsource_default_visible = get_field('crowdsource_default_visible');
    $show_breadcrumbs = get_field('show_breadcrumbs');
    $questions = get_field('questions');

    // Template Part Args
    $embedded = isset($args['embed']) ? $args['embed'] : 0;

    // Placement Options
    $wrap_width = $embedded ? 'full' : 'wide';
    
    // Original Post
    $ipiden = get_ipiden();	
    $uid = get_current_user_id() ? get_current_user_id() : 4; // Default "anonymous" user is 4	
    $current_post_args = array(
        "post_type" 	 => 'diy_responses',
        "author"		 => $uid,
        "orderby" 		 => 'date', // Get the most recent
        "order"			 => 'DESC', // Get the most recent
        "post_status" 	 => 'draft', // Incomplete thoughts only
        "posts_per_page" => 1,
        "ipiden"         => $ipiden,
    );
    $current_post_loop = new WP_Query($current_post_args);
    $started_activity_id = $current_post_loop->found_posts ? $current_post_loop->post->ID : null;  
    
    // Presets
    $activity_start_row = 0;

    // Decided not to allow thought continuation
    /*
    if($started_activity_id) { 
        $prev_responses = get_field('questions', $started_activity_id);
    }
    */
?>
	
<?php if( $questions ): ?>
<div class="wrap <?php echo $wrap_width; ?> no-margin-mobile">	
    <form id="diy-questions-container" action="#" method="POST" data-skippable="<?php echo $allow_question_skipping; ?>" data-aos="fade-left">	

        <?php if($show_next_previews): ?>
            <?php if($allow_question_skipping): ?>
                <button class="peek diy-carousel-nav fade-left" data-glide-dir="<"></button>
                <button class="peek diy-carousel-nav fade-right" data-glide-dir=">"></button>
            <?php else : ?>
                <div class="peek fade-left"></div>
                <div class="peek fade-right"></div>
            <?php endif; ?>
        <?php endif; ?>

        <div id="diy-questions" class="glide" data-start="<?php echo $activity_start_row; ?>" data-peek="<?php echo $show_next_previews; ?>" data-skip="<?php echo $allow_question_skipping; ?>">	
        <div class="glide__track" data-glide-el="track">
            <ol class="glide__slides">
                <?php
                    while( have_rows('questions') ) : the_row();
                    $row_index = get_row_index();
                    //$row_index = get_sub_field('id');
                    $required_field = $allow_question_skipping ? '' : ' required';
                    ?>
                        <li class="glide__slide">
                            <div class="question bubble light-blue round-bl mb-4" data-question="q<?php echo $row_index; ?>">
                            <div class="inner">

                                <?php if(get_sub_field('question')): ?><div class="label bold"><?php the_sub_field('question'); ?></div><?php endif; ?>
                                <?php if(get_sub_field('description')): ?><p><?php the_sub_field('description'); ?></p><?php endif; ?>
                                <p>
                                    <textarea name="answer_<?php echo $row_index; ?>" placeholder="<?php echo get_sub_field('placeholder'); ?>" tabindex="-1" data-question="<?php echo $row_index; ?>"<?php echo $required_field; ?>></textarea>
                                </p>
                                
                                <div class="container-fluid">
                                <div class="row">

                                    <div class="col-12 col-md-7 d-md-block d-none text-left mb-3 pl-0">
                                        <?php 
                                            if($allow_crowdsource_viewing): 
                                                $crowdsource_expanded = $crowdsource_default_visible ? 'true' : 'false';
                                                ?>
                                                    <button class="bar toggle-crowdthoughts" data-toggle="collapse" href="#crowdthoughts" role="button" aria-expanded="<?php echo $crowdsource_expanded; ?>" aria-controls="crowdthoughts" tabindex="-1">
                                                        <?php echo $crowdsource_button_label; ?>&nbsp;&raquo;
                                                    </button>
                                                <?php 
                                            endif; 
                                        ?>
                                    </div>

                                    <?php                                       
                                        $container_atts = '';   
                                        $button_atts = '';
                                        if(count($questions) == (get_row_index() + 1) ){                                          
                                            $container_atts = '';     
                                            $button_atts .= 'class="round-tiny-tl red action-button next-question submit" data-question="q'.$row_index.'"';                                         
                                        } else {                                        
                                            $container_atts = 'data-glide-el="controls"';        
                                            $button_atts .= 'class="bar action-button next-question" data-glide-dir="='.(get_row_index() + 1).'" data-question="'.$row_index.'"';   
                                        }

                                        if(!$allow_question_skipping){
                                            $button_atts .= ' disabled';
                                        }
                                    ?>
                                    <div class="col-12 col-md-5 text-right text-md-end p-0"<?php echo $container_atts; ?>>
                                        <button <?php echo $button_atts; ?> tabindex="-1">
                                            <?php echo get_sub_field('next_button'); ?>&nbsp;&raquo;
                                        </button>
                                    </div>

                                </div>
                                </div>

                            </div>
                            </div>

                            <?php if($allow_crowdsource_viewing): ?>
                            <div class="col-12 d-md-none d-block text-left pb-4 pl-0">
                                <button class="bar toggle-crowdthoughts" data-toggle="collapse" href="#crowdthoughts" role="button" aria-expanded="false" aria-controls="crowdthoughts" tabindex="-1">
                                    <?php echo $crowdsource_button_label; ?>&nbsp;&raquo;
                                </button>
                            </div>
                            <?php endif; ?>
                            
                        </li>
                    <?php
                    endwhile;
                    wp_reset_query();
                ?>
            </ol>
        </div>
</div>

        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('diySubmission'); ?>" />
        <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>" />
        <input type="hidden" name="ref_code" value="<?php echo get_query_var('ref'); ?>" />
        <input type="hidden" name="pid" value="<?php echo $activity_id; ?>" />
    </form>
    
    <?php 
        // Placement Options
        $wrap_width_crowd = $embedded ? 'full' : 'wide no-margin-mobile';
    ?>
    <div class="wrap <?php echo $wrap_width_crowd; ?>">
        <?php 
            if($allow_crowdsource_viewing): 
                $crowdsource_classes = [];
                $crowdsource_classes[] = $crowdsource_default_visible ? 'collapse show' : 'collapse';   
                $crowdsource_atts = $crowdsource_default_visible ? ' data-aos="fade-up"' : '';                
            ?>
            <div id="crowdthoughts" class="<?php echo implode(' ', $crowdsource_classes); ?>">
                <div class="pb-2 pt-4"<?php echo $crowdsource_atts; ?>>
                    <h3 class="text-center text-blue mb-0 pb-3"><?php echo $crowdsource_heading; ?></h3>
                </div>
                <div id="crowdthoughtsContent" class="carousel" data-carousel="1" data-question="0" data-activity="<?php echo $activity_id; ?>" data-current=""></div>
            </div>
            <?php 
            endif; 
        ?>
    </div>

    <?php 
        // Placement Options
        $wrap_width_bread = $embedded ? 'full' : 'medium';
    ?>
    <div class="wrap <?php echo $wrap_width_bread; ?>">
        <?php if($show_breadcrumbs): ?>
        <ol class="question-breadcrumb" data-aos="fade-up">

            <li class="arrow"><button class="question-prev diy-carousel-nav" data-glide-dir="<">Previous</button></li>

            <?php
                foreach($questions as $k => $v){
                    $disabled = $allow_question_skipping ? '' : $allow_question_skipping;
                    echo '<li><button data-glide-dir="='.$k.'" class="question-direct diy-carousel-nav" data-question="q'.$k.'"'.$disabled.'>';
                    echo '<span class="text">'.$v['question_label'].'</span>';
                    echo '</button></li>';
                }
            ?>

            <li class="arrow"><button class="question-next diy-carousel-nav" data-glide-dir=">">Next</button></li>

        </ol>
        <?php endif; ?>
    </div>


<?php endif; ?>
</div>