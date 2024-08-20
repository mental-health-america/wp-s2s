<?php
/**
 * Tool for admins to auto fill out forms for results
 */
add_filter( 'gform_pre_render', 'mha_admin_populate_screen_tester' );
function mha_admin_populate_screen_tester( $form ) {
    
    if ( current_user_can( 'administrator' ) || current_user_can( 'editor' )  ):
    if ( strpos(strtolower($form['title']), 'test') !== false || strpos(strtolower($form['title']), 'survey') !== false || strpos(strtolower($form['title']), 'quiz') !== false ):

        $current_page = GFFormDisplay::get_current_page( $form['id'] );
        $screen_id = get_the_ID();
        $custom_logic = get_field('custom_results_logic', $screen_id) ? true : false;
        $results = [];

        if($custom_logic){
        ?>

            <div class="bubble round-br pale-orange normal mb-5">
            <div class="inner">
                Admin pre-fill is currently not available for this test.
            </div>
            </div>
        <?php
        }
        if(!$custom_logic && $current_page == 1){
            if( have_rows('results', $screen_id) ):
                while( have_rows('results', $screen_id) ) : the_row();
                    $results[get_row_index()] = array(
                        'title' => get_sub_field('result_title'),
                        'min' => get_sub_field('score_range_minimum'),
                        'max' => get_sub_field('score_range_max'),
                    );
                endwhile;        
            endif;
            ?>
                <div class="bubble round-br pale-orange normal mb-5">
                <div class="inner">
                    <form action="#" method="POST" id="admin-screen-tester">
                        <fieldset>
                            <legend class="mb-3"><strong>[Admin Only]</strong> Select a result to pre-fill</legend>
                            <?php 
                                foreach($results as $k => $v): 
                                    $radio_id = 'adminpre_'.$screen_id.'_'.$k;
                            ?>
                                <div class="d-block">
                                    <input type="radio" id="<?php echo $radio_id; ?>" name="adminpre_<?php echo $screen_id; ?>" value="" data-min="<?php echo $results[$k]['min']; ?>" data-max="<?php echo $results[$k]['max']; ?>" />
                                    <label for="<?php echo $radio_id; ?>" class="d-inline-block"><?php echo $results[$k]['title']; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </fieldset>
                    </form>
                </div>
                </div>
            <?php
        }

        $fields = $form['fields'];
        foreach( $form['fields'] as &$field ) {
            if( $field->label == 'Zip/Postal Code' ){
                $_POST['input_'.$field->id] = 'TEST';
            }
        }

    endif; 
    endif; // End admin only
    
    return $form;
}