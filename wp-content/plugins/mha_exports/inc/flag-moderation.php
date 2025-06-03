<?php

function get_mha_flagged_diy_response( $type, $responses, $key ){

    if($type == 'diy_responses'){
        if(isset($responses) && is_array($responses) && isset($key)){
            foreach($responses as $r){
                if($r['id'] == $key){
                    return $r['answer'];
                }
            }
        }
    }

    return false;

}

function mhaflaggedthoughtmod(){
?>

<div class="wrap">


<h1>Flagged Thoughts</h1>	
<p></p>

<?php

global $wpdb;
if(isset($_GET['pager'])){
    $paged = intval($_GET['pager']);
} else {
    $paged = 0;
}

if(isset($_GET['show_admin'])){
    $show_admin = intval($_GET['show_admin']);
} else {
    $show_admin = 0;
}

$items = 100;
$offset = $paged * $items;

if($show_admin == 0){
    
    $flag_query = $wpdb->get_results( 'SELECT * FROM thoughts_flags ORDER BY date DESC LIMIT '.$offset.', '.$items );
    echo '<p><a class="button button-primary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&show_admin=1">Show Flags with Admin Notes</a></p>';

} else {

    echo '<p><a class="button button-primary" href="/wp-admin/admin.php?page=mhaflaggedthoughtmod&show_admin=0">Hide Flags with Admin Notes</a></p>';
    $flag_args = array(
        "post_type"     	=> 'diy_responses',
        "order"             => 'DESC',
        "orderby"           => 'date',
        "post_status"       => array('publish'),
        "posts_per_page"    => $items,
        "paged"             => $paged,
        "meta_query"		=> array(
            array(
                'key'       => 'admin_notes',
                'value'     => '',
                'compare'   => '!='
            )
        )
    );
    $flag_loop = new WP_Query($flag_args);      
    $flag_query = $flag_loop->posts;
}


if($flag_query){ ?>

    <table class="wp-list-table widefat striped">
        <tr>
            <th class="text-left">
                <?php 
                    if($show_admin == 0){
                        echo 'Flagged By';
                    } else {
                        echo 'Admin Note Author';
                    }
                ?></th>
            <th class="text-left">
                <?php 
                    if($show_admin == 0){
                        echo 'Date';
                    } else {
                        echo 'Admin Note Date';
                    }
                ?>
            </th>
            <th class="text-left">Comment</th>
            <th class="text-left">Admin Notes</th>
            <th class="text-left">Edit</th>
        </tr>
        <?php foreach($flag_query as $flag): ?>

            <?php

                // General Vars
                $pid = $show_admin == 0 ? $flag->pid : $flag->ID;

                // Skip if there is an admin note
                $admin_note = get_field('admin_notes', $pid);
                if($admin_note && $show_admin == 0){
                    continue;
                }

                $type = get_post_type($pid);

                // Thoughts
                $responses = null;
                if(get_field('responses', $pid)){
                    $responses = get_field('responses', $pid);
                }

                // DIY Tools
                if(get_field('response', $pid)){
                    $responses = get_field('response', $pid);
                }

                // Skips
                if(!$responses){
                    //echo '1';
                    continue;
                }
                if($type == 'thought'){
                    if(is_numeric($responses[$flag->row]['admin_pre_seeded_thought']) || $responses[$flag->row]['response'] == ''){
                        //echo '2';
                        continue;
                    }
                    if($type == 'thought_activity' || $responses[$flag->row]['hide'] == 1 || get_field('admin_notes', $pid) || $responses[$flag->row]['response'] == ''){
                        //echo '3';
                        continue;
                    }
                }
                if($type == 'diy_responses'){
                    if($show_admin == 0){
                        if(get_field('crowdsource_hidden', $flag->pid) || get_field('admin_notes', $flag->pid) || !get_mha_flagged_diy_response( 'diy_responses', $responses, $flag->row ) ){
                            //echo '4';
                            continue;
                        }
                    }
                }
            ?>

            <tr>
                <td>
                    <?php 
                        if($show_admin == 0){
                            $user = get_userdata($flag->uid); 
                            echo '<a href="'.get_edit_user_link($flag->uid).'">';
                            echo $user->user_login;
                            echo '</a>';
                        } else {
                            $mod_id = get_post_meta( $pid, '_edit_last', true );
                            $mod_user = get_userdata( $mod_id );
                            echo $mod_user->user_login;
                        }
                    ?>
                </td>
                <td>
                    <?php 
                        if($show_admin == 0){
                            echo date( 'm-d-Y', strtotime($flag->date) ); 
                        } else {
                            echo get_the_modified_date('m-d-Y', $pid);
                        }
                    ?>
                </td>
                <td>
                    <?php
                        $type = get_post_type($pid);
                        $edit_pid = $pid;
                        
                        if($type == 'thought'){
                            echo '<strong>Thoughts</strong><br />';
                            if(is_numeric($responses[$flag->row]['user_pre_seeded_thought'])){
                                $user_response = get_field('responses', $responses[$flag->row]['user_pre_seeded_thought']);
                                $edit_pid = $responses[$flag->row]['user_pre_seeded_thought'];
                                echo $user_response[$flag->row]['response'];                                       
                            }
                            else if(isset($responses[$flag->row]['response']) && $responses[$flag->row]['response'] != ''){
                                echo $responses[$flag->row]['response'];   
                            } else {
                                echo '<em>&mdash; Thought Deleted &mdash;</em>';
                            }
                        }

                        if($type == 'diy_responses'){
                            echo '<strong>DIY Tool</strong><br />';

                            if($show_admin == 0){
                                if( get_mha_flagged_diy_response( 'diy_responses', $responses, $flag->row ) ){
                                    echo get_mha_flagged_diy_response( 'diy_responses', $responses, $flag->row );  
                                } else {
                                    echo '<em>&mdash; Thought Deleted &mdash;</em>';
                                }
                            } else {

                                $flagged_response = $wpdb->get_results( 'SELECT * FROM thoughts_flags WHERE pid = '.$pid.' GROUP BY pid');
                                foreach($flagged_response as $fr){
                                    echo $responses[$fr->row]['answer'].'<br />';                                       
                                }

                            }

                        }
                    ?>
                </td>
                <td>
                    <?php the_field('admin_notes', $pid); ?>
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