<?php

function mha_results_related_articles( $args ){                        

    // Args
    $defaults = array(
        'demo_steps'         => array(),
        'next_step_manual'   => array(),
        'user_screen_result' => array(),
        'excluded_ids'       => array(),
        'next_step_terms'    => array(),
        'espanol'            => '',
        'iframe_var'         => '',
        'partner_var'        => '',
        'total'              => 500,
        'skip'               => 0,
        'style'              => 'text',
        'layout'             => array(),
        'hide_all'           => false
    );   
    $list_items = [];
    $temp_excluded = [];
    $used_ids = [];
    $args = wp_parse_args( $args, $defaults );
    $user_screen_result = $args['user_screen_result'];

    $return = [];
    $return_html = '';

    switch($args['style']):
        case 'button';
            $list_link_class = 'button green thin round mr-3 mb-3';
        break;
        case 'featured';
            $list_link_class = 'button green thin round mr-3 rec-screen-featured-style';
        break;
        default:
            $list_link_class = 'dark-gray plain';
            break;
    endswitch;

    if($args['style'] == 'featured'){
        $return_html .= '<div class="wrap narrow mb-5">';
    }

    // Demographic Data for later
    $user_demo_ages = [];
    $user_demo_lgbtq = false;
    $user_demo_bipoc = false;
    $user_demo_caregiver = false;

    // Begin answering Demos
    foreach($args['answered_demos'] as $key => $value){

        // Age
        if (strpos(strtolower($key), 'age') !== false || strpos(strtolower($key), 'edad') !== false) {
            foreach($value as $v){
                $val_ages = explode('-', $v);
                foreach($val_ages as $va){
                    $user_demo_ages[] = intval($va);
                }
            }
        }

        // LGBTQ
        if (strpos(strtolower($key), 'populations') !== false || strpos(strtolower($key), 'poblaciones') !== false) {
            foreach($value as $v){
                if(strpos(strtolower($v), 'lgbtq') !== false){
                    $user_demo_lgbtq = true;
                }
            }
        }

        // Ethnicity
        if (strpos(strtolower($key), 'ethnicity') !== false || strpos(strtolower($key), 'étnico') !== false) {
            foreach($value as $v){
                if(strpos(strtolower($v), 'white') === false || strpos(strtolower($v), 'blanco') === false){
                    $user_demo_bipoc = true;
                }
            }
        }

        // Caregiver
        if (strpos(strtolower($key), 'someone else') !== false || strpos(strtolower($key), 'otra persona') !== false) {
            foreach($value as $v){
                if(strpos(strtolower($v), 'someone else') !== false || strpos(strtolower($v), 'otra persona') !== false){
                    $user_demo_caregiver = true;
                }
            }
        }

    }
    
    $age_check = null;
    $user_demo_ages_cleaned = [];
    foreach($user_demo_ages as $ud){
        if($ud > 0){
            $user_demo_ages_cleaned[] = $ud;
        }
    }

    foreach($user_demo_ages_cleaned as $uda){
        if($uda >= 18){
            $age_check = 'over';
        } else if($uda < 18){
            $age_check = 'under';
        }
    }

    if($args['style'] != 'button' && $args['style'] != 'featured' ){
        if(!in_array('ras_r', $args['layout']) ){
            $return_html .= '<ol class="next-steps masonry">';
        } else {
            $return_html .= '<ol class="next-steps columned">';
        }
    }

    if($args['style'] == 'featured' ){
        $return_html .= '<ol class="next-steps single-column">';
    }
    
    /**
     * Demo Based Steps
     */
    foreach($args['demo_steps'] as $link){
        if( !in_array($link->ID, $args['excluded_ids']) && !in_array($link->ID, $temp_excluded) ){

            $new_list_item = '<a class="'.$list_link_class.' rec-screen-demobased" href="'.get_the_permalink($link->ID).'">'.$link->post_title;
            if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
                $new_list_item .= '<br /><span class="small text-red">ID: #'.$link->ID.'</span> <span class="small text-red">(Demographic Based Condition)</span>';
            }
            $new_list_item .= '</a>';
            $list_items[] = array(
                'id' => $link->ID,
                'type' => 'demo',
                'html' => $new_list_item
            );
            $temp_excluded[] = $link->ID;
        }
    }

    /**
     * Result Based Manual Steps
     */
    foreach($args['next_step_manual'] as $step){
        if( !in_array($step, $args['excluded_ids']) && !in_array($step, $temp_excluded) ){
            $step_link = get_the_permalink($step);
            $step_link_target = '';
            if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                //$step_link = add_query_arg( 'partner', $args['partner_var'], $step_link );
            }
            if($args['iframe_var']){                                         
                //$step_link = add_query_arg( 'iframe','true', $step_link );
                $step_link_target = ' target="_blank"';
            }

            $new_list_item = '<a class="'.$list_link_class.' rec-result-manual"'.$step_link_target.' href="'.$step_link.'">'.get_the_title($step).'</a>';
            if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
                $new_list_item .= '<br /><span class="small text-red">ID: #'.$step.'</span> <span class="small text-red">(Result Based Manual Condition)</span>';
            }
            $new_list_item .= '</a>';

            if(!in_array($step, $args['excluded_ids'])){
                $list_items[] = array(
                    'id' => $step,
                    'type' => 'result_manual',
                    'html' => $new_list_item
                );
                $temp_excluded[] = $step;
            }
        }
    }

    /**
     * Manual Steps
     */
    if( have_rows('featured_next_steps', $user_screen_result['screen_id']) ):
    while( have_rows('featured_next_steps', $user_screen_result['screen_id']) ) : the_row();
        $step = get_sub_field('link');
        if($step && !in_array($step->ID, $args['excluded_ids']) && !in_array($step->ID, $temp_excluded)){
            $manual_step_link = get_the_permalink($step->ID);
            $manual_step_target = '';
            if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                //$manual_step_link = add_query_arg( 'partner', $args['partner_var'], $manual_step_link );
            }
            if($args['iframe_var']){                                         
                //$manual_step_link = add_query_arg( 'iframe','true', $manual_step_link );
                $manual_step_target = ' target="_blank"';
            }

            $new_list_item = '<a class="'.$list_link_class.' rec-screen-manual"'.$manual_step_target.' href="'.$manual_step_link.'">'.$step->post_title;
            if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
                $new_list_item .= '<br /><span class="small text-red">ID: #'.$step->ID.'</span> <span class="small text-red">(Manual Condition)</span>';
            }
            $new_list_item .= '</a>';
            $list_items[] = array(
                'id' => $step->ID,
                'type' => 'manual',
                'html' => $new_list_item
            );
            $temp_excluded[] = $step->ID;
        }
    endwhile;        
    endif;


    /**
     * URL Inclusion Overrides
     */
    if(get_query_var('include_ids')){
        $include_ids = explode(',', get_query_var('include_ids'));
        foreach($include_ids as $iid){
            if( !in_array($iid, $args['excluded_ids']) && !in_array($iid, $temp_excluded) ){
                $new_list_item = '<a class="'.$list_link_class.' rec-screen-urlbased" href="'.get_the_permalink($iid).'">'.get_the_title($iid);
                if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
                    $new_list_item .= '<br /><span class="small text-red">ID: #'.$iid.'</span> <span class="small text-red">(URL Based)</span>';
                }
                $new_list_item .= '</a>';
                $list_items[] = array(
                    'id' => $iid,
                    'type' => 'include_ids',
                    'html' => $new_list_item
                );
                $temp_excluded[] = $iid;
            }
        }
    }

    /**
     * Automatic Query Args
     */

    $loop_args = array(
        "post_type" => ['article','diy'],
        "order"	=> 'ASC',
        "orderby" => 'title',
        "post_status" => 'publish',
        "posts_per_page" => 50,
        /*
        "meta_query" => array(
            array(
                'key' => 'type',
                'value' => array('condition', 'diy','connect','treatment','provider'),
                'compare' => 'IN'
            )
        )
        */
    );

    if(in_array('ras_r', $args['layout'])){
        $loop_args = array(
            "post_type" => 'article',
            "order"	=> 'ASC',
            "orderby" => 'date',
            "post_status" => 'publish',
            "posts_per_page" => 500,
            "meta_query" => array(
                array(
                    "key"       => 'type',
                    "value"     => 'condition',
                    'compare'   => 'LIKE'
                )
            )
        );
    }


    /**
     * Result Based Next Steps
     */
    $taxonomy_query = [];
    if(isset($user_screen_result['next_step_terms'])){
        $next_step_terms = array_unique($user_screen_result['next_step_terms']);
        foreach($next_step_terms as $step){

            $step_con = get_term($step, 'condition');
            if($step_con){
                $taxonomy_query[$step_con->taxonomy][] = $step_con->term_id;
            }

            $step_age_group = get_term($step, 'age_group');
            if($step_age_group){
                $taxonomy_query[$step_age_group->taxonomy][] = $step_age_group->term_id;
            }

            $step_post_tag = get_term($step, 'post_tag');
            if($step_post_tag){
                $taxonomy_query[$step_post_tag->taxonomy][] = $step_post_tag->term_id;
            }

        }
    }

    // Demographic based steps
    if(!empty($user_screen_result['result_terms'])){
        foreach($user_screen_result['result_terms'] as $step){ 
            if($step['taxonomy'] == 'condition' || $step['taxonomy'] == 'age_group' || $step['taxonomy'] == 'post_tag'){
                $taxonomy_query[$step['taxonomy']][] = $step['id'];    
            }   
        }
    }

    // Overall screen based steps
    $tags = get_field('related_tags', $user_screen_result['screen_id']);
    if($tags){
        foreach($tags as $step){
            if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                $taxonomy_query[$step->taxonomy][] = $step->term_id;
            }
        }
    }

    // Excluded previous manual 
    $loop_args['post__not_in'] = array_unique($args['excluded_ids']); 

    // Set up taxonomy query filters
    if(isset($taxonomy_query)){
        foreach($taxonomy_query as $k => $v){
            $loop_args['tax_query'][] = array(
                'taxonomy' => $k,
                'field'    => 'term_id',
                'terms'    => $v
            );
        }
    }

    // Make all tags required for multiple taxonomies (e.g. avoid eating disorder articles on depression results 
    // if someone answered 18-25 demographic questions)
    if(isset($taxonomy_query) && is_array($taxonomy_query) && count($taxonomy_query) > 1){
        $loop_args['tax_query']['relation'] = 'AND';
    }

    if($args['espanol']){
        unset($loop_args['meta_query']);
        unset($loop_args['tax_query']);

        $loop_args['post_type'] = ['article','diy'];
        $loop_args['posts_per_page'] = 50;
        $loop_args['tax_query'] = array(
            array(
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => array(49),
            )
        );
    }

    // Automatic Related Article Query
    $loop = new WP_Query($loop_args);
    $pop_array = mha_monthly_pop_articles('read');            
    $screen_conditions = get_the_terms( $user_screen_result['screen_id'], 'condition' );

    // Get Primary condition
    $primary_condition_yoast = get_post_meta($user_screen_result['screen_id'],'_yoast_wpseo_primary_condition', true);
    if($primary_condition_yoast){
        $primary_condition = get_term($primary_condition_yoast, 'condition');
    } else {                
        $primary_condition = $screen_conditions ? $screen_conditions[0] : null;
    }
    
    $terms_tags = get_the_terms( $user_screen_result['screen_id'], 'post_tag' );
    $related_articles = [];
    
    while($loop->have_posts()) : $loop->the_post();
        
        $related_link = get_the_permalink();
        $related_link_target = '';
        
        /*
        if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
            $related_link = add_query_arg( 'partner', $args['partner_var'], $related_link );
        }
        */

        if($args['iframe_var']){                                         
            //$related_link = add_query_arg( 'iframe','true', $related_link );
            $related_link_target = ' target="_blank"';
        }

        $rel_score = 0;
        $article_id = get_the_ID();
        $article_conditions = get_the_terms( $article_id, 'condition' );
        $article_tags = get_the_terms( $article_id, 'post_tag' );
        $article_primary_condition = get_field('primary_condition', $article_id);

        // Immediate skips
        if(in_array($article_id, $args['excluded_ids']) || in_array($article_id, $temp_excluded)){
            continue;
        }

        // Defaults
        $related_articles[$article_id] = [];
        $related_articles[$article_id]['score_debug'] = '';

        // Skip Spanish Articles
        if(!$args['espanol'] && get_field('espanol')){
            unset($related_articles[$article_id]);
            continue;
        }

        // Skip local providers
        if(get_field('area_served')){
            unset($related_articles[$article_id]);
            continue;
        }
        
        // Matching primary condition
        $hasPrimary = false;
        if(
            $article_primary_condition && $primary_condition && isset($article_primary_condition->term_id) && $article_primary_condition->term_id == $primary_condition->term_id || 
            is_array($article_conditions) && count($article_conditions) == 1 || 
            $primary_condition && 
            $article_primary_condition && 
            isset($article_primary_condition->term_id) && 
            isset($primary_condition->term_id) && 
            $article_primary_condition->term_id == $primary_condition->term_id 
        ){
            $rel_score = $rel_score + get_field('scoring_primary_condition', 'options');
            $related_articles[$article_id]['score_debug'] .= 'OnlyPrimary ';
            $hasPrimary = true;
        }

        // Matching Popular article
        if(in_array($article_id, $pop_array)){
            $rel_score = $rel_score + get_field('scoring_popular', 'options');
            $related_articles[$article_id]['score_debug'] .= 'IsPopular ';
        }

        // Matching conditions     
        if(is_array($article_conditions)):
            foreach($article_conditions as $nc){  
                $hasCondition = false;
                if($screen_conditions){
                    foreach($screen_conditions as $sc){
                        if($nc->term_id == $sc->term_id){
                            $hasCondition = true;
                            break;
                        }
                    }
                }
                if($hasCondition){
                    $rel_score = $rel_score + get_field('scoring_condition', 'options');
                    $related_articles[$article_id]['score_debug'] .= 'HasPrimaryCondition ';
                    break;
                }
                else if($primary_condition && $nc->term_id == $primary_condition->term_id){      
                    if(!$hasPrimary){                  
                        $rel_score = $rel_score + get_field('scoring_matching_primary', 'options');
                        $related_articles[$article_id]['score_debug'] .= 'HasCondition ';
                        break;
                    }
                }
            }
        endif;

        // Matching Article Conditions
        /*
        $screen_conditions_array = [];
        $article_conditions_array = [];
        foreach($screen_conditions as $sc){
            $screen_conditions_array[] = $sc->slug;
        }
        foreach($article_conditions as $ac){
            $article_conditions_array[] = $ac->slug;
        }

        $condition_compare = count( array_intersect( $screen_conditions_array, $article_conditions_array) );
        if($hasPrimary){
            $condition_compare--;
        }

        if($condition_compare > 0){
            $rel_score = $rel_score + ( $condition_compare * get_field('scoring_condition', 'options') );
            $related_articles[$article_id]['score_debug'] .= 'SharesOtherConditions ';
        }
        */

        // Matching Tags
        if($article_tags):
            foreach($article_tags as $nt){                    
                
                // Article has that tag
                $hasTag = false;
                if($terms_tags){
                    foreach($terms_tags as $tt){
                        if($nt->term_id == $tt->term_id){
                            $hasTag = true;
                            break;
                        }
                    }
                }

                // Result based tags check
                if( is_array($user_screen_result['next_step_terms']) ){
                    foreach( $user_screen_result['next_step_terms'] as $nst ){
                        $nst_term = get_term( $nst, 'post_tag' );
                        if($nst_term && $nt->term_id == $nst_term->term_id){
                            $hasTag = true;
                            break;
                        }
                    }
                }

                if($hasTag){
                    $rel_score = $rel_score + get_field('scoring_tag', 'options');
                    $related_articles[$article_id]['score_debug'] .= 'Tag ';
                }
                if($primary_condition && $nt->term_id == $primary_condition->term_id){
                    $rel_score = $rel_score + get_field('scoring_primary_tag', 'options');
                    $related_articles[$article_id]['score_debug'] .= 'HasTag ';
                }

                // Youth and Age 
                if($nt->slug == 'youth' && strpos(strtolower(get_the_title($user_screen_result['screen_id'])), 'youth') === false && strpos(strtolower(get_the_title($user_screen_result['screen_id'])), 'parent') === false ){
                    if($age_check == 'over'){
                        $rel_score = $rel_score - get_field('scoring_over_18', 'options');
                        $related_articles[$article_id]['score_debug'] .= 'Over18 ';
                    }
                    if($age_check == 'under'){
                        $rel_score = $rel_score + get_field('scoring_under_18', 'options');
                        $related_articles[$article_id]['score_debug'] .= 'Under18 ';
                    }
                }

                // LGBTQ+
                if($nt->slug == 'lgbtq' && $user_demo_lgbtq){
                    $rel_score = $rel_score + get_field('scoring_lgbtq', 'options');
                    $related_articles[$article_id]['score_debug'] .= 'LGBTQ+ ';
                }

                // BIPOC
                if($nt->slug == 'bipoc' && $user_demo_bipoc){
                    $rel_score = $rel_score + get_field('scoring_bipoc', 'options');
                    $related_articles[$article_id]['score_debug'] .= 'BIPOC ';
                }

                // Caregiver
                if($nt->slug == 'caregiver' && $user_demo_caregiver){
                    $rel_score = $rel_score + get_field('scoring_caregiver', 'options');
                    $related_articles[$article_id]['score_debug'] .= 'Caregiver ';
                }

            }
        endif;

        
        // Article Type Scoring
        $article_type = get_field('type');            
        if($article_type && is_array($article_type)){

            // Español override
            if($args['espanol']){
                if(get_field('espanol')){
                    $rel_score = $rel_score + 1;
                    $related_articles[$article_id]['score_debug'] .= 'SharesEspanol ';
                } else {
                    unset($related_articles[$article_id]);
                    continue;
                }
            }

            else if(count(array_intersect( array('diy','connect','provider'), $article_type)) > 0){
                $article_ages = get_the_terms( $article_id, 'age_group' );

                $user_no_age = true;
                $user_under_11 = false;
                $user_11_17 = false;
                $user_18_up = false;

                foreach($user_demo_ages as $uda){
                    if($uda <= 11){
                        $user_under_11 = true;
                        $user_no_age = false;
                    } else if($uda < 18){
                        $user_11_17 = true;
                        $user_no_age = false;
                    } if($uda >= 18){
                        $user_under_11 = true;
                        $user_no_age = false;
                    }     
                }
                
                if($article_ages):
                    foreach($article_ages as $a){
                        if($a->slug == 'under-11' && $user_under_11 == false || $a->slug == 'under-11' && $user_no_age == true){
                            unset($related_articles[$article_id]);
                            continue;
                        }

                        if($a->slug == '11-17' && $user_under_11 == false || $a->slug == '11-17' && $user_no_age == true){
                            unset($related_articles[$article_id]);
                            continue;
                        }
                        
                        if($a->slug == 'over-18' && $user_under_11 == false || $a->slug == 'over-18' && $user_no_age == true){
                            unset($related_articles[$article_id]);
                            continue;
                        }
                    }
                endif;

            }

        }

        
        if(!isset($related_articles[$article_id]['score_debug'])){
            unset($related_articles[$article_id]);
            continue;
        }
        
        $related_articles[$article_id]['id'] = $article_id;
        $related_articles[$article_id]['title'] = get_the_title();
        $related_articles[$article_id]['score'] = $rel_score;
        $related_articles[$article_id]['list_link_class'] = $list_link_class;
        $related_articles[$article_id]['related_link_target'] = $related_link_target;
        $related_articles[$article_id]['related_link'] = $related_link;
        $related_articles[$article_id]['pop'] = array_search($article_id, $pop_array) ? array_search($article_id, $pop_array) : '999';

    endwhile;

    // Sort by score and add to list
    if(!in_array('ras_r', $args['layout'])){
        
        /*
        usort($related_articles, function ($item1, $item2) {
            return $item2['score'] <=> $item1['score'];
        });
        */

        // Sort by score and then secondarily by popular
        array_multisort(
            array_column($related_articles, 'score'), SORT_DESC,
            array_column($related_articles, 'pop'), SORT_ASC,
        $related_articles);
    }

    //$related_articles_display = array_slice($related_articles, 0, $args['total'] - count($list_items));
    $related_articles_display = array_slice($related_articles, 0, $args['total']);
    $ti = 1;

    foreach($related_articles_display as $rad){
        $article_display = '<a class="'.$rad['list_link_class'].' rec-auto"'.$rad['related_link_target'].' href="'.$rad['related_link'].'">'.$rad['title'];
        if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
            // Scoring debug options for Editors
            $article_display .= '<br /><span class="small text-red">(Score: '.$rad['score'].', Popularity: #'.$rad['pop'].') <br /> ['.$rad['score_debug'].']</span>';
        }
        $article_display .= '</a>';
        
        $list_items[] = array(
            'id' => $rad['id'],
            'type' => 'display',
            'html' => $article_display
        );
        $temp_excluded[] = $rad['id'];
        $ti++;
    }

    // Print all the items
    $i = 1;

    foreach($list_items as $item){

        if($i > $args['total']){
            break;
        }
        if($args['skip'] > 0 && $i <= $args['skip']){
            $i++;
            continue;
        }

        if($args['style'] != 'button'){ $return_html .= '<li>'; }
        $return_html .= $item['html'];
        if($args['style'] != 'button'){ $return_html .= '</li>'; }

        $args['excluded_ids'][] = $item['id'];

        $i++;
    }

    // See All Link
    if($args['hide_all'] === false):
        if(get_field('see_all_link', $user_screen_result['screen_id'])){
            $see_all_text = 'See All';
            $see_all_link = get_field('see_all_link', $user_screen_result['screen_id']);
            $see_all_target = '';
            if(get_field('see_all_link_text', $user_screen_result['screen_id'])){
                $see_all_text = get_field('see_all_link_text', $user_screen_result['screen_id']);
            }
            if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                //$see_all_link = add_query_arg( 'partner', $args['partner_var'], $see_all_link );
            }
            if($args['iframe_var']){                                         
                //$see_all_link = add_query_arg( 'iframe','true', $see_all_link );
                $see_all_target = ' target="_blank"';
            }                
            $return_html .=  '<li><a class="caps cerulean plain"'.$see_all_target.' href="'.$see_all_link.'">'.$see_all_text.'</a></li>';
        }
    endif;

    if($args['style'] != 'button'){
        $return_html .=  '</ol>';
    }

    if($args['style'] == 'featured'){
        $return_html .= '</div>';
    }

    $return['html'] = $return_html;
    $return['excluded_ids'] = $args['excluded_ids'];

    // October 2023 Custom Featured Next Steps Return
    if($args['style'] == 'featured'){  

        $link_groups = [];
        $used_links = [];
        $used_counter = 1;
        shuffle($temp_excluded);
        foreach($temp_excluded as $lnk){
            if($used_counter > $args['total']){
                break;
            }
            $link_groups['related_links'][$used_counter] = $lnk;
            $used_links[] = $lnk;
            $used_counter++;
        }
        $results = array(
            'heading' => 'Next Steps',
            'hide_group_titles' => 1,
            'link_groups' => $link_groups,
            'additional_result_text' => [],
            'used_links' => $used_links,
            //'used_counter' => $used_counter,
            //'args_total' => $args['total'],
        );
        return json_encode( $results, false, JSON_UNESCAPED_SLASHES );      

    }

    return $return;

}


