<?php
/**
 * Tool for admins to auto fill out forms for results
 */
add_filter( 'gform_pre_render', 'mha_admin_populate_screen_tester' );
function mha_admin_populate_screen_tester( $form ) {
    
    if ( current_user_can( 'administrator' ) || current_user_can( 'editor' )  ):
    if ( strpos(strtolower($form['title']), 'test') !== false || strpos(strtolower($form['title']), 'quiz') !== false ):
    if ( !get_field('survey', get_the_ID() ) ):

        $current_page = GFFormDisplay::get_current_page( $form['id'] );
        $screen_id = get_the_ID();
        $custom_logic = get_field('custom_results_logic', $screen_id);
        $results = [];

        // Form header
        ?>

            <div class="bubble round-br pale-orange normal mb-5">
            <div class="inner">
                <form action="#" method="POST" id="admin-screen-tester<?php if($custom_logic){ echo '-custom'; } ?>">
                    <fieldset>
                        <legend class="mb-3"><strong>[Admin Only]</strong> Select a result to auto-fill</legend>
                        
                        <?php

                        if($custom_logic){

                            // Custom logic screen overrides
                            switch($custom_logic):

                                case 'eating_disorder':

                                    $eating_options = [
                                        [
                                            // BMI
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [124],
                                                'type' => 'input',
                                                'value' => 100
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [127],
                                                'type' => 'input',
                                                'value' => 100
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [130],
                                                'type' => 'input',
                                                'value' => 5
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [131],
                                                'type' => 'input',
                                                'value' => 6
                                            ],

                                            // Scores
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [49],
                                                'type' => 'checkbox',
                                                'value' => 100
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [47,48,51,70,71,72,73,74,75,64],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [50],
                                                'type' => 'checkbox',
                                                'value' => 100
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [60],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [61,62,63],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ],
                                            [
                                                'group' => 'Low Risk',
                                                'ids' => [65],
                                                'type' => 'checkbox',
                                                'value' => 'No'
                                            ],
                                            [
                                                'group' => 'At Risk for Eating Disorder',
                                                'ids' => [53,55,57,58,59],
                                                'type' => 'input',
                                                'value' => 0
                                            ]
                                        ],

                                        [
                                            [
                                                'group' => 'At Risk for Avoidant/Restrictive Food Intake Disorder (ARFID)',
                                                'ids' => [49,47,48,50,51,70,71,72,73,74,75,60,64],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ],
                                            [
                                                'group' => 'At Risk for Avoidant/Restrictive Food Intake Disorder (ARFID)',
                                                'ids' => [61,62,63],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ],
                                            [
                                                'group' => 'Low Risk',
                                                'ids' => [65],
                                                'type' => 'checkbox',
                                                'value' => 'No'
                                            ],
                                            [
                                                'group' => 'At Risk for Avoidant/Restrictive Food Intake Disorder (ARFID)',
                                                'ids' => [53,55,57,58,59],
                                                'type' => 'input',
                                                'value' => 0
                                            ]
                                        ],

                                        [
                                            [
                                                'group' => 'Low Risk',
                                                'ids' => [49,47,48,50,51,70,71,72,73,74,75,60,61,62,63,64,65],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ],
                                            [
                                                'group' => 'Low Risk',
                                                'ids' => [65],
                                                'type' => 'checkbox',
                                                'value' => 'No'
                                            ],
                                            [
                                                'group' => 'Low Risk',
                                                'ids' => [61,62,63],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ],
                                            [
                                                'group' => 'Low Risk',
                                                'ids' => [53,55,57,58,59],
                                                'type' => 'input',
                                                'value' => 0
                                            ]
                                        ]
                                    ];  

                                    $i = 0;
                                    foreach($eating_options as $eo){
                                        ?>
                                            <div class="d-block">
                                                <input type="radio" id="adminpre_13_<?php echo $i; ?>" name="adminpre_13" value="" data-values="<?php echo htmlspecialchars(json_encode($eo), ENT_QUOTES, 'UTF-8'); ?>" />
                                                <label for="adminpre_13_<?php echo $i; ?>" class="d-inline-block"><?php echo $eo[0]['group']; ?></label>
                                            </div>
                                        <?php
                                        $i++;
                                    }
                                break;


                                case 'addiction_2';
                                ?>

                                    <div class="d-block">
                                        <?php
                                            // Alcohol - 1
                                            $alcohol_1 = [];
                                            $alcohol_1[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $alcohol_1[] = [
                                                'ids' => [130,132,134,136,138,140,142,144,146,148,150],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $alcohol_1[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_1" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($alcohol_1), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_1" class="d-inline-block">Alcohol - 1 / Unlikely</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Alcohol - 2
                                            $alcohol_2 = [];
                                            $alcohol_2[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $alcohol_2[] = [
                                                'ids' => [130,132,134],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $alcohol_2[] = [
                                                'ids' => [136,138,140,142,144,146,148,150],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $alcohol_2[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_2" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($alcohol_2), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_2" class="d-inline-block">Alcohol - 2 / Mild concern</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Alcohol - 3
                                            $alcohol_3 = [];
                                            $alcohol_3[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $alcohol_3[] = [
                                                'ids' => [130,132,134,136,138],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $alcohol_3[] = [
                                                'ids' => [140,142,144,146,148,150],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $alcohol_3[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_3" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($alcohol_3), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_3" class="d-inline-block">Alcohol - 3 / Moderate</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Alcohol - 4
                                            $alcohol_4 = [];
                                            $alcohol_4[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $alcohol_4[] = [
                                                'ids' => [130,132,134,136,138,140,142,144,146,148,150],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $alcohol_4[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_4" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($alcohol_4), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_4" class="d-inline-block">Alcohol - 4 / Severe</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Drug - 5
                                            $drug_1 = [];
                                            $drug_1[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another drug or multiple drugs'
                                            ];
                                            $drug_1[] = [
                                                'ids' => [5,98,47,48,49,99,100,101,102,103,104],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $drug_1[] = [
                                                'ids' => [51.2],
                                                'type' => 'checkbox',
                                                'value' => 'Marijuana'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_5" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($drug_1), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_5" class="d-inline-block">Another Drug - 5 / Unlikely</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Drug - 6
                                            $drug_2 = [];
                                            $drug_2[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another drug or multiple drugs'
                                            ];
                                            $drug_2[] = [
                                                'ids' => [5,98,47],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $drug_2[] = [
                                                'ids' => [48,49,99,100,101,102,103,104],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $drug_2[] = [
                                                'ids' => [51.2],
                                                'type' => 'checkbox',
                                                'value' => 'Marijuana'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_6" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($drug_2), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_6" class="d-inline-block">Another Drug - 6 / Mild</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Drug - 7
                                            $drug_3 = [];
                                            $drug_3[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another drug or multiple drugs'
                                            ];
                                            $drug_3[] = [
                                                'ids' => [5,98,47,48,49],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $drug_3[] = [
                                                'ids' => [99,100,101,102,103,104],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $drug_3[] = [
                                                'ids' => [51.2],
                                                'type' => 'checkbox',
                                                'value' => 'Marijuana'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_7" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($drug_3), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_7" class="d-inline-block">Another Drug - 7 / Moderate</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Drug - 8
                                            $drug_4 = [];
                                            $drug_4[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another drug or multiple drugs'
                                            ];
                                            $drug_4[] = [
                                                'ids' => [5,98,47,48,49,99,100,101,102,103,104],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $drug_4[] = [
                                                'ids' => [51.2],
                                                'type' => 'checkbox',
                                                'value' => 'Marijuana'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_8" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($drug_4), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_8" class="d-inline-block">Another Drug - 8 / Severe</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Behavior - 9
                                            $behavior_1 = [];
                                            $behavior_1[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another behavior (gambling, self-harm, etc.)'
                                            ];
                                            $behavior_1[] = [
                                                'ids' => [131,133,135,137,139,141,143,145,147,149,151],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $behavior_1[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $behavior_1[] = [
                                                'ids' => [163],
                                                'type' => 'checkbox',
                                                'value' => 'More than once a day'
                                            ];
                                            $behavior_1[] = [
                                                'ids' => [165],
                                                'type' => 'checkbox',
                                                'value' => 'Strongly Disagree'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_9" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($behavior_1), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_9" class="d-inline-block">Another Behavior - 9 / Unlikely</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Behavior - 10
                                            $behavior_2 = [];
                                            $behavior_2[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another behavior (gambling, self-harm, etc.)'
                                            ];
                                            $behavior_2[] = [
                                                'ids' => [131,133,135],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $behavior_2[] = [
                                                'ids' => [137,139,141,143,145,147,149,151],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $behavior_2[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $behavior_2[] = [
                                                'ids' => [163],
                                                'type' => 'checkbox',
                                                'value' => 'More than once a day'
                                            ];
                                            $behavior_2[] = [
                                                'ids' => [165],
                                                'type' => 'checkbox',
                                                'value' => 'Strongly Disagree'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_10" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($behavior_2), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_10" class="d-inline-block">Another Behavior - 10 / Mild</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Behavior - 11
                                            $behavior_3 = [];
                                            $behavior_3[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another behavior (gambling, self-harm, etc.)'
                                            ];
                                            $behavior_3[] = [
                                                'ids' => [131,133,135,137,139],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $behavior_3[] = [
                                                'ids' => [141,143,145,147,149,151],
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ];
                                            $behavior_3[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $behavior_3[] = [
                                                'ids' => [163],
                                                'type' => 'checkbox',
                                                'value' => 'More than once a day'
                                            ];
                                            $behavior_3[] = [
                                                'ids' => [165],
                                                'type' => 'checkbox',
                                                'value' => 'Strongly Disagree'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_11" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($behavior_3), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_11" class="d-inline-block">Another Behavior - 11 / Moderate</label>
                                    </div>

                                    <div class="d-block">
                                        <?php
                                            // Another Behavior - 12
                                            $behavior_4 = [];
                                            $behavior_4[] = [
                                                'ids' => [127],
                                                'type' => 'checkbox',
                                                'value' => 'Another behavior (gambling, self-harm, etc.)'
                                            ];
                                            $behavior_4[] = [
                                                'ids' => [131,133,135,137,139,141,143,145,147,149,151],
                                                'type' => 'checkbox',
                                                'value' => 1
                                            ];
                                            $behavior_4[] = [
                                                'ids' => [51.3],
                                                'type' => 'checkbox',
                                                'value' => 'Marijuana'
                                            ];
                                            $behavior_4[] = [
                                                'ids' => [51.1],
                                                'type' => 'checkbox',
                                                'value' => 'Alcohol'
                                            ];
                                            $behavior_4[] = [
                                                'ids' => [163],
                                                'type' => 'checkbox',
                                                'value' => 'More than once a day'
                                            ];
                                            $behavior_4[] = [
                                                'ids' => [165],
                                                'type' => 'checkbox',
                                                'value' => 'Strongly Disagree'
                                            ];
                                        ?>
                                        <input type="radio" id="adminpre_58_12" name="adminpre_58" value="" data-values="<?php echo htmlspecialchars(json_encode($behavior_4), ENT_QUOTES, 'UTF-8'); ?>" />
                                        <label for="adminpre_58_12" class="d-inline-block">Another Behavior - 12 / Severe</label>
                                    </div>
                                <?php
                                break;


                                case 'bipolar';
                                    ?>

                                        <div class="d-block">
                                            <?php
                                                // Negative values
                                                $bipolar_negative_values = [];
                                                $bipolar_negative_values[] = [
                                                    'ids' => [47,50,51,52,53,54,55,56,57,58,59,60],
                                                    'type' => 'checkbox',
                                                    'value' => 0
                                                ];
                                                $bipolar_negative_values[] = [
                                                    'ids' => [61],
                                                    'type' => 'checkbox',
                                                    'value' => 0
                                                ];
                                                $bipolar_negative_values[] = [
                                                    'ids' => [62],
                                                    'type' => 'checkbox',
                                                    'value'=> 0
                                                ];
                                                $bipolar_negative_values[] = [
                                                    'ids' => [63,64],
                                                    'type' => 'checkbox',
                                                    'value'=> 'No'
                                                ];
                                            ?>
                                            <input type="radio" id="adminpre_13_1" name="adminpre_13" value="" data-values="<?php echo htmlspecialchars(json_encode($bipolar_negative_values), ENT_QUOTES, 'UTF-8'); ?>" />
                                            <label for="adminpre_13_1" class="d-inline-block">Bipolar Negative</label>
                                        </div>
                                        <div class="d-block">
                                            <?php
                                                // Positive values
                                                $bipolar_positive_values = [];
                                                $bipolar_positive_values[] = [
                                                    'ids' => [47,50,51,52,53,54,55,56,57,58,59,60],
                                                    'type' => 'checkbox',
                                                    'value' => 1
                                                ];
                                                $bipolar_positive_values[] = [
                                                    'ids' => [61],
                                                    'type' => 'checkbox',
                                                    'value' => 1
                                                ];
                                                $bipolar_positive_values[] = [
                                                    'ids' => [62],
                                                    'type' => 'checkbox',
                                                    'value'=> 2
                                                ];
                                                $bipolar_positive_values[] = [
                                                    'ids' => [63,64],
                                                    'type' => 'checkbox',
                                                    'value'=> 'No'
                                                ];
                                            ?>
                                            <input type="radio" id="adminpre_13_2" name="adminpre_13" value="" data-values="<?php echo htmlspecialchars(json_encode($bipolar_positive_values), ENT_QUOTES, 'UTF-8'); ?>" />
                                            <label for="adminpre_13_2" class="d-inline-block">Bipolar Positive</label>
                                        </div>

                                    <?php

                                break;

                            endswitch;

                        } else {

                            // Generic Tests
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

                                foreach($results as $k => $v): 
                                    $radio_id = 'adminpre_'.$screen_id.'_'.$k;
                                ?>
                                    <div class="d-block">
                                        <input type="radio" id="<?php echo $radio_id; ?>" name="adminpre_<?php echo $screen_id; ?>" value="" data-min="<?php echo $results[$k]['min']; ?>" data-max="<?php echo $results[$k]['max']; ?>" />
                                        <label for="<?php echo $radio_id; ?>" class="d-inline-block"><?php echo $results[$k]['title']; ?></label>
                                    </div>
                                <?php 
                                endforeach;
                            }
                        }

                        // Form footer
                        ?>
                        
                    </fieldset>
                </form>
            </div>
            </div>
        <?php

        $fields = $form['fields'];
        foreach( $form['fields'] as &$field ) {
            if( $field->label == 'Zip/Postal Code' || $field->label == 'Código postal' ){
                $_POST['input_'.$field->id] = 'TEST';
            }
        }

    endif; 
    endif; 
    endif; // End admin only
    
    return $form;
}
