<?php
class MembershipSignup {
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

    /**
     * Get the singleton instance of the class.
     *
     * @return MembershipSignup
     */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the class by adding necessary actions.
     */
    public static function init() {
        add_action('wp_ajax_membership_processing', [__CLASS__, 'checkingStateCodeBeforeProcessSignup']);
        add_action('wp_ajax_nopriv_membership_processing', [__CLASS__, 'checkingStateCodeBeforeProcessSignup']);
        add_action('wp_ajax_mp_register', [__CLASS__, 'mpRegister']);
        add_action('wp_ajax_nopriv_mp_register', [__CLASS__, 'mpRegister']);
        add_action('wp_ajax_paystack_payment', [__CLASS__,'handle_paystack_payment']);
        add_action('wp_ajax_nopriv_paystack_payment', [__CLASS__,'handle_paystack_payment']);
        add_action('wp_ajax_verify_payment', [__CLASS__,'verify_payment']);
        add_action('wp_ajax_nopriv_verify_payment', [__CLASS__,'verify_payment']);
        add_action('wp_ajax_loan_payment', [__CLASS__,'handle_loan_payment']);
    }

    /**
     * Render the state code check form.
     *
     * @return string
     */
    public static function checkStateCodeHtml(){
        ob_start();
        $settings = self::getInstance()->settings;
        ?>
        <form id="membership-state-code-checkup" method="post">
            <div class="state-error"></div>
            <input type="hidden" name="action" value="membership_processing">
            <input type="text" name="state_code" placeholder="State Code (AA/17A/0000)" required title="Format: AA/17A/0000">
            <div class="actionlinks">
                <p>Already have an account? <a href="<?php echo get_the_permalink($settings['user_signin_page']) ?>">Login</a></p>
            </div>
            <input type="submit" value="Check State Code">
            <?php wp_nonce_field('membership_processing', 'signup_nonce'); ?>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the signup form.
     *
     * @param string $state_code
     * @return string
     */
    public static function renderSignupForm($state_code) {
        ob_start();
        ?>
        <form id="membership-signup-form" method="post" class="membership-form">
            <div class="state-error"></div>
            <input type="hidden" name="action" value="mp_register">
            <?php wp_nonce_field('mp_register', 'details_nonce'); ?>
    
            <div class="form-error"></div>
    
            <input type="hidden" name="state_code" value="<?php echo esc_attr($state_code); ?>">
            
            <div class="state-field">
                <label>State Code:</label>
                <input type="text" readonly value="<?php echo $state_code ?>">
            </div>

            <div class="state-field">
                <label>Username:</label>
                <input type="text" name="username" autocomplete="off" required>
            </div>

            <div class="state-field">
                <label>Email:</label>
                <input type="email" name="email" autocomplete="off" required>
            </div>
            
            <div class="state-field">
                <label>Password:</label>
                <input type="password" name="password" required autocomplete="off">
            </div>
            
            <div class="state-row">
                <div class="state-field">
                    <label>First Name:</label>
                    <input type="text" name="first_name" required>
                </div>
                
                <div class="state-field">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>
            
            <div class="state-field">
                <label>State of Origin:</label>
                <?php
                $saved_origins = get_option('state_of_origins', []);
                ?>
                <select name="state_of_origin" required>
                    <option selected value="">Select state</option>
                    <?php
                    if (sizeof($saved_origins) > 0) {
                        foreach ($saved_origins as $origin) {
                            echo '<option value="'.$origin.'">'.$origin.'</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="state-field">
                <label>Gender:</label>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <input type="submit" value="Complete Registration">
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if state code exists in the database.
     *
     * @param string $state_code
     * @return mixed
     */
    public static function isStateCodeExist($state_code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'usermeta';
        $query = $wpdb->prepare("SELECT user_id FROM $table_name WHERE meta_key = %s AND meta_value = %s", 'state_code', $state_code);
        return $wpdb->get_var($query);
    }

    /**
     * Check if state code is valid before processing signup.
     */
    public static function checkingStateCodeBeforeProcessSignup() {
        // Verify nonce
        check_ajax_referer('membership_processing', 'signup_nonce');

        // Sanitize and validate state code
        $state_code = sanitize_text_field($_POST['state_code']);
        $state_code = strtoupper( $state_code );

        // Validate state code
        if (!StateCodeValidator::validate_state_code($state_code)) {
            wp_send_json_error('Invalid state code format');
        }

        if(self::isStateCodeExist($state_code)){
            wp_send_json_error('The state code is already in use');
        }

        wp_send_json_success([
            'status' => 'valid',
            'msg' => 'State code is valid',
            'form' => self::renderSignupForm($state_code)
        ]);
    }

    /**
     * Process the registration of user with the proper information.
     */
    public static function mpRegister() {
        // Verify nonce
        check_admin_referer('mp_register', 'details_nonce');

        $settings = self::getInstance()->settings;
        // Sanitize and validate inputs
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $state_code = sanitize_text_field($_POST['state_code']);
        $state_code = strtoupper( $state_code );

        // Initialize error array
        $errors = [];

        if (empty($state_code)) {
            $errors[] = 'State code cannot be empty.';
        }
        if (empty($username)) {
            $errors[] = 'Username is required.';
        }
        if (empty($email) || !is_email($email)) {
            $errors[] = 'A valid email address is required.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        // Check for existing errors
        if (!empty($errors)) {
            wp_send_json_error( implode('<br>', $errors) );
        }

        $isStateCode = self::isStateCodeExist($state_code);
        // Create WordPress user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json( [
                'status' => $isStateCode ? 'login': 'error',
                'data' => $user_id->get_error_message(),
                'redirect' => get_the_permalink( $settings['user_signin_page'] )
            ] );
        }
        
        $usr_status = StateCodeValidator::getUserStatus($state_code);
        // Save additional details to user meta
        update_user_meta($user_id, 'state_code', $state_code);
        update_user_meta($user_id, 'user_level', $usr_status);
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
        update_user_meta($user_id, 'state_of_origin', sanitize_text_field($_POST['state_of_origin']));
        update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
        
        wp_send_json_success( [
            'status' => 'payment_due',
            'msg' => 'Registration successful! Make payment to complete the profile setup.',
            'formData' => [
                'payableAmount' => (($usr_status === 'AJUWAYA') ? $settings['less_payment_amount'] : $settings['greater_payment_amount']),
                'state_code' => $state_code,
            ]
        ] );
    }

    /**
     * Update customer name on Paystack.
     */
    public static function updateCustomerName($email, $first_name, $last_name){
        $settings = self::getInstance()->settings;
        $paystack_secret_key = $settings['paystack_secret_key'];
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/customer/$email",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer $paystack_secret_key",
            "Cache-Control: no-cache",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        

        $response_data = json_decode($response, true);

        if ($response_data['status']) {
            $customer_code = $response_data['data']['customer_code'];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/customer/$customer_code",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => json_encode([
                    'first_name' => $first_name,
                    'last_name' => $last_name
                ]),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer $paystack_secret_key",
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
        }
    }
    
