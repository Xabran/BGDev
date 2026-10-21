<?php
/*
Plugin Updater
Author: Mark Joseph
Author URI: http://infusedaddons.com
License: MIT
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ia_auto_update
{  
    /** 
     * The plugin current version 
     * @var string 
     */  
    public $current_version;  
    /** 
     * The plugin remote update path 
     * @var string 
     */  
    public $update_path;  
    /** 
     * Plugin Slug (plugin_directory/plugin_file.php) 
     * @var string 
     */  
    public $plugin_slug;  
    /** 
     * Plugin name (plugin_file) 
     * @var string 
     */  
    public $slug;  
    /** 
     * Initialize a new instance of the WordPress Auto-Update class 
     * @param string $current_version 
     * @param string $update_path 
     * @param string $plugin_slug 
     */  
    function __construct($current_version, $update_path, $plugin_slug, $licensekey)  
    {  
        // Set the class public variables  
        $this->current_version = $current_version;  
        
        $sh = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $this->update_path = $update_path . "?d=" . $sh . "&" . "l=" . $licensekey;  
        $this->lic_key = $licensekey;
        
        
        $this->remote_version = $this->getRemote_version();

        // If a newer version is available, check license
        if (version_compare($this->current_version, $this->remote_version, '<')) {  
            if(empty($this->lic_key))
                $this->lic_validate   = 'empty';
            else
                $this->lic_validate   = 'valid';

        } else $this->lic_validate = "skipped";


        $this->plugin_slug = $plugin_slug;  
        list ($t1, $t2) = explode('/', $plugin_slug);  
        $this->slug = str_replace('.php', '', $t2);  
        // define the alternative API for updating checking  
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));  
        // Define the alternative response for information checking  
        add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);  


    }  
    /** 
     * Add our self-hosted autoupdate plugin to the filter transient 
     * 
     * @param $transient 
     * @return object $ transient 
     */  
    public function check_update($transient)  
    {  
        if (empty($transient->checked)) {  
            return $transient;  
        }  

        // If a newer version is available, add the update  
        if (version_compare($this->current_version, $this->remote_version, '<')) {  
            $obj = new stdClass();  
            $obj->slug = $this->slug;  
            $obj->new_version = $this->remote_version;  
            $obj->url = $this->update_path;  
            $obj->package = $this->update_path;  
            $transient->response[$this->plugin_slug] = $obj;  
        }  
        return $transient;  
    }  
    /** 
     * Add our self-hosted description to the filter 
     * 
     * @param boolean $false 
     * @param array $action 
     * @param object $arg 
     * @return bool|object 
     */  

    public function check_info($false, $action, $arg)  
    {  
        if (isset($arg->slug) && $arg->slug === $this->slug) {  
            $information = $this->getRemote_information();  
            return $information;  
        }  
        return false;  
    } 

    public function validateLicense()  
    {  
        $sh = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'validate', 'host' => $sh, 'lic' => $this->lic_key)));  
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
            return $request['body'];  
        }  
        return false;     
    }  
    /** 
     * Return the remote version 
     * @return string $remote_version 
     */  
    public function getRemote_version()  
    {  
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'version')));  
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
            return $request['body'];  
        }  
        return false;  
    }  
    /** 
     * Get information about the remote version 
     * @return bool|object 
     */  
    public function getRemote_information()  
    {  
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'info')));  
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
            return unserialize($request['body']);  
        }  
        return false;  
    }  
    /** 
     * Return the status of the plugin licensing 
     * @return boolean $remote_license 
     */  
    public function getRemote_license()  
    {  
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'license')));  
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {  
            return $request['body'];  
        }  
        return false;  
    }  
}

function iwpro_autoupdate()  {  
    if( is_admin() && current_user_can('update_plugins') ) {   
        global $iwpro; 
        $plugin_current_version = INFUSEDWOO_PRO_VER;  

        $plugin_remote_path = INFUSEDWOO_PRO_UPDATER;  
        $plugin_slug = INFUSEDWOO_PRO_BASE;  
        $autoupdate = new ia_auto_update ($plugin_current_version, $plugin_remote_path, $plugin_slug, $iwpro->lic_key);

        set_transient( 'infusedwoo_remote_ver', $autoupdate->remote_version );
        set_transient( 'infusedwoo_lic_validate', $autoupdate->lic_validate );
    }
} 

