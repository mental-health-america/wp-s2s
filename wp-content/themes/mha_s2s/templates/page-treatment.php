<?php 
/* Template Name: Treatment */
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

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#treatment-filter" aria-expanded="true" aria-controls="treatment-filter">Filters</button>

            <form action="#" method="POST" id="treatment-filter" class="search-filters form-container collapse show">

                <a href="/treatment" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label">Filters</p>

                <p><input type="text" name="search" class="gray" placeholder="Search" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#treatmentType" aria-expanded="true" aria-controls="treatmentType">Type</button>
                <div id="treatmentType" class="collapse show filter-checkboxes">
                    <?php
                        $treatment_type = get_field_object('field_5fd3f7a3951ad');
                        if( $treatment_type['choices'] ): ?>
                            <?php foreach( $treatment_type['choices'] as $value => $label ): ?>
                                <div class="form-item">
                                    <input id="treatment-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="treatment_type[]" />
                                    <label for="treatment-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php 
                        endif; 
                    ?>
                </div>

                <input type="hidden" name="type" value="treatment" />
                <!--<button class="button red round block thin mt-4" style="width: 100%;">Search</button>-->

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php echo get_articles( 'treatment' ); ?>

        </div>
        </div>

    </div>

</div>

<?php
get_footer();