    /**
     * Handle Paystack payment initialization securely.
     */
    public static function handle_paystack_payment() {
        if (empty($_POST['stateCode'])) {
            wp_send_json_error('Required fields are missing.');
        }
        
        $settings = self::getInstance()->settings;
        $paystack_secret_key = $settings['paystack_secret_key'];

        $referrer = esc_url($_POST['referrer']);
        $stateCode = sanitize_text_field($_POST['stateCode']);
        $stateCode = strtoupper( $stateCode );
        $usr_status = StateCodeValidator::getUserStatus($stateCode);
        $user_id = self::isStateCodeExist($stateCode);

        if(!$user_id){
            wp_send_json_error('Invalid state code.');
        }

        $user = get_user_by( 'ID', $user_id );
        $email = $user->user_email;

        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);

        $payableAmount = (($usr_status === 'AJUWAYA') ? $settings['less_payment_amount'] : $settings['greater_payment_amount']);
        $payableAmount = intval($payableAmount) * 100;

        // check if reference exist then send the reference and authorization_url to the frontend
        $reference = get_user_meta($user_id, 'payment_reference', true);
        if(!empty($reference)){
            $response_data = unserialize(get_user_meta($user_id, 'pending_payment', true));
            wp_send_json([
                'success' => true,
                'email' => $email,
                'payableAmount' => $payableAmount,
                'data' => $response_data['data']
            ]);
        }

        $url = 'https://api.paystack.co/transaction/initialize';
        $fields = [
            'email' => $email,
            'amount' => $payableAmount,
            'callback_url' => $referrer,
            'metadata' => [
                'custom_fields' => [
                    [
                        'display_name' => 'Payment Type',
                        'variable_name' => 'payment_type',
                        'value' => "Membership Payment"
                    ],
                    [
                        'display_name' => 'State Code',
                        'variable_name' => 'state_code',
                        'value' => $stateCode
                    ],
                    [
                        'display_name' => 'User Status',
                        'variable_name' => 'user_status',
                        'value' => $usr_status
                    ],
                    [
                        'display_name' => 'First Name',
                        'variable_name' => 'first_name',
                        'value' => sanitize_text_field($first_name)
                    ],
                    [
                        'display_name' => 'Last Name',
                        'variable_name' => 'last_name',
                        'value' => sanitize_text_field($last_name)
                    ]
                ]
            ]
        ];

