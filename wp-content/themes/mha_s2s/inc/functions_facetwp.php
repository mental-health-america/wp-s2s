<?php

/**
 * FacetWP Integration
 */

add_filter( 'facetwp_template_force_load', '__return_true' );

// Add to main queries
add_filter( 'facetwp_is_main_query', function( $is_main_query, $query ) {
	if ( isset( $query->query_vars['facetwp'] ) ) {
		$is_main_query = true;
	} else {
		$is_main_query = false;
	}
	return $is_main_query;
}, 10, 2 );

// Necessary overrides for taxonomy terms
add_filter( 'facetwp_index_row', function( $params, $class ) {
	if ( 'Conditions' == $params['facet_name'] ) {
		$term_id = (int) $params['term_id'];
		$value = get_term_meta( $term_id, 'related_condition', true );
		$params['facet_display_value'] = $value;
	}
	if ( 'Tags' == $params['facet_name'] ) {
		$term_id = (int) $params['term_id'];
		$value = get_term_meta( $term_id, 'tag', true );
		$params['facet_display_value'] = $value;
	}
	return $params;
}, 10, 2 );

// Staging server login for indexing/testing
add_filter( 'http_request_args', function( $args, $url ) {
    if ( 0 === strpos( $url, get_site_url() ) ) {
        $args['headers'] = [
            'Authorization' => 'Basic ' . base64_encode( 'mhanationalstg:mementos' )
        ];
    }
    return $args;
}, 10, 2 );

// Override search field icon
add_filter( 'facetwp_facet_html', function( $output, $params ) {

	// Search field icon
    if ( 'search' == $params['facet']['type'] ) {
        $output = str_replace( 'facetwp-icon', 'fa fa-search search-icon', $output );
    }

	// Location search text
    if ( 'proximity' == $params['facet']['type'] ) {
        $output = str_replace( 'Enter location', 'Enter your zip code', $output );
    }

	// Sort by design override
    if ( 'sort_by' == $params['facet']['name'] ) {
		// Selection Button
		$output = '<button class="button gray round-br dropdown-toggle normal-case" type="button" id="orderSelection" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">';
			$current_sort = get_query_var('_sort_by');
			$sort_label = 'Default';
			foreach ( $params['facet']['sort_options'] as $key => $atts ) {
				if($atts['name'] == $current_sort){
					$sort_label = $atts['label'];
				}
			}
			$output .= '<span class="mobile-label d-inline d-md-none">Sort</span>';
			$output .= '<span class="desktop-label d-none d-md-inline">'.$sort_label.'</span>';
		$output .= '</button>';

		// Dropdown options
		$output .= '<div class="dropdown-menu" aria-labelledby="orderSelection">';
			$output .= '<button class="dropdown-item normal-case filter-order sort-order-button" type="button" data-value="" data-type="sort">Default</button>';
			foreach ( $params['facet']['sort_options'] as $key => $atts ) {
				$output .= '<button class="dropdown-item normal-case filter-order sort-order-button" type="button" data-value="' . $atts['name'] . '" data-type="sort">' . $atts['label'] . '</button>';
			}
		$output .= '</div>';
    }
    if ( 'sort_by_location' == $params['facet']['name'] ) {
		// Selection Button
		$output = '<button class="button gray round-br dropdown-toggle normal-case" type="button" id="orderSelection" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">';
			$current_sort = get_query_var('_sort_by');
			$sort_label = 'Default';
			foreach ( $params['facet']['sort_options'] as $key => $atts ) {
				if($atts['name'] == $current_sort){
					$sort_label = $atts['label'];
				}
			}
			$output .= '<span class="mobile-label d-inline d-md-none">Sort</span>';
			$output .= '<span class="desktop-label d-none d-md-inline">'.$sort_label.'</span>';
		$output .= '</button>';

		// Dropdown options
		$output .= '<div class="dropdown-menu" aria-labelledby="orderSelection">';
			$output .= '<button class="dropdown-item normal-case filter-order sort-order-button" type="button" data-value="" data-type="sort">Default</button>';
			foreach ( $params['facet']['sort_options'] as $key => $atts ) {
				$output .= '<button class="dropdown-item normal-case filter-order sort-order-button" type="button" data-value="' . $atts['name'] . '" data-type="sort">' . $atts['label'] . '</button>';
			}
		$output .= '</div>';
    }

    return $output;
}, 10, 2 );

