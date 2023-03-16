<?php 
    $layout = isset($args['layout']) ? $args['layout'] : array();
    $user_screen_result = $args['user_screen_result'];
    $espanol = isset($args['espanol']) ? $args['espanol'] : false;
?>

<div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="score-interpretation">
<div class="inner small">
    <div class="container-fluid">
        <!--<h3 class="section-title dark-teal mb-4">Interpretation of Scores</h3>-->
        <?php the_field('interpretation_of_scores', $user_screen_result['screen_id']); ?>
    </div>
</div>
</div>     

<div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 collapse anchor-content" id="your-answers">
<div class="inner small">
    <div class="container-fluid p-0">
        <?php 
            echo ($espanol ? '<h3 class="section-title dark-teal mb-4">Sus respuestas</h3>' : '<h3 class="section-title dark-teal mb-4">Your Answers</h3>');
            echo $user_screen_result['your_answers']; 
        ?>
    </div>
</div>
</div>