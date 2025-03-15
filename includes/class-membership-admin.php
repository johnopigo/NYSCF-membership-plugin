<?php
if (!defined('ABSPATH')) exit; // Security check

class MembershipAdmin {
    // Add custom fields to the user profile edit page
    public static function custom_user_profile_fields($user) {
        ?>
        <h3>State Code User Fields</h3>
        <table class="form-table">
            <tr>
                <th><label for="level">Level</label></th>
                <td>
                    <input type="text" name="level" id="level" value="<?php echo esc_attr(get_the_author_meta('state_user_level', $user->ID)); ?>" class="regular-text" />
                    <p class="description">Enter the user level.</p>
                </td>
            </tr>
            <tr>
                <th><label for="avatar">Avatar</label></th>
                <td>
                    <div id="avatar-preview" style="margin-bottom: 10px;">
                        <?php
                        $avatar = get_user_meta($user->ID, 'state_user_avatar', true);
                        if ($avatar) {
                            echo '<img src="' . esc_url($avatar) . '" width="100" />';
                        }
                        ?>
                    </div>
                    <input type="hidden" name="avatar" id="avatar" value="<?php echo esc_url($avatar); ?>" />
                    <button type="button" class="button" id="upload-avatar">Upload Avatar</button>
                    <button type="button" class="button" id="remove-avatar" style="display: <?php echo $avatar ? 'inline-block' : 'none'; ?>;">Remove Avatar</button>
                    <p class="description">Upload an avatar using the WordPress Media Library.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    // Save custom fields
    public static function save_custom_user_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Save Level field
        if (isset($_POST['level'])) {
            update_user_meta($user_id, 'state_user_level', sanitize_text_field($_POST['level']));
        }

        // Save Avatar field
        if (isset($_POST['avatar'])) {
            update_user_meta($user_id, 'state_user_avatar', esc_url_raw($_POST['avatar']));
        }
    }

    // Enqueue media uploader script
    public static function enqueue_media_uploader() {
        if (is_admin()) {
            wp_enqueue_media();
            wp_enqueue_script('stm-script', plugin_dir_url( dirname(__FILE__) ) . 'assets/admin/scripts.js', ['jquery'], STATE_MEMBERSHIP_PLUGIN_VERSION, true);
        }
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Membership Management',
            'Membership',
            'manage_options',
            'membership-management',
            [__CLASS__, 'render_admin_page'],
            'dashicons-admin-users',
            20
        );

        add_submenu_page(
            'membership-management',
            'User Management',
            'Users',
            'manage_options',
            'membership-users',
            [__CLASS__, 'render_users_page']
        );

        add_submenu_page(
            'membership-management',
            'Plugin Settings',
            'Settings',
            'manage_options',
            'membership-settings',
            [__CLASS__, 'render_settings_page']
        );

