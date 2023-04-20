<?php
/* Template Name: Custom Dev Override Tool - PTSD/Trauma conversion */
get_header();

function my_remove_post_term( $post_id, $term, $taxonomy ) {

	if ( ! is_numeric( $term ) ) {
		$term = get_term( $term, $taxonomy );
		if ( ! $term || is_wp_error( $term ) )
			return false;
		$term_id = $term->term_id;
	} else {
		$term_id = $term;
	}

	// Get the existing terms and only keep the ones we don't want removed
	$new_terms = array();
	$current_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );

	foreach ( $current_terms as $current_term ) {
		if ( $current_term != $term_id ){
			$new_terms[] = intval( $current_term );
		}
	}

	return wp_set_object_terms( $post_id, $new_terms, $taxonomy );
}


$args = array(
	"post_type" 	 => 'article', // article, reading_path
	"post_status" 	 => array('publish','draft'),
	"posts_per_page" => 600,
	/*
	'tax_query' => array(
		array(
			'taxonomy' => 'condition',
			'field' => 'slug',
			'terms' => array('ptsd'),
			'operator' => 'IN'
		),
	)
	*/                        
	'meta_query'     => array(
		array(
			'key'       => 'primary_condition',
			'value'     => '92', // 92, 112
			'compare'   => 'LIKE',
		)
	)
);
$loop = new WP_Query($args);
echo 'Total: '.$loop->found_posts;
while($loop->have_posts()) : $loop->the_post();
	$pid = get_the_ID();
	
	// Add new term
	//wp_set_post_terms($pid, 119, 'condition', true); // Trauma & PTSD

	// Remove old terms
	//my_remove_post_term($pid, 92, 'condition'); // PTSD
	//my_remove_post_term($pid, 112, 'condition'); // Trauma

	// Display
	the_title('<p>','</p>');
	$pc = get_field("primary_condition");
	//pre($pc);
	echo $pc->term_id;
	echo $pc->taxonomy;
	/*
	$terms = get_the_terms( $pid, 'condition');
	echo '<ol>';
	foreach($terms as $t){
		if($t->slug == 'ptsd' || $t->slug == 'trauma' || $t->slug == 'trauma-ptsd'){
			echo '<li>'.$t->slug.'</li>';
		}
	}
	echo '</ol>';
	*/


	echo '<hr />';
endwhile;

get_footer();
