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
	if ( 'general_mental_health' == $params['facet_name'] ) {
        // If the value is 0 (false), prevent it from being indexed
        if ( 0 == $params['facet_value'] || '0' == $params['facet_value'] || 'No' == $params['facet_value'] || 'no' == $params['facet_value'] ) {
            $params['facet_display_value'] = ''; // Set display value to empty
            $params['facet_value'] = '';         // Set value to empty
        }
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

// FacetWP label overrides
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

			/**
			 * FacetWP sorting
			 */
			$(document).on('click', '.sort-order-button', function() {
				var val = $(this).attr('data-value');
				FWP.facets['sort_by'] = [val];
				FWP.toggleOverlay('on'); 
				FWP.fetchData();
				FWP.setHash();
			});

			/**
			 * FacetWP language toggle
			 */
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
				// Clear combined container so it rebuilds after refresh
				$('.facetwp-facet-combined-conditions-tags').html('');
				$('.facetwp-facet-conditions, .facetwp-facet-tag').show();
				// Mark that we're refreshing to prevent clicks during refresh
				$(document).data('facetwp-refreshing', true);
			});
			$(document).on('facetwp-loaded', function() {
				$('.facetwp-template').addClass('loaded').animate({ opacity: 1 }, 150);
			});

			/**
			 * FacetWP Loaded
			 */
			$(document).on('facetwp-loaded', function() {
				
				// Prevent rapid rebuilds of combined container
				if ($(document).data('rebuilding-combined-container')) {
					return;
				}

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
				if ('undefined' !== typeof FWP.facets['location_search'] && FWP.facets['location_search'].length > 0 && FWP.settings && FWP.settings.pager && FWP.settings.pager.total_rows > 0) {
					var $locationInput = $('input.facetwp-location');
					var geo_search_current = $locationInput.length > 0 ? ($locationInput.val() || '') : '';
					if (geo_search_current && $('#geo-search-current').length > 0) {
						$('#geo-search-current').text(geo_search_current);
					}
					if ($('#geo-search-message').length > 0) {
						$('#geo-search-message').show();
					}
				} else {
					if ($('#geo-search-message').length > 0) {
						$('#geo-search-message').hide();
					}
				}

				// Combine conditions and tags facets
				var $combinedContainer = $('.facetwp-facet-combined-conditions-tags');
				if ($combinedContainer.length > 0) {
					// Mark as rebuilding
					$(document).data('rebuilding-combined-container', true);
					var limit = parseInt($combinedContainer.data('limit')) || 7;
					var $conditionsFacet = $('.facetwp-facet-conditions');
					var $tagsFacet = $('.facetwp-facet-tag');
					
					if ($conditionsFacet.length > 0 || $tagsFacet.length > 0) {
						var allCombinedItems = [];
						
						// Get conditions items
						$conditionsFacet.find('.facetwp-checkbox').each(function() {
							var $item = $(this);
							var counter = parseInt($item.find('.facetwp-counter').text().replace(/[^0-9]/g, '')) || 0;
							var $temp = $('<div>').html($item[0].outerHTML);
							$temp.find('.facetwp-checkbox').attr('data-facet-name', 'conditions');
							allCombinedItems.push({
								html: $temp.html(),
								counter: counter,
								facet: 'conditions',
								value: $item.data('value')
							});
						});
						
						// Get tags items
						$tagsFacet.find('.facetwp-checkbox').each(function() {
							var $item = $(this);
							var counter = parseInt($item.find('.facetwp-counter').text().replace(/[^0-9]/g, '')) || 0;
							var $temp = $('<div>').html($item[0].outerHTML);
							$temp.find('.facetwp-checkbox').attr('data-facet-name', 'tag');
							allCombinedItems.push({
								html: $temp.html(),
								counter: counter,
								facet: 'tag',
								value: $item.data('value')
							});
						});
						
						// Sort by counter (descending)
						allCombinedItems.sort(function(a, b) {
							return b.counter - a.counter;
						});
						
						// Function to render items
						function renderItems(items, showAll) {
							var itemsToShow = showAll ? items : items.slice(0, limit);
							var combinedHtml = '';
							itemsToShow.forEach(function(item) {
								combinedHtml += item.html;
							});
							
							// Add "View all topics" button if there are more items than limit
							if (!showAll && items.length > limit) {
								combinedHtml += '<button type="button" class="view-all-topics-btn plain small mt-2 mb-2" style="display: block; width: 100%; text-align: left; padding: 0.5rem 0; color: inherit; border: none; background: none; cursor: pointer;">View all topics</button>';
							} else if (showAll && items.length > limit) {
								combinedHtml += '<button type="button" class="view-all-topics-btn plain small mt-2 mb-2" style="display: block; width: 100%; text-align: left; padding: 0.5rem 0; color: inherit; border: none; background: none; cursor: pointer;">Show less</button>';
							}
							
							$combinedContainer.html(combinedHtml);
							
							// Handle "View all topics" button click
							$combinedContainer.find('.view-all-topics-btn').on('click', function(e) {
								e.preventDefault();
								var $btn = $(this);
								if ($btn.text().indexOf('Show less') !== -1) {
									renderItems(allCombinedItems, false);
								} else {
									renderItems(allCombinedItems, true);
								}
								return false;
							});
						}
						
						// Initial render (show top 7)
						renderItems(allCombinedItems, false);
						
						// Handle clicks on combined items - trigger clicks on original hidden facets
						// Use off() first to prevent duplicate handlers, then on() for delegated event handling
						$combinedContainer.off('click', '.facetwp-checkbox').on('click', '.facetwp-checkbox', function(e) {
							e.preventDefault();
							e.stopPropagation();
							
							// Prevent clicks during refresh or rebuild
							if ($(document).data('facetwp-refreshing') || $(document).data('rebuilding-combined-container')) {
								return false;
							}
							
							// Prevent multiple rapid clicks
							if ($(this).data('click-processing')) {
								return false;
							}
							
							var $checkbox = $(this);
							var facetName = $checkbox.data('facet-name') || $checkbox.attr('data-facet-name');
							var value = $checkbox.data('value');
							
							// Mark as processing
							$checkbox.data('click-processing', true);
							
							// Find the original checkbox
							var $original = null;
							if (facetName === 'conditions') {
								$original = $conditionsFacet.find('.facetwp-checkbox[data-value="' + value + '"]');
							} else if (facetName === 'tag') {
								$original = $tagsFacet.find('.facetwp-checkbox[data-value="' + value + '"]');
							}
							
							// Only trigger click if original exists
							if ($original.length) {
								// Trigger click on original checkbox
								$original.trigger('click');
							}
							
							// Clear processing flag after a delay to prevent rapid clicks
							setTimeout(function() {
								$checkbox.data('click-processing', false);
							}, 300);
							
							return false;
						});
						
						// Hide original facets if combined container exists (but keep them in DOM for FacetWP)
						$conditionsFacet.css('display', 'none');
						$tagsFacet.css('display', 'none');
					}
					
					// Clear rebuilding flag after a short delay
					setTimeout(function() {
						$(document).data('rebuilding-combined-container', false);
						$(document).data('facetwp-refreshing', false);
					}, 100);
				}

			});
		})(jQuery);
	</script>
	<?php
}, 100 );


