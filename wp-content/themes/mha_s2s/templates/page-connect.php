<?php 
/* Template Name: Connect */
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

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#connect-filter" aria-expanded="true" aria-controls="connect-filter">Filters</button>

            <form action="#" method="POST" id="connect-filter" class="search-filters form-container collapse show">

                <a href="/connect" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label">FILTERS</p>

                <p><input type="text" name="search" class="gray" placeholder="Search" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList">Conditions</button>
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
                                echo $html;
                            }
                        ?>
                    </div>
                </div>

                <input type="hidden" name="type" value="connect" />
                <!--<button class="button red round block thin mt-4" style="width: 100%;">Search</button>-->

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php
                $options = array(
                    'type' => 'connect'
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