        add_submenu_page(
            'membership-management',
            'Origins',
            'Origins',
            'manage_options',
            'membership-origins',
            [__CLASS__, 'render_origins_page']
        );
    }

    public static function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Membership Management Dashboard</h1>
            <hr>
            <div class="dashboard-widgets">
                <?php self::display_membership_stats(); ?>
            </div>
        </div>
        <?php
    }

    public static function render_users_page() {
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
    
        // Query users with pagination
        $users = new WP_User_Query([
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key'     => 'state_code',
                    'value'   => '', // Matches values
                    'compare' => '!=', // Not equal to empty
                ],
            ],
            'count_total' => true,
        ]);
    
        // Get results and total count
        $results = $users->get_results();
        $total_users = $users->get_total(); // Total number of users matching the query
        $total_pages = ceil($total_users / $per_page);
    
        ?>
        <div class="wrap">
            <h1>Registered Users</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>State Code</th>
                        <th>State of Origin</th>
                        <th>Payment</th>
                        <th>Payment Reference</th>
                        <th>Loan application</th>
                        <th>Status/Level</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($results)) : ?>
                        <?php foreach ($results as $user) : ?>
                            <tr>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html(get_user_meta($user->ID, 'state_code', true) ?? 'N/A'); ?></td>
                                <td><?php echo esc_html(get_user_meta($user->ID, 'state_of_origin', true) ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    if(get_user_meta($user->ID, 'payment_status', true) == 'paid'){
                                        echo '<span style="color: green;">Paid</span>';
                                    }else{
                                        echo '<span style="color: red;">—</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                <?php 
                                    if(!empty(get_user_meta($user->ID, 'payment_reference', true))){
                                        echo get_user_meta($user->ID, 'payment_reference', true);
                                    }else{
                                        echo '<span style="color: red;">—</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if(get_user_meta($user->ID, 'loan_payment_status', true) == 'paid'){
                                        echo '<span style="color: green;">Applied</span>';
                                    }else{
                                        echo '<span style="color: red;">—</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $stateCode = get_user_meta($user->ID, 'state_code', true);
                                    if(!empty($stateCode)){
                                        $usr_status = StateCodeValidator::getUserStatus($stateCode);
                                        echo $usr_status;
                                    }else{
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url("user-edit.php?user_id={$user->ID}")); ?>">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    
            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous'),
                        'next_text' => __('Next &raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }    

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['membership_settings'])) {
            check_admin_referer('membership_settings_nonce');

            $settings = [
                'paystack_public_key' => sanitize_text_field($_POST['paystack_public_key']),
                'paystack_secret_key' => sanitize_text_field($_POST['paystack_secret_key']),
                'user_dashboard_page' => intval($_POST['user_dashboard_page']),
                'user_signin_page' => intval($_POST['user_signin_page']),
                'user_signup_page' => intval($_POST['user_signup_page']),
                'less_payment_amount' => intval($_POST['less_payment_amount']),
                'greater_payment_amount' => intval($_POST['greater_payment_amount']),
                'loan_amount' => intval($_POST['loan_amount']),
            ];

            update_option('membership_plugin_settings', $settings);
            add_settings_error('membership_settings', 'settings_updated', 'Settings saved.', 'updated');
        }

        $settings = get_option('membership_plugin_settings', [
            'paystack_public_key' => '',
            'paystack_secret_key' => '',
            'user_dashboard_page' => '',
            'user_signin_page' => '',
            'user_signup_page' => '',
            'less_payment_amount' => '',
            'greater_payment_amount' => '',
            'loan_amount' => ''
        ]);
        ?>
        <div class="wrap" style="max-width: 500px">
            <h1>Membership Plugin Settings</h1>
            <hr>
            <form method="post" action="">
                <?php wp_nonce_field('membership_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Paystack public key</th>
                        <td>
                            <input type="text" name="paystack_public_key" value="<?php echo esc_attr($settings['paystack_public_key']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Paystack secret key</th>
                        <td>
                            <input type="text" name="paystack_secret_key" value="<?php echo esc_attr($settings['paystack_secret_key']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Amount to pay <= 2022</th>
                        <td>
                            <input type="number" name="less_payment_amount" value="<?php echo esc_attr($settings['less_payment_amount']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Amount to pay >= 2023</th>
                        <td>
                            <input type="number" name="greater_payment_amount" value="<?php echo esc_attr($settings['greater_payment_amount']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Loan amount</th>
                        <td>
                            <input type="number" name="loan_amount" value="<?php echo esc_attr($settings['loan_amount']); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Dashboard Page</th>
                        <td>
                            <select required name="user_dashboard_page">
                                <option value="">Select</option>
                                <?php 
                                $pages = get_pages();
                                foreach ($pages as $page) {
                                    $selected = $settings['user_dashboard_page'] == $page->ID ? 'selected' : '';
                                    echo '<option value="'.esc_attr($page->ID).'" '.$selected.'>'.esc_html($page->post_title).'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Sign-in Page</th>
                        <td>
                            <select required name="user_signin_page">
                                <option value="">Select</option>
                                <?php 
                                $pages = get_pages();
                                foreach ($pages as $page) {
                                    $selected = $settings['user_signin_page'] == $page->ID ? 'selected' : '';
                                    echo '<option value="'.esc_attr($page->ID).'" '.$selected.'>'.esc_html($page->post_title).'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Sign-up Page</th>
                        <td>
                            <select required name="user_signup_page">
                                <option value="">Select</option>
                                <?php 
                                $pages = get_pages();
                                foreach ($pages as $page) {
                                    $selected = $settings['user_signup_page'] == $page->ID ? 'selected' : '';
                                    echo '<option value="'.esc_attr($page->ID).'" '.$selected.'>'.esc_html($page->post_title).'</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings', 'button-primary', 'membership_settings'); ?>
            </form>

            <h3>Shortcodes</h3>
            <hr>
            <ul>
                <li>Signup form: [membership_signup]</li>
                <li>Login form: [membership_login]</li>
                <li>Dashboard: [user_dashboard]</li>
            </ul>
        </div>
        <?php
    }

    public static function render_origins_page(){
        ?>
        <h3>State of Origins</h3>
        <hr>
    
        <!-- Form for saving options -->
        <form method="POST" action="">
            <!-- Container to hold dynamic fields -->
            <div id="origins-container">
                <?php
                // Get saved options if any
                $saved_origins = get_option('state_of_origins', []);

                echo '<p>Total: '.sizeof($saved_origins).'</p>';
                
                if (sizeof($saved_origins) > 0) {
                    foreach ($saved_origins as $origin) {
                        ?>
                        <div class="origin-field" style="margin-bottom: 10px;">
                            <input type="text" name="state_of_origin[]" value="<?php echo esc_attr($origin); ?>" class="regular-text" placeholder="Enter state of origin" style="margin-right: 10px;">
                            <button type="button" class="button button-secondary delete-origin">Delete</button>
                        </div>
                        <?php
                    }
                }else{
                    echo '<p>No origin added.</p>';
                }
                ?>
            </div>
    
            <div style="display: flex; align-items: center; gap: 10px">
                <!-- Button to add a new state of origin -->
                <button type="button" id="add-origin-button" class="button button-secondary">Add State of Origin</button>
    
                <!-- Save Button -->
                <input type="submit" name="save_state_origins" value="Save" class="button button-primary" />
            </div>
    
        </form>
    
        <script type="text/javascript">
            // JavaScript to dynamically add/remove state of origin fields
            document.getElementById('add-origin-button').addEventListener('click', function() {
                // Create a new div element to hold the new input field and delete button
                const originDiv = document.createElement('div');
                originDiv.classList.add('origin-field');
                originDiv.style.marginBottom = '10px';
    
                // Create the input field for the state of origin
                const inputField = document.createElement('input');
                inputField.type = 'text';
                inputField.name = 'state_of_origin[]';
                inputField.placeholder = 'Enter state of origin';
                inputField.classList.add('regular-text');
                inputField.style.marginRight = '10px';
                
                // Create the delete button
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.classList.add('button', 'button-secondary');
                deleteButton.textContent = 'Delete';
                
                // Add event listener to remove the field when clicked
                deleteButton.addEventListener('click', function() {
                    originDiv.remove();
                });
    
                // Append the input field and delete button to the new div
                originDiv.appendChild(inputField);
                originDiv.appendChild(deleteButton);
    
                // Append the new div to the container
                document.getElementById('origins-container').appendChild(originDiv);
            });
    
            // Delete button functionality for dynamically added fields
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('delete-origin')) {
                    e.target.closest('.origin-field').remove();
                }
            });
        </script>
    
        <?php
        // Handle the form submission and save the options
        if (isset($_POST['save_state_origins'])) {
            // Get the submitted state of origins
            $origins = isset($_POST['state_of_origin']) ? array_map('sanitize_text_field', $_POST['state_of_origin']) : [];
            
            // Remove empty states
            $origins = array_filter($origins, function($value) {
                return !empty($value); // Filter out empty values
            });
        
            // Save the origins as a WordPress option only if it's not empty
            if (!empty($origins)) {
                update_option('state_of_origins', $origins);
            } else {
                delete_option('state_of_origins'); // Clean up if all states are empty
            }
        
            // Display a success message
            echo '<div class="updated"><p>State of origins saved successfully!</p></div>';
            
            // Redirect to avoid resubmission
            wp_safe_redirect(menu_page_url('membership-origins', false));
            exit;
        }
        
    }    

    private static function display_membership_stats() {
        $users = new WP_User_Query([
            'number' => '-1', // Retrieve all users (no limit)
            'count_total' => true,
            'meta_query' => [
                [
                    'key'     => 'state_code',
                    'value'   => '', // Matches empty values
                    'compare' => '!=', // Excludes empty values
                ],
            ],
        ]);
        
        // Get results
        $total_users = $users->get_total();
        ?>
        <div class="membership-stats">
            <div class="stat-box">
                <h3>Total Registered Users: <small><?php echo $total_users; ?></small></h3>
            </div>
            <?php if($total_users > 0){ ?>
                <a class="button-secondary" href="?membership_export=csv">Download user data as a CSV</a>
            <?php } ?>

            <form method="post" enctype="multipart/form-data" class="import-form" style="margin-top: 32px;">
                <input type="file" name="membership_import_file" accept=".csv" />
                <?php wp_nonce_field('membership_import_nonce', 'membership_import_nonce'); ?>
                <input type="submit" name="membership_import" class="button-primary" value="Import Users" />
            </form>
        </div>
        <?php
    }

    public static function export_users_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }

        $users = new WP_User_Query([
            'number' => "-1",
            'orderby' => 'ID',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key'     => 'state_code',
                    'value'   => '', // Matches values
                    'compare' => '!=', // Not equal to empty
                ],
            ]
        ]);
    
        // Get results and total count
        $results = $users->get_results();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="membership-users-export.csv"');

        $fp = fopen('php://output', 'wb');

        fputcsv($fp, [
            'User ID', 'Username', 'Email', 'State Code', 
            'First Name', 'Last Name', 'State of Origin', 'Gender', "Level", "Payment Status", "Payment Reference", "Loan Application Status", "Loan Reference"
        ]);

        foreach ($results as $user) {
            fputcsv($fp, [
                $user->ID, 
                $user->user_login, 
                $user->user_email, 
                get_user_meta($user->ID, 'state_code', true) ?: '',
                get_user_meta($user->ID, 'first_name', true) ?: '',
                get_user_meta($user->ID, 'last_name', true) ?: '',
                get_user_meta($user->ID, 'state_of_origin', true) ?: '',
                get_user_meta($user->ID, 'gender', true) ?: '',
                get_user_meta($user->ID, 'user_level', true) ?: '',
                get_user_meta($user->ID, 'payment_status', true) ?: '',
                get_user_meta($user->ID, 'payment_reference', true) ?: '',
                get_user_meta($user->ID, 'loan_payment_status', true) ?: '',
                get_user_meta($user->ID, 'loan_payment_reference', true) ?: ''
            ]);
        }

        fclose($fp);
        exit();
    }

    public static function import_users_csv() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
    
        // Verify nonce for security
        if (!isset($_POST['membership_import_nonce']) || 
            !wp_verify_nonce($_POST['membership_import_nonce'], 'membership_import_nonce')) {
            wp_die('Security check failed');
        }
    
        // Check if file was uploaded
        if (!isset($_FILES['membership_import_file']) || $_FILES['membership_import_file']['error'] > 0) {
            wp_die('No file uploaded or upload error occurred');
        }
    
        $file = $_FILES['membership_import_file'];
    
        // Validate file type
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($file_extension) !== 'csv') {
            wp_die('Invalid file type. Please upload a CSV file.');
        }
    
        // Open the CSV file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            wp_die('Unable to open uploaded file');
        }
    
        // Read header row
        $headers = fgetcsv($handle);
    
        // Validate headers match expected format
        $expected_headers = [
            'User ID', 'Username', 'Email', 'State Code', 
            'First Name', 'Last Name', 'State of Origin', 'Gender', 
            'Level', 'Payment Status', 'Payment Reference', 
            'Loan Application Status', 'Loan Reference'
        ];
    
        if ($headers !== $expected_headers) {
            fclose($handle);
            wp_die('CSV file headers do not match expected format');
        }
    
        // Track import statistics
        $imported_count = 0;
        $updated_count = 0;
    
        // Process each row
        while (($data = fgetcsv($handle)) !== false) {
            // Map CSV columns to user meta
            $user_data = [
                'user_login' => sanitize_user($data[1]),
                'user_email' => sanitize_email($data[2]),
            ];
    
            // Check if user already exists
            $existing_user = get_user_by('email', $user_data['user_email']);
    
            if ($existing_user) {
                // Update existing user
                $user_id = $existing_user->ID;
                $updated_count++;
            } else {
                // Create new user
                $user_id = wp_insert_user($user_data);
                $imported_count++;
            }
    
            if (!is_wp_error($user_id)) {
                // Update user meta
                $meta_keys = [
                    'state_code' => $data[3],
                    'first_name' => $data[4],
                    'last_name' => $data[5],
                    'state_of_origin' => $data[6],
                    'gender' => $data[7],
                    'user_level' => $data[8],
                    'payment_status' => $data[9],
                    'payment_reference' => $data[10],
                    'loan_payment_status' => $data[11],
                    'loan_payment_reference' => $data[12]
                ];
    
                foreach ($meta_keys as $key => $value) {
                    update_user_meta($user_id, $key, sanitize_text_field($value));
                }
            }
        }
    
        fclose($handle);
    
        // Redirect back with import statistics
        wp_redirect(add_query_arg([
            'membership_import' => 'success', 
            'imported' => $imported_count, 
            'updated' => $updated_count
        ], wp_get_referer()));
        exit();
    }
    
    // Update admin notices to show both imported and updated counts
    public static function membership_import_admin_notices() {
        if (isset($_GET['membership_import']) && $_GET['membership_import'] == 'success') {
            $imported = isset($_GET['imported']) ? intval($_GET['imported']): 0;
            $updated = isset($_GET['updated']) ? intval($_GET['updated']): 0;
            ?>
            <div class="notice notice-success">
                <p>CSV Import completed. New users created: <?php echo $imported; ?>. Existing users updated: <?php echo $updated; ?>.</p>
            </div>
            <?php
        }
    }
}

add_action('admin_notices', [MembershipAdmin::class, 'membership_import_admin_notices']);
add_action('admin_menu', ['MembershipAdmin', 'add_admin_menu']);
add_action('admin_init', function() {
    if (isset($_GET['membership_export']) && $_GET['membership_export'] == 'csv') {
        MembershipAdmin::export_users_csv();
    }

    // Import functionality
    if (isset($_POST['membership_import'])) {
        MembershipAdmin::import_users_csv();
    }
});