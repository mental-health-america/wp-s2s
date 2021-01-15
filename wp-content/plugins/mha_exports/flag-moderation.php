<?php
function mhaflaggedthoughtmod(){
?>

<div class="wrap">


<h1>Flagged Thoughts</h1>	
<p></p>

<?php

global $wpdb;
if($_GET['pager']){
    $paged = intval($_GET['pager']);
} else {
    $paged = 0;
}

if($_GET['show_admin']){
    $show_admin = intval($_GET['show_admin']);
} else {
    $show_admin = 0;
}

if($show_admin == 0){
    echo '<p><a class="button button-primary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&show_admin=1">Show Flags with Admin Notes</a></p>';
} else {
    echo '<p><a class="button button-primary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&show_admin=0">Hide Flags with Admin Notes</a></p>';
}
?>

<?php

$items = 100;
$offset = $paged * $items;
$flag_query = $wpdb->get_results( 'SELECT * FROM thoughts_flags ORDER BY date DESC LIMIT '.$offset.', '.$items );

if($flag_query){ ?>

    <table class="wp-list-table widefat striped">
        <tr>
            <th class="text-left">Flagged By</th>
            <th class="text-left">Date</th>
            <th class="text-left">Comment</th>
            <th class="text-left">Admin Notes</th>
            <th class="text-left">Edit</th>
        </tr>
        <?php foreach($flag_query as $flag): ?>

            <?php
                // Skip moderated flags
                if($show_admin == 0){ 
                    if(get_field('admin_notes', $flag->pid)){
                        continue;
                    }
                }
            ?>

            <tr>
                <td>
                    <?php 
                        $user = get_userdata($flag->uid); 
                        echo '<a href="'.get_edit_user_link($flag->uid).'">';
                        echo $user->user_login;
                        echo '</a>';
                    ?>
                </td>
                <td>
                    <?php echo $flag->date; ?>
                </td>
                <td>
                    <?php
                        $responses = get_field('responses', $flag->pid);
                        $type = get_post_type($flag->pid);
                        //echo $flag->row;
                        $edit_pid = $flag->pid;

                        if($type == 'thought_activity'){

                            // Admin seeded thought
                            $initial_thought = get_field('pre_generated_responses', $flag->pid);
                            echo '<strong>Admin Seeded Thought:</strong><br />'.$initial_thought[$flag->row]['response'];

                        } else {

                            // Other thoughts
                            echo $responses[$flag->row]['response'];   

                        }

                    ?>
                </td>
                <td>
                    <?php the_field('admin_notes', $flag->pid); ?>
                </td>
                <td>
                    <?php edit_post_link('Edit', '', '', $edit_pid); ?>
                </td>
            </tr>
        <?php endforeach;?> 
    </table>
    <br /><br />

    <?php if($paged > 0): ?>
        <a class="button button-secondary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&pager=<?php echo $paged - 1 ; ?>&show_admin=<?php echo $show_admin; ?>">Previous Page</a>
    <?php endif; ?>
    <a class="button button-secondary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&pager=<?php echo $paged + 1; ?>&show_admin=<?php echo $show_admin; ?>">Next Page</a>

    <?php
        } else {
            echo '<p>Nothing to display.</p>';
            if($paged > 0): ?>
                <a class="button button-secondary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&pager=<?php echo $paged - 1 ; ?>&show_admin=<?php echo $show_admin; ?>">Previous Page</a>
            <?php endif;
        } 
    ?>
    
</div>

<?php
}
?>