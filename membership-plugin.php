<?php
/*
Plugin Name: State Membership Plugin
Description: Custom membership with state code validation and payment integration
Version: 1.0.10
Author: Devjoo
*/
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ABSPATH')) exit; // Security check
define("STATE_MEMBERSHIP_PLUGIN_VERSION", "1.0.5");
define("STATE_MEMBERSHIP_PLUGIN_SCRIPTS_VERSION", "1.0.9");

if(get_option('_state_membership_version') !== STATE_MEMBERSHIP_PLUGIN_VERSION){
    delete_option( 'membership_plugin_settings' );
    update_option( '_state_membership_version', STATE_MEMBERSHIP_PLUGIN_VERSION );
}

class StateMembershipPlugin {
    public function __construct() {
        // Include necessary files
        $this->include_files();
        
        // Register hooks
        $this->init_hooks();
    }

    private function include_files() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-state-code-validator.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-membership-signup.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-membership-login.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-membership-admin.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-membership-user-dashboard.php';
    }

    private function init_hooks() {        
        // Add admin menu
        add_action('admin_menu', array('MembershipAdmin', 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array('MembershipAdmin','enqueue_media_uploader'));
        add_action('show_user_profile', array('MembershipAdmin','custom_user_profile_fields'));
        add_action('edit_user_profile', array('MembershipAdmin','custom_user_profile_fields'));
        add_action('personal_options_update', array('MembershipAdmin','save_custom_user_profile_fields'));
        add_action('edit_user_profile_update', array('MembershipAdmin','save_custom_user_profile_fields'));
        
        // Shortcodes for forms
        add_shortcode('membership_signup', array('MembershipSignup', 'checkStateCodeHtml'));
        add_shortcode('membership_login', array('MembershipLogin', 'render_login_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('get_avatar_url', array($this, 'custom_get_avatar_url'), 10, 3);
        add_filter('login_footer', array($this, 'login_footer_scripts'), 10);
    }

    function login_footer_scripts() {
        if(isset($_REQUEST['action']) && $_REQUEST['action'] === 'lostpassword'){
        ?>
        <style>
            .wp-login-logo,
            .login #nav {
                display: none;
            }

            .login form {
                padding: 0;
                background: transparent;
                box-shadow: none;
                border: none;
            }
            div#login {
                width: 500px;
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                padding-top: 0;
            }

            .wp-core-ui .button-primary {
                width: 100%;
                padding: 8px;
                height: 48px;
            }

            form#lostpasswordform input {
                all: unset;
                height: 50px !important;
                padding: 10px 10px !important;
                margin-bottom: 12px !important;
                border-radius: 5px !important;
                border: 1px solid #a8a8a8 !important;
                outline: none !important;
                box-shadow: none !important;
                width: calc(100% - 22px);
                font-size: 16px;
            }

            form#lostpasswordform input[type="submit"] {
                all: unset;
                background: #2196F3;
                border: none;
                outline: none;
                box-shadow: none;
                padding: 14px;
                color: #fff;
                width: calc(100% - 22px);
                border-radius: 3px;
                -webkit-border-radius: 3px;
                -moz-border-radius: 3px;
                -ms-border-radius: 3px;
                -o-border-radius: 3px;
                transition: all 0.3s;
                -webkit-transition: all 0.3s;
                -moz-transition: all 0.3s;
                -ms-transition: all 0.3s;
                -o-transition: all 0.3s;
                text-align: center;
                cursor: pointer;
                font-size: 16px;
                margin: 0;
            }

            #login form p.submit {
                margin: 0 !important;!i;!;
                padding: 0 !important;!i;!;
            }
        </style>
        <?php  
        }
    }    

    function custom_get_avatar_url($url, $id_or_email, $args) {
        $custom_url = get_user_meta( $id_or_email, "state_user_avatar", true );
        if(empty($custom_url)){
            $custom_url = plugin_dir_url( __FILE__ ).'assets/avatar.png';
        }
        return $custom_url;
    }

    function enqueue_scripts(){
        $settings = MembershipSignup::getInstance()->settings;

        wp_enqueue_script('paystack-inline', 'https://js.paystack.co/v1/inline.js', [], null, true);
        wp_enqueue_style( 'state-membership-plugin', plugin_dir_url( __FILE__ )."assets/public/css/membership-styles.css", array(), STATE_MEMBERSHIP_PLUGIN_SCRIPTS_VERSION, "all" );
        wp_enqueue_script( 'html2pdf', plugin_dir_url( __FILE__ )."assets/public/js/html2pdf.bundle.min.js", array('jquery'), STATE_MEMBERSHIP_PLUGIN_SCRIPTS_VERSION, false );
        wp_enqueue_script( 'html2canvas', plugin_dir_url( __FILE__ )."assets/public/js/html2canvas.min.js", array('jquery'), STATE_MEMBERSHIP_PLUGIN_SCRIPTS_VERSION, false );
        wp_enqueue_script( 'state-membership-plugin', plugin_dir_url( __FILE__ )."assets/public/js/membership-scripts.js", array('jquery', 'html2pdf', 'html2canvas', 'paystack-inline'), STATE_MEMBERSHIP_PLUGIN_SCRIPTS_VERSION, true );
        wp_localize_script( "state-membership-plugin", "statefragment", array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'state-nonce' ),
            'pkey' => $settings['paystack_public_key'],
            'loginUrl' => get_the_permalink($settings['user_signin_page']),
            'greater_payment_amount' => intval($settings['greater_payment_amount']) * 100,
            'less_payment_amount' => intval($settings['less_payment_amount']) * 100,
            'redirectUrl'  => get_the_permalink($settings['user_dashboard_page']),
        ) );
    }
    
}

// Instantiate the plugin
new StateMembershipPlugin();
