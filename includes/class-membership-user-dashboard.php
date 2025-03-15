<?php
// User Dashboard Class
class MembershipUserDashboard {
    public static function render_user_dashboard() {
        $settings = get_option('membership_plugin_settings', [
            'paystack_public_key' => '',
            'paystack_secret_key' => '',
            'user_dashboard_page' => '',
            'user_signin_page' => '',
            'user_signup_page' => '',
            'less_payment_amount' => '',
            'greater_payment_amount' => '',
            'loan_amount' => '',
        ]);

        // Ensure user is logged in
        if (!is_user_logged_in()) {
            wp_redirect(get_the_permalink($settings['user_signin_page']));
            exit;
        }

        $current_user = wp_get_current_user();
        $state_code = get_user_meta( $current_user->ID, "state_code", true );
        if(!$state_code){
            return "You are not allowed to this page.";
        }
        
        $first_name = get_user_meta( $current_user->ID, "first_name", true );
        $last_name = get_user_meta( $current_user->ID, "last_name", true );
        $state_of_origin = get_user_meta( $current_user->ID, "state_of_origin", true );
        $user_level = get_user_meta( $current_user->ID, "user_level", true );
        $gender = get_user_meta( $current_user->ID, "gender", true );

        $fullname = '';
        if (!empty($first_name) && !empty($last_name)) {
            $fullname = trim($first_name . ' ' . $last_name);
        }
        
        if (empty($fullname)) {
            $fullname = $current_user->display_name;
        }   

        ob_start();
        require_once plugin_dir_path( __FILE__ ) ."dashboard-template.php";
        return ob_get_clean();
    }
}  

// Initialize hooks
add_action('init', function() {
    add_shortcode('user_dashboard', ['MembershipUserDashboard', 'render_user_dashboard']);
    // Add shortcode for user dashboard
    if (is_user_logged_in() && isset($_GET['download_id_card']) && wp_verify_nonce($_GET['_wpnonce'], 'download_id_card')) {
        $current_user = wp_get_current_user();

        $first_name = get_user_meta( $current_user->ID, "first_name", true );
        $last_name = get_user_meta( $current_user->ID, "last_name", true );
        $fullname = '';
        if (!empty($first_name) && !empty($last_name)) {
            $fullname = trim($first_name . ' ' . $last_name);
        }
        
        if (empty($fullname)) {
            $fullname = $current_user->display_name;
        } 

        $state_code = get_user_meta( $current_user->ID, "state_code", true );

        $user_details = [
            'member_since' => date("F j, Y", strtotime($current_user->user_registered)),
            'state_code' => $state_code,
            'name' => $fullname,
            'level' => get_user_meta($current_user->ID, 'user_level', true),
            'user_avatar' => get_avatar_url($current_user->ID),
            'first_name' => $first_name,
            'last_name' => $last_name,
        ];
        
        IDCardGenerator::generate_id_card($user_details);
        exit;
    }
});

add_action("admin_post_save_user_profile", "save_membership_user_data");
function save_membership_user_data() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_redirect(home_url());
        exit;
    }

    $current_user = wp_get_current_user();
    $errors = [];

    // Sanitize input data
    $email = sanitize_email($_POST['email']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $state_of_origin = sanitize_text_field($_POST['state_of_origin']);
    $gender = sanitize_text_field($_POST['gender']);

    // Check for email conflicts
    $existing_user = get_user_by('email', $email);
    if ($existing_user && $existing_user->ID != $current_user->ID) {
        $errors[] = 'This email address is already in use by another user.';
    }

    // Validate required fields
    if (empty($email) || empty($first_name) || empty($last_name) || empty($state_of_origin) || empty($gender)) {
        $errors[] = 'All fields marked as required must be filled out.';
    }

    // If there are errors, save them to a transient and redirect back
    if (!empty($errors)) {
        set_transient('user_profile_update_errors', $errors, 30);
        wp_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }

    // Update user data
    wp_update_user([
        'ID' => $current_user->ID,
        'user_email' => $email,
    ]);

    // Update user meta
    update_user_meta($current_user->ID, 'first_name', $first_name);
    update_user_meta($current_user->ID, 'last_name', $last_name);
    update_user_meta($current_user->ID, 'state_of_origin', $state_of_origin);
    update_user_meta($current_user->ID, 'gender', $gender);

    // Handle avatar upload
    if (!empty($_FILES['user_avatar']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $uploadedfile = $_FILES['user_avatar'];
        $upload_overrides = ['test_form' => false];

        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            update_user_meta($current_user->ID, 'state_user_avatar', $movefile['url']);
        } else {
            $errors[] = 'Avatar upload failed. Please try again.';
        }
    }

    // Redirect back to the profile page with success message
    if (!empty($errors)) {
        set_transient('user_profile_update_errors', $errors, 30);
    } else {
        set_transient('user_profile_update_success', 'Profile updated successfully.', 30);
    }

    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}