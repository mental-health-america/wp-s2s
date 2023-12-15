<?php
    $activity_id = get_the_ID();
    $unique_id_suffix = wp_unique_id();
    $unique_activity_id = $activity_id.'_'.$unique_id_suffix;
    $allow_question_skipping = get_field('allow_question_skipping') ? get_field('allow_question_skipping') : 0;
    $show_next_previews = get_field('show_next_previews') ? get_field('show_next_previews') : 0;
    $allow_crowdsource_viewing = get_field('allow_crowdsource_viewing') ? get_field('allow_crowdsource_viewing') : 0;
    $crowdsource_heading = get_field('crowdsource_heading');
    $crowdsource_button_label = get_field('crowdsource_button_label');
    $crowdsource_default_visible = get_field('crowdsource_default_visible');
    $show_breadcrumbs = get_field('show_breadcrumbs');
    $questions = get_field('questions');

    // Embed Related Args
    $embedded = isset($args['embed']) ? $args['embed'] : 0;
    $embedded_class = isset($args['embed']) ? 'embedded-diy' : '';
    $embed_type = isset($args['embed_type']) ? $args['embed_type'] : '';
    $single_form_atts = $embed_type == 'single' ? ' data-embed-single="true" data-action="'.get_permalink($activity_id).'"' : '';
    if(get_query_var('diy_continue')){
        $single_form_atts = ' data-embed-continue="true"';
    }
    if($embed_type == 'single'){
        $show_next_previews = 0;
    }
    if( isset($args['start_page']) ){
        $start_page = intval($args['start_page']);
    } else if( isset($_POST['start_page']) ){
        $start_page = intval($_POST['start_page']);
    } else {
        $start_page = '';
    }

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
    $activity_start_row = get_query_var('diy_continue') ? 1 : 0;

    // Decided not to allow thought continuation
    /*
    if($started_activity_id) { 
        $prev_responses = get_field('questions', $started_activity_id);
    }
    */
?>
	
