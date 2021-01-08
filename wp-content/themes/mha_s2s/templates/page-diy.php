<?php 
/* Template Name: DIY Tools */
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

                <a href="/diy-tools" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label">Filters</p>

                <p><input type="text" name="search" class="gray" placeholder="Search" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#diyType" aria-expanded="true" aria-controls="diyType">Type</button>
                <div id="diyType" class="collapse show filter-checkboxes">
                    <?php
                        $diy_type = get_field_object('field_5fd3f1a935255');
                        if( $diy_type['choices'] ): ?>
                            <?php foreach( $diy_type['choices'] as $value => $label ): ?>
                                <div class="form-item">
                                    <input id="type-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="diy_type[]" />
                                    <label for="type-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php 
                        endif; 
                    ?>
                </div>

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

                <input type="hidden" name="type" value="diy" />
                <!--<button class="button red round block thin mt-4" style="width: 100%;">Search</button>-->

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php echo get_articles( 'diy' ); ?>

        </div>
        </div>

    </div>

</div>

<?php
get_footer();