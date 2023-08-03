<?php
/**
 * Plugin Name: MHA - SSO Options
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Import tools for MHA
 */


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Mythril Core
 */
class MHA {

	public function signin(){  

        require_once 'vendor/autoload.php';

        $client_id = '819582484303-rn798qps23jaqga8lo70fs4q9rijih25.apps.googleusercontent.com';
        $client_secret = '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';
        

        // Creating new google client instance
        $client = new Google_Client();
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri('https://mhanationalstg.wpengine.com/sso');
        $client->addScope("email");
        $client->addScope("profile");

        if(isset($_GET['code'])):
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
            if(!isset($token["error"])){
                $client->setAccessToken($token['access_token']);
                $google_oauth = new Google_Service_Oauth2($client);
                $google_account_info = $google_oauth->userinfo->get();
            
                // Storing data into database
                /*
                $id = mysqli_real_escape_string($db_connection, $google_account_info->id);
                $full_name = mysqli_real_escape_string($db_connection, trim($google_account_info->name));
                $email = mysqli_real_escape_string($db_connection, $google_account_info->email);
                $profile_pic = mysqli_real_escape_string($db_connection, $google_account_info->picture);
                $get_user = mysqli_query($db_connection, "SELECT `google_id` FROM `users` WHERE `google_id`='$id'");
                */

                pre($google_account_info);

                return;

                if(mysqli_num_rows($get_user) > 0){
                    $_SESSION['login_id'] = $id; 
                    header('Location: https://mhanationalstg.wpengine.com?sso=google');
                    exit;
                } else{
                    // if user not exists we will insert the user
                    // $insert = mysqli_query($db_connection, "INSERT INTO `users`(`google_id`,`name`,`email`,`profile_image`) VALUES('$id','$full_name','$email','$profile_pic')");
                    if($insert){
                        $_SESSION['login_id'] = $id; 
                        header('Location: https://mhanationalstg.wpengine.com?sso=google');
                        exit;
                    }
                    else{
                        echo "Sign up failed! (Something went wrong).";
                    }
                }
            }
            else{
                header('Location: login.php');
                exit;
            }
            
        else: 
        ?>            
            <a type="button" class="login-with-google-btn" href="<?php echo $client->createAuthUrl(); ?>">
                Sign in with Google
            </a>
        <?php endif;

    }

}