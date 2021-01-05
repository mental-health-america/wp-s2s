<?php 
/* Template Name: Providers */
get_header(); 
?>

<div class="wrap medium center mb-5">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="page-heading plain red">			
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
            <div class="page-intro">
                <?php the_content(); ?>				
            </div>
        </div>
    </article>
</div>

<div class="wrap normal clearfix pt-4">

    <div id="filters-container">

        <?php get_template_part( 'templates/blocks/filter-order' ); ?>

        <div id="filters" class="clear">
        <div class="inner">

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#provider-filter" aria-expanded="true" aria-controls="provider-filter">Filters</button>

            <form action="#" method="POST" id="provider-filter" class="search-filters form-container collapse show">

                <a href="/get-help" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label">Filters</p>

                <p><input type="text" name="search" class="gray" placeholder="Keyword Search" /></p>
                
                <label for="zip" class="text-blue-dark">Location Search</label>
                <p><input type="number" id="zip" name="zip" class="gray" placeholder="Enter your zip code" value="<?php echo get_query_var('geo'); ?>" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#locationList" aria-expanded="true" aria-controls="locationList">Area Served</button>
                <div id="locationList" class="collapse show filter-checkboxes">
                    <?php
                        $area_served = get_field_object('field_5fd3eef624b35');
                        if( $area_served['choices'] ): ?>
                            <?php foreach( $area_served['choices'] as $value => $label ): ?>
                                <div class="form-item">
                                    <input id="area-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="area_served[]" />
                                    <label for="area-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php 
                        endif; 
                    ?>
                </div>

                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#serviceTypes" aria-expanded="true" aria-controls="serviceTypes">Service Type</button>
                <div id="serviceTypes" class="collapse show filter-checkboxes">
                    <?php
                        $treatment_type = get_field_object('field_5fdc0a1448b13');
                        if( $treatment_type['choices'] ): ?>
                            <?php foreach( $treatment_type['choices'] as $value => $label ): ?>
                                <div class="form-item">
                                    <input id="service-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="service_type[]" />
                                    <label for="service-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php 
                        endif; 
                    ?>
                </div>

                <input type="hidden" name="type" value="provider" />
                <button class="button red round block thin mt-4" style="width: 100%;">Search</button>

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php echo get_articles( 'provider', '', '', '', 'DESC', 'featured', get_geo(get_query_var('geo')) ); ?>

        </div>
        </div>

    </div>

</div>

<?php
get_footer();