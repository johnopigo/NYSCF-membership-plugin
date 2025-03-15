<?php
class MembershipLogin {
    private static $instance;
    public $settings;

    private function __construct() {
        $this->settings = get_option('membership_plugin_settings', [
            'paystack_public_key' => '',
            'paystack_secret_key' => '',
            'user_dashboard_page' => '',
            'user_signin_page' => '',
            'user_signup_page' => '',
            'less_payment_amount' => '',
            'greater_payment_amount' => '',
            'loan_amount' => '',
        ]);
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function render_login_form() {
        $error = isset($_GET['login_error']) ? urldecode($_GET['login_error']) : '';
        $lostpass = wp_lostpassword_url(  );
        $settings = self::getInstance()->settings;
        ob_start();

        if(isset($_REQUEST['action']) && $_REQUEST['action'] === 'payment' && isset($_REQUEST['user']) && is_numeric($_REQUEST['user'])){
            $state_code = get_user_meta( $_REQUEST['user'], "state_code", true );
            $reference = get_user_meta( $_REQUEST['user'], "payment_reference", true );

            if(!empty($reference)){
                if(self::checkPaymentDeeply($reference)){
                    $dashboard_page = $settings['user_dashboard_page'];

                    if (!$dashboard_page) {
                        self::redirect_with_error('Dashboard page not configured.');
                    }
            
                    wp_redirect(get_permalink($dashboard_page));
                    exit;
                }
            }

            $usr_status = StateCodeValidator::getUserStatus($state_code);
            $payableAmount = (($usr_status === 'AJUWAYA') ? $settings['less_payment_amount'] : $settings['greater_payment_amount']);
            ?>
            <div class="successBox error membership-form">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="100px" height="100px"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm-1.5-5.009c0-.867.659-1.491 1.491-1.491.85 0 1.509.624 1.509 1.491 0 .867-.659 1.509-1.509 1.509-.832 0-1.491-.642-1.491-1.509zM11.172 6a.5.5 0 0 0-.499.522l.306 7a.5.5 0 0 0 .5.478h1.043a.5.5 0 0 0 .5-.478l.305-7a.5.5 0 0 0-.5-.522h-1.655z" fill="#000000"></path></g></svg>
                <h3>Your payment was not successful</h3>
                <div class="paymentInfo" style="text-align: center;">
                    <p>Payment due: NGN <?php echo $payableAmount ?></p>
                    <h4><?php echo $state_code ?></h4>
                </div>
                <button data-state="<?php echo $state_code ?>" class="paymentProceed">Proceed to Payment</button>
            </div>
            <?php
        }else{
            ?>
            <div id="membership-login-container">
                <?php if ($error): ?>
                    <div class="login-error">
                        <?php echo esc_html($error); ?>
                    </div>
                <?php endif; ?>
                <form id="membership-login" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="membership-form">
                    <input type="hidden" name="action" value="membership_login">
                    <input type="text" name="state_code" placeholder="State Code" required>
                    <input type="password" name="password" placeholder="Password" required>
                    
                    <div class="actionlinks">
                        <p>Need an account? <a href="<?php echo get_the_permalink($settings['user_signup_page']) ?>">Create an account</a></p>
                        <p>Forget password? <a href="<?php echo esc_url($lostpass) ?>">Reset password</a></p>
                    </div>
    
                    <input type="submit" value="Login">
                </form>
            </div>
            <?php
        }
        
        if (ob_get_length() !== false) {
            return ob_get_clean();
        }
        return '';
    }

    public static function checkPaymentDeeply($reference){
        $settings = self::getInstance()->settings;
        $paystack_secret_key = $settings['paystack_secret_key'];

        $url = "https://api.paystack.co/transaction/verify/$reference";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $paystack_secret_key",
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $response_data = json_decode($response, true);

        if ($response_data['status'] && $response_data['data']['status'] === 'success') {
            return true;
        }

        return false;
    }

    public static function process_login() {        
        $settings = self::getInstance()->settings;

        // Sanitize input
        $state_code = sanitize_text_field($_POST['state_code']);
        $state_code = strtoupper( $state_code );
        $password = sanitize_text_field($_POST['password']);

        if (empty($state_code) || empty($password)) {
            self::redirect_with_error('Both fields are required.');
        }

        // Check if state code exists in meta table
        global $wpdb;
        $table_name = $wpdb->prefix . 'usermeta';
        $user_id = $wpdb->get_var(
            $wpdb->prepare("SELECT user_id FROM $table_name WHERE meta_key = 'state_code' AND meta_value = %s", $state_code)
        );

        if (!$user_id) {
            self::redirect_with_error('Invalid state code.');
        }

        $reference = get_user_meta( $user_id, "payment_reference", true );
        if(empty($reference)){
            wp_redirect(add_query_arg(array(
                'action' => 'payment',
                'user' => $user_id
            ), get_the_permalink( $settings['user_signin_page'] )));

            exit;
        }else{
            $isPaid = self::checkPaymentDeeply($reference);
            if(!$isPaid){
                wp_redirect(add_query_arg(array(
                    'action' => 'payment',
                    'user' => $user_id
                ), get_the_permalink( $settings['user_signin_page'] )));

                exit;
            }
        }

        // Verify WordPress user's credentials
        $user = get_user_by('ID', $user_id);

        if (!$user || !wp_check_password($password, $user->user_pass, $user->ID)) {
            self::redirect_with_error('Invalid password.');
        }

        // Log the user in
        wp_set_auth_cookie($user_id, true);

        // Redirect to user dashboard
        $settings = self::getInstance()->settings;
        $dashboard_page = $settings['user_dashboard_page'];

        if (!$dashboard_page) {
            self::redirect_with_error('Dashboard page not configured.');
        }

        wp_redirect(get_permalink($dashboard_page));
        exit;
    }

    private static function redirect_with_error($error_message) {
        $settings = self::getInstance()->settings;
        wp_redirect(add_query_arg('login_error', urlencode($error_message), get_the_permalink( $settings['user_signin_page'] )));
        exit;
    }
}

// Initialize login handling
add_action('admin_post_membership_login', ['MembershipLogin', 'process_login']);
add_action('admin_post_nopriv_membership_login', ['MembershipLogin', 'process_login']);

// Display login form with error messages
add_shortcode('membership_login', function() {
    return MembershipLogin::render_login_form();
});