// Exclude local-only providers when location_search is not active
// Using facetwp_query_args filter as recommended by FacetWP documentation
add_filter( 'facetwp_query_args', function( $query_args, $class ) {
	// Only apply to provider queries - check if type includes provider
	$is_provider_query = false;
	
	// Check meta_query for type = provider
	if ( isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
		foreach ( $query_args['meta_query'] as $meta_query ) {
			if ( isset( $meta_query['key'] ) && $meta_query['key'] === 'type' ) {
				if ( isset( $meta_query['value'] ) && strpos( $meta_query['value'], 'provider' ) !== false ) {
					$is_provider_query = true;
					break;
				}
			}
		}
	}
	
	// Also check if post_type is article (provider posts are article type)
	// This ensures we catch queries even if meta_query hasn't been set up yet
	if ( ! $is_provider_query && isset( $query_args['post_type'] ) && $query_args['post_type'] === 'article' ) {
		// Check if this is likely a provider query by looking at the context
		// On the providers page, all article queries should be provider queries
		if ( is_page_template( 'page-providers.php' ) || ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'get-help' ) !== false ) ) {
			$is_provider_query = true;
		}
	}
	
	// Only apply to provider queries
	if ( ! $is_provider_query ) {
		return $query_args;
	}
	
	// Check if location_search facet has a value FIRST
	// If active, include all posts for proximity filtering (distance will be calculated)
	$location_search_active = false;
	
	// Check POST data first (for AJAX requests) - this is more reliable
	if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
		parse_str( $_POST['data'], $post_data );
		if ( isset( $post_data['location_search'] ) ) {
			// location_search can be an array [lat, lng, radius, address] or a string
			$location_value = $post_data['location_search'];
			if ( is_array( $location_value ) && ! empty( array_filter( $location_value ) ) ) {
				$location_search_active = true;
			} elseif ( ! is_array( $location_value ) && ! empty( $location_value ) ) {
				$location_search_active = true;
			}
		}
	}
	
	// Fallback: Check URL vars via FacetWP helper (for initial page load)
	if ( ! $location_search_active && class_exists( 'FWP' ) ) {
		try {
			$fwp = FWP();
			if ( $fwp && isset( $fwp->helper ) && method_exists( $fwp->helper, 'get_url_vars' ) ) {
				$url_vars = $fwp->helper->get_url_vars();
				if ( isset( $url_vars['location_search'] ) && ! empty( $url_vars['location_search'] ) ) {
					$location_search_active = true;
				}
			}
		} catch ( Exception $e ) {
			// Silently fail if FacetWP helper is not available
		}
	}
	
	// Also check GET parameter as final fallback (FacetWP uses _location_search with underscore)
	if ( ! $location_search_active && isset( $_GET['_location_search'] ) && ! empty( $_GET['_location_search'] ) ) {
		$location_search_active = true;
	}
	
	// If location_search is active, don't add any filters - let FacetWP handle proximity search
	// This includes ALL posts (both national and local) so proximity filtering can work
	if ( $location_search_active ) {
		return $query_args;
	}
	
	// Check if area_served facet is being used - if so, don't exclude local-only posts
	// This allows "local" to appear as a facet choice
	$area_served_facet_active = false;
	$has_active_facets = false;
	if ( class_exists( 'FWP' ) ) {
		try {
			$fwp = FWP();
			if ( $fwp && isset( $fwp->helper ) && method_exists( $fwp->helper, 'get_url_vars' ) ) {
				$url_vars = $fwp->helper->get_url_vars();
				// Check if any facets are active
				$has_active_facets = ! empty( $url_vars );
				if ( isset( $url_vars['area_served'] ) && ! empty( $url_vars['area_served'] ) ) {
					$area_served_facet_active = true;
				}
			}
		} catch ( Exception $e ) {
			// Silently fail if FacetWP helper is not available
		}
	}
	
	// Also check POST data for area_served
	if ( ! $area_served_facet_active && isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
		if ( ! isset( $post_data ) ) {
			parse_str( $_POST['data'], $post_data );
		}
		if ( isset( $post_data['area_served'] ) && ! empty( $post_data['area_served'] ) ) {
			$area_served_facet_active = true;
		}
		// Check if any facets are active in POST data
		if ( ! $has_active_facets ) {
			$has_active_facets = count( $post_data ) > 0;
		}
	}
	
	// If area_served facet is being used, don't exclude local-only posts
	// This allows "local" to appear as a facet choice that users can select
	if ( $area_served_facet_active ) {
		return $query_args;
	}
	
	// Default behavior: Exclude posts with only "local" in area_served
	// This applies to initial page load and when other facets (but not area_served) are active
	// We want to show posts that have "national" in area_served (or both national and local)
	// Note: We don't check $has_active_facets here because we want this exclusion on initial load too
	
	// Ensure meta_query exists
	if ( ! isset( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
		$query_args['meta_query'] = array();
	}
	
	// Store existing relation if set, then remove it from array
	$existing_relation = 'AND';
	$meta_queries = array();
	
	// First pass: collect all meta queries EXCEPT area_served ones
	foreach ( $query_args['meta_query'] as $key => $value ) {
		if ( $key === 'relation' ) {
			$existing_relation = $value;
		} else {
			// Skip any existing area_served filters - we'll add our own
			if ( is_array( $value ) && isset( $value['key'] ) && $value['key'] === 'area_served' ) {
				continue; // Skip this filter, we'll add our own
			}
			// Also check nested queries for area_served
			if ( is_array( $value ) && isset( $value['relation'] ) ) {
				$has_area_served = false;
				foreach ( $value as $sub_key => $sub_value ) {
					if ( is_array( $sub_value ) && isset( $sub_value['key'] ) && $sub_value['key'] === 'area_served' ) {
						$has_area_served = true;
						break;
					}
				}
				if ( $has_area_served ) {
					continue; // Skip nested queries that include area_served
				}
			}
			$meta_queries[] = $value;
		}
	}
	
	// Always add our area_served filter to exclude local-only posts
	// This ensures only posts with 'national' (or no area_served) are included
	$meta_queries[] = array(
		'relation' => 'OR',
		array(
			'key'     => 'area_served',
			'value'   => 'national',
			'compare' => 'LIKE'
		),
		array(
			'key'     => 'area_served',
			'compare' => 'NOT EXISTS'
		)
	);
	
	// Rebuild meta_query - relation only needed if multiple queries
	$query_args['meta_query'] = $meta_queries;
	if ( count( $meta_queries ) > 1 ) {
		$query_args['meta_query']['relation'] = $existing_relation;
	}
	
	return $query_args;
}, 10, 2 );

