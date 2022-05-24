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
                <p class="bold text-dark-blue caps nb-3 intro-label montserrat">Filters</p>

                <p><input type="text" name="search" class="gray" placeholder="Search" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#treatmentType" aria-expanded="true" aria-controls="treatmentType">Treatment Type</button>
                <div id="treatmentType" class="collapse show filter-checkboxes">
                    <?php
                        $treatment_type = get_field_object('field_5fd3f7a3951ad');
                        
                        // Pre-checked filters
                        $params = explode(',', get_query_var('treatment'));
                        $checked_params = [];
                        foreach($params as $p){ $checked_params[] = strtolower(urldecode($p)); }

                        if( $treatment_type['choices'] ):
                        foreach( $treatment_type['choices'] as $value => $label ): 
                            $checked = in_array( strtolower(urldecode($label)), $checked_params) ? ' checked="checked"' : '';
                            ?>
                                <div class="form-item">
                                    <input id="treatment-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="treatment_type[]"<?php echo $checked; ?>/>
                                    <label for="treatment-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php 
                        endforeach;                                
                        endif; 
                    ?>
                </div>
                
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList">Conditions</button>
                <div id="conditionsList" class="collapse show filter-checkboxes">
                
                    <div class="form-item collapse" id="all-conditions-container">
                        <input id="all_conditions" type="checkbox" value="1" name="all_conditions" />
                        <label for="all_conditions">Include articles that apply to all conditions</label><br />
                    </div>

                    <div class="show-all-conditions">
                        <?php
                            $tag_options = array(
                                "post_type"      => 'article',
                                "type"           => 'treatment',
                                "taxonomy"       => 'condition',
                            );         
                            echo get_tag_filters( $tag_options );
                        ?>
                    </div>
                </div>
                
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#tagsList" aria-expanded="true" aria-controls="tagsList">Tags</button>
                <div id="tagsList" class="collapse show filter-checkboxes">
                    <?php          
                        $tag_options = array(
                            "post_type"      => 'article',
                            "type"           => 'treatment',
                            "taxonomy"       => 'tags',
                        );         
                        echo get_tag_filters( $tag_options );
                    ?>
                </div>

                <input type="hidden" name="type" value="treatment" />
                <!--<button class="button red round block thin mt-4" style="width: 100%;">Search</button>-->

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php
                $options = array(
                    'type' => 'treatment'
                );
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