        $fields_string = http_build_query($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $paystack_secret_key",
            "Cache-Control: no-cache",
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $response_data = json_decode($response, true);

        if ($response_data['status']) {
            if(isset($response_data['data']['reference'])){
                update_user_meta($user_id, 'pending_payment', serialize($response_data));
                update_user_meta($user_id, 'payment_reference', $response_data['data']['reference']);
                self::updateCustomerName($email, $first_name, $last_name);

                // Return reference and authorization_url to JavaScript
                wp_send_json([
                    'success' => true,
                    'email' => $email,
                    'payableAmount' => $payableAmount,
                    'data' => $response_data['data']
                ]);
            } else {
                wp_send_json_error('Payment initialization failed.');
            }
        } else {
            wp_send_json_error('Payment initialization failed.');
        }
    }
      
    /**
     * Handle loan payment initialization securely.
     */
    public static function handle_loan_payment() {
        $settings = self::getInstance()->settings;
        $user = get_user_by( "ID", get_current_user_id(  ) );
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        $referrer = esc_url($_POST['referrer']);
        $email = $user->user_email;
        $payableAmount = $settings['loan_amount'];
        $payableAmount = $payableAmount * 100;
        $paystack_secret_key = $settings['paystack_secret_key'];

        // check if reference exist then send the reference and authorization_url to the frontend
        $reference = get_user_meta(get_current_user_id(  ), 'loan_payment_reference', true);
        if(!empty($reference)){
            $response_data = unserialize(get_user_meta(get_current_user_id(  ), 'pending_loan_payment', true));
            wp_send_json([
                'success' => true,
                'email' => $email,
                'payableAmount' => $payableAmount,
                'data' => $response_data['data']
            ]);
        }

        $url = 'https://api.paystack.co/transaction/initialize';
        $fields = [
            'email' => $email,
            'name' => $first_name .' '.$last_name,
            'amount' => $payableAmount,
            'callback_url' => $referrer,
            'metadata' => [
                'custom_fields' => [
                    [
                        'display_name' => 'Payment Type',
                        'variable_name' => 'payment_type',
                        'value' => "Loan Payment"
                    ],
                    [
                        'display_name' => 'Customer Email',
                        'variable_name' => 'email',
                        'value' => $email
                    ],
                    [
                        'display_name' => 'First Name',
                        'variable_name' => 'first_name',
                        'value' => sanitize_text_field($first_name)
                    ],
                    [
                        'display_name' => 'Last Name',
                        'variable_name' => 'last_name',
                        'value' => sanitize_text_field($last_name)
                    ]
                ]
            ]
        ];

        $fields_string = http_build_query($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $paystack_secret_key",
            "Cache-Control: no-cache",
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $response_data = json_decode($response, true);

        if ($response_data['status']) {
            if(isset($response_data['data']['reference'])){
                update_user_meta($user->ID, 'pending_loan_payment', serialize($response_data));
                update_user_meta($user->ID, 'loan_payment_reference', $response_data['data']['reference']);

                // Return reference and authorization_url to JavaScript
                wp_send_json([
                    'success' => true,
                    'email' => $email,
                    'payableAmount' => $payableAmount,
                    'data' => $response_data['data']
                ]);
            } else {
                wp_send_json_error('Payment initialization failed.');
            }
        } else {
            wp_send_json_error('Payment initialization failed.');
        }
    }

    /**
     * Verify payment.
     */
    public static function verify_payment() {
        if(!wp_verify_nonce( $_POST['nonce'], "state-nonce" )){
            wp_send_json_error('Invalid request.');
        }

        if (empty($_POST['reference'])) {
            wp_send_json_error('Invalid payment reference.');
        }

        $settings = self::getInstance()->settings;
        $paystack_secret_key = $settings['paystack_secret_key'];

        $reference = sanitize_text_field($_POST['reference']);

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
            $user = get_user_by('email', $response_data['data']['customer']['email']);

            if(isset($_POST['loan_payment'])){
                update_user_meta($user->ID, 'loan_payment_status', 'paid');
                delete_user_meta( $user->ID, 'pending_loan_payment' );
            } else {
                update_user_meta($user->ID, 'payment_status', 'paid');
                wp_set_auth_cookie($user->ID, true);
                delete_user_meta( $user->ID, 'pending_payment' );
            }

            wp_send_json_success( 'Payment successful!', 200 );
        } else {
            json_send_error('Payment verification failed.');
        }
    }
}

// Initialize the signup process
add_action('init', ['MembershipSignup', 'init']);