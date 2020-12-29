<?php
/**
 * Simple Page Template
 */

get_header();

if(get_the_author_meta('ID') != get_current_user_id()):
    echo '<p class="text-center">You are not authorized to view this page. Please log in.</p>';
else:
    $activity_id = get_field('activity');
    ?>

    <div class="wrap normal">

        <article id="post-<?php the_ID(); ?>" <?php post_class('thought_activity'); ?>>
            <div class="page-heading plain">			
                <h1 class="text-blue-dark"><?php echo get_the_title( $activity_id ); ?></h1>
                <p class="bold text-blue">Submitted on <?php echo get_the_date('F j, Y'); ?></p>
            </div>
        </article>

        <div class="bubble round-bl light-blue thin">
        <div class="inner">
            <div class="wrap narrow">
                <h3 class="pb-4">Your Responses</h3>
                
                <?php
                    $counter = 0;
                    $questions = get_field('paths', $activity_id);

                    if( have_rows('responses') ):
                    while( have_rows('responses') ) : the_row();
                    $path = get_sub_field('path');
                    $question = get_sub_field('question');
                ?>

                <p>
                    <strong>
                    <?php 
                        if($counter == 0){
                            the_field('question', $activity_id);
                        } else {
                            echo $questions[$path]['questions'][$question]['question'];
                        }
                    ?>
                    </strong><br />
                    <?php 
                        if($counter == 0){
                            $responses = get_field('responses');
                                                    
                            if(get_sub_field('response')){
                                // User's thought
                                the_sub_field('response'); 
                            } else if(get_sub_field('admin_pre_seeded_thought') != '') {
                                // Admin pre-seeded thought
                                $initial_thought = get_field('pre_generated_responses', $activity_id);
                                echo $initial_thought[get_sub_field('admin_pre_seeded_thought')]['response'];
                            } else if(get_sub_field('user_pre_seeded_thought') != '') {
                                // Other user pre-seeded thought
                                $initial_thought = get_field('responses', get_sub_field('user_pre_seeded_thought'));
                                echo $initial_thought[0]['response'];
                            } else {
                                continue;
                            } 
                        } else {
                            the_sub_field('response'); 
                        }
                    ?>
                </p>

                <?php   
                $counter++;         
                endwhile;
                endif;
                ?>
            </div>
        </div>
        </div>

    </div>

<?php
endif; 

get_footer();
