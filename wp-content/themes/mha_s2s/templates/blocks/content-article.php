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
$article_id = get_the_ID();
$resources = array('diy','connect','treatment','provider');
$article_type = get_field('type');

// Related content triggers
$article_conditions = [];
$article_diy_issue = get_field('diy_type');
$article_treatment_type = get_field('treatment_type');
$article_service_type = get_field('service_type');

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

                <aside class="article-right col-12 col-md-4 pl-0 pr-0 pl-md-5">

                    <?php 
                    $terms_conditions = get_the_terms( $article_id, 'condition', );
                    if(count(array_intersect($article_type, $resources)) > 0){
                        $categoryColor = 'raspberry';
                    } else {
                        $categoryColor = 'dark-blue';
                    }
                    if($terms_conditions):
                    ?>
                        <div class="bubble <?php echo $categoryColor; ?> thin round-big-tl mb-4">
                        <div class="inner">
                            <h4>Categories</h4>
                            <p class="mb-2">Tags associated with this article:</p>
                            <?php 
                                echo '<ol class="plain ml-5 mb-0">'; 
                                foreach($terms_conditions as $c){
                                    if ($c->parent == 0){
                                        echo '<li><a class="plain bold caps" href="'.get_term_link($c->term_id).'">'.$c->name.'</a></li>';
                                        $article_conditions[] = $c->term_id; // Used later for related content 
                                    }
                                }
                                echo '</ol>';
                            ?>
                        </div>
                        </div>
                    <?php endif; ?>
                
                    <?php
                        /**
                         * Test CTA
                         */

                        $primary_condition = get_field('primary_condition');

                        // Pathway override
                        if(get_query_var('pathway')){
                            $path_terms = get_the_terms(get_query_var('pathway'), 'condition');
                            $primary_condition = $path_terms[0]->term_id;
                        }

                        if($primary_condition){

                            // Show Specific Related Test
                            if(count(array_intersect($article_type, $resources)) == 0){
                                $args = array(
                                    "post_type"         => 'screen',
                                    "order"	            => 'DESC',
                                    "post_status"       => 'publish',
                                    "posts_per_page"    => 1,
                                    'tax_query'      => array(
                                        array(
                                            'taxonomy'          => 'condition',
                                            'include_children'  => false,
                                            'field'             => 'term_id',
                                            'terms'             => $primary_condition
                                        ),
                                    )
                                );
                                $loop = new WP_Query($args);
                                if($loop->have_posts()):
                                ?>
                                    <div class="bubble orange thin round-big-tl mb-4">
                                    <div class="inner">
                                    <?php while($loop->have_posts()) : $loop->the_post(); ?>                              
                                        <?php the_title('<h4>Take a ','</h4>'); ?>   
                                        <div class="excerpt"><?php the_excerpt(); ?></div>
                                        <div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a <?php the_title(); ?></a></div>
                                    <?php endwhile; ?>
                                    </div>
                                    </div>
                                <?php
                                endif;
                                wp_reset_query();
                            }

                        } else {

                            // Show Random Related Test
                            if(count(array_intersect($article_type, $resources)) == 0){
                                $args = array(
                                    "post_type"      => 'screen',
                                    "orderby"        => 'rand',
                                    "order"	         => 'DESC',
                                    "post_status"    => 'publish',
                                    "posts_per_page" => 1,
                                    'tax_query'      => array(
                                        array(
                                            'taxonomy'          => 'condition',
                                            'include_children'  => false,
                                            'field'             => 'term_id',
                                            'terms'             => $article_conditions
                                        ),
                                    )
                                );
                                $loop = new WP_Query($args);
                                if($loop->have_posts()):
                                ?>
                                    <div class="bubble orange thin round-big-tl mb-4">
                                    <div class="inner">
                                    <?php while($loop->have_posts()) : $loop->the_post(); ?>                              
                                        <?php the_title('<h4>Take a ','</h4>'); ?>   
                                        <div class="excerpt"><?php the_excerpt(); ?></div>
                                        <div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a <?php the_title(); ?></a></div>
                                    <?php endwhile; ?>
                                    </div>
                                    </div>
                                <?php
                                endif;
                                wp_reset_query();
                            }

                        }

                    ?>

                    <?php
                        /**
                         * Related Articles
                         */
                        if(count(array_intersect($article_type, $resources)) > 0){   
                            
                            $more_links = get_field('more_links');
                            $args = array(
                                "post_type"      => 'article',
                                "orderby"        => 'rand',
                                "post_status"    => 'publish',
                                "posts_per_page" => 5,
                                'tax_query'      => array(
                                    array(
                                        'taxonomy'          => 'condition',
                                        'include_children'  => false,
                                        'field'             => 'term_id',
                                        'terms'             => $article_conditions
                                    ),
                                )
                            );
                            $loop = new WP_Query($args);
                            

                            if($loop->have_posts() || $more_links):                     
                            ?>

                                <div class="bubble coral thin round-big-tl mb-4">
                                <div class="inner">
                                    <h4>Related Articles</h4>
                                    <?php 
                                        echo '<ol class="plain ml-5 mb-0">';                                             

                                            // Manual Related Links
                                            if( have_rows('more_links') ):
                                            while( have_rows('more_links') ) : the_row();                                        
                                                $page = get_sub_field('page');
                                                echo '<li><a class="plain white bold caps" href="'.get_the_permalink($page).'">';
                                                    if(get_sub_field('custom_title')){
                                                        the_sub_field('custom_title');
                                                    } else {
                                                        echo get_the_title($page);
                                                    }
                                                echo '</a></li>';
                                            endwhile;
                                            endif;

                                            // Automatic Related
                                            while($loop->have_posts()) : $loop->the_post();
                                                echo '<li><a class="plain white bold caps" href="'.get_the_permalink().'">'.get_the_title().'</a></li>';
                                            endwhile;

                                        echo '</ol>';
                                    ?>
                                </div>
                                </div>

                            <?php
                            endif;
                            wp_reset_query();
                        }
                    ?>

                    <div class="article-actions">
                        <?php 
                            /**
                             * Article actions
                             */
                            $uid = get_current_user_id();
                            $like_class = '';
                            $like_check = checkArticleLikes( $article_id, $uid );
                            $like_prefix = 'Save ';
                            if($like_check){
                                $like_class .= ' liked';
                                $like_prefix = 'Unsave ';
                            }
                            
                            if(is_user_logged_in()){
                                $like_class .= ' article-like';
                            } else {
                                $like_class .= ' logged-out';
                            }
                        ?>
                        <p>
                            <button class="icon caps like-button<?php echo $like_class; ?>" data-pid="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('articleLike'); ?>">
                                <span class="image"><?php include get_theme_file_path("assets/images/save.svg"); ?></span>                        
                                <span class="text blue caps light"><?php echo $like_prefix; ?>This Page</span>
                            </button>
                        </p>

                        <?php
                            // Share
                            if(get_query_var('pathway')){
                                $share_url = add_query_arg('pathway', get_query_var('pathway'), get_the_permalink($next_id));
                            } else {
                                $share_url = get_the_permalink($next_id);
                            }
                        ?>
                        <div class="dropdown mb-4">
                            <button class="icon share-button dropdown-toggle" type="button" id="shareOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="image"><?php include get_theme_file_path("assets/images/share.svg"); ?></span>                        
                                <span class="text blue caps light">Share</span>
                            </button>
                            <div class="dropdown-menu" aria-labelledby="shareOptions">
                                <a class="dropdown-item social-share" href="<?php echo formatTwitter(get_the_title(), $share_url); ?>">Share on Twitter</a>
                                <a class="dropdown-item social-share" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>">Share on Facebook</a>
                            </div>
                        </div>

                    </div>

                </aside>

            </div>
            </div>
		</div>

	</article>

</section>