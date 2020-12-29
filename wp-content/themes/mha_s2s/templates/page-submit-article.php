<?php 
/* Template Name: Submit Article */
acf_form_head();
get_header(); 
?>


<div class="wrap medium">
    <?php
        while ( have_posts() ) : the_post();
            get_template_part( 'templates/blocks/content', 'plain' );
        endwhile;        
    ?>
</div>

<div class="wrap medium">    
<div class="bubble round-tl light-blue">
<div class="inner">
    <?php 
        acf_form(array(
            'post_id'           => 'new_post',                
            'post_content'      => true,
            'post_title'        => true,
            'updated_message'   => get_field('thank_you_message'),
            'honeypot'          => true,
            'new_post'          => array(
                'post_type'     => 'article',
                'post_status'   => 'draft'
            ),
            'fields' => array(
                'field_5fd3eec524b34', // Type
                'field_5fd3f1a935255', // DIY Type
                'field_5fea345c4d25c', // DIY Issue
                'field_5fd3f7a3951ad', // Treatment Type
                'field_5fd3eef624b35', // Area Served
                'field_5fdc0a1448b13', // Service Type
                'field_5fd3ef47dab98', // Location

                'field_5fea2f2863cdb', // Condition
                'field_5fea2f4463cdc', // Age

                'field_5fea2f6663cdd', // Featured Image
                'field_5fea2f7063cde', // Link

                'pricing_information', // Pricing
                'field_5fea3372584f9', // Privacy
                'field_5fea337d584fa', // Disclaimer
                'field_5fea327fa3cc0', // Accolades
                
                'field_5fea2e5763cd8', // Contact Name
                'field_5fea359ded711', // Contact Title
                'field_5fea2ee063cd9', // Contact Email
                'field_5fea2ee963cda', // Contact Phone
               

            ),
            'submit_value'  => 'Submit Article for Review'
        )); 
    ?>
</div>
</div>
</div>

<?php get_footer(); ?>