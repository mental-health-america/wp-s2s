<?php

// Custom Admin Javascript
function mha_admin_scripts($hook) {
    wp_enqueue_script('mythril_shard_scripts', plugin_dir_url(__FILE__) . 'js/mha_admin.js', array(), '1.0');
    wp_enqueue_style( 'mythril_shard_scripts', plugin_dir_url(__FILE__) . 'css/mythril_shard.css', array(), '1.1' );
}

add_action('admin_enqueue_scripts', 'mha_admin_scripts');

// Disable comment button on toolbar
function remove_comments(){
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}
add_action( 'wp_before_admin_bar_render', 'remove_comments' );

// Disabmle xmlrpc.php access
add_filter( 'xmlrpc_enabled', '__return_false' );

function remove_dashboard_meta() {
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8    
    remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
}
add_action( 'admin_init', 'remove_dashboard_meta' );

/** Override for extra admin menu item hiding */
function custom_admin_css_for_partner_role() {
    $current_user = wp_get_current_user();
    if (in_array('partner', $current_user->roles)) {
        echo '<style>
            #toplevel_page_acf-options-mha-redirects,
            #toplevel_page_acf-options-mha-global-options { display: none !important; }
        </style>';
    }
}
add_action('admin_head', 'custom_admin_css_for_partner_role');


// Dynamically Populate the "Demographic Based Next Steps" Key Field
/*
function my_acf_load_field( $field ) {
    
    // reset choices
    $field['choices'] = array();    

    // Get demo labels
    $forms = GFAPI::get_forms();  
    $demo_labels = [];
    foreach($forms as $form){
        foreach($form['fields'] as $field){
            if(strpos($field->cssClass, 'optional') !== false){
                $demo_labels[] = $field->label;
            }
        }
    }
    $demo_labels = array_unique($demo_labels);
    $demo_labels = array_values($demo_labels);
    
    foreach( $demo_labels as $choice ) {            
        $field['choices'] = $demo_labels;            
    }   

    return $field;
    
}

add_filter('acf/load_field/name=key_test', 'acf_load_screen_field_choices');
*/

/** 
 * Custom Columns for DIY Responses
 */

// Add the columns
function diy_responses_add_custom_columns($columns) {
    $columns = array_slice($columns, 0, 2, true) + ['activity_id' => __('DIY Activity', 'textdomain')] + array_slice($columns, 2, null, true);
    return $columns;
}
add_filter('manage_edit-diy_responses_columns', 'diy_responses_add_custom_columns');

// Populate the custom column with the DIY Activity Title
function diy_responses_custom_column_content($column, $post_id) {
    if ($column === 'activity_id') {
        $activity_filter = isset($_GET['activity_id_filter']) ? sanitize_text_field($_GET['activity_id_filter']) : null;
        if($activity_filter){
            echo get_the_title(sanitize_text_field($activity_filter));
        } else {
            $activity_id = get_field('activity_id', $post_id); 
            echo $activity_id ? $activity_id->post_title : '-';
        }
    }
}
add_action('manage_diy_responses_posts_custom_column', 'diy_responses_custom_column_content', 10, 2);

// Add filters
function diy_responses_filter_by_activity_id($post_type) {
    if ($post_type === 'diy_responses') {
        $selected = isset($_GET['activity_id_filter']) ? sanitize_text_field($_GET['activity_id_filter']) : '';

        $diy_posts = get_posts([
            'post_type'      => 'diy',
            'posts_per_page' => -1,
            'post_status'    => array('draft','publish','private'),
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);

        if (!empty($diy_posts)) {
            ?>
            <select name="activity_id_filter">
                <option value=""><?php _e('All DIY Tools', 'textdomain'); ?></option>
                <?php foreach ($diy_posts as $diy_id) : 
                    ?>
                    <option value="<?php echo esc_attr($diy_id); ?>" <?php selected($selected, $diy_id); ?>>
                        <?php 
                            echo get_the_title($diy_id); 
                            $post_status = get_post_status($diy_id);
                            echo $post_status != 'publish' ? '<strong class="post-state"> &ndash; '.strtoupper($post_status).'</strong>' : '';
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }
}
add_action('restrict_manage_posts', 'diy_responses_filter_by_activity_id');

// Modify query to filter diy_responses by Activity ID
function diy_responses_filter_query($query) {
    global $pagenow;
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['activity_id_filter']) && !empty($_GET['activity_id_filter'])) {
        $query->query_vars['meta_query'][] = array(
            'key'     => 'activity_id',
            'value'   => '"'.sanitize_text_field($_GET['activity_id_filter']).'"',
            'compare' => 'LIKE'
        );
    }
}
add_filter('pre_get_posts', 'diy_responses_filter_query');

