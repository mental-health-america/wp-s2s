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

$type = get_post_type();
$customClasses = '';

if($type == 'article'){
	$article_type = get_field('type');
	if (in_array(array('diy','connect','treatment','provider'), $article_type)) {
		$customClasses = ' red';
	}
}

?>

<section class="article-columns clearfix">

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if(!get_field('hero_headline') || !get_field('hero_introduction')): ?>
			<div class="page-heading bar<?php echo $customClasses; ?>">	
			<div class="wrap normal">		
				
				<?php
					if ( function_exists('yoast_breadcrumb') ) {
						yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
					}
				?>
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				
			</div>
			</div>
		<?php endif; ?>

		<div class="wrap normal clearfix">	
            <div class="container-fluid">
            <div class="row">

                <div class="page-content article-left col-8">
                    <?php the_content(); ?>		
                </div>      

                <aside class="article-right col-4">

                    <div class="bubble dark-blue thin round-tl">
                    <div class="inner">
                        <h4>Categories</h4>
                        <p>Tags associated with this article:</p>
                        <?php 
                            $terms_conditions = get_the_terms( get_the_ID(), 'condition'); 
                            foreach($terms_conditions as $c){
                                
                                echo '<a href="'.get_term_link($c->term_id).'">'.get_term($c->term_id)->name.'</a>';
                            }
                            //$terms_age = get_the_terms( get_the_ID(), 'age_group'); 
                        ?>
                    </div>
                    </div>

                </aside>

            </div>
            </div>
		</div>

	</article>

</section>