<?php 
/* Template Name: Content Import */
get_header(); 

// This shouldn't be on production!
//die();

function acf_field_array_conversion( $string ){            
    if($string != ''){
        $array = explode(',', $string);
        $return_array = [];
        foreach($array as $item){
            $item_clean = trim(str_replace('&amp; ', '', $item));
            $return_array[] = strtolower(str_replace(' ', '-', $item_clean));
        }
        return $return_array;
    }
}

function field_exploder($init_array, $object){
    $data_array = [];
    $data = explode(', ', $init_array);
    foreach($data as $t){
        foreach($object['choices'] as $k => $v){
            if($t == $v){
                $data_array[] = $k;
            }
        }
    }
    return $data_array;
}

?>

<div class="wrap medium">

<?php    

/**
 * Big Content Tag Update
 */

 
$article_args = array(
    'post_type' => 'article',
    'posts_per_page' => -1,
    "post_status" => array('publish'),
    "meta_query" => array(
        array(
            'key' => 'whole_state',
            'value' => 'Minnesota'
        )
    )
);
pre($article_args);
$article_query = new WP_Query( $article_args );
while($article_query->have_posts()) : $article_query->the_post();

    the_title();

endwhile; 

pre($updates);

 /*
    $obj_diy_type = get_field_object('field_5fd3f1a935255');
    $obj_diy_issue = get_field_object('field_5fea345c4d25c');
    $obj_service_type = get_field_object('field_5fdc0a1448b13');
    $obj_treatment_type = get_field_object('field_5fd3f7a3951ad');

    $csv = ck_csv('https://mhascreening.wpengine.com/all-articles.csv?c=2'.rand(0,10000));
    $csv_fix = array_shift($csv);
    //pre($csv);
    $updates = [];
    */

    //foreach($csv as $c){

            /*
            if($c['All Conditions?'] != ''){ 
                $updates[$post_id]['conditions'] = wp_set_object_terms( $post_id, 119, 'condition', true ); 
            }
            */

            // Update Post
            /*
            $data = array(
                'ID' => $post_id,
                'post_status' => $c['Published'],                
                'post_name' => $c['Link'],
            );               
            wp_update_post( $data );

            // Update Fields
            $updates[$post_id]['type name'] = $c['type'];  
            $updates[$post_id]['type'] = update_field('field_5fd3eec524b34', $c['type'], $post_id);  
            
            if($c['All Conditions?'] != ''){ 
                $updates[$post_id]['all_conditions'] = update_field('all_conditions', 1, $post_id);
                $updates[$post_id]['conditions'] = wp_set_object_terms( $post_id, $conditions, 'condition', false ); 
            }

            if($c['DIY Type'] != ''){                
                $array_diy_type = field_exploder($c['DIY Type'], $obj_diy_type);
                $updates[$post_id]['diy type'] = update_field('diy_type', $array_diy_type, $post_id); 
            }

            if($c['Issue'] != ''){                
                $array_diy_issue = field_exploder($c['Issue'], $obj_diy_issue);
                $updates[$post_id]['diy issue'] = update_field('diy_issue', $array_diy_issue, $post_id); 
            }

            if($c['Service Type'] != ''){                
                $array_service_type = field_exploder($c['Service Type'], $obj_service_type);
                $updates[$post_id]['service_type'] = update_field('service_type', $array_service_type, $post_id); 
            }

            if($c['Treatment Category'] != ''){                
                $array_treatment_cat = field_exploder($c['Treatment Category'], $obj_treatment_type);
                $updates[$post_id]['service_type'] = update_field('treatment_type', $array_treatment_cat, $post_id); 
            }

            // Update Taxonomy
            if($c['Conditions'] != ''){
                $conditions = explode(', ',$c['Conditions']);
                $updates[$post_id]['conditions'] = wp_set_object_terms( $post_id, $conditions, 'condition', false );
            } else {
                $updates[$post_id]['conditions'] = wp_set_object_terms( $post_id, '', 'condition', false );
            }
            
            if($c['Age'] != ''){
                $age_group = explode(', ',$c['Age']);
                $updates[$post_id]['age_group'] = wp_set_object_terms( $post_id, $age_group, 'age_group', false );
            } else {
                $updates[$post_id]['age_group'] = wp_set_object_terms( $post_id, '', 'age_group', false );
            }
            
            if($c['Tags'] != ''){
                $tags = explode(', ',$c['Tags']);
                $updates[$post_id]['tags'] = wp_set_object_terms( $post_id, $tags, 'post_tag', false );
            } else {
                $updates[$post_id]['tags'] = wp_set_object_terms( $post_id, '', 'post_tag', false );
            }
            */


    //}
    










    /**
     * Article Updater
     */
    /*
    $obj_treatment = get_field_object('field_5fd3f7a3951ad');

    $csv = ck_csv('https://mhascreening.wpengine.com/articles.csv?c='.rand(0,10000));
    foreach($csv as $c){


        $article_args = array(
            'posts_per_page' => 1,
            'post_type' => 'article',
            'meta_query' => array(
                array(
                    'key' => 'drupal_id',
                    'value' => $c['id']
                )
            )
        );
        $article_query = new WP_Query( $article_args );
        $counter = 0;
        while($article_query->have_posts()) : $article_query->the_post();

            $post_id = get_the_ID();
            $type = 'treatment';
            $treatment = explode(', ', $c['treatment']);
            $treatment_array = [];

            echo '<p>';
                echo 'Updated ID: '.$post_id.'<br />';

                pre($treatment);

                foreach($treatment as $t){
                    foreach($obj_treatment['choices'] as $k => $v){
                        if($t == $v){
                            $treatment_array[] = $k;
                        }
                    }
                }

                pre($treatment_array);
                echo "<hr />";

                $update_type = update_field('type', $type, $post_id);        
                $update_treatment = update_field('treatment_type', $treatment_array, $post_id);    

            echo '</p>';

        endwhile;  
                
    }
    */















    /**
     * Clear excerpts
     */

    /*
    global $wpdb;
    $querystr = "SELECT * FROM $wpdb->posts WHERE $wpdb->posts.post_excerpt = 'Find a life-changing therapist.'";     
    $posts = $wpdb->get_results($querystr);

    foreach($posts as $p){        
        $updateArgs = array(
            'ID'           => $p->ID,
            'post_excerpt' => '',
        );
        wp_update_post( $updateArgs );
        echo 'Updated '.$p->ID.'<br />';    
    }
    */



    /**
     * Find broken media
     */

    /*
    $args = array(
        'posts_per_page'	=> -1,
        'post_type'		    => 'article'
    );
    $query = new WP_Query( $args );
    while($query->have_posts()) : $query->the_post();
        
        $post_id = get_the_ID();
        $old_content = get_the_content();

        //the_content();

        // Find all links with node/####
        preg_match_all('/src="\/sites\/default\/files\/(.*?)\"/', $old_content, $matches, PREG_PATTERN_ORDER);
        
        if($matches[0]){            
            the_title('<h4>','</h4>');
            pre($matches[0]);
            echo '<a href="'.get_edit_post_link().'">Edit</a>';
            echo '<hr />';
        } else {
            //echo 'Skipped<br />';;
        }
    endwhile;
    */


    /**
     * Fix Broken Drupal Links
     */

    /*
    $args = array(
        'posts_per_page'	=> 10,
        'post_type'		    => 'article'
    );
    $query = new WP_Query( $args );
    while($query->have_posts()) : $query->the_post();
        
        the_title('<h4>','</h4>');
        $post_id = get_the_ID();
        $old_content = get_the_content();
        $new_links = [];

        // Find all links with node/####
        preg_match_all('(\/node\/\d+)', $old_content, $matches, PREG_PATTERN_ORDER);

        if(count($matches[0]) > 0){
            foreach($matches[0] as $match){

                // Get new URLs
                $drupal_id = str_replace('/node/', '', $match);
                $article_args = array(
                    'posts_per_page'	=> 1,
                    'post_type'		    => 'article',
                    'meta_query'        => array(
                        array(
                            'key' => 'drupal_id',
                            'value' => $drupal_id
                        )
                    )
                );
                $article_query = new WP_Query( $article_args );
                while($article_query->have_posts()) : $article_query->the_post();
                    $new_links[] = get_the_permalink();  
                    echo $drupal_id .' '.get_the_permalink().'<br />';              
                endwhile;
                
            }
            
            $new_content = str_replace($matches[0], $new_links, $old_content);
            
            // Update content    
            $updateArgs = array(
                'ID'           => $post_id,
                'post_content' => $new_content,
            );
            wp_update_post( $updateArgs );
            echo 'Updated '.$post_id.'<br />';  
        } else {
            echo 'Skipped '.$post_id.'<br />';  
        }

        echo '<hr />';       
        
    endwhile;
    */



    /**
     * Dupe Checker and General Importer
     */

    /*
    $request  = wp_remote_get( 'https://mhascreening.wpengine.com/content-export.json?c='.rand(0,10000) );
    $response = wp_remote_retrieve_body( $request );
    $data = json_decode( $response, true );
    wp_reset_query();
    $args = array(
        "post_type" => 'article',
        "order"	=> 'ASC',
        "post_status" => array('publish','draft'),
        "orderby" => 'date',
        "posts_per_page" => 9999
    );
    $loop = new WP_Query( $args ); 
    $dupe_checker = [];
    while($loop->have_posts()) : $loop->the_post();
        $dupe_checker[get_the_ID()] = get_field('drupal_id', get_the_ID());
    endwhile;
    $uarr = array_unique($dupe_checker);
    $diff = array_diff($dupe_checker, array_diff($uarr, array_diff_assoc($dupe_checker, $uarr)));

    foreach($diff as $k => $v){        
        echo $k . ' / ' . $v . ' / ' . get_post_status($k).'<br />';
    }

    foreach($data as $item){

        // Skip existing posts
        $args = array(
            'numberposts'	=> 1,
            'post_type'		=> 'article',
            "post_status"   => array('publish','draft'),
            'meta_key'		=> 'drupal_id',
            'meta_value'	=> $item['nid']
        );
        $query = new WP_Query( $args );
        $total = $query->post_count;

        while($query->have_posts()) : $query->the_post();

            update_field('featured', 0, get_the_ID());

            // Add Excerpts
            /*
            if($item['field_connect_tool_friendly_desc']){
                $excerpt = $item['field_connect_tool_friendly_desc'];
            }
            if($item['field_diy_tool_friendly_descript']){
                $excerpt = $item['field_diy_tool_friendly_descript'];
            }
            if($item['field_referral_tool_friendly_des']){
                $excerpt = $item['field_referral_tool_friendly_des'];
            }

            if($excerpt != ''){
                $updateArgs = array(
                    'ID'           => get_the_ID(),
                    'post_excerpt' => $excerpt,
                );
                wp_update_post( $updateArgs );
                echo 'Updated '.get_the_ID().'<br />';
            }
        endwhile;

        if( $total == 0 ){

            // Article Type
            $content_type = $item['type'];
            $article_type = '';
            if($content_type == 'connect_tool') {
                $article_type = 'connect';
            }
            else if($content_type == 'treatment_info') {
                $article_type = 'treatment';
            }
            else if($content_type == 'referral_to_treatment_profile') {
                $article_type = 'provider';
            }
            else if($content_type == 'diy_tool') {
                $article_type = 'diy';
            }
            else if($content_type == 'condition') {
                $article_type = 'condition';
            }


            // Conditions
            $field_condition_severity = $item['field_condition_severity'];
            $condition_array = explode(',',$field_condition_severity);
            $conditions = [];
            foreach($condition_array as $c){
                $term = get_term_by('name', $c, 'condition');
                if($term){
                    $conditions[] = $term->term_id;
                }

                // Assign parents too while we're at it
                $termParent = ($term->parent == 0) ? $term : get_term($term->parent, 'condition');
                if($termParent){
                    $conditions[$termParent->name] = $termParent->term_id;
                }
            }

            // Ages
            $field_age = $item['field_age'];
            $age_array = explode(',',$field_age);
            $ages = [];
            foreach($age_array as $c){
                $term =  get_term_by('name', $c, 'age_group');
                if($term){
                    $ages[] = $term->term_id;
                }
            }

            // Tags
            $field_tags = $item['field_tags'];
            $tag_array = explode(',',$field_tags);
            $tags = [];
            foreach($tag_array as $c){
                $term =  get_term_by('name', $c, 'post_tag');
                if($term){
                    $tags[] = $term->term_id;
                }
            }
            
            // Status
            $status = $item['status'];
            if($status == 'True'){
                $status = 'publish';
            } else {
                $status = 'draft';
            }
            
            // Username
            $username = $item['uid'];
            $user = get_user_by('login', $username);
            if($user){
                $user_id = $user->ID;
            } else {
                $user_id = 1;
            }

            // Prep other fields
            $diy_type = acf_field_array_conversion( $item['field_diy_type'] );
            $diy_issue = acf_field_array_conversion( $item['field_issue'] );
            $service_type = acf_field_array_conversion( $item['field_service_type'] );
            $area_served = acf_field_array_conversion( $item['field_area_served'] );
            $treatment_type = acf_field_array_conversion( $item['field_treatment_category'] );

            $post_details = array(
                'post_type'     => 'article',
                'post_title'    => wp_strip_all_tags( $item['title'] ),
                'post_content'  => $item['body'],
                'post_status'   => $status,
                'post_author'   => $user_id,
                'post_date'     => $item['created']
            );

            // Insert the post into the database
            $post_id = wp_insert_post( $post_details );

            // Add image attachment
            $image_id = attach_remote_image_to_post($item['field_image'], $post_id, $item['field_image_1']);
            if($image_id){
                set_post_thumbnail( $post_id, $image_id );
            }

            // Taxonomy
            wp_set_post_terms( $post_id, $ages, 'age_group');
            wp_set_post_terms( $post_id, $conditions, 'condition');
            wp_set_post_terms( $post_id, $tags, 'post_tag');

            // Simple Custom Fields
            update_field('type', $article_type, $post_id);
            update_field('drupal_id', $item['nid'], $post_id);
            if($diy_type) { update_field('diy_type', $diy_type, $post_id); }
            if($diy_issue) { update_field('diy_issue', $diy_issue, $post_id); }
            if($service_type) { update_field('service_type', $service_type, $post_id); }
            if($area_served) { update_field('area_served', $area_served, $post_id); }
            if($treatment_type) { update_field('treatment_type', $treatment_type, $post_id); }

            // Service Location
            if($item['field_service_location'] != ''){  
                $location = explode(',', trim(str_replace(' ', '', $item['field_service_location'])));
                $location_data = array(
                    array(
                        "latitude"      => $location[0],
                        "longitude"     => $location[1]
                    )
                );
                update_field('location', $location_data, $post_id);
            }

            // New Article
            echo 'Imported '.$item['nid'].' / '.$post_id.'<br />';
            
        } else {
            
            // Already there
            echo 'Skipped '.$item['nid'].' / --<br />';

        }

    }
    */
?>

</div>

<?php
get_footer();