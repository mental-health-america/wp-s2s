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
    $flag_query = $wpdb->get_results( 'SELECT * FROM thoughts_flags ORDER BY date DESC LIMIT 30' );
    $flag_count = 0;

    if($flag_query){ ?>
    <table class="wp-list-table widefat striped">
        <tr>
            <th class="text-left">Flagged By</th>
            <th class="text-left">Date</th>
            <th class="text-left">Comment</th>
            <th class="text-left">Edit</th>
        </tr>
        <?php 
            foreach($flag_query as $flag): 
            
                // Skip if there is an admin note
                $admin_note = get_field('admin_notes', $flag->pid);
                if($admin_note || $admin_note != ''){
                    continue;
                }

                $type = get_post_type($flag->pid);

                // Thoughts
                $responses = null;
                if(get_field('responses', $flag->pid)){
                    $responses = get_field('responses', $flag->pid);
                }
                
                // DIY Tools
                if(get_field('response', $flag->pid)){
                    $responses = get_field('response', $flag->pid);
                }

                // Skips
                if(!$responses){
                    //echo '1';
                    continue;
                }
                if($type == 'thought'){
                    if(is_numeric($responses[$flag->row]['admin_pre_seeded_thought']) || $responses[$flag->row]['response'] == ''){
                        //echo '2';
                        continue;
                    }
                    if($type == 'thought_activity' || $responses[$flag->row]['hide'] == 1 || get_field('admin_notes', $flag->pid) || $responses[$flag->row]['response'] == ''){
                        //echo '3';
                        continue;
                    }
                }
                if($type == 'diy_responses'){
                    if(get_field('crowdsource_hidden', $flag->pid) || get_field('admin_notes', $flag->pid) || !get_mha_flagged_diy_response( 'diy_responses', $responses, $flag->row ) ){
                        //echo '4';
                        continue;
                    }
                }
                $flag_count++;
                ?>

                <tr>
                    <td>
                        <?php 
                            $user = get_userdata($flag->uid); 
                            echo '<a href="'.get_edit_user_link($flag->uid).'">';
                            //echo $user->user_login;
                            echo (strlen($user->user_login) > 16) ? substr($user->user_login,0,15).'...' : $user->user_login;
                            echo '</a>';
                        ?>
                    </td>
                    <td>
                        <?php echo date( 'm-d-Y', strtotime($flag->date) ); ?>
                    </td>
                    <td>
                        <?php
                            $edit_pid = $flag->pid;
                            if($type == 'thought'){
                                echo '<strong>Thoughts</strong><br />';
                                if(is_numeric($responses[$flag->row]['user_pre_seeded_thought'])){
                                    $user_response = get_field('responses', $responses[$flag->row]['user_pre_seeded_thought']);
                                    $edit_pid = $responses[$flag->row]['user_pre_seeded_thought'];
                                    echo $user_response[$flag->row]['response'];                                       
                                }
                                else if(isset($responses[$flag->row]['response']) && $responses[$flag->row]['response'] != ''){
                                    echo $responses[$flag->row]['response'];   
                                } else {
                                    echo '<em>&mdash; Thought Deleted &mdash;</em>';
                                }
                            }

                            if($type == 'diy_responses'){
                                echo '<strong>DIY Tool</strong><br />';
                                if( get_mha_flagged_diy_response( 'diy_responses', $responses, $flag->row ) ){
                                    echo get_mha_flagged_diy_response( 'diy_responses', $responses, $flag->row );   
                                } else {
                                    echo '<em>&mdash; Thought Deleted/Not Available &mdash;</em>';
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
    }
    
    if($flag_count == 0){
        echo '<p>No flagged thoughts available for review.</p>';
    }

}