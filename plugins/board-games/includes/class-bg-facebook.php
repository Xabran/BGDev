<?php
/**
 * Package Included for Board Games Plugin
 * 
 * Do not Modify this under any circumtances
 *
 * @package     Board Games 
 * @subpackage  Board Games Facebook API
 * @author      SilverKenn
 * @version     1.0
 */
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Facebook' ) ) {
    require( BR_DIR .'/includes/facebook/facebook.php');
}

// Duplication is not allowed
if( !class_exists( 'BG_Facebook' ) ) {

    //Lets Rock!
    class BG_Facebook {

        public $fb = array();
        
        public function __construct() {

            $this->fb = new Facebook( apply_filters( 'bg/fbapp/data' , array(
                'appId'     => '748091538669166',
                'secret'    => '4201d19fbd2cfaa7dced368f57621b06'
            ) ) );

            $this->connect();
        }

        public function connect() {
            add_filter('init', array( $this, 'login_var' ) );
            add_action('parse_request', array( $this, 'query' ) );
            add_action('login_init', array( $this, 'fb_login' ) );
            add_filter('get_avatar', array( $this, 'avatar' ), 5, 5);

            add_shortcode('brgm_fb_link' , array( $this, 'login_link') );
            add_shortcode('brgm_fb_url' , array( $this, 'login_url') );
            #add_action( 'wp_head', array( $this, 'bah' ) );

        }

        public function bah() {

            $user = $this->fb->getUser();
            if ( $user ) $user = $this->fb->api('/me', 'GET', array('fields' => 'id,name,email,first_name,last_name,birthday,location'));
            echo '<pre>Bah- ', print_r( $user, 1 ), '</pre>';
        }

        public function login_var() {
            global $wp;
            $wp->add_query_var('editProfileRedirect');
            $wp->add_query_var('loginFacebook');
            $wp->add_query_var('loginFacebookdoauth');
        }
        
        public function query() {
            global $wp;
            if ( $wp->request == 'loginFacebook' || isset($wp->query_vars['loginFacebook'] ) ) {
                $this->bg_fb_action();
            }
        }

        public function fb_login() {
            if ( isset( $_REQUEST['loginFacebook'] ) && $_REQUEST['loginFacebook'] == 'yes' ) {
                $this->bg_fb_action();
            }
        }

        public function bg_fb_action() {

            global $wp, $wpdb;
            if ( isset($_GET['action']) && $_GET['action'] == 'unlink') {
                $user_info = wp_get_current_user();
                if ($user_info->ID) {
                    $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->prefix . 'social_users
                    WHERE ID = %d
                    AND type = \'fb\'', $user_info->ID));
                    set_site_transient($user_info->ID.'_new_fb_admin_notice',__('Your Facebook profile is successfully unlinked from your account.', 'bgrm'), 3600);
                }
                 //CHECK FOR REVIEW HERE:
                if($this->checkForReviewSubmit()){
                    review_autosubmit($current_user->ID, 'fb'); //will redirect
                }else{
                    $this->redirect();
                }
            }
            $user = $this->fb->getUser();

            if ( $user && is_user_logged_in() && $this->connected() ) {
                //CHECK FOR REVIEW HERE:
                if($this->checkForReviewSubmit()){
                    review_autosubmit($current_user->ID, 'fb'); //will redirect
                }else{
                    $this->redirect();
                }
            } elseif ($user) {

                try {

                    $user_profile = $this->fb->api('/me', 'GET', 
                        array('fields' => 'id,name,email,first_name,last_name,birthday,location'));
                    $ID = $wpdb->get_var($wpdb->prepare('
                    SELECT ID FROM ' . $wpdb->prefix . 'social_users WHERE type = "fb" AND identifier = "%d"
                    ', $user_profile['id']));
                    if (!get_user_by('id', $ID)) {
                        $wpdb->query($wpdb->prepare('
                        DELETE FROM ' . $wpdb->prefix . 'social_users WHERE ID = "%d"
                        ', $ID));
                        $ID = null;
                    }
                    if ( !is_user_logged_in() ) {
                        if ($ID == NULL) { // Register

                            if (!isset($user_profile['email'])) $user_profile['email'] = $user_profile['id'] . '@facebook.com';
                            $ID = email_exists($user_profile['email']);

                            if ($ID == false) { // Real register

                                require_once (ABSPATH . WPINC . '/registration.php');
                                $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                                $username = strtolower( $user_profile['first_name'] . $user_profile['last_name'] );
                                $sanitized_user_login = sanitize_user( ''. $username);
                                
                                if (!validate_username($sanitized_user_login)) {
                                    $sanitized_user_login = sanitize_user('' . $user_profile['id']);
                                }

                                $defaul_user_name = $sanitized_user_login;
                                $i = 1;
                                while (username_exists($sanitized_user_login)) {
                                    $sanitized_user_login = $defaul_user_name . $i;
                                    $i++;
                                }
                                $ID = wp_create_user($sanitized_user_login, $random_password, $user_profile['email']);
                                if (!is_wp_error($ID)) {
                                    wp_new_user_notification($ID, $random_password);
                                    $user_info = get_userdata($ID);
                                    wp_update_user(array(
                                        'ID' => $ID,
                                        'display_name' => $user_profile['name'],
                                        'first_name' => $user_profile['first_name'],
                                        'last_name' => $user_profile['last_name']
                                    ));

                                    //update_user_meta( $ID, 'new_fb_default_password', $user_info->user_pass);
                                    do_action('bg_user_registered', $ID, $user_profile, $this->fb);
                                } else {
                                    return;
                                }
                            }
                            if ($ID) {
                                $wpdb->insert($wpdb->prefix . 'social_users', array(
                                    'ID' => $ID,
                                    'type' => 'fb',
                                    'identifier' => $user_profile['id']
                                ) , array( '%d', '%s', '%s' ));
                            }
                        }

                        if ($ID) { // Login

                            $secure_cookie = is_ssl();
                            $secure_cookie = apply_filters('secure_signon_cookie', $secure_cookie, array());
                            global $auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie

                            $auth_secure_cookie = $secure_cookie;
                            wp_set_auth_cookie($ID, true, $secure_cookie);
                            $user_info = get_userdata($ID);
                            update_user_meta($ID, 'fb_profile_picture', 'https://graph.facebook.com/' . $user_profile['id'] . '/picture?type=large');
                            do_action('wp_login', $user_info->user_login, $user_info);
                            update_user_meta($ID, 'fb_user_access_token', $this->fb->getAccessToken());
                            do_action('bg_fb_user_logged_in', $ID, $user_profile, $this->fb );                            
                        }
                    } else {
                        $current_user = wp_get_current_user();
                        if ($current_user->ID == $ID) {

                        } elseif ($ID === NULL) { // Let's connect the accout to the current user!

                            $wpdb->insert($wpdb->prefix . 'social_users', array(
                            'ID' => $current_user->ID,
                            'type' => 'fb',
                            'identifier' => $user_profile['id']
                            ) , array( '%d', '%s', '%s' ));
                            update_user_meta($current_user->ID, 'fb_user_access_token', $this->fb->getAccessToken());
                            do_action('bg_fb_user_account_linked', $ID, $user_profile, $this->fb);
                            $user_info = wp_get_current_user();
                            set_site_transient($user_info->ID.'_new_fb_admin_notice',__('Your Facebook profile is successfully linked with your account. Now you can sign in with Facebook easily.', 'bgrm'), 3600);
                        } else {
                            $user_info = wp_get_current_user();
                            set_site_transient($user_info->ID.'_new_fb_admin_notice',__('This Facebook profile is already linked with other account. Linking process failed!', 'bgrm'), 3600);
                        }
                    }
                    
                    //CHECK FOR REVIEW HERE:
                    if($this->checkForReviewSubmit()){
                        review_autosubmit($current_user->ID, 'fb'); //will redirect
                    }else{
                        $this->redirect();
                    }
                }
                catch(FacebookApiException $e) {
                    echo 'Caught exception: ', $e->getMessage() , "\n";
                    $user = null;
                }
                exit;
            } else if( !isset($_GET['code']) ) {
                $scope = apply_filters('bg_fb_scope', 'email,user_birthday,user_location');
                $loginUrl = $this->fb->getLoginUrl(array(
                    'scope' => $scope
                ));            
                header('Location: ' . $loginUrl);
                exit;
            } else {
                echo "Login error!";
                exit;
            }
        }
        
        public function checkForReviewSubmit()
        {
            //CHECK FOR REVIEW HERE:
            if(isset($_COOKIE['RstarRating'])){
                return true;
            }else{
                return false;
            }
        }

        public function avatar($avatar = '', $id_or_email, $size = 96, $default = '', $alt = false) {
            $id = 0;
            if (is_numeric($id_or_email)) {
                $id = $id_or_email;
            } else if (is_object($id_or_email)) {
                $id = $id_or_email->user_id;
            } else {
                $id = get_user_by( 'email', $id_or_email ); 
            }
            if ($id == 0) return $avatar;
            $pic = get_user_meta($id, 'fb_profile_picture', true);
            if (!$pic || $pic == '') return $avatar;
            $avatar = preg_replace('/src=("|\').*?("|\')/i', 'src=\'' . $pic . '\'', $avatar);
            return $avatar;
        }

        public function connected() {
            global $wpdb;
            $current_user = wp_get_current_user();
            $ID = $wpdb->get_var($wpdb->prepare('
            SELECT identifier FROM ' . $wpdb->prefix . 'social_users WHERE type = "fb" AND ID = "%d"
            ', $current_user->ID));
            if ($ID === NULL) return false;
            return $ID;
        }
        public function redirect() {
            $url = $_COOKIE['bg_redirect'];
            $redirect = ( $url ) ? $url : site_url().'/account' ;
            $redirect = wp_sanitize_redirect($redirect);
            $redirect = wp_validate_redirect($redirect, site_url());
            header('LOCATION: ' . $redirect);
            exit;
        }

        public static function login_url() {
            //return site_url('wp-login.php') . '?loginFacebook=yes';
            return site_url('/account/') . '?loginFacebook=yes';
        }
        public static function login_link( $icon = null ) {
            $title = ( $icon ) ? $icon : '<img class="bglogin" src="'.BR_URL.'img/fb.png" title="Login with Facebook">' ;
            $orText = '';
            if(!is_user_logged_in() && !is_page('account') && !is_page('checkout') && !is_product()){
                $orText = "or<br/>";
            }
            return ( !is_user_logged_in() ) ? $orText.'<a href="' . esc_url( self::login_url() ) . '" rel="nofollow">'. $title .'</a>' : '' ;
        }
    }   
    new BG_Facebook;
}