// Additional filter using posts_where to ensure local-only posts are excluded
// This runs after meta_query and provides a direct SQL exclusion
add_filter( 'posts_where', function( $where, $query ) {
	// Only apply to FacetWP queries that are provider queries
	if ( ! isset( $query->query_vars['facetwp'] ) || ! $query->query_vars['facetwp'] ) {
		return $where;
	}
	
	// Check if this is a provider query
	$is_provider_query = false;
	if ( isset( $query->query_vars['meta_query'] ) && is_array( $query->query_vars['meta_query'] ) ) {
		foreach ( $query->query_vars['meta_query'] as $meta_query ) {
			if ( isset( $meta_query['key'] ) && $meta_query['key'] === 'type' ) {
				if ( isset( $meta_query['value'] ) && strpos( $meta_query['value'], 'provider' ) !== false ) {
					$is_provider_query = true;
					break;
				}
			}
		}
	}
	
	if ( ! $is_provider_query ) {
		return $where;
	}
	
	// Check if location_search is active - if so, don't exclude local posts
	$location_search_active = false;
	
	// Check POST data first (for AJAX requests)
	if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
		parse_str( $_POST['data'], $post_data );
		if ( isset( $post_data['location_search'] ) && ! empty( $post_data['location_search'] ) ) {
			$location_search_active = true;
		}
	}
	
	// Check FacetWP's internal facets array
	if ( ! $location_search_active && class_exists( 'FWP' ) ) {
		try {
			$fwp = FWP();
			if ( $fwp && isset( $fwp->facets ) && isset( $fwp->facets['location_search'] ) && ! empty( $fwp->facets['location_search'] ) ) {
				$location_search_active = true;
			}
		} catch ( Exception $e ) {
			// Silently fail
		}
	}
	
	// Check URL vars via FacetWP helper (for initial page load)
	if ( ! $location_search_active && class_exists( 'FWP' ) ) {
		try {
			$fwp = FWP();
			if ( $fwp && isset( $fwp->helper ) && method_exists( $fwp->helper, 'get_url_vars' ) ) {
				$url_vars = $fwp->helper->get_url_vars();
				if ( isset( $url_vars['location_search'] ) && ! empty( $url_vars['location_search'] ) ) {
					$location_search_active = true;
				}
			}
		} catch ( Exception $e ) {
			// Silently fail
		}
	}
	
	// Check GET parameter as final fallback
	if ( ! $location_search_active && isset( $_GET['_location_search'] ) && ! empty( $_GET['_location_search'] ) ) {
		$location_search_active = true;
	}
	
	if ( $location_search_active ) {
		return $where;
	}
	
	global $wpdb;
	
	// Exclude posts where area_served exists and contains 'local' but NOT 'national'
	// This handles serialized checkbox arrays
	// We'll exclude posts that have area_served with 'local' but don't have 'national'
	$where .= " AND NOT EXISTS (
		SELECT 1 FROM {$wpdb->postmeta} pm
		WHERE pm.post_id = {$wpdb->posts}.ID
		AND pm.meta_key = 'area_served'
		AND pm.meta_value LIKE '%local%'
		AND pm.meta_value NOT LIKE '%national%'
	)";
	
	return $where;
}, 10, 2 );

// Reset Area Served when using a location search
add_action( 'wp_footer', function() {
?>
    <script>
        document.addEventListener('facetwp-refresh', function() {
            if(FWP.facets.hasOwnProperty('location_search') && FWP.facets['location_search'].length > 0){
                FWP.facets['area_served'] = [];
				console.log('Refreshed');
            }
        });

		var firstLoad = 0;
		document.addEventListener('facetwp-loaded', function() {
			// Force load location searches from URL sharing, otherwise no results are displayed
			if( firstLoad < 3 && FWP.facets.hasOwnProperty('location_search') && FWP.facets['location_search'].length > 0 ){
				FWP.fetchData();
				firstLoad++;
				console.log('Loaded');
			}	
		});
    </script>
<?php
}, 100 );

