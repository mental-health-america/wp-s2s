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

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-5 mb-md-4" type="button" data-toggle="collapse" data-target="#provider-filter" aria-expanded="true" aria-controls="provider-filter">Filters</button>

            <form action="#" method="POST" id="provider-filter" class="search-filters form-container collapse show">

                <a href="/diy-tools" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label montserrat">Filters</p>

                <p><input type="text" name="search" class="gray" placeholder="Search" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#diyType" aria-expanded="true" aria-controls="diyType">Type</button>
                <div id="diyType" class="collapse show filter-checkboxes">
                    <?php
                        $diy_type = get_field_object('field_5fd3f1a935255');

                        // Pre-checked filters
                        $params = explode(',', get_query_var('type'));
                        $checked_params = [];
                        foreach($params as $p){ $checked_params[] = strtolower(urldecode($p)); }

                        if( $diy_type['choices'] ):
                        foreach( $diy_type['choices'] as $value => $label ): 
                            $checked = in_array( strtolower(urldecode($label)), $checked_params) ? ' checked="checked"' : '';
                            ?>
                                <div class="form-item">
                                    <input id="type-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="diy_type[]" <?php echo $checked; ?> />
                                    <label for="type-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php 
                        endforeach;
                        endif; 
                    ?>
                </div>
                
                <?php 
                /*
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#diyIssue" aria-expanded="true" aria-controls="diyIssue">Issue</button>
                <div id="diyIssue" class="collapse show filter-checkboxes">
                    <?php
                        $diy_issue = get_field_object('field_5fea345c4d25c');
                        if( $diy_issue['choices'] ): ?>
                            <?php foreach( $diy_issue['choices'] as $value => $label ): ?>
                                <div class="form-item">
                                    <input id="issue-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="diy_issue[]" />
                                    <label for="issue-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php 
                        endif; 
                    ?>
                </div> 
                */ 
                ?>
                
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#tagsList" aria-expanded="true" aria-controls="tagsList">Tags</button>
                <div id="tagsList" class="collapse show filter-checkboxes">
                    <?php          
                        $tag_options = array(
                            "post_type"      => 'article',
                            "type"           => 'diy',
                            "taxonomy"       => 'tags',
                        );         
                        echo get_tag_filters( $tag_options );
                    ?>
                </div>
                
                <button class="bold text-gray caps accordion-button mb-3 mt-3" type="button" data-toggle="collapse" data-target="#espanolCheck" aria-expanded="true" aria-controls="espanolCheck">Languages</button>
                <div id="espanolCheck" class="collapse show filter-checkboxes">
                    <div class="form-item" >
                        <input id="espanol" type="checkbox" value="1" name="espanol" <?php if(get_query_var('language') == 'español'){ echo ' checked="checked"'; } ?>/>
                        <label for="espanol">Español</label><br />
                    </div>
                </div>

                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#conditionsList" aria-expanded="true" aria-controls="conditionsList">Conditions</button>
                <div id="conditionsList" class="collapse show filter-checkboxes">
                
                    <div class="form-item collapse" id="all-conditions-container">
                        <input id="all_conditions" type="checkbox" value="1" name="all_conditions" />
                        <label for="all_conditions">Include articles that apply to all conditions</label><br />
                    </div>

                    <div class="show-all-conditions">
                        <?php
                            $tag_options = array(
                                "post_type"      => 'article',
                                "type"           => 'diy',
                                "taxonomy"       => 'condition',
                            );         
                            echo get_tag_filters( $tag_options );
                        ?>
                    </div>
                </div>

                <input type="hidden" name="type" value="diy" />
                <!--<button class="button red round block thin mt-4" style="width: 100%;">Search</button>-->

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php
                $options = array(
                    'type' => 'diy',
                    'espanol' => '!='
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