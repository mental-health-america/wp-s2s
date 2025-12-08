<?php 
/* Template Name: Connect */
get_header(); 
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="page-heading bar red">	
    <div class="wrap normal">				
        <?php 
            get_template_part( 'templates/blocks/breadcrumbs' );
            the_title( '<h1 class="entry-title">', '</h1>' ); 
        ?>
        <div class="page-intro">
            <?php the_content(); ?>				
        </div>
    </div>
    </div>
</article>

<div class="wrap normal clearfix pt-4">

    <div id="filters-container">

        <?php get_template_part( 'templates/blocks/filter-order' ); ?>
        
        <div id="filters" class="clear">
        <div class="inner">

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#connect-filter" aria-expanded="false" aria-controls="connect-filter" data-expanded-md="true">Filters</button>

            <div id="connect-filter" class="search-filters form-container collapse show-md">

                <a href="/diy-tools" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label montserrat">Filters</p>

                <?php echo facetwp_display( 'facet', 'search' ); ?>

                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList">Topics</button>
                <div id="conditionsList" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'general_mental_health' ); ?>
                    <?php 
                    // Display conditions and tags separately (hidden) for FacetWP to process
                    echo facetwp_display( 'facet', 'conditions' ); 
                    echo facetwp_display( 'facet', 'tag' ); 
                    // Display combined list (top 7)
                    echo facetwp_display_combined_conditions_tags( 7 );
                    ?>
                </div>

            </div>
            
        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content" class="facetwp-template">

            <?php
                $options = array(
                    'type' => 'connect'
                );
                echo get_articles_faceted( $options ); 
            ?>

        </div>
        </div>

    </div>
        
    <div class="clear pt-4">
        <?php 
            // Content Blocks
            wp_reset_query();
            if( have_rows('block') ):
            while ( have_rows('block') ) : the_row();
                $layout = get_row_layout();
                if( get_template_part( 'templates/blocks/block', $layout ) ):
                    get_template_part( 'templates/blocks/block', $layout );
                endif;
            endwhile;
            endif;
        ?>
    </div>

</div>

<?php
get_footer();