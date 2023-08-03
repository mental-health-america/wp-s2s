<?php 
/* Template Name: SSO */
get_header(); 
?>

<?php
    $mha_sso = new MHA();
    echo $mha_sso->signin;
?>

<?php
get_footer();