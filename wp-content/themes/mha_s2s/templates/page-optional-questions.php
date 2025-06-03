<?php
/* Template Name: Optional Questions */

get_header();
$layout = get_layout_array(get_query_var('layout'));
$wrap_width = get_field('page_content_width') ? get_field('page_content_width') : 'normal';
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if(in_array('screen_header_v1', $layout)): ?>
			<div class="wrap normal">
				<div class="page-heading plain">			
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</div>
			</div>
		<?php else: ?>
			<div class="page-heading mint bar">	
			<div class="wrap <?php echo $wrap_width; ?>">		
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>			
			</div>
			</div>
		<?php endif; ?>

		<div class="wrap normal">
			<div class="page-intro">
                <?php
                    // Breadcrumbs
                    $form_id = get_query_var('form');
                    $form = GFAPI::get_form( $form_id );
                    if(empty($form['pagination'])){
                        //$form['pagination']
                    }
                    if($form):
                        custom_screen_progress_bar('', $form, '');
                    endif;

                    // Content
                    the_content(); 
                ?>				
			</div>
		</div>

	</article>

<?php
get_footer();
