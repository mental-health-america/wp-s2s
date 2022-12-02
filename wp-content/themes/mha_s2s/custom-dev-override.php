<?php
/* Template Name: Custom Dev Override */
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
	"post_type" 	 => 'article',
	"post_status" 	 => array('publish','draft'),
	"posts_per_page" => 500,
	'tax_query' => array(
		array(
			'taxonomy' => 'condition',
			'field' => 'slug',
			'terms' => array('ptsd'),
			'operator' => 'IN'
		),
	)
);
$loop = new WP_Query($args);
echo 'Total: '.$loop->found_posts;
while($loop->have_posts()) : $loop->the_post();
	$pid = get_the_ID();
	
	// Add new term
	wp_set_post_terms($pid, 118, 'condition', true);

	// Remove old terms
	my_remove_post_term($pid, 92, 'condition');
	my_remove_post_term($pid, 112, 'condition');

	// Display
	the_title('<p>','</p>');
	$terms = get_the_terms( $pid, 'condition');
	foreach($terms as $t){
		if($t->slug == 'ptsd' || $t->slug == 'trauma' || $t->slug == 'trauma-ptsd'){
			echo $t->slug.'<br />';
		}
	}


	echo '<hr />';
endwhile;

get_footer();
