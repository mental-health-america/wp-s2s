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

                <p><input id="keyword-search" type="text" name="search" class="gray input-text" placeholder="Keyword Search" /></p>
                
                <label for="zip" class="text-blue-dark">Location Search</label>
                <p><input id="zip-search" type="number" id="zip" name="zip" class="gray input-text" placeholder="Enter your zip code" value="<?php echo get_query_var('geo'); ?>" /></p>
                
                <input id="area-national" type="hidden" value="national" name="area_served[]" />
                
                <?php /*
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
                </div> */ ?>

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
                
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList">Condition Treated</button>
                <div id="conditionsList" class="collapse show filter-checkboxes">
                
                    <div class="form-item collapse" id="all-conditions-container">
                        <input id="all_conditions" type="checkbox" value="1" name="all_conditions" />
                        <label for="all_conditions">Include articles that apply to all conditions</label><br />
                    </div>

                    <div class="show-all-conditions">
                        <?php
                            // Condition Filters
                            $query = get_terms(array(
                                'taxonomy' => 'condition',
                                'hide_empty' => true,
                                'parent' => 0
                            ));
                            
                            $conditions = [];
                            if($query){
                                foreach($query as $c){
                                    if(!get_field('hide_on_front_end', $c->taxonomy.'_'.$c->term_id)){
                                    ?>
                                        <div class="form-item">
                                            <input id="condition-<?php echo $c->term_id; ?>" type="checkbox" value="<?php echo $c->term_id; ?>" name="condition[]" />
                                            <label for="condition-<?php echo $c->term_id; ?>"><?php echo $c->name; ?></label>
                                        </div>
                                    <?php
                                    }
                                }
                            }
                        ?>
                    </div>
                </div>
                
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#tagsList" aria-expanded="true" aria-controls="tagsList">Tags</button>
                <div id="tagsList" class="collapse show filter-checkboxes">                
                    <?php
                        // Condition Filters
                        $query = get_terms(array(
                            'taxonomy' => 'post_tag',
                            'hide_empty' => true,
                            'parent' => 0
                        ));
                        
                        if($query){
                            foreach($query as $c){
                                if(!get_field('hide_on_front_end', $c->taxonomy.'_'.$c->term_id)){
                                ?>
                                    <div class="form-item">
                                        <input id="tag-<?php echo $c->term_id; ?>" type="checkbox" value="<?php echo $c->term_id; ?>" name="tags[]" />
                                        <label for="tag-<?php echo $c->term_id; ?>"><?php echo $c->name; ?></label>
                                    </div>
                                <?php
                                }
                            }
                        }
                    ?>
                </div>

                <input type="hidden" name="type" value="provider" />
                <!--<button class="button red round block thin mt-4" style="width: 100%;">Search</button>-->

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php 
                $options = array(
                    'type'          => 'provider',
                    'area_served'   => 'national'
                );
                if(get_query_var('geo')){
                    $options['geo'] = get_geo(get_query_var('geo'));
                }
                echo get_articles( $options ); 
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