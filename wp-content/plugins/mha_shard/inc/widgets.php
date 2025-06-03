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

    // Hide for Partners
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_roles = $current_user->roles;
    $is_partner = in_array('partner', $user_roles);

    if (!$is_partner) {

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

    // Hide for Partners
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_roles = $current_user->roles;
    $is_partner = in_array('partner', $user_roles);

    if (!$is_partner) {

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
                                $user_display = $user ? $user->user_login : 'Deleted UID #'.$flag->uid;
                                echo (strlen($user_display) > 16) ? substr($user_display,0,15).'...' : $user_display;
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

}

/**
 * Custom Widget for Displaying Pending Posts
 */
function custom_dashboard_pending_posts_widget() {
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Check if the user has the 'partner' role
    $user_roles = $current_user->roles;
    $is_partner = in_array('partner', $user_roles);

    // Query pending posts of type 'partner' and 'cta'
    $args = [
        'post_type'      => ['partners', 'cta'],
        'post_status'    => 'pending',
        'posts_per_page' => 10, // Adjust as needed
        'orderby'        => 'date',
        'order'          => 'DESC'
    ];

    // If the user is a "partner", only show their own posts
    if ($is_partner) {
        $args['author'] = $user_id;
    }

    $pending_posts = new WP_Query($args);
    ?>
    <div class="custom-pending-posts">
        <ul>
            <?php
            if ($pending_posts->have_posts()) {
                while ($pending_posts->have_posts()) {
                    $pending_posts->the_post();
                    $post_id = get_the_ID();
                    $post_type = get_post_type($post_id);
                    $post_type_label = get_post_type_object($post_type) ? get_post_type_object($post_type)->labels->singular_name : 'N/A'; // Get human-readable name
                    $author_id = get_the_author_meta('ID');
                    $author_name = get_the_author_meta('display_name', $author_id);
                    ?>

                    <li>
                        <strong>
                            <a href="<?php echo get_edit_post_link($post_id); ?>"><?php the_title(); ?></a>
                            (<?php echo esc_html($post_type_label); ?>)
                        </strong>
                        <br>By <em><?php echo esc_html($author_name); ?></em> on <?php echo get_the_date(); ?>
                    </li>
                    <?php
                }
                wp_reset_postdata();
            } else {
                echo '<li>No pending posts found.</li>';
            }
            ?>
        </ul>
    </div>
    <?php
}

// Register the widget in the WordPress dashboard
function add_custom_dashboard_widget() {
    wp_add_dashboard_widget(
        'custom_pending_posts_widget',
        'Pending Partner & CTA Posts',
        'custom_dashboard_pending_posts_widget'
    );
}
add_action('wp_dashboard_setup', 'add_custom_dashboard_widget');