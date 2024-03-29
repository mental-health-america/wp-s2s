<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package LCV VF
 * @subpackage LCV VF
 * @since 1.0
 * @version 1.0
 */

 // General vars
$type = get_post_type();
$customClasses = '';
$customContentClasses = '';
//$resources = array('diy','connect','treatment','provider');
$resources = array('diy','connect','provider');
$article_type = get_field('type');
$layout = get_layout_array(get_query_var('layout')); // Used for A/B testing

// Custom styling for resources
if($type == 'article' && count(array_intersect($article_type, $resources)) > 0){
    $customClasses = ' red';
    $customContentClasses = ' content-red';
}

?>

<section class="article-columns clearfix">

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if(!get_field('hero_headline') || !get_field('hero_introduction')): ?>
			<div class="page-heading bar<?php echo $customClasses; ?>">	
			<div class="wrap normal">		
				
                <?php
                    /*
					if ( function_exists('yoast_breadcrumb') ) {
						yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
                    }
                    */
                    get_template_part( 'templates/blocks/breadcrumbs' );
				?>
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				
			</div>
			</div>
		<?php endif; ?>

		<div class="wrap normal clearfix">	
            <div class="container-fluid">
            <div class="row <?php echo $customContentClasses; ?>">

                <div class="page-content article-left col-12 <?php if(strpos(get_query_var('layout'), 'actions_ah_sidebar') === false): ?>col-lg-8 <?php endif; ?>pl-0 pr-0 pr-lg-4">

                    <?php 

                        // Image
                        if(count(array_intersect( array('featured_image_article'), $layout))){
                            if(has_post_thumbnail()){
                                echo '<div class="featured-image mb-5">';
                                    the_post_thumbnail();
                                echo '</div>';
                            } elseif( get_field('featured_image') ) {
                                echo '<div class="featured-image mb-5">';                                
                                echo wp_get_attachment_image( get_field('featured_image'), 'banner' );
                                echo '</div>';
                            }
                        }

                        // Featured Link
                        if(get_field('featured_link')){
                            
                            if(get_field('featured_link_text')){
                                $featured_text = get_field('featured_link_text');
                            } else {
                                $featured_text = get_field('featured_link');
                            }
                            echo '<div class="mb-4 text-center">';                            
                                echo '<a class="button round" href="'.get_field('featured_link').'">'.$featured_text.'</a>';
                            echo '</div>';
                        }

                        // Introductory Content
                        echo '<div class="article--introductory_content">';
                        echo get_field('introductory_content');
                        echo '</div>';
                    
                        // Main Content
                        the_content();
                        
                        // Locations
                        if(!get_field('hide_locations')){
                            $location = get_field('location');                        
                            if( $location && $location[0]['address'] != '') {

                                if(count($location) > 1){ 

                                    // Check to hide or display the phone column
                                    $hasPhones = 0;
                                    foreach( $location as $row ) {
                                        if(trim($row['phone']) != ''){
                                            $hasPhones++;
                                        }
                                    }

                                    echo '<h2>Locations</h2>';

                                    echo '<div class="table-wrapper" style="overflow-x: auto;"><table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Address</th>';
                                            if($hasPhones > 0){ 
                                                echo '<th>Phone</th>'; 
                                            }
                                        echo '</tr>
                                    </thead>
                                    <tbody>';
                                    foreach( $location as $row ) {
                                        echo '<tr>';
                                            echo '<td>'.$row['address'].'</td>';
                                            if($hasPhones > 0){ echo '<td><a href="tel:'.$row['phone'].'">'.$row['phone'].'</a></td>'; }
                                        echo '</tr>';
                                    }
                                    echo '</tbody>
                                    </table></div>';
                                } else {
                                    echo '<h2>Location</h2>';
                                    echo '<p>';
                                    foreach( $location as $row ) {
                                        if($row['address'] != ''){ 
                                            echo $row['address'].'<br />'; 
                                        }
                                        if($row['phone'] != ''){ 
                                            echo '<a class="text-nowrap" href="tel:'.$row['phone'].'">'.$row['phone'].'</a>'; 
                                        }
                                    }
                                    echo '</p>';
                                }
                            }
                        }
                        
                        // Contact Information
                        if(get_field('customer_service_email') || get_field('customer_service_contact_form') || get_field('customer_service_phone') ){
                            echo '<h2>Contact Info</h2>';
                            echo '<div class="mb-5">';
                        }
                            if(get_field('customer_service_email')) { 
                                echo '<p class="mb-0"><strong>Email:</strong> <a href="mailto:'.get_field('customer_service_email').'">'.get_field('customer_service_email').'</a></p>'; 
                            }
                            if(get_field('customer_service_contact_form')) { 
                                echo '<p class="mb-0"><strong>Contact form:</strong> <a href="'.get_field('customer_service_contact_form').'" target="_blank">'.get_field('customer_service_contact_form').'</a></p>'; 
                            }
                            if(get_field('customer_service_phone')) { 
                                echo '<p class="mb-0"><strong>Phone:</strong> <a href="tel:'.get_field('customer_service_phone').'">'.get_field('customer_service_phone').'</a></p>'; 
                            }
                        if(get_field('customer_service_email') || get_field('customer_service_contact_form') || get_field('customer_service_phone') ){
                            echo '</div>';
                        }
                        
                        // Pricing Information
                        if(get_field('pricing_information')){
                            echo '<div class="mb-4">';
                                echo '<h2>Pricing Information</h2>';
                                the_field('pricing_information');
                            echo '</div>';
                        }

                        // Accolades
                        $accolades = get_field('accolades');                        
                        if( $accolades ) {
                            echo '<h2>What People Are Saying</h2>';
                            foreach( $accolades as $row ) {
                                echo '<blockquote>';
                                    echo $row['text'];
                                    if($row['source']){
                                        echo '<cite>'.$row['source'].'</cite>';
                                    }
                                echo '</blockquote>';
                            }
                            echo '</ul>';
                        }

                        // Privacy
                        if(get_field('privacy_information')){
                            echo '<div class="mb-4">';
                                echo '<h2>Privacy Information</h2>';
                                the_field('privacy_information');
                            echo '</div>';
                        }

                        // Disclaimer
                        if(get_field('disclaimer')){
                            echo '<div class="mb-4">';
                                echo '<h2>Disclaimer</h2>';
                                the_field('disclaimer');
                            echo '</div>';
                        }

                        // Global article footer
                        the_field('article_footer_content', 'options');
                    ?>
                </div>      
                
                <aside class="article-right col-12 col-md-5 col-lg-4 pl-0 pr-0 pl-md-5 hide-tablet">
					<?php 
						if(get_field('espanol')){
							get_template_part( 'templates/blocks/article', 'sidebar-espanol', array( 'placement' => 'desktop' ) ); 
						} else {
							get_template_part( 'templates/blocks/article', 'sidebar', array( 'placement' => 'desktop' ) ); 
						}
					?>
                </aside>
                
                <?php if(get_field('article_footer_content')): ?>
                <div class="col-12">
                    <?php
                        // Introductory Content
                        echo '<div class="article--footer_content">';
                        the_field('article_footer_content');
                        echo '</div>';
                    ?>
                </div>
                <?php endif; ?>

            </div>
            </div>
		</div>

	</article>

</section>