<?php if( $questions ): ?>
<div class="wrap <?php echo $wrap_width; ?> no-margin-mobile diy-tool-container" id="diy-tool-<?php echo $unique_activity_id; ?>">	
    <form class="diy-questions-container <?php echo $embedded_class; ?>" action="#" method="POST" data-skippable="<?php echo $allow_question_skipping; ?>" data-aos="fade-left"<?php echo $single_form_atts; ?>>	

        <?php if($show_next_previews): ?>
            <?php if($allow_question_skipping): ?>
                <button class="peek diy-carousel-nav fade-left" data-glide-dir="<">Previous</button>
            <?php else : ?>
                <div class="peek fade-left"></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="diy-questions glide" data-start="<?php echo $activity_start_row; ?>" data-peek="<?php echo $show_next_previews; ?>" data-skip="<?php echo $allow_question_skipping; ?>" role="slider">	
        <div class="glide__track" data-glide-el="track">
            <ol class="glide__slides">
                <?php
                    reset_rows();
                    while( have_rows('questions') ) : the_row();
                    $row_index = get_row_index();
                    //$row_index = get_sub_field('id');
                    $required_field = $allow_question_skipping ? '' : ' required';

                    // Default tabindexes
                    $tabindex = '-1';
                    if($row_index == 0){
                        $tabindex = '0';
                    }
                    ?>
                        <li class="glide__slide">
                            <div class="question bubble light-blue round-bl mb-4" data-question="q<?php echo $row_index; ?>">
                            <div class="inner">

                                <?php if(get_sub_field('question')): ?><div class="label bold mb-1"><?php the_sub_field('question'); ?></div><?php endif; ?>
                                <?php if(get_sub_field('description')): ?><p><?php the_sub_field('description'); ?></p><?php endif; ?>

                                <p>
                                    <?php
                                        $question_type = get_sub_field('question_type');
                                        switch($question_type){
                                            case 'checkbox':
                                            case 'radio':
                                                $question_options = get_sub_field('options');
                                                $brackets = $question_type == 'checkbox' ? '[]' : '';
                                                foreach($question_options as $q):
                                                    $field_id = 'answer_'.$row_index.'_'.sanitize_title($q['option_text']);
                                                ?>
                                                    <label for="<?php echo $field_id; ?>">
                                                        <input name="answer_<?php echo $row_index; echo $brackets; ?>" id="<?php echo $field_id; ?>" type="<?php echo $question_type; ?>" value="<?php echo $q['option_text']; ?>" tabindex="<?php echo $tabindex; ?>" data-question="<?php echo $row_index; ?>"<?php echo $required_field; ?> />
                                                        <span class="label-text pt-1 pl-2"><?php echo $q['option_text']; ?></span>
                                                    </label>
                                                <?php
                                                endforeach;
                                                break;

                                            case 'text':
                                            default:
                                                $textarea_value = '';
                                                if(get_query_var('diy_continue') && isset($_POST["answer_$row_index"])){ 
                                                    $textarea_value = sanitize_text_field( $_POST["answer_$row_index"] ); 
                                                }
                                                ?>
                                                    <textarea name="answer_<?php echo $row_index; ?>" placeholder="<?php echo get_sub_field('placeholder'); ?>" tabindex="<?php echo $tabindex; ?>" data-question="<?php echo $row_index; ?>"<?php echo $required_field; ?>><?php echo $textarea_value; ?></textarea>
                                                <?php
                                                break;
                                        }
                                    ?>
                                </p>
                                
                                <div class="container-fluid">
                                <div class="row">

                                    <div class="col-12 col-md-7 d-md-block d-none text-left mb-3 pl-0">
                                        <?php 
                                            if($allow_crowdsource_viewing): 
                                                $crowdsource_expanded = $crowdsource_default_visible ? 'true' : 'false';
                                                ?>
                                                    <button class="bar toggle-crowdthoughts" data-toggle="collapse" href="#crowdthoughts<?php echo $unique_activity_id; ?>" role="button" aria-expanded="<?php echo $crowdsource_expanded; ?>" aria-c1ontrols="crowdthoughts<?php echo $unique_activity_id; ?>" tabindex="<?php echo $tabindex; ?>">
                                                        <?php echo $crowdsource_button_label; ?>&nbsp;&raquo;
                                                    </button>
                                                <?php 
                                            endif; 
                                        ?>
                                    </div>

                                    <?php                                       
                                        $container_atts = '';   
                                        $button_atts = '';
                                        $crowdsource_ask = '';
                                        if(count($questions) == (get_row_index() + 1) ){      
                                            // Submit button                                    
                                            $container_atts = '';     
                                            $button_atts .= 'class="round-tiny-tl red action-button next-question submit" data-question="q'.$row_index.'"';   
                                            $crowdsource_ask = '
                                                <label for="crowdsource_hidden_'.$activity_id.'" class="font-weight-normal d-inline-block" data-toggle="tooltip" data-placement="bottom" title="When checked this submission will be hidden from other users and only visible only to you.">
                                                <input name="crowdsource_hidden" class="crowdsource_hidden" id="crowdsource_hidden_'.$activity_id.'" type="checkbox" value="1" tabindex="'.$tabindex.'" /> '.get_field('user_opt_out_language').'</label>';
                                        } else {
                                            // Next
                                            // $container_atts = 'data-glide-el="controls"';        
                                            $button_atts .= 'class="bar diy-carousel-nav next-question-button" data-glide-dir=">"';   
                                        }

                                        if(!$allow_question_skipping){
                                            $button_atts .= ' disabled';
                                        }
                                    ?>
                                    <div class="col-12 col-md-5 text-right text-md-end p-0"<?php echo $container_atts; ?>>
                                        <button <?php echo $button_atts; ?> tabindex="<?php echo $tabindex; ?>">
                                            <?php
                                                echo ($embed_type == 'single') ? 'Continue' : get_sub_field('next_button');
                                            ?>&nbsp;&raquo;
                                        </button>
                                    </div>

                                    <?php if( $crowdsource_ask && get_field('allow_user_opt_out_of_crowdsource') ): ?>
                                        <div class="col-12 text-right text-md-end p-0">
                                            <div class="diy-opt-out pt-3"><?php echo $crowdsource_ask; ?></div>
                                            <!--<div class="diy-opt-out-message invisible text-left bubble white thinner round-small-tl d-inline-block"><div class="inner small"><br /><br /></div></div>-->
                                        </div>
                                    <?php endif; ?>

                                </div>
                                </div>

                            </div>
                            </div>

                            <?php if($allow_crowdsource_viewing): ?>
                            <div class="col-12 d-md-none d-block text-left pb-4 pl-0">
                                <button class="bar toggle-crowdthoughts" data-toggle="collapse" href="#crowdthoughts<?php echo $unique_activity_id; ?>" role="button" aria-expanded="<?php echo $crowdsource_expanded; ?>" aria-c1ontrols="crowdthoughts<?php echo $unique_activity_id; ?>" tabindex="<?php echo $tabindex; ?>">
                                    <?php echo $crowdsource_button_label; ?>&nbsp;&raquo;
                                </button>
                            </div>
                            <?php endif; ?>
                            
                        </li>
                    <?php

                    // When embed type is "single", only show the first question.
                    if($embed_type == 'single'){
                        break;
                    }
                    
                    endwhile;
                    wp_reset_query();
                ?>
            </ol>
        </div>
        </div>

        <?php if($show_next_previews): ?>
            <?php if($allow_question_skipping): ?>
                <button class="peek diy-carousel-nav fade-right" data-glide-dir=">">Next</button>
            <?php else : ?>
                <div class="peek fade-right"></div>
            <?php endif; ?>
        <?php endif; ?>

        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('diySubmission'); ?>" />
        <input type="hidden" name="activity_id" value="<?php echo $activity_id; ?>" />
        <input type="hidden" name="pid" value="<?php echo $activity_id; ?>" />
        <input type="hidden" name="ref_code" value="<?php echo get_query_var('ref'); ?>" />
        <input type="hidden" name="current_url" value="<?php echo sanitize_url("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" />
        <input type="hidden" name="diytool_current_id" value="" />
        <input type="hidden" name="opened_diy" value="" />
        <input type="hidden" name="opened_diy_question" value="" />
        <input type="hidden" name="start_page" value="<?php echo $start_page; ?>" />
    </form>
    
    <?php 
        // Placement Options
        $wrap_width_crowd = $embedded ? 'full' : 'wide no-margin-mobile';
    ?>
    <div class="diy-footer wrap position-relative <?php echo $wrap_width_crowd; ?>">
        <?php 
            if($allow_crowdsource_viewing): 
                $crowdsource_classes = [];
                $crowdsource_classes[] = $crowdsource_default_visible ? 'collapse show' : 'collapse';   
                $crowdsource_atts = $crowdsource_default_visible ? ' data-aos="fade-up"' : '';                
            ?>
            <div id="crowdthoughts<?php echo $unique_activity_id; ?>" class="crowdthoughts <?php echo implode(' ', $crowdsource_classes); ?>">
                <div class="pb-2 pt-4"<?php echo $crowdsource_atts; ?>>
                    <h3 class="text-center text-blue mb-0 pb-3"><?php echo $crowdsource_heading; ?></h3>
                </div>
                <div class="crowdthoughtsContent carousel" data-moving="0" data-carousel="1" data-question="0" data-activity="<?php echo $activity_id; ?>" data-current="" data-page="1" data-loaded="0"></div>
            </div>
            <?php 
            endif; 
        ?>

        <?php 
            // Placement Options
            $wrap_width_bread = $embedded ? 'full' : 'medium';
            if($embed_type != 'single'):
        ?>
        <div class="question-breadcrumb-container">
        <div class="wrap <?php echo $wrap_width_bread; ?>">
        <div class="wrap-inner">
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
        </div>
        </div>
        <?php endif; ?>
    </div>

<?php endif; ?>
</div>