// Override specific labels
add_filter( 'facetwp_facet_display_value', function( $label, $params ) {
    if ( 'language' == $params['facet']['name'] && 'Yes' == $label ) {
        $label = 'Español';
    }
    if ( 'language' == $params['facet']['name'] && 'No' == $label ) {
        $label = 'English';
    }
    if ( 'general_mental_health' == $params['facet']['name'] && '1' == $label ) {
        $label = 'General Mental Health';
    }
    return $label;
}, 10, 2 );

// Custom facetWP Search dropdown triggers
add_action( 'wp_head', function() {
?>
	<script>
		
		(function($) {

			$(document).on('click', '.sort-order-button', function() {
				var val = $(this).attr('data-value');
				FWP.facets['sort_by'] = [val];
				FWP.toggleOverlay('on'); 
				FWP.fetchData();
				FWP.setHash();
			});

			$(document).on('click', '.language-toggle', function() {
				if(FWP.facets.language[0] == 1 || FWP.facets.language[0] == '1'){
					FWP.facets['language'] = ['0'];
					$(this).removeClass('checked');
				}
				else if(FWP.facets.language === undefined || FWP.facets.language.length == 0 || FWP.facets.language[0] == '0'){
					FWP.facets['language'] = ['1'];
					$(this).addClass('checked');
				}
				FWP.fetchData();
				FWP.setHash();
			});

			/**
			 * FacetWP Content Animations
			 */
			$(document).on('facetwp-refresh', function() {
				$('.facetwp-template').addClass('loaded').animate({ opacity: .25 }, 150);
			});
			$(document).on('facetwp-loaded', function() {
				$('.facetwp-template').addClass('loaded').animate({ opacity: 1 }, 150);
			});


			var firstLoad = 0;			
			$(document).on('facetwp-loaded', function() {

				// Change the current sort display on the dropdown
				if ('undefined' !== typeof FWP.facets['sort_by']) {
					$('.sort-order-button').filter('[data-value="' + FWP.facets['sort_by'] + '"]').addClass("checked");
					let current_sort = $('.sort-order-button').filter('[data-value="' + FWP.facets['sort_by'] + '"]').text();
					$('#orderSelection .desktop-label').text(current_sort);
				}

				// Toggle the fake Espanol checkbox
				if ('undefined' !== typeof FWP.facets['language']) {
					$('.sort-order-button').filter('[data-value="' + FWP.facets['sort_by'] + '"]').addClass("checked");					
				}

				// Location searches; show/hide #geo-search-message if a zip code is used
				if ('undefined' !== typeof FWP.facets['location_search'] && FWP.facets['location_search'].length > 0 && FWP.settings.pager.total_rows > 0) {
					var geo_search_current = $('input.facetwp-location').val();
					$('#geo-search-current').text(geo_search_current);
					$('#geo-search-message').show();
				} else {
					$('#geo-search-message').hide();
				}

			});
		})(jQuery);
	</script>
	<?php
}, 100 );


// Reset Area Served when using a location search
add_action( 'wp_footer', function() {
?>
    <script>
        document.addEventListener('facetwp-refresh', function() {
            if(FWP.facets.hasOwnProperty('location_search') && FWP.facets['location_search'].length > 0){
                FWP.facets['area_served'] = [];
            }
        });

		var firstLoad = 0;
		document.addEventListener('facetwp-loaded', function() {
			// Force load location searches from URL sharing, otherwise no results are displayed
			if( firstLoad < 3 && FWP.facets.hasOwnProperty('location_search') && FWP.facets['location_search'].length > 0 ){
				FWP.fetchData();
				firstLoad++;
			}	
		});
    </script>
<?php
}, 100 );

// English default facet
add_filter( 'facetwp_preload_url_vars', function( $url_vars ) {
    if ( 'diy' == FWP()->helper->get_uri() ) {
        if ( empty( $url_vars['language'] ) ) {
            $url_vars['language'] = [ '0' ];
        }
    }	
    if ( 'get-help' == FWP()->helper->get_uri() ) {
        if ( empty( $url_vars['area_served'] ) ) {
            $url_vars['area_served'] = [ 'national' ];
        }
    }
    return $url_vars;
} );

// Limit Location Search to just zip codes and the US
add_filter( 'facetwp_proximity_autocomplete_options', function( $options ) {	
    $options['types'] = ['postal_code'];
    $options['componentRestrictions'] = [
        'country' => ['us'],
    ];

    return $options;
});

 // Minimum 5 characters for zip codes
add_filter( 'facetwp_assets', function( $assets ) {
    FWP()->display->json['proximity']['minLength'] = 5;
    return $assets;
} );