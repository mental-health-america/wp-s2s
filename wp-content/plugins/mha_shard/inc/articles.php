<?php	

/**
 * Display related articles to the taxonomy term
 */
function get_condition_articles($tax = null, $tag = null, $search_query = null, $is_espanol = false){

    remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
    remove_filter( 'the_posts', 'relevanssi_query', 99 );
    
    // Default Vars
    $article_array = [];
    $orderby = get_query_var('orderby');
    $order = get_query_var('order') ? get_query_var('order') : 'DESC';
    $search_query = sanitize_text_field( $search_query );

    // Pagination
    $current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $posts_per_page = 40;
    
    // Get Articles and DIY Tools
    $args = array(
        "post_type" => array('article', 'diy'),
        "posts_per_page" => -1,
        "post_status" => 'publish',
        "meta_query" => array(
            array(
                'key' => 'type',
                'value' => 'provider',
                'compare' => 'NOT LIKE'
            )
        ),
        "tax_query" => array(
            array(
                'taxonomy' => $tax,
                'field'    => 'id',
                'terms'    => $tag
            )
        )
    );

    // Append search query if present
    if($search_query){
        $args['s'] = $search_query;
    }					

    // Popular         
    $popular = do_shortcode("[mha_popular_articles tag='$tag' tax='$tax' style='inline']");
    $popular_titles = preg_replace(array('/&nbsp;/','/\s{2,}/', '/[\t\n]/'), ' ', strip_tags($popular));
    $popular_array = explode(' | ', trim($popular_titles));
    
    $loop = new WP_Query($args);
    while($loop->have_posts()) : $loop->the_post();	

        // Skips
        if(get_field('invisible') || get_field('survey') || !$is_espanol && get_field('espanol')){
            continue;
        }

        // General Vars
        $score = 1;
        $article_title = get_the_title();
        $article_conditions = get_the_terms(get_the_ID(), 'condition');

        // Primary or Only Condition (+2)
        $primary_condition = get_field('primary_condition');
        if($primary_condition && $primary_condition->term_id == $tag || $article_conditions && count($article_conditions) == 1 && $article_conditions[0]->term_id == $tag) {
            $score = $score + 2;
        }

        // Featured (+1)
        if(get_field('featured')){
            $score++;
        }

        // All Condition when its a "Condition" tag page (-1)
        if(get_field('all_conditions') && $tax == 'condition'){
            $score--;
        }

        // Popular         
        if(in_array($article_title, $popular_array)){
            $score++;
        }

        // Types
        $article_type = get_field('type');
        if( in_array( "diy", $article_type) ||  in_array( "connect", $article_type) ){
            $score--;
        }

        $article_array[] = array(
            'id' => get_the_ID(),
            'title' => $article_title,
            'link' => get_the_permalink(),
            'published' => get_the_date('Ymd'),
            'score' => $score
        );

    endwhile;
    wp_reset_query();

    // All condition appended  
    /*
    $args = array(
        "post_type" => 'article',
        "posts_per_page" => -1,
        "post_status" => 'publish',        
        "meta_query" => array(
            'relation' => 'AND',
            'all_conditions' => array(
                array(
                    'key' => 'all_conditions',
                    'value' => 1
                )
            ),
            'article_type' => array(
                array(
                    'key' => 'type',
                    'value' => 'provider',
                    'compare' => 'NOT LIKE'
                )
            ),
            'language' => array(
                'relation' => 'OR',
                array(
                    'key' => 'espanol',
                    'value' => '1',
                    'compare' => '!='
                ),
                array(
                    'key' => 'espanol',
                    'value' => '1',
                    'compare' => 'NOT EXISTS'
                )
            ),
        )
    );
    $loop = new WP_Query($args);
    while($loop->have_posts()) : $loop->the_post();	
        $article_array[] = array(
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'link' => get_the_permalink(),
            'published' => get_the_date('Ymd'),
            'score' => -1
        );
    endwhile;
    */

    // Final Pagination    
    $total_posts = count($article_array);
    $max_pages = ceil($total_posts / $posts_per_page);
    $offset = ($current_page - 1) * $posts_per_page;
    $offset_ceil = $current_page * $posts_per_page;


    // Article ordering options
    if($orderby == 'title'){
        $sort_type = 'title';
    } else if($orderby == 'date'){
        $sort_type = 'published';
    } else {
        $sort_type = 'score';
    }

    // Sort articles unless there was a search query
    if(!$search_query || $search_query && $sort_type != 'score'):
        $article_sort = array_column($article_array, $sort_type);									
        if($order == 'ASC'){
            array_multisort($article_sort, SORT_ASC, $article_array);
        } else {
            array_multisort($article_sort, SORT_DESC, $article_array);
        }
    endif;
    
    // Print articles
    $html = '<ol class="plain mb-0">';        
        $i = 0;
        foreach($article_array as $a):
            if($i >= $offset && $i < $offset_ceil){
                $html .= '<li class="mb-4"><p class="mb-2"><a class="dark-gray plain" href="'.add_query_arg('ref', $tag, $a['link']).'">'.$a['title'].'</a></p></li>';
            }
            $i++;
        endforeach;
    $html .= '</ol>';
    
    // Pagination
    $html .=  '<div class="navigation pagination pt-5">';
    $html .=  paginate_links( array(
        'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'total'        => $max_pages,
        'current'      => $current_page,
        'format'       => '?paged=%#%',
        'show_all'     => false,
        'type'         => 'plain',
        'end_size'     => 2,
        'mid_size'     => 1,
        'prev_next'    => true,
        'prev_text'    => sprintf( '<i></i> %1$s', __( 'Previous', 'text-domain' ) ),
        'next_text'    => sprintf( '%1$s <i></i>', __( 'Next', 'text-domain' ) ),
        'add_args'     => false,
        'add_fragment' => '',
    ) );
    $html .=  '</div>';

    return $html;

}

function get_articles_by_custom_field($field = null, $search_query = null){

    wp_reset_postdata();
    wp_reset_query();

    // Default Vars
    $article_array = [];
    $orderby = get_query_var('orderby');
    $order = get_query_var('order') ? get_query_var('order') : 'DESC';
    $search_query = sanitize_text_field( $search_query );

    // Pagination
    $current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $posts_per_page = 40;
    
    remove_filter( 'posts_request', 'relevanssi_prevent_default_request' );
    remove_filter( 'the_posts', 'relevanssi_query', 99 );

    // Get Articles
    $args = array(
        "post_type" => 'article',
        "posts_per_page" => -1,
        "post_status" => 'publish',
        "meta_query" => array(
            'relationship' => 'AND',
            array(
                'key' => $field,
                'value' => 1,
                'compare' => '='
            ),
            array(
                'key' => 'type',
                'value' => 'provider',
                'compare' => 'NOT LIKE'
            )
        ),
    );

    // Append search query if present
    if($search_query){
        $args['s'] = $search_query;
    }					

    // Popular         
    $popular = do_shortcode("[mha_popular_articles style='inline']");
    $popular_titles = preg_replace(array('/&nbsp;/','/\s{2,}/', '/[\t\n]/'), ' ', strip_tags($popular));
    $popular_array = explode(' | ', trim($popular_titles));

    $loop = new WP_Query($args);

    while($loop->have_posts()) : $loop->the_post();	

        // Skips
        if(get_field('invisible') || get_field('survey') || get_field('espanol')){
            continue;
        }

        // General Vars
        $score = 1;
        $article_title = get_the_title();
        $article_conditions = get_the_terms(get_the_ID(), 'condition');

        // Featured (+1)
        if(get_field('featured')){
            $score++;
        }

        // Popular         
        if(in_array($article_title, $popular_array)){
            $score++;
        }

        // Types
        $article_type = get_field('type');
        if( in_array( "diy", $article_type) ||  in_array( "connect", $article_type) ){
            $score--;
        }

        $article_array[] = array(
            'id' => get_the_ID(),
            'title' => $article_title,
            'link' => get_the_permalink(),
            'published' => get_the_date('Ymd'),
            'score' => $score
        );

    endwhile;
    wp_reset_query();

    
    // Final Pagination    
    $total_posts = count($article_array);
    $max_pages = ceil($total_posts / $posts_per_page);
    $offset = ($current_page - 1) * $posts_per_page;
    $offset_ceil = $current_page * $posts_per_page;


    // Article ordering options
    if($orderby == 'title'){
        $sort_type = 'title';
    } else if($orderby == 'date'){
        $sort_type = 'published';
    } else {
        $sort_type = 'score';
    }

    // Sort articles unless there was a search query
    if(!$search_query || $search_query && $sort_type != 'score'):
        $article_sort = array_column($article_array, $sort_type);									
        if($order == 'ASC'){
            array_multisort($article_sort, SORT_ASC, $article_array);
        } else {
            array_multisort($article_sort, SORT_DESC, $article_array);
        }
    endif;
    
    // Print articles
    $html = '<ol class="plain mb-0">';        
        $i = 0;
        foreach($article_array as $a):
            if($i >= $offset && $i < $offset_ceil){
                $html .= '<li class="mb-4"><p class="mb-2"><a class="dark-gray plain" href="'.$a['link'].'">'.$a['title'].'</a></p></li>';
            }
            $i++;
        endforeach;
    $html .= '</ol>';
    
    // Pagination
    $html .=  '<div class="navigation pagination pt-5">';
    $html .=  paginate_links( array(
        'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
        'total'        => $max_pages,
        'current'      => $current_page,
        'format'       => '?paged=%#%',
        'show_all'     => false,
        'type'         => 'plain',
        'end_size'     => 2,
        'mid_size'     => 1,
        'prev_next'    => true,
        'prev_text'    => sprintf( '<i></i> %1$s', __( 'Previous', 'text-domain' ) ),
        'next_text'    => sprintf( '%1$s <i></i>', __( 'Next', 'text-domain' ) ),
        'add_args'     => false,
        'add_fragment' => '',
    ) );
    $html .=  '</div>';

    return $html;

}