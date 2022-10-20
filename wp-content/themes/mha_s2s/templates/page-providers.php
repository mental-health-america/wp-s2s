<?php 
/* Template Name: Providers */
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

        <div class="dropdown text-right pr-0 pr-md-4 mb-4">
            <label class="inline text-dark-blue small bold"><?php echo _e('Sort by:', 'mhas2s'); ?> &nbsp; </label>
            <?php echo facetwp_display( 'facet', 'sort_by_location' ); ?>
        </div>
        
        <div id="filters" class="clear">
        <div class="inner">

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#diy-filter" aria-expanded="true" aria-controls="diy-filter">Filters</button>

            <div id="diy-filter" class="search-filters form-container collapse">

                <a href="<?php echo get_the_permalink(); ?>" class="right plain pt-1 red small bold"><?php echo _e('Clear All', 'mhas2s'); ?></a>
                <p class="bold text-dark-blue caps nb-3 intro-label montserrat"><?php echo _e('Filters', 'mhas2s'); ?></p>

                <?php echo facetwp_display( 'facet', 'search' ); ?>
                
                <label for="zip-code-search" class="text-blue-dark"><?php echo _e('Search for resources near you', 'mhas2s'); ?></label>
                <div class="facetwp-facet facetwp-facet-custom facetwp-type-custom">
                    <span class="facetwp-input-wrap">
                        <i class="facetwp-icon zip-icon faux-submit submit-zip-search"></i>
                        <input type="text" id="zip-code-search" class="location-search-zip facetwp-location" value="" placeholder="Zip code" autocomplete="off" data-connect="location_search">
                    </span>
                </div>
                <div class="d-none"><?php echo facetwp_display( 'facet', 'location_search' ); ?></div>

                <div class="d-none">
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#areaServed" aria-expanded="true" aria-controls="areaServed"><?php echo _e('Area Served', 'mhas2s'); ?></button>
                <div id="areaServed" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'area_served' ); ?>
                </div>
                </div>

                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#serviceType" aria-expanded="true" aria-controls="serviceType"><?php echo _e('Service Type', 'mhas2s'); ?></button>
                <div id="serviceType" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'service_type' ); ?>
                </div>

                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList"><?php echo _e('Conditions', 'mhas2s'); ?></button>
                <div id="conditionsList" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'general_mental_health' ); ?>
                    <?php echo facetwp_display( 'facet', 'conditions' ); ?>
                </div>

                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#tagsList" aria-expanded="true" aria-controls="tagsList"><?php echo _e('Tags'); ?></button>
                <div id="tagsList" class="collapse show filter-checkboxes">
                    <?php echo facetwp_display( 'facet', 'tag' ); ?>
                </div>

            </div>
            
        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content" class="facetwp-template">

            <div id="geo-search-message" class="bubble round thin orange mb-4" style="width: 100%; display: none;"><div class="inner text-center"><strong>
                <?php echo _e('Currently displaying resources near <span id="geo-search-current"></span>. To see nationwide resources, <a href="/get-help">click here</a>. To find more local resources, please use the <a href="https://findtreatment.samhsa.gov/" target="_blank">SAMHSA Treatment Locator</a>.'); ?>
            </strong></div></div>

            <?php
                $options = array(
                    'type'          => 'provider',
                    //'area_served'   => 'national'
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