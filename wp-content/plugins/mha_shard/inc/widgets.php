<?php

/** 
 * Recent Article Submissions
 */
add_action('wp_dashboard_setup', 'recent_article_submissions_widget');  
function recent_article_submissions_widget() {
    global $wp_meta_boxes;    
    wp_add_dashboard_widget('custom_help_widget', 'Recent Article Drafts', 'recent_article_drafts');
} 
function recent_article_drafts() {

    $args = array(
        "post_type" => 'article',
        "orderby" => 'date',
        "order"	=> 'DESC',
        "post_status" => 'draft',
        "posts_per_page" => 10
    );
    $loop = new WP_Query($args);
    if($loop->have_posts()):
        echo '<div class="rss-widget"><ol>';
        while($loop->have_posts()) : $loop->the_post();
            echo '<li>'.get_the_date().'<br /><strong><a href="'.get_the_permalink().'">'.get_the_title().'</a></strong><br />'.get_the_author().'</li>';
        endwhile;
        echo '</ol></div>';
    endif;

}



/** 
 * Recent Flags
 */
add_action('wp_dashboard_setup', 'recent_flagged_thoughts_widget');  
function recent_flagged_thoughts_widget() {
    global $wp_meta_boxes;    
    wp_add_dashboard_widget('custom_flags_widget', 'Recent Flagged Thoughts', 'recent_flagged_thoughts');
} 
function recent_flagged_thoughts() {

    global $wpdb;
    $flag_query = $wpdb->get_results( 'SELECT * FROM thoughts_flags ORDER BY date DESC LIMIT 10' );

    if($flag_query){ ?>
    <table class="wp-list-table widefat striped">
        <tr>
            <th class="text-left">Flagged By</th>
            <th class="text-left">Date</th>
            <th class="text-left">Comment</th>
            <th class="text-left">Edit</th>
        </tr>
        <?php foreach($flag_query as $flag): ?>
            <tr>
                <td>
                    <?php 
                        $user = get_userdata($flag->uid); 
                        echo '<a href="'.get_edit_user_link($flag->uid).'">';
                        echo $user->user_login;
                        echo '</a>';
                    ?>
                </td>
                <td>
                    <?php echo $flag->date; ?>
                </td>
                <td>
                    <?php
                        $responses = get_field('responses', $flag->pid);
                        $type = get_post_type($flag->pid);
                        //echo $flag->row;
                        $edit_pid = $flag->pid;

                        if($type == 'thought_activity'){

                            // Admin seeded thought
                            $initial_thought = get_field('pre_generated_responses', $flag->pid);
                            echo '<strong>Admin Seeded Thought:</strong><br />'.$initial_thought[$flag->row]['response'];

                        } else {

                            // Other thoughts
                            if(isset($responses[$flag->row]['response'])){
                                echo $responses[$flag->row]['response'];   
                            } else {
                                echo '<em>&mdash; Thought Deleted &mdash;</em>';
                            }

                        }

                    ?>
                </td>
                <td>
                    <?php edit_post_link('Edit', '', '', $edit_pid); ?>
                </td>
            </tr>
        <?php endforeach;?> 
        </table>
        
        <hr />

        <a href="/wp-admin/admin.php?page=mhaflaggedthoughtmod" class="button primary">View All Flagged Thoughts</a>
    <?php
    } else {
        echo '<p>No flagged thoughts available for review.</p>';
    }

}