// English default facet
add_filter( 'facetwp_preload_url_vars', function( $url_vars ) {
    if ( 'diy' == FWP()->helper->get_uri() || 'diy-tools' == FWP()->helper->get_uri() ) {
        if ( empty( $url_vars['language'] ) ) {
            $url_vars['language'] = [ '0' ];
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

/**
 * Display combined conditions and tags facets as a single sorted list (top 7)
 * This creates a container that JavaScript will populate
 */
function facetwp_display_combined_conditions_tags( $limit = 7 ) {
	return '<div class="facetwp-facet-combined-conditions-tags" data-limit="' . esc_attr( $limit ) . '"></div>';
}

/** ACF Google Maps API Key */
function my_acf_google_map_api( $api ){
    $api['key'] = GOOGLE_API_KEY;
    return $api;
}
add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

/**
 * Ensure ALL published articles are indexed for ALL facets
 * This removes restrictive filters (like area_served and type) during indexing
 * so all published articles are indexed regardless of their type or area_served value
 */
add_filter( 'facetwp_indexer_query_args', function( $query_args ) {
	// Limit indexing to article post types only
	$query_args['post_type'] = array('article','screen','diy','page',);
	$query_args['post_status'] = 'publish';

	// Remove restrictive filters during indexing - we want ALL published articles indexed
	// This ensures facets like diy_type, condition, etc. index ALL articles, not just providers
	if ( isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ) {
		$meta_queries = array();
		$relation = isset( $query_args['meta_query']['relation'] ) ? $query_args['meta_query']['relation'] : 'AND';
		
		foreach ( $query_args['meta_query'] as $key => $meta_query ) {
			if ( $key === 'relation' ) {
				continue;
			}
			
			// Remove area_served filters during indexing - we want ALL posts indexed
			// regardless of whether they're national or local
			if ( isset( $meta_query['key'] ) && $meta_query['key'] === 'area_served' ) {
				continue;
			}
			
			// Remove type filters during indexing - we want ALL article types indexed
			// (provider, diy, condition, connect, treatment) for ALL facets
			if ( isset( $meta_query['key'] ) && $meta_query['key'] === 'type' ) {
				continue;
			}
			
			// Keep all other meta queries
			$meta_queries[] = $meta_query;
		}
		
		// Update meta_query if we removed any filters
		if ( count( $meta_queries ) !== count( $query_args['meta_query'] ) - ( isset( $query_args['meta_query']['relation'] ) ? 1 : 0 ) ) {
			$query_args['meta_query'] = $meta_queries;
			if ( count( $meta_queries ) > 1 ) {
				$query_args['meta_query']['relation'] = $relation;
			} elseif ( count( $meta_queries ) === 0 ) {
				// Remove meta_query entirely if it's empty
				unset( $query_args['meta_query'] );
			}
		}
	}
	
	return $query_args;
}, 5, 1 );

/**
 * Index ACF repeater location fields for proximity search
 * This processes the repeater field and creates index entries
 * Priority 5 to run before other filters that might interfere
 */
// PREVENT FacetWP from indexing location_search with default behavior
// Handle custom indexing for location_search facet
add_filter( 'facetwp_index_row', function( $params, $class ) {
    // Check if this is the location_search facet (case-insensitive check)
    $facet_name = isset( $params['facet_name'] ) ? $params['facet_name'] : '';
    
    // Only handle location_search facet
    if ( strtolower( $facet_name ) !== 'location_search' ) {
        return $params; // Allow other facets to index normally
    }
    
    // Use a static flag to ensure we only index once per session
    static $location_indexed = false;
    
    if ( ! $location_indexed ) {
        $location_indexed = true;
        
        // Clear ALL existing location_search entries first (including any hash values)
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->prefix}facetwp_index WHERE facet_name = 'location_search'" );
        
        // Get all published provider posts
        $provider_posts = get_posts( array(
            'post_type' => 'article',
            'post_status' => 'publish',
            'meta_key' => 'type',
            'meta_value' => 'provider',
            'meta_compare' => 'LIKE',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ) );
        
        // Index each provider post that has location data
        foreach ( $provider_posts as $post_id ) {
            // Get all location repeater rows using get_field() which returns array format
            $location_rows = get_field( 'location', $post_id );
            
            if ( $location_rows && is_array( $location_rows ) ) {
                foreach ( $location_rows as $row_index => $location_row ) {
                    // Try field names first, then fallback to field IDs
                    $lat = '';
                    $lng = '';
                    
                    if ( isset( $location_row['latitude'] ) ) {
                        $lat = $location_row['latitude'];
                    } elseif ( isset( $location_row['field_5fd3efa5c74a5'] ) ) {
                        $lat = $location_row['field_5fd3efa5c74a5'];
                    }
                    
                    if ( isset( $location_row['longitude'] ) ) {
                        $lng = $location_row['longitude'];
                    } elseif ( isset( $location_row['field_5fd3efaac74a6'] ) ) {
                        $lng = $location_row['field_5fd3efaac74a6'];
                    }

                    // Skip if lat/lng are empty or invalid
                    if ( '' === trim((string) $lat) || '' === trim((string) $lng) ) {
                        continue;
                    }
                    
                    // Validate that lat/lng are numeric
                    $lat = floatval( trim( $lat ) );
                    $lng = floatval( trim( $lng ) );
                    
                    if ( empty( $lat ) || empty( $lng ) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
                        continue;
                    }

                    // Build the row data for this location
                    $row = array(
                        'post_id' => $post_id,
                        'facet_name' => 'location_search',
                        'facet_value' => $lat . ',' . $lng,
                        'facet_display_value' => '',
                        'term_id' => 0,
                        'parent_id' => 0,
                        'depth' => 0,
                    );

                    // Optional: show something readable - get from array directly
                    $addr = isset( $location_row['address'] ) ? $location_row['address'] : '';
                    $city = isset( $location_row['city'] ) ? $location_row['city'] : '';
                    $state = isset( $location_row['state'] ) ? $location_row['state'] : '';
                    $display_parts = array_filter( array( $addr, $city, $state ) );
                    $row['facet_display_value'] = ! empty( $display_parts ) ? implode( ' ', $display_parts ) : $lat . ',' . $lng;

                    // Use direct database insert for reliability
                    $wpdb->insert(
                        $wpdb->prefix . 'facetwp_index',
                        $row,
                        array( '%d', '%s', '%s', '%s', '%d', '%d', '%d' )
                    );
                }
            }
        }
    }
    
    // CRITICAL: Return false to prevent FacetWP from storing default/hashed values
    // This must be returned AFTER we've done our custom indexing
    return false;
}, 1, 2 ); // Priority 1 - run FIRST, before all other filters

/**
 * Manually index all provider posts with locations
 * This function can be called from multiple hooks to ensure it runs
 */
function facetwp_index_location_data() {
    global $wpdb;
    
    // Logging
    $log_file = WP_CONTENT_DIR . '/location-indexing-debug.log';
    $log = function( $message, $data = null ) use ( $log_file ) {
        $timestamp = date( 'Y-m-d H:i:s' );
        $log_entry = "[{$timestamp}] {$message}";
        if ( $data !== null ) {
            $log_entry .= "\n" . print_r( $data, true );
        }
        $log_entry .= "\n" . str_repeat( '-', 80 ) . "\n";
        file_put_contents( $log_file, $log_entry, FILE_APPEND );
    };
    
    $log( "=== FACETWP_INDEX_LOCATION_DATA CALLED ===" );
    
    // Use a transient to prevent duplicate runs within 2 seconds
    $transient_key = 'facetwp_location_indexing';
    if ( get_transient( $transient_key ) ) {
        $log( "Skipping - transient lock active" );
        return;
    }
    set_transient( $transient_key, true, 2 ); // 2 second lock
    
    // Clear ALL existing location_search entries first (including any hash values)
    $deleted = $wpdb->query( "DELETE FROM {$wpdb->prefix}facetwp_index WHERE facet_name = 'location_search'" );
    $log( "Deleted {$deleted} existing location_search entries" );
    
    // Get all published provider posts
    $provider_posts = get_posts( array(
        'post_type' => 'article',
        'post_status' => 'publish',
        'meta_key' => 'type',
        'meta_value' => 'provider',
        'meta_compare' => 'LIKE',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ) );
    
    $log( "Found " . count( $provider_posts ) . " provider posts to index" );
    
    if ( empty( $provider_posts ) ) {
        $log( "No provider posts found - exiting" );
        return;
    }
    
    $table_name = $wpdb->prefix . 'facetwp_index';
    $inserted_count = 0;
    
    // Index each provider post that has location data
    foreach ( $provider_posts as $post_id ) {
        // Get all location repeater rows using get_field() which returns array format
        $location_rows = get_field( 'location', $post_id );
        
        if ( ! $location_rows || ! is_array( $location_rows ) ) {
            continue;
        }
        
        $log( "Post #{$post_id} has " . count( $location_rows ) . " location rows" );
        
        foreach ( $location_rows as $row_index => $location_row ) {
            // Try field names first, then fallback to field IDs
            $lat = '';
            $lng = '';
            
            if ( isset( $location_row['latitude'] ) ) {
                $lat = $location_row['latitude'];
            } elseif ( isset( $location_row['field_5fd3efa5c74a5'] ) ) {
                $lat = $location_row['field_5fd3efa5c74a5'];
            }
            
            if ( isset( $location_row['longitude'] ) ) {
                $lng = $location_row['longitude'];
            } elseif ( isset( $location_row['field_5fd3efaac74a6'] ) ) {
                $lng = $location_row['field_5fd3efaac74a6'];
            }
            
            $log( "Post #{$post_id}, Row {$row_index} - lat: {$lat}, lng: {$lng}" );

            // Skip if lat/lng are empty or invalid
            if ( '' === trim((string) $lat) || '' === trim((string) $lng) ) {
                $log( "Skipping - empty lat/lng" );
                continue;
            }
            
            // Validate that lat/lng are numeric
            $lat = floatval( trim( $lat ) );
            $lng = floatval( trim( $lng ) );
            
            if ( empty( $lat ) || empty( $lng ) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
                $log( "Skipping - invalid lat/lng values" );
                continue;
            }

            // Build display value - get from array directly
            $addr = isset( $location_row['address'] ) ? $location_row['address'] : '';
            $city = isset( $location_row['city'] ) ? $location_row['city'] : '';
            $state = isset( $location_row['state'] ) ? $location_row['state'] : '';
            $display_parts = array_filter( array( $addr, $city, $state ) );
            $display_value = ! empty( $display_parts ) ? implode( ' ', $display_parts ) : $lat . ',' . $lng;
            
            // Insert directly into database
            $facet_value = $lat . ',' . $lng;
            
            $result = $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'facet_name' => 'location_search',
                    'facet_value' => $facet_value,
                    'facet_display_value' => $display_value,
                    'term_id' => 0,
                    'parent_id' => 0,
                    'depth' => 0,
                    'variation_id' => 0
                ),
                array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d' )
            );
            
            if ( $result ) {
                $inserted_count++;
                $log( "Inserted: Post #{$post_id}, {$facet_value}" );
            } else {
                $log( "Failed to insert: Post #{$post_id}, Error: " . $wpdb->last_error );
            }
        }
    }
    
    $log( "Total inserted: {$inserted_count} location entries" );
    
    // Verify final count
    $final_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}facetwp_index WHERE facet_name = 'location_search'" );
    $log( "Final location_search entries in database: {$final_count}" );
}