function iwpro_check_autoupdate() {
    $last_check = get_transient('infusedwoo_autoupdate_last_check' );

    if(time() > ($last_check + 12*3600)) {
        iwpro_autoupdate();
    }
}

function iwpro_update_notices() {
    if( is_admin() && current_user_can('update_plugins')) {
        if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) 
            $wcs = 'wc-settings';
        else
            $wcs = 'woocommerce_settings';

        ?>
        <style type="text/css">
            .infusedwoo-alerts-over {
              padding:  2px 4px 4px 130px !important;
              background-color: #293D67 !important; 
              margin-left: 18px; color: white; 
              margin-top: 4px;
              background-image: url('<?php echo INFUSEDWOO_PRO_URL . "images/infusedwoo.png" ?>') !important;
              background-size: 117px 30px !important;
              background-repeat: no-repeat !important;
              background-position: 5px 6px !important; 
            }

            .infusedwoo-alerts-over a {
              color: #94D020 !important;
            }
        </style>

        <?php
        $remote_ver = get_transient('infusedwoo_remote_ver');
        $lic_validate = get_transient('infusedwoo_lic_validate');

        if(version_compare($remote_ver, INFUSEDWOO_PRO_VER) === 1) {
            if($lic_validate == "invalid") {
                echo '<div class="error infusedwoo-alerts infusedwoo-alerts-over"><p>'.sprintf(__('There is an update available ( <a href="%s" style="color:green;"><u>See Version %s Update</u></a> ) but it seems that your license key is not valid. Make sure you <a href="%s">entered your license key correctly.</a>', 'woothemes'), admin_url("admin.php?page=infusedwoo-menu-2&submenu=update"),$remote_ver, admin_url("admin.php?page=infusedwoo-menu-2&submenu=update")).'</p></div>';
            } else if($lic_validate == "exceed") {
                echo '<div class="error infusedwoo-alerts infusedwoo-alerts-over"><p>'.sprintf(__('There is an update available ( <a href="%s" style="color:green;"><u>See Version %s Update</u></a> ) but our servers noticed that the license key has reached its limit (domain count exceeded). Upgrade your license or contact support.</a>', 'woothemes'), admin_url("admin.php?page=infusedwoo-menu-2&submenu=update"),$remote_ver , admin_url("admin.php?page=infusedwoo-menu-2&submenu=update")).'</p></div>';
            }  else if($lic_validate == "empty") {
                echo '<div class="error infusedwoo-alerts infusedwoo-alerts-over"><p>'.sprintf(__('There is an update available ( <a href="%s" style="color:green;"><u>See Version %s Update</u></a> ). To get this update, please <a href="%s">update your license key in the settings.</a>', 'woothemes'),admin_url("admin.php?page=infusedwoo-menu-2&submenu=update"), $remote_ver , admin_url("admin.php?page=infusedwoo-menu-2&submenu=update")).'</p></div>';
            }  else if($lic_validate == "valid") {
                echo '<div class="updated infusedwoo-alerts infusedwoo-alerts-over"><p>'.sprintf(__('There is a new update available ( <a href="%s" style="color:green;"><u>See Version %s Update</u></a> ). <a href="%s"><u>Click here to update</u>.</a>', 'woothemes'), admin_url("admin.php?page=infusedwoo-menu-2&submenu=update"), $remote_ver, admin_url("admin.php?page=infusedwoo-menu-2&submenu=update")) .'</p></div>';
            }  else if($lic_validate == "expired") {
                echo '<div class="error infusedwoo-alerts infusedwoo-alerts-over"><p>'.sprintf(__('There is a new update available ( <a href="%s" style="color:green;"><u>See Version %s Update</u></a> ). But your license key has already expired. To update, renew your license <a href="%s" target="_blank">in the customer portal</a>. Renew your license within 30 days after license expiration to get 50% discount.', 'woothemes'), admin_url("admin.php?page=infusedwoo-menu-2&submenu=update"), $remote_ver, 'https://infusedaddons.com/portal') .'</p></div>';
            }
        }   
    }
}



add_action('iwpro_ready', 'iwpro_check_autoupdate', 10, 0);
add_action('admin_notices', 'iwpro_update_notices', 10, 0) 


?>