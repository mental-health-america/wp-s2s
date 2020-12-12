<?php
/**
 * The front page template file
 *
 * If the user has selected a static page for their homepage, this is what will
 * appear.
 * Learn more: https://codex.wordpress.org/Template_Hierarchy
 *
 * @package MHA S2S
 * @subpackage MHA S2S
 * @since 1.0
 * @version 1.0
 */

get_header(); 
?>

<div class="wrap wide">
    <div class="bubble round-small-tl">
        <div class="inner">
<?php 
    the_title('<h1>','</h1>');
    the_content();
?>

<?php get_footer();