// Backup: Also call indexing from hooks in case facetwp_index_row filter doesn't get called
// This ensures indexing happens even if FacetWP doesn't process location_search through the filter
add_action( 'facetwp_indexer_start', function() {
    // Check if location_search is already indexed (avoid duplicates)
    global $wpdb;
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}facetwp_index WHERE facet_name = 'location_search' AND facet_value REGEXP '^-?[0-9]+\\.[0-9]+,-?[0-9]+\\.[0-9]+$'" );
    if ( $count > 0 ) {
        return; // Already indexed by the filter
    }
    facetwp_index_location_data();
}, 5 ); // Low priority to run early

add_action( 'facetwp_indexer_complete', function() {
    // Final check: if location_search wasn't indexed, do it now
    global $wpdb;
    $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}facetwp_index WHERE facet_name = 'location_search' AND facet_value REGEXP '^-?[0-9]+\\.[0-9]+,-?[0-9]+\\.[0-9]+$'" );
    if ( $count == 0 ) {
        facetwp_index_location_data();
    }
}, 999 ); // High priority to run late

/**
 * Post-indexing cleanup: Remove any hash values that might have been inserted
 * This runs AFTER FacetWP completes indexing as a safety net to catch any hash values
 * that might have been inserted before our fix, or if FacetWP somehow bypassed our filter
 */
add_action( 'facetwp_indexer_finished', function() {
    global $wpdb;
    
    // Only delete hash values (32-char hex strings) - don't touch valid lat,lng entries
    // Hash values are MD5 hashes from Google Map fields
    $deleted = $wpdb->query(
        "DELETE FROM {$wpdb->prefix}facetwp_index 
         WHERE facet_name = 'location_search' 
         AND LENGTH(facet_value) = 32 
         AND facet_value REGEXP '^[a-f0-9]{32}$'"
    );
    
    // Also clean up any values that don't match the lat,lng pattern (e.g., addresses or other non-coordinate formats)
    $wpdb->query(
        "DELETE FROM {$wpdb->prefix}facetwp_index 
         WHERE facet_name = 'location_search' 
         AND facet_value NOT REGEXP '^-?[0-9]+\\.[0-9]+,-?[0-9]+\\.[0-9]+$'"
    );
}, 1000 ); // Very high priority to run after all indexing

