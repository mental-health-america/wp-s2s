<?php

    $type = get_field('type');

    $html = '<a href="'.get_the_permalink().'" class="filter-bubble red">';
    $html .= '<p class="inline m-0">';

    if(get_the_post_thumbnail_url()){
        //$html .= '<span class="block image" style="background-image: url(\''.get_the_post_thumbnail_url().'\');"></span>';
        //$html .= '<span class="block image"><span class="table"><span class="cell"><img src="'.get_the_post_thumbnail_url().'" alt="" /></span></span></span>';        
        $html .= '<span class="image-container"><span class="image-container-inner" style="background-image: url(\''.get_the_post_thumbnail_url().'\');"></span></span>';
        $html .= '<span class="inner-text block">';
        $html .= '<strong class="text-red title caps block mb-3 montserrat bold">'.get_the_title().' </strong>';
    } else {
        $html .= '<span class="title-image image block"><span class="table"><span class="cell"><strong class="text-red caps">'.get_the_title().' </strong></span></span></span>';
        $html .= '<span class="inner-text block">';
    }

    // Custom display based on `type` field
    if(in_array('provider', $type)){
        // Location
        $location = get_field('area_served');
        $location_display = '';
        foreach($location as $loc){
            if($loc == 'local'){
                $loc_location = get_field('location');
                $location_display = 'Local';
                if($loc_location && count($loc_location) > 0) {
                    $local_locations = [];
                    foreach($loc_location as $l){
                        if($l['city'] != '' && $l['state'] != ''){
                            $local_locations[] = $l['city'].', '.$l['state'];
                        }
                    };
                    if(count($local_locations) > 0){
                        $location_display = implode('; ', array_unique($local_locations));
                    }
                }
                
            } else {
                $location_display = ucfirst($loc);
            }
        }

        // Services
        $services_check = get_field('service_type');	
        $services = [];
        if($services_check){						
            foreach($services_check as $service){
                $services[] = $service['label'];
            }
        }
        $html .= '<span class="text-gray excerpt block pb-5">';
        $html .= '<span class="excerpt-text block mb-3">'.short_excerpt().' </span>';
        if (current_user_can('edit_posts')) {
            $html .= '<span class="score block small text-red mb-3">( Score: '.$args['score'].' )<br />[ '.$args['score_labels'].' ]</span>';
        }
        $html .= '<span class="block mb-3"><strong>Location:</strong> '.$location_display.' </span>'; 
        $html .= '<strong>Service Type:</strong> '.implode(', ',$services); 
        $html .= ' </span>'; 
    } else {
        $html .= '<span class="text-gray excerpt block pb-5">'.short_excerpt(35).' </span>'; 
        if (current_user_can('edit_posts')) {
            $html .= '<span class="score block small text-red mb-3">( Score: '.$args['score'].' )<br />[ '.$args['score_labels'].' ]</span>';
        }
    }

    $html .= '<strong class="text-red caps block learn-more"> Learn More </strong>';
    $html .= '<div style="display:none"></div>';
    $html .= '</span>';
    $html .= '</p>';
    $html .= '</a>'; 

    echo $html;
?>