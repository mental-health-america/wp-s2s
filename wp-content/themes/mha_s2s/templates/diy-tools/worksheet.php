<?php
    $activity_id = get_the_ID();
    $unique_id_suffix = wp_unique_id();
    $unique_activity_id = $activity_id.'_'.$unique_id_suffix;
    $single_question_mode = get_field('single_question_mode');
    $allow_question_skipping = get_field('allow_question_skipping') ? get_field('allow_question_skipping') : 0;
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
        
    // Presets
    $activity_start_row = get_query_var('diy_continue') ? 1 : 0;
?>
	
<?php if( $questions ): ?>
<div class="wrap <?php echo $wrap_width; ?> no-margin-mobile diy-tool-container" id="diy-tool-<?php echo $unique_activity_id; ?>">	
    <form class="diy-questions-container <?php echo $embedded_class; ?>" action="#" method="POST" data-skippable="<?php echo $allow_question_skipping; ?>" data-aos="fade-left"<?php echo $single_form_atts; ?>>	

        <div class="diy-questions diy-worksheet" data-start="<?php echo $activity_start_row; ?>" role="slider">
            <ol class="bubble red round-tl">
                
                <?php if(!$embedded): ?>
                <li class="page-intro">
                    <div class="inner">
                        <?php the_content(); ?>				
                    </div>
                </li>
                <?php endif; ?>

                <?php
                    reset_rows();
                    $breathe_check = false;
                    while( have_rows('questions') ) : the_row();
                        $row_index = get_row_index();
                        $required_field = $allow_question_skipping ? '' : ' required';

                        // Default tabindexes
                        $tabindex = $row_index + 1;

                        // Breathe
                        $breathe_atts = '';
                        if($breathe_check) { $breathe_atts = ' style="display: none;" aria-hidden="true"'; }

                        ?>
                            <li class="worksheet-item"<?php echo $breathe_atts; ?>>
                                <div class="question mb-4" data-question="q<?php echo $row_index; ?>">
                                <div class="inner">

                                    <?php if(get_sub_field('question')): ?><div class="label text-red mb-3"><?php the_sub_field('question'); ?></div><?php endif; ?>
                                    <?php if(get_sub_field('description')): ?><p class="text-wine mb-3"><?php the_sub_field('description'); ?></p><?php endif; ?>

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

                                                case 'breathe':
                                                    $breathe_check = true;                                            
                                                    $textarea_value = '';
                                                    $timer = get_sub_field('timer') ? get_sub_field('timer') : 10;
                                                    if(get_query_var('diy_continue') && isset($_POST["answer_$row_index"])){ 
                                                        $textarea_value = sanitize_text_field( $_POST["answer_$row_index"] ); 
                                                    }
                                                    echo '<div class="breathing-container text-center" data-breathe="bid_'.$unique_activity_id.'_'.$row_index.'">';
                                                        echo '<div class="breathing-inner">';
                                                            echo '<div class="breathing-circle"><span></span></div>';
                                                        echo '</div>';
                                                        echo '<button role="timer" class="start-breathing button red round-tl extra-wide" tabindex="'.$tabindex.'" data-timer='.$timer.' data-breathe="bid_'.$unique_activity_id.'_'.$row_index.'"><span class="text">Start</span></button>';
                                                    echo '</div>';
                                                    break;
                                                    
                                                case 'text':
                                                default:
                                                    $textarea_value = '';
                                                    if(get_query_var('diy_continue') && isset($_POST["answer_$row_index"])){ 
                                                        $textarea_value = sanitize_text_field( $_POST["answer_$row_index"] ); 
                                                    }
                                                    ?>
                                                        <textarea maxlength="1000" name="answer_<?php echo $row_index; ?>" placeholder="<?php echo get_sub_field('placeholder'); ?>" tabindex="<?php echo $tabindex; ?>" data-question="<?php echo $row_index; ?>"<?php echo $required_field; ?>><?php echo $textarea_value; ?></textarea>
                                                        <span class="character-counter text-right bold d-none" data-answer="answer_<?php echo $row_index; ?>">
                                                            <span class="current">0</span> <span class="maximum">/ 1000</span>
                                                        </span>
                                                    <?php
                                                    break;
                                            }
                                        ?>
                                    </p>
                                    
                                    <div class="container-fluid">
                                    <div class="row">
                                        
                                        <?php
                                            // Submit button
                                            $button_atts = 'class="round-tiny-tl red action-button next-question submit" data-question="q'.$row_index.'"';   
                                            $button_label = get_sub_field('next_button');
                                            if( $button_label || $single_question_mode ):
                                        ?>
                                        <div class="col-12 text-center p-0">
                                            <button <?php echo $button_atts; ?> tabindex="<?php echo $tabindex; ?>">
                                                <?php echo $button_label; ?>
                                            </button>
                                        </div>
                                        <?php endif; ?>

                                    </div>
                                    </div>

                                </div>
                </div>
                                
                            </li>
                        <?php                    
                        endwhile;
                    wp_reset_query();
                ?>
                <li class="disclaimer">
                <div class="inner">
                    <?php echo get_field('disclaimer', $activity_id); ?>
                </div>
                </li>
            </ol>

            <?php                
                // Download button
                $link = get_field('download', $activity_id);
                if( $link ): 
                    $link_url = $link['url'];
                    $link_title = $link['title'];
                    $link_target = $link['target'] ? $link['target'] : '_self';
                ?>
                    <p class="text-center mt-5"><a class="button red round download-worksheet" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo esc_html( $link_title ); ?></a></p>
            <?php endif; ?> 
        </div>

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
    
<?php endif; ?>
</div>