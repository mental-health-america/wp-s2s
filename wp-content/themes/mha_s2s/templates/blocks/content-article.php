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
$resources = array('diy','connect','treatment','provider');
$article_type = get_field('type');

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

                <div class="page-content article-left col-12 col-md-8 pl-0 pr-0 pr-md-4">

                    <?php 

                        if(has_post_thumbnail()){
                            echo '<div class="featured-image mb-5">';
                                the_post_thumbnail();
                            echo '</div>';
                        }

                        if(get_field('featured_link')){
                            
                            if(get_field('featured_link')){
                                $featured_text = get_field('featured_link_text');
                            } else {
                                $featured_text = get_field('featured_link');
                            }
                            echo '<div class="mb-4 text-center">';                            
                                echo '<a class="button round" href="'.get_field('featured_link').'">'.$featured_text.'</a>';
                            echo '</div>';
                        }
                    
                        the_content();
                        
                        if(!get_field('hide_locations')){
                            $location = get_field('location');                        
                            if( $location && $location[0]['address'] != '') {
                                if(count($location) > 1){ 
                                    echo '<h2>Locations</h2>';
                                } else {
                                    echo '<h2>Location</h2>';
                                }
                                echo '<p>';
                                foreach( $location as $row ) {
                                    echo ''.$row['address'].'<br />';
                                }
                                echo '</p>';
                            }
                        }

                        if(get_field('pricing_information')){
                            echo '<div class="mb-4">';
                                echo '<h2>Pricing Information</h2>';
                                the_field('pricing_information');
                            echo '</div>';
                        }

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

                        if(get_field('privacy_information')){
                            echo '<div class="mb-4">';
                                echo '<h2>Privacy Information</h2>';
                                the_field('privacy_information');
                            echo '</div>';
                        }

                        if(get_field('disclaimer')){
                            echo '<div class="mb-4">';
                                echo '<h2>Disclaimer</h2>';
                                the_field('disclaimer');
                            echo '</div>';
                        }
                    ?>
                </div>      
                
                <aside class="article-right col-12 col-md-4 pl-0 pr-0 pl-md-5 hide-mobile">
                    <?php get_template_part( 'templates/blocks/article', 'sidebar' ); ?>
                </aside>

            </div>
            </div>
		</div>

	</article>

</section>