/**
 * Alternative approach: Intercept proximity queries at the query level
 * This runs before facetwp_facet_filter_posts and might catch FacetWP's built-in proximity handling
 */
add_filter( 'facetwp_query_args', function( $query_args, $class ) {
    // Check if location_search is active and handle it ourselves
    if ( ! isset( $query_args['facetwp'] ) || ! $query_args['facetwp'] ) {
        return $query_args;
    }
    
    // Check for location_search in POST data or GET params
    $location_value = null;
    if ( isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
        parse_str( $_POST['data'], $post_data );
        if ( isset( $post_data['location_search'] ) && ! empty( $post_data['location_search'] ) ) {
            $location_value = $post_data['location_search'];
        }
    }
    
    if ( empty( $location_value ) && isset( $_GET['_location_search'] ) && ! empty( $_GET['_location_search'] ) ) {
        $location_value = $_GET['_location_search'];
    }
    
    // If location_search is active, we'll handle it in the filter, so don't modify query here
    // This just ensures our filter gets called
    return $query_args;
}, 5, 2 );

/**
 * Custom proximity filter for ACF repeater locations
 * Manually calculates distances to handle Google Places API issues
 */
add_filter( 'facetwp_facet_filter_posts', function( $post_ids, $class ) {
    // Logging function
    $log_file = WP_CONTENT_DIR . '/proximity-filter-debug.log';
    $log = function( $message, $data = null ) use ( $log_file ) {
        $timestamp = date( 'Y-m-d H:i:s' );
        $log_entry = "[{$timestamp}] {$message}";
        if ( $data !== null ) {
            $log_entry .= "\n" . print_r( $data, true );
        }
        $log_entry .= "\n" . str_repeat( '-', 80 ) . "\n";
        file_put_contents( $log_file, $log_entry, FILE_APPEND );
    };
    
    $log( "=== FACETWP_FACET_FILTER_POSTS CALLED ===" );
    $log( "Initial post_ids:", $post_ids );
    
    // Log the class parameter type and contents
    $class_type = gettype( $class );
    $log( "Class parameter type: {$class_type}" );
    
    // Safely access the facet information
    $facet = null;
    $facet_name = '';
    $facet_type = '';
    
    // Handle both object and array cases
    if ( is_object( $class ) ) {
        $log( "Class is an object: " . get_class( $class ) );
        $class_props = get_object_vars( $class );
        $log( "All class object properties:", $class_props );
        
        // Method 1: Check class->facet property
        if ( isset( $class->facet ) && is_array( $class->facet ) ) {
            $facet = $class->facet;
            $facet_name = isset( $facet['name'] ) ? $facet['name'] : '';
            $facet_type = isset( $facet['type'] ) ? $facet['type'] : '';
            $log( "Found facet from class->facet: name={$facet_name}, type={$facet_type}" );
        }
        
        // Method 2: Check class->facet_name and class->facet_type directly
        if ( empty( $facet_name ) && isset( $class->facet_name ) ) {
            $facet_name = $class->facet_name;
            $facet_type = isset( $class->facet_type ) ? $class->facet_type : '';
            $log( "Found facet from class->facet_name: name={$facet_name}, type={$facet_type}" );
            if ( ! empty( $facet_name ) ) {
                $facet = array( 'name' => $facet_name, 'type' => $facet_type );
            }
        }
        
        // Method 3: Check if facet_name is in the properties array
        if ( empty( $facet_name ) && isset( $class_props['facet_name'] ) ) {
            $facet_name = $class_props['facet_name'];
            $facet_type = isset( $class_props['facet_type'] ) ? $class_props['facet_type'] : '';
            $log( "Found facet from class_props: name={$facet_name}, type={$facet_type}" );
            if ( ! empty( $facet_name ) ) {
                $facet = array( 'name' => $facet_name, 'type' => $facet_type );
            }
        }
    } elseif ( is_array( $class ) ) {
        $log( "Class is an array, contents:", $class );
        
        // Method 1: Check if the array itself is the facet
        if ( isset( $class['name'] ) && isset( $class['type'] ) ) {
            $facet = $class;
            $facet_name = $class['name'];
            $facet_type = $class['type'];
            $log( "Found facet from array directly: name={$facet_name}, type={$facet_type}" );
        }
        
        // Method 2: Check for facet nested inside
        if ( empty( $facet_name ) && isset( $class['facet'] ) && is_array( $class['facet'] ) ) {
            $facet = $class['facet'];
            $facet_name = isset( $facet['name'] ) ? $facet['name'] : '';
            $facet_type = isset( $facet['type'] ) ? $facet['type'] : '';
            $log( "Found facet from class['facet']: name={$facet_name}, type={$facet_type}" );
        }
        
        // Method 3: Check for facet_name key directly
        if ( empty( $facet_name ) && isset( $class['facet_name'] ) ) {
            $facet_name = $class['facet_name'];
            $facet_type = isset( $class['facet_type'] ) ? $class['facet_type'] : '';
            $log( "Found facet from class['facet_name']: name={$facet_name}, type={$facet_type}" );
            if ( ! empty( $facet_name ) ) {
                $facet = array( 'name' => $facet_name, 'type' => $facet_type );
            }
        }
    }
    
    $log( "Final facet info - name: '{$facet_name}', type: '{$facet_type}'" );
    $log( "Final facet array:", $facet );
    
    // Only handle location_search proximity facet
    // Check both the array format and direct string comparison
    $is_location_search = false;
    if ( ! empty( $facet_name ) && 'location_search' === $facet_name ) {
        if ( 'proximity' === $facet_type ) {
            $is_location_search = true;
        } else {
            $log( "Facet name is 'location_search' but type is '{$facet_type}', not 'proximity'" );
        }
    } elseif ( is_array( $facet ) && isset( $facet['name'] ) && 'location_search' === $facet['name'] ) {
        if ( isset( $facet['type'] ) && 'proximity' === $facet['type'] ) {
            $is_location_search = true;
        } else {
            $log( "Facet array has name 'location_search' but type is '{$facet['type']}', not 'proximity'" );
        }
    }
    
    if ( ! $is_location_search ) {
        $log( "Not location_search proximity facet - returning original post_ids" );
        return $post_ids;
    }
    
    $log( "Processing location_search proximity facet" );
    
    // When FacetWP sets up a proximity search, it may pass $post_ids as [0] as a placeholder
    // We need to ignore that and query all posts, then filter by proximity
    // Store original post_ids for reference, but we'll query all indexed locations
    
    // Get the active facet value - try multiple methods
    $location_value = null;
    
    // Method 0: Check the class's value property directly (handles both object and array)
    if ( empty( $location_value ) ) {
        if ( is_object( $class ) && isset( $class->value ) && ! empty( $class->value ) ) {
            $location_value = $class->value;
            $log( "Location value from class->value:", $location_value );
        } elseif ( is_array( $class ) && isset( $class['value'] ) && ! empty( $class['value'] ) ) {
            $location_value = $class['value'];
            $log( "Location value from class['value']:", $location_value );
        }
    }
    
    // Method 1: Check class for selected_values or values property (handles both object and array)
    if ( empty( $location_value ) ) {
        if ( is_object( $class ) ) {
            if ( isset( $class->selected_values ) && ! empty( $class->selected_values ) ) {
                $location_value = $class->selected_values;
                $log( "Location value from class->selected_values:", $location_value );
            } elseif ( isset( $class->values ) && ! empty( $class->values ) ) {
                $location_value = $class->values;
                $log( "Location value from class->values:", $location_value );
            }
        } elseif ( is_array( $class ) ) {
            if ( isset( $class['selected_values'] ) && ! empty( $class['selected_values'] ) ) {
                $location_value = $class['selected_values'];
                $log( "Location value from class['selected_values']:", $location_value );
            } elseif ( isset( $class['values'] ) && ! empty( $class['values'] ) ) {
                $location_value = $class['values'];
                $log( "Location value from class['values']:", $location_value );
            }
        }
    }
    
    // Method 2: Check if facet has selected_values (FacetWP's internal storage)
    // This is the most reliable method when FacetWP passes the facet object
    // Based on debug output, FacetWP passes: ["39.1457139", "-77.067959", "50", "Olney%2C%20MD%2020832%2C%20USA"]
    if ( empty( $location_value ) && isset( $facet['selected_values'] ) && ! empty( $facet['selected_values'] ) ) {
        $location_value = $facet['selected_values'];
        // FacetWP stores it as an array directly, which is what we want
        // But handle it if it's a string
        if ( is_string( $location_value ) ) {
            $decoded = json_decode( $location_value, true );
            if ( is_array( $decoded ) ) {
                $location_value = $decoded;
            } else {
                // Try URL decode
                $decoded = urldecode( $location_value );
                $json_decoded = json_decode( $decoded, true );
                if ( is_array( $json_decoded ) ) {
                    $location_value = $json_decoded;
                }
            }
        }
        // FacetWP passes it as array: [lat, lng, radius, address]
        // Ensure it's an array and has at least 2 elements (lat, lng)
        if ( ! is_array( $location_value ) || count( $location_value ) < 2 ) {
            $location_value = null;
        }
    }
    
    // Method 3: Check POST data (for AJAX requests) - this is more reliable
    if ( empty( $location_value ) && isset( $_POST['data'] ) && is_string( $_POST['data'] ) ) {
        parse_str( $_POST['data'], $post_data );
        if ( isset( $post_data['location_search'] ) ) {
            $location_value = $post_data['location_search'];
            $log( "Location value from POST['data']:", $location_value );
        }
    }
    
    // Method 3b: Check REQUEST data directly (FacetWP might put it there)
    if ( empty( $location_value ) && isset( $_REQUEST['location_search'] ) && ! empty( $_REQUEST['location_search'] ) ) {
        $location_value = $_REQUEST['location_search'];
    }
    
    // Method 3c: Check POST data directly (without parsing data string)
    if ( empty( $location_value ) && isset( $_POST['location_search'] ) && ! empty( $_POST['location_search'] ) ) {
        $location_value = $_POST['location_search'];
    }
    
    // Method 4: Check FacetWP's active values - THIS IS THE MOST IMPORTANT FOR BUILT-IN PROXIMITY
    if ( empty( $location_value ) && class_exists( 'FWP' ) ) {
        try {
            $fwp = FWP();
            $log( "FWP() object exists, checking facets..." );
            if ( $fwp && isset( $fwp->facets ) ) {
                $log( "FWP()->facets:", $fwp->facets );
                if ( isset( $fwp->facets['location_search'] ) ) {
                    $facet_data = $fwp->facets['location_search'];
                    $log( "FWP()->facets['location_search']:", $facet_data );
                    // Check if it has selected_values (from debug output, this is where it's stored)
                    if ( isset( $facet_data['selected_values'] ) && ! empty( $facet_data['selected_values'] ) ) {
                        $location_value = $facet_data['selected_values'];
                        $log( "Location value from FWP()->facets['location_search']['selected_values']:", $location_value );
                    } elseif ( is_array( $facet_data ) && count( $facet_data ) >= 2 ) {
                        // Or it might be passed directly as [lat, lng, radius]
                        $location_value = $facet_data;
                        $log( "Location value from FWP()->facets['location_search'] (direct array):", $location_value );
                    } else {
                        $log( "FWP()->facets['location_search'] exists but doesn't have valid data structure" );
                    }
                } else {
                    $log( "FWP()->facets['location_search'] does not exist" );
                }
            } else {
                $log( "FWP()->facets does not exist" );
            }
        } catch ( Exception $e ) {
            $log( "Error accessing FWP(): " . $e->getMessage() );
        }
    }
    
    // Method 5: Check URL vars via FacetWP helper (for initial page load)
    if ( empty( $location_value ) && class_exists( 'FWP' ) ) {
        try {
            $fwp = FWP();
            if ( $fwp && isset( $fwp->helper ) && method_exists( $fwp->helper, 'get_url_vars' ) ) {
                $url_vars = $fwp->helper->get_url_vars();
                $log( "URL vars from FWP()->helper->get_url_vars():", $url_vars );
                if ( isset( $url_vars['location_search'] ) && ! empty( $url_vars['location_search'] ) ) {
                    $location_value = $url_vars['location_search'];
                    $log( "Location value from URL vars:", $location_value );
                }
            }
        } catch ( Exception $e ) {
            $log( "Error accessing FWP()->helper->get_url_vars(): " . $e->getMessage() );
        }
    }
    
    // Method 6: Check GET parameter (FacetWP uses _location_search with underscore)
    // This is often how FacetWP passes location values on initial page load
    if ( empty( $location_value ) && isset( $_GET['_location_search'] ) && ! empty( $_GET['_location_search'] ) ) {
        $location_value = $_GET['_location_search'];
    }
    
    // If location_value is a string, try to parse it
    if ( ! is_array( $location_value ) && ! empty( $location_value ) ) {
        // First, URL decode in case it's URL-encoded (common with GET parameters)
        $decoded = urldecode( $location_value );
        
        // Try JSON decode first
        $json_decoded = json_decode( $decoded, true );
        if ( is_array( $json_decoded ) ) {
            $location_value = $json_decoded;
        } else {
            // Try splitting by comma (format: "lat,lng,radius,address")
            // The address may contain commas, so we only need first 3 parts (lat, lng, radius)
            $parts = explode( ',', $decoded );
            if ( count( $parts ) >= 2 ) {
                // Take only lat, lng, and optionally radius
                // Address is in parts[3+] but we don't need it for distance calculation
                $location_value = array(
                    trim( $parts[0] ), // lat
                    trim( $parts[1] ), // lng
                    isset( $parts[2] ) ? trim( $parts[2] ) : 50 // radius, default 50
                );
            }
        }
    }
    
    $log( "Final location_value after all checks:", $location_value );
    
    // If no location value, return original post_ids (no filtering)
    if ( empty( $location_value ) || ! is_array( $location_value ) || count( $location_value ) < 2 ) {
        // No location search active, return original post_ids unchanged
        // But if post_ids is [0] (FacetWP placeholder), return empty array
        $log( "No valid location_value found - returning original post_ids" );
        if ( $post_ids === array( 0 ) || ( is_array( $post_ids ) && count( $post_ids ) === 1 && $post_ids[0] === 0 ) ) {
            $log( "post_ids is [0] placeholder - returning empty array" );
            return array();
        }
        return $post_ids;
    }
    
    // Extract lat, lng, and radius from facet value
    // Format: [lat, lng, radius, address] or [lat, lng]
    // Debug shows: ["39.1457139", "-77.067959", "50", "Olney%2C%20MD%2020832%2C%20USA"]
    $search_lat = floatval( $location_value[0] );
    $search_lng = floatval( $location_value[1] );
    $radius = isset( $location_value[2] ) ? floatval( $location_value[2] ) : 50; // Default 50 miles
    
    $log( "Extracted search params - lat: {$search_lat}, lng: {$search_lng}, radius: {$radius}" );
    
    if ( empty( $search_lat ) || empty( $search_lng ) ) {
        $log( "Invalid lat/lng - returning original post_ids" );
        // Invalid lat/lng - if post_ids is [0] placeholder, return empty
        if ( $post_ids === array( 0 ) || ( is_array( $post_ids ) && count( $post_ids ) === 1 && $post_ids[0] === 0 ) ) {
            $log( "post_ids is [0] placeholder - returning empty array" );
            return array();
        }
        return $post_ids;
    }
    
    // Get all indexed locations for the location_search facet
    global $wpdb;
    
    $table_name = esc_sql( $wpdb->prefix . 'facetwp_index' );
    $facet_name = esc_sql( 'location_search' );
    
    // When location_search is active, query ALL indexed posts with locations
    // The initial query might have been filtered, but we want to find ALL matching posts
    // regardless of what was passed in $post_ids
    // This ensures we get both national and local posts that match the proximity search
    $query = "SELECT DISTINCT post_id, facet_value 
              FROM {$table_name} 
              WHERE facet_name = '{$facet_name}'";
    
    $indexed_rows = $wpdb->get_results( $query, ARRAY_A );
    
    $log( "Found " . count( $indexed_rows ) . " indexed location rows" );
    
    if ( empty( $indexed_rows ) ) {
        $log( "No indexed rows found - returning empty array" );
        return array(); // No locations indexed, return empty
    }
    
    // Calculate distance for each post and filter
    $valid_post_ids = array();
    $earth_radius = 3959; // Miles (use 6371 for kilometers)
    
    foreach ( $indexed_rows as $row ) {
        // Parse lat,lng from facet_value
        $coords = explode( ',', $row['facet_value'] );
        if ( count( $coords ) !== 2 ) {
            continue;
        }
        
        $post_lat = floatval( trim( $coords[0] ) );
        $post_lng = floatval( trim( $coords[1] ) );
        
        if ( empty( $post_lat ) || empty( $post_lng ) ) {
            continue;
        }
        
        // Calculate distance using Haversine formula
        $lat_diff = deg2rad( $search_lat - $post_lat );
        $lng_diff = deg2rad( $search_lng - $post_lng );
        
        $a = sin( $lat_diff / 2 ) * sin( $lat_diff / 2 ) +
             cos( deg2rad( $search_lat ) ) * cos( deg2rad( $post_lat ) ) *
             sin( $lng_diff / 2 ) * sin( $lng_diff / 2 );
        
        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
        $distance = $earth_radius * $c;
        
        // If distance is within radius, include this post
        if ( $distance <= $radius ) {
            $valid_post_ids[] = intval( $row['post_id'] );
        }
    }
    
    // Remove duplicates
    $valid_post_ids = array_unique( $valid_post_ids );
    
    $log( "Valid post IDs within radius:", $valid_post_ids );
    $log( "Total valid posts: " . count( $valid_post_ids ) );
    
    // When location_search is active, we want ALL posts within radius
    // Don't limit to $post_ids because those might have been filtered by area_served
    // Return all valid posts that are within the search radius
    // If we got valid posts, return them; otherwise return empty array (no matches)
    if ( ! empty( $valid_post_ids ) ) {
        $log( "Returning valid post IDs: " . implode( ', ', $valid_post_ids ) );
        return $valid_post_ids;
    } else {
        $log( "No posts within radius - returning empty array" );
        // No posts found within radius - return empty array (not original post_ids)
        return array();
    }
}, 10, 2 ); // Priority 10 - run early to ensure we filter before FacetWP applies defaults