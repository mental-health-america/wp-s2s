<?php 
/* Template Name: DIY Tools */
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

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#diy-filter" aria-expanded="true" aria-controls="diy-filter">Filters</button>

            <div id="diy-filter" class="search-filters form-container collapse show">

                <a href="/diy-tools" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label montserrat">Filters</p>

                <?php echo facetwp_display( 'facet', 'search' ); ?>

                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#diyType" aria-expanded="true" aria-controls="diyType">Type</button>
                <div id="diyType" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'diy_type' ); ?>
                </div>

                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#tagsList" aria-expanded="true" aria-controls="tagsList">Tags</button>
                <div id="tagsList" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'tag' ); ?>
                </div>

                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#espanolCheck" aria-expanded="true" aria-controls="espanolCheck">Languages</button>
                <div id="espanolCheck" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'language' ); ?>
                    <div class="language-toggle facetwp-checkbox" data-value="1"><span class="facetwp-display-value">Español</span><span id="espanol-total"></span></div>
                </div>

                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList">Conditions</button>
                <div id="conditionsList" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'general_mental_health' ); ?>
                    <?php echo facetwp_display( 'facet', 'conditions' ); ?>
                </div>

            </div>
            
        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content" class="facetwp-template">

            <?php
                $options = array(
                    'type' => 'diy',
                    'espanol' => '!='
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