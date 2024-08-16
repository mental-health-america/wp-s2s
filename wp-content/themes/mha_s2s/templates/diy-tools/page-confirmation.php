<?php
    // General Vars
    $pid = isset($args['id']) ? $args['id'] : get_the_ID();
    $activity_id_raw = get_post( get_field('activity_id', $pid) );
    $activity_id = $activity_id_raw->ID;
    $questions = get_field('questions', $activity_id);
    $crowdsource_heading = get_field('crowdsource_heading', $activity_id);
    $allow_crowdsource_viewing = get_field('allow_crowdsource_viewing', $activity_id) ? get_field('allow_crowdsource_viewing', $activity_id) : 0;
    $show_next_previews = get_field('show_next_previews', $activity_id) ? get_field('show_next_previews', $activity_id) : 0;
    $crowdsource_button_label = get_field('crowdsource_button_label', $activity_id);
    $crowdsource_default_visible = get_field('crowdsource_default_visible', $activity_id);
    $article_classes = get_post_class( 'article-diy-page-confirmation', $pid );
    $embedded = isset($args['embedded']) ? $args['embedded'] : 0;
    $tool_type = get_field('tool_type', $activity_id);

    $link = get_field('download', $activity_id);
    $link_url = isset($link['url']) ? $link['url'] : '';
    $link_title = isset($link['title']) ? $link['title'] : '';
    $link_target = isset($link['target']) ? $link['target'] : '_self';
    
    // Tool Styles
    switch($tool_type){
        case 'worksheet':
            $title_color = 'red';
            $text_color = 'text-dark-red';
            $label_color = 'text-coral';
            $button_color = 'blue';
            $bubble_color = 'red';
            $answer_style = 'text-wine bubble wine round-tl thin mt-3 mb-5';
            break;
        default:
            $title_color = 'dark-blue';
            $text_color = 'text-blue';
            $label_color = '';
            $button_color = 'blue';
            $bubble_color = 'light-blue';
            $answer_style = 'large';
            break;
    }
?>

<?php if($embedded): ?>
    <div class="embedded-confirmation-container bubble <?php echo $bubble_color; ?> round-bl mb-4">
    <div class="inner">
<?php endif; ?>

<?php //if($tool_type == 'worksheet'): ?>
<div class="diy-questions diy-worksheet">
<?php //endif; ?>

<div class="wrap<?php echo $embedded ? '' : ' medium'; ?>">	
    <div class="question bubble <?php echo $bubble_color; ?> round-bl <?php if(!$embedded) { echo 'mb-4'; } ?>">
    <div class="inner">

        <?php //if($tool_type == 'worksheet'): ?>
            
            <p class="bold">Submitted on <?php echo get_the_date('F j, Y', $pid); ?></p>
            <h1 class="entry-title small <?php echo $label_color; ?>"><?php echo get_the_title($activity_id); ?></h1>

            <article class="page-intro mx-auto">
                <?php 
                    $activity = get_post($activity_id);
                    if(get_field('completed_tool_message', $activity_id)){
                        if($tool_type == 'worksheet'){
                            // echo apply_filters('the_content', $activity->post_content);
                        } else {
                            echo get_field('completed_tool_message', $activity_id);
                        }
                    } else {
                        $activity = get_post($activity_id);
                        echo apply_filters('the_content', $activity->post_content);
                    }

                    if($embedded){
                        //echo '<hr class="mb-4 '.$title_color.'" />';
                    }
                ?>            
            </article>
            
        <?php //endif; ?>

        <?php
            $tooltip_pattern = [
                '/\[mha_tooltip\b[^\]]*\].*?\[\/mha_tooltip\]/s',
                '/<button\b[^>]*>.*?<\/button>/s'
            ];

            if( have_rows('response', $pid) ):
            while( have_rows('response', $pid) ) : the_row();
                $key = get_sub_field('id');
                ?>
                    <div class="label bold <?php echo $label_color; ?>">
                        
                        <?php 
                            $question = $questions[$key]['question']; 
                            echo preg_replace($tooltip_pattern, '', $question);
                        ?>
                    </div>
                    <p class="mb-1 <?php echo $text_color; ?>"><?php echo $questions[$key]['description']; ?></p>
                    <div class="px-4 mb-4 <?php echo $answer_style; ?>">
                        <?php if($tool_type == 'worksheet'){ echo '<div class="inner">'; } ?>
                            <?php 
                                if(get_sub_field('answer')){
                                    the_sub_field('answer'); 
                                } else {
                                    echo '&mdash;';
                                }
                            ?>
                        <?php if($tool_type == 'worksheet'){ echo '</div>'; } ?>
                    </div>
                <?php
            endwhile;
            endif;
        ?>

        <?php
            if( $tool_type != 'worksheet' ):
            if( !get_field('viewed_result', $pid) || get_the_author_meta('ID') == get_current_user_id() ):
        ?>
            <hr />
            <p>
                <a class="button blue round-tl thin wide" target="_blank" href="<?php echo get_the_permalink($activity_id); ?>">
                    <?php echo get_field('try_again_label', $activity_id) ? get_field('try_again_label', $activity_id) : 'Try this activity again'; ?>
                </a>
            </p>
            <p>
                <label for="private_thought_<?php echo $pid; ?>">
                    <input type="checkbox" 
                        value="1" 
                        class="toggle_private_thought"
                        name="private_thought_<?php echo $pid; ?>"
                        id="private_thought_<?php echo $pid; ?>" 
                        data-id="<?php echo $pid; ?>" 
                        <?php if(get_field('crowdsource_hidden', $pid)){ echo 'checked'; }; ?> 
                    />
                    Hide this from public submissions (all submissions are anonymous)
                </label>
            </p>
            <div data-thought="<?php echo $pid; ?>" class="toggle_private_thought_message bubble white thinner d-none round-tl"><div class="inner"></div></div>
        <?php 
            endif; 
            endif; 
        ?>
        
        <?php if( $tool_type == 'worksheet' ): ?> 
            <article class="worksheet-footer mt-5">
                <?php echo get_field('completed_tool_message', $activity_id); ?>

                <div class="small text-center mt-5">
                    
                    <?php if(!$embedded): ?>
                        <p class="mb-4"><input type="button" class="button <?php echo $button_color; ?> round-tl thin wide" value="Print" onClick="window.print()"></p>
                    <?php endif; ?>

                    <?php if($embedded): ?>
                        <p class="mb-4"><a href="<?php echo get_the_permalink($pid); ?>" class="button <?php echo $button_color; ?> round-tl thin wide">View / print completed form</a></p>
                        <p class="mb-4">
                            <a class="button <?php echo $button_color; ?> round-tl thin wide" target="_blank" href="<?php echo get_the_permalink($activity_id); ?>">
                                <?php echo get_field('try_again_label', $activity_id) ? get_field('try_again_label', $activity_id) : 'Try this activity again'; ?>
                            </a>
                        </p>
                        <p class="mb-4">
                            <a class="button <?php echo $button_color; ?> round-tl thin wide" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>">
                                <?php echo _e('Download printable worksheet', 'mhas2s'); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <div class="disclaimer mt-5">
                        <?php echo get_field('disclaimer', $activity_id); ?>
                    </div>

                </div>
            </article>
        <?php endif; ?>

    </div>
    </div>
</div>

<?php //if($tool_type == 'worksheet'): ?>
</div>
<?php //endif; ?>

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
                // Crowdsource
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

                // Download button
                if(!$embedded && $link ): 
                ?>
                    <a class="button red round" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo esc_html( $link_title ); ?></a>
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
    <div id="crowdthoughtsAll" class="crowdthoughtsAll pt-5 <?php echo implode(' ', $crowdsource_classes); ?>">
        <div class="pb-2">
            <h3 class="text-center text-blue mb-0 pb-3"><?php echo $crowdsource_heading; ?></h3>
        </div>
        <div class="crowdthoughtsContent carousel" data-moving="0" data-carousel="1" data-question="0" data-activity="<?php echo $activity_id; ?>" data-current="<?php echo $pid; ?>" data-page="1"></div>
    </div>
</div>

<?php
// Update "Viewed" checkbox
if(!get_field('viewed_result', $pid)):
    update_field('viewed_result', 1, $pid);
endif;
?>