/**
 * Original related articles algorithm
 */
function mha_results_related_articles_simple( $args ){                        

    // Args
    $defaults = array(
        'demo_steps'         => array(),
        'next_step_manual'   => array(),
        'user_screen_result' => array(),
        'excluded_ids'       => array(),
        'next_step_terms'    => array(),
        'total'              => 20,
        'espanol'            => '',
        'iframe_var'         => '',
        'partner_var'        => '',
        'skip'               => 0,
        'style'              => 'text',
        'layout'             => array(),
        'hide_all'           => false
    );       

    $exclude_id = [];
    $list_items = [];
    $args = wp_parse_args( $args, $defaults );
    $user_screen_result = $args['user_screen_result'];

    $list_link_class = ($args['style'] == 'button') ? 'button green thin round mr-3 mb-3' : 'dark-gray plain';
    
    /**
     * Demo Based Steps
     */
    foreach($args['demo_steps'] as $link){
        $list_items[] = '<a class="'.$list_link_class.' rec-screen-demobased" href="'.get_the_permalink($link->ID).'">'.$link->post_title.'</a>';
    }

    /**
     * Result Based Manual Steps
     */
    foreach($args['next_step_manual'] as $step){
        $step_link = get_the_permalink($step);
        $step_link_target = '';
        if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
            //$step_link = add_query_arg( 'partner', $args['partner_var'], $step_link );
        }
        if($args['iframe_var']){                                         
            //$step_link = add_query_arg( 'iframe','true', $step_link );
            $step_link_target = ' target="_blank"';
        }
        if(!in_array($step, $args['excluded_ids'])){

            $new_list_item = '<a class="'.$list_link_class.' rec-result-manual"'.$step_link_target.' href="'.$step_link.'">'.get_the_title($step);
            if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
                $new_list_item .= '<br /><span class="small text-red">ID: #'.$iid.'</span> <span class="small text-red">(Result Based Manual Condition)</span>';
            }
            $new_list_item .= '</a>';
            $list_items[] = $new_list_item;

            $exclude_id[] = $step;
        }
    }

    /**
     * Manual Steps
     */
    if( have_rows('featured_next_steps', $user_screen_result['screen_id']) ):
    while( have_rows('featured_next_steps', $user_screen_result['screen_id']) ) : the_row();
        $step = get_sub_field('link');
        if($step && !in_array($step->id, $args['excluded_ids'])){
            $manual_step_link = get_the_permalink($step->ID);
            $manual_step_target = '';
            if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                //$manual_step_link = add_query_arg( 'partner', $args['partner_var'], $manual_step_link );
            }
            if($args['iframe_var']){                                         
                //$manual_step_link = add_query_arg( 'iframe','true', $manual_step_link );
                $manual_step_target = ' target="_blank"';
            }

            $new_list_item = '<a class="'.$list_link_class.' rec-screen-manual"'.$manual_step_target.' href="'.$manual_step_link.'">'.$step->post_title;
            if (current_user_can('edit_posts') && !in_array('ras_r', $args['layout'])) {
                $new_list_item .= '<br /><span class="small text-red">(Manual Condition)</span>';
            }
            $new_list_item .= '</a>';
            $list_items[] = $new_list_item;

            $exclude_id[] = $step->ID;
        }
    endwhile;        
    endif;

    /**
     * Automatic Query Args
     */
    if(!empty($exclude_id)){
        $total_exclude = count($exclude_id);
    } else {
        $total_exclude = 0;
    }
    $total_recs = $args['total'] - $total_exclude;
    $loop_args = array(
        "post_type" => 'article',
        "order"	=> 'ASC',
        "orderby" => 'date',
        "post_status" => 'publish',
        "posts_per_page" => $total_recs,
        "meta_query" => array(
            array(
                "key"       => 'type',
                "value"     => 'condition',
                'compare'   => 'LIKE'
            )
        )
    );

    if($args['espanol']){
        $loop_args['meta_query'] = array(
            array(
                "key" => 'espanol',
                "value" => 1
            )
        );
        //$loop_args['meta_query']['relationship'] = 'AND';
    }


    /**
     * Result Based Next Steps
     */
    if(isset($next_step_terms)){
        $next_step_terms = array_unique($next_step_terms);
        $taxonomy_query = [];
        foreach($next_step_terms as $step){
            $step = get_term($next);
            if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                $taxonomy_query[$step->taxonomy][] = $step->term_id;
            }
        }
    }

    // Demographic based steps
    if(!empty($user_screen_result['result_terms'])){
        foreach($user_screen_result['result_terms'] as $step){ 
            if($step['taxonomy'] == 'condition' || $step['taxonomy'] == 'age_group' || $step['taxonomy'] == 'post_tag'){
                $taxonomy_query[$step['taxonomy']][] = $step['id'];    
            }   
        }
    }

    // Overall screen based steps
    $tags = get_field('related_tags', $user_screen_result['screen_id']);
    if($tags){
        foreach($tags as $step){
            if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                $taxonomy_query[$step->taxonomy][] = $step->term_id;
            }
        }
    }

    // Excluded previous manual 
    if(!empty($exclude_id)){
        $loop_args['post__not_in'] = $exclude_id; 
    }

    // Set up taxonomy query filters
    foreach($taxonomy_query as $k => $v){
        $loop_args['tax_query'][] = array(
            'taxonomy' => $k,
            'field'    => 'term_id',
            'terms'    => $v
        );
    }

    // Make all tags required for multiple taxonomies (e.g. avoid eating disorder articles on depression results 
    // if someone answered 18-25 demographic questions)
    if(count($taxonomy_query) > 1){
        $loop_args['tax_query']['relation'] = 'AND';
    }

    // Automatic Related Article Query
    $loop = new WP_Query($loop_args);
    while($loop->have_posts()) : $loop->the_post();
        $related_link = get_the_permalink();
        $related_link_target = '';
        if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
            //$related_link = add_query_arg( 'partner', $args['partner_var'], $related_link );
        }
        if($args['iframe_var']){                                         
            //$related_link = add_query_arg( 'iframe','true', $related_link );
            $related_link_target = ' target="_blank"';
        }
        if(!in_array(get_the_ID(), $args['excluded_ids'])){
            $list_items[] = '<a class="'.$list_link_class.' rec-auto"'.$related_link_target.' href="'.$related_link.'">'.get_the_title().'</a>';
        }
    endwhile;

    // See All Link
    if($args['hide_all'] === false):
        if(get_field('see_all_link', $user_screen_result['screen_id'])){
            $see_all_text = 'See All';
            $see_all_link = get_field('see_all_link', $user_screen_result['screen_id']);
            $see_all_target = '';
            if(get_field('see_all_link_text', $user_screen_result['screen_id'])){
                $see_all_text = get_field('see_all_link_text', $user_screen_result['screen_id']);
            }
            if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                //$see_all_link = add_query_arg( 'partner', $args['partner_var'], $see_all_link );
            }
            if($args['iframe_var']){                                         
                //$see_all_link = add_query_arg( 'iframe','true', $see_all_link );
                $see_all_target = ' target="_blank"';
            }
            $list_items[] = '<a class="caps cerulean plain"'.$see_all_target.' href="'.$see_all_link.'">'.$see_all_text.'</a>';
        }
    endif;
    
    $i = 1;
    if($args['style'] != 'button'){
        if(!in_array('ras_r', $args['layout'])){
            echo '<ol class="next-steps masonry">';
        } else {
            echo '<ol class="next-steps columned">';
        }
    }
        foreach($list_items as $item){

            // Skip items if its set
            if($args['skip'] > 0 && $i <= $args['skip']){
                $i++;
                continue;
            }

            if($args['style'] != 'button'){ echo '<li>'; }
            echo $item;
            if($args['style'] != 'button'){ echo '</li>'; }

            $i++;
        }
    if($args['style'] != 'button'){
        echo '</ol>';
    }

    return;

}