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

<div class="wrap wide clearfix pt-4">

    <div id="filters-container">

        <div id="filters">
        <div class="inner">

            <button id="filter-toggle" class="bold text-gray caps accordion-button mb-4" type="button" data-toggle="collapse" data-target="#provider-filter" aria-expanded="true" aria-controls="provider-filter">Filters</button>

            <form action="#" method="POST" id="provider-filter" class="search-filters form-container collapse show">

                <a href="/connect" class="right plain pt-1 red small bold">Clear All</a>
                <p class="bold text-dark-blue caps nb-3 intro-label">Filters</p>

                <p><input type="text" name="search" class="gray" placeholder="Search" /></p>
                
                <button class="bold text-gray caps accordion-button mb-3" type="button" data-toggle="collapse" data-target="#diyType" aria-expanded="true" aria-controls="diyType">Type</button>
                <div id="diyType" class="collapse show filter-checkboxes">
                    <?php
                        $diy_type = get_field_object('field_5fd3f1a935255');
                        if( $diy_type['choices'] ): ?>
                            <?php foreach( $diy_type['choices'] as $value => $label ): ?>
                                <div class="form-item">
                                    <input id="condition-<?php echo $value; ?>" type="checkbox" value="<?php echo $value; ?>" name="diy_type[]" />
                                    <label for="condition-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php 
                        endif; 
                    ?>
                </div>
                <button class="button red round block thin mt-4" style="width: 100%;">Search</button>

            </form>

        </div>
        </div>

        <div id="filters-content-container">
        <div id="filters-content">

            <?php echo get_articles( 'provider' ); ?>

        </div>
        </div>

    </div>

</div>

<?php
get_footer();