<?php 
$currentUrl = get_the_permalink( $settings['user_dashboard_page'] );
$currentUrl = trailingslashit( $currentUrl );
?>
<div class="udb-dashboard-container">
    <div class="udb-sidebar">
        <div class="udb-profile-header">
            <div class="udb-avatar">
                <img src="<?php echo get_avatar_url( $current_user->ID ); ?>">
            </div>
            <div class="udb-profile-info">
                <h2><?php echo esc_attr($fullname) ?></h2>
                <p>Premium User</p>
            </div>
        </div>
        <nav class="udb-nav-links">
            <a href="<?php echo $currentUrl ?>" class="udb-nav-link <?php echo (!isset($_GET['pac'])) ? 'active': '' ?>">
                <svg viewBox="0 0 20 20" width="16px" height="16px" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="currentColor"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-180.000000, -2159.000000)" fill="currentColor"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M134,2008.99998 C131.783496,2008.99998 129.980955,2007.20598 129.980955,2004.99998 C129.980955,2002.79398 131.783496,2000.99998 134,2000.99998 C136.216504,2000.99998 138.019045,2002.79398 138.019045,2004.99998 C138.019045,2007.20598 136.216504,2008.99998 134,2008.99998 M137.775893,2009.67298 C139.370449,2008.39598 140.299854,2006.33098 139.958235,2004.06998 C139.561354,2001.44698 137.368965,1999.34798 134.722423,1999.04198 C131.070116,1998.61898 127.971432,2001.44898 127.971432,2004.99998 C127.971432,2006.88998 128.851603,2008.57398 130.224107,2009.67298 C126.852128,2010.93398 124.390463,2013.89498 124.004634,2017.89098 C123.948368,2018.48198 124.411563,2018.99998 125.008391,2018.99998 C125.519814,2018.99998 125.955881,2018.61598 126.001095,2018.10898 C126.404004,2013.64598 129.837274,2010.99998 134,2010.99998 C138.162726,2010.99998 141.595996,2013.64598 141.998905,2018.10898 C142.044119,2018.61598 142.480186,2018.99998 142.991609,2018.99998 C143.588437,2018.99998 144.051632,2018.48198 143.995366,2017.89098 C143.609537,2013.89498 141.147872,2010.93398 137.775893,2009.67298"> </path> </g> </g> </g> </g></svg>Profile
            </a>
            <a href="<?php echo $currentUrl.'?pac=edit-profile' ?>" class="udb-nav-link <?php echo (isset($_GET['pac']) && $_GET['pac'] === 'edit-profile') ? 'active': '' ?>">
                <svg viewBox="0 0 24 24" width="16px" height="16px" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>Edit Profile
            </a>
            <a href="<?php echo $currentUrl.'?pac=id-card' ?>" class="udb-nav-link <?php echo (isset($_GET['pac']) && $_GET['pac'] === 'id-card') ? 'active': '' ?>">
                <svg viewBox="0 0 24 24" width="16px" height="16px" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M3 5C2.44772 5 2 5.44771 2 6V18C2 18.5523 2.44772 19 3 19H21C21.5523 19 22 18.5523 22 18V6C22 5.44772 21.5523 5 21 5H3ZM0 6C0 4.34315 1.34314 3 3 3H21C22.6569 3 24 4.34315 24 6V18C24 19.6569 22.6569 21 21 21H3C1.34315 21 0 19.6569 0 18V6ZM6 10.5C6 9.67157 6.67157 9 7.5 9C8.32843 9 9 9.67157 9 10.5C9 11.3284 8.32843 12 7.5 12C6.67157 12 6 11.3284 6 10.5ZM10.1756 12.7565C10.69 12.1472 11 11.3598 11 10.5C11 8.567 9.433 7 7.5 7C5.567 7 4 8.567 4 10.5C4 11.3598 4.31002 12.1472 4.82438 12.7565C3.68235 13.4994 3 14.7069 3 16C3 16.5523 3.44772 17 4 17C4.55228 17 5 16.5523 5 16C5 15.1145 5.80048 14 7.5 14C9.19952 14 10 15.1145 10 16C10 16.5523 10.4477 17 11 17C11.5523 17 12 16.5523 12 16C12 14.7069 11.3177 13.4994 10.1756 12.7565ZM13 8C12.4477 8 12 8.44772 12 9C12 9.55228 12.4477 10 13 10H19C19.5523 10 20 9.55228 20 9C20 8.44772 19.5523 8 19 8H13ZM14 12C13.4477 12 13 12.4477 13 13C13 13.5523 13.4477 14 14 14H18C18.5523 14 19 13.5523 19 13C19 12.4477 18.5523 12 18 12H14Z" fill="currentColor"></path> </g></svg>ID Card
            </a>
            <a href="<?php echo $currentUrl.'?pac=loadform' ?>" class="udb-nav-link <?php echo (isset($_GET['pac']) && $_GET['pac'] === 'loadform') ? 'active': '' ?>">
                <svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="6" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M3 10H20.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 15H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>Loan Application Form
            </a>
            <a href="<?php echo wp_logout_url( get_the_permalink( $settings['user_signin_page'] ) ) ?>" class="udb-nav-link">
                <svg viewBox="0 0 24 24" width="16px" height="16px" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M15 12L6 12M6 12L8 14M6 12L8 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12 21.9827C10.4465 21.9359 9.51995 21.7626 8.87865 21.1213C8.11027 20.3529 8.01382 19.175 8.00171 17M16 21.9983C18.175 21.9862 19.3529 21.8897 20.1213 21.1213C21 20.2426 21 18.8284 21 16V14V10V8C21 5.17157 21 3.75736 20.1213 2.87868C19.2426 2 17.8284 2 15 2H14C11.1715 2 9.75733 2 8.87865 2.87868C8.11027 3.64706 8.01382 4.82497 8.00171 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M3 9.5V14.5C3 16.857 3 18.0355 3.73223 18.7678C4.46447 19.5 5.64298 19.5 8 19.5M3.73223 5.23223C4.46447 4.5 5.64298 4.5 8 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                Logout
            </a>
            
        </nav>
    </div>
    <div class="udb-main-content">
        <?php 
        if(isset($_GET['pac'])):
            if($_GET['pac'] === 'edit-profile'): 
                $errors = get_transient('user_profile_update_errors');
                $success = get_transient('user_profile_update_success');

                if (!empty($errors)) {
                    echo '<div class="error-messages">';
                    foreach ($errors as $error) {
                        echo '<p class="error">' . esc_html($error) . '</p>';
                    }
                    echo '</div>';
                    delete_transient('user_profile_update_errors');
                }

                if (!empty($success)) {
                    echo '<div class="success-message">';
                    echo '<p class="success">' . esc_html($success) . '</p>';
                    echo '</div>';
                    delete_transient('user_profile_update_success');
                }
                ?>
                <form id="edit-profile" class="udb-edit-profile-section" method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="save_user_profile">

                    <div class="udb-edit-profile-field">
                        <div class="avatarbox">
                            <img id="user-avatar-preview" src="<?php echo esc_url(get_avatar_url($current_user->ID)); ?>" alt="User Avatar">
                            <label>Change
                                <input type="file" id="user-avatar-file" name="user_avatar" accept="image/*">
                            </label>
                        </div>
                        <p class="upload-hint">Upload 220x240 image for a better look.</p>
                    </div>

                    <div class="udb-edit-profile-field">
                        <label>State Code:</label>
                        <input type="text" value="<?php echo !empty($state_code) ? esc_attr($state_code) : ''; ?>" readonly>
                    </div>

                    <div class="udb-edit-profile-field">
                        <label>Email:</label>
                        <input type="email" name="email" value="<?php echo !empty($current_user->user_email) ? esc_attr($current_user->user_email) : ''; ?>" required>
                    </div>

                    <div class="udb-state-row">
                        <div class="udb-edit-profile-field">
                            <label>First Name:</label>
                            <input type="text" name="first_name" value="<?php echo !empty($first_name) ? esc_attr($first_name) : ''; ?>" required>
                        </div>

                        <div class="udb-edit-profile-field">
                            <label>Last Name:</label>
                            <input type="text" name="last_name" value="<?php echo !empty($last_name) ? esc_attr($last_name) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="udb-edit-profile-field">
                        <label>State of Origin:</label>
                        <?php
                        $saved_origins = get_option('state_of_origins', []);
                        ?>
                        <select name="state_of_origin" required>
                            <option selected>Select state</option>
                            <?php
                            if (sizeof($saved_origins) > 0) {
                                foreach ($saved_origins as $origin) {
                                    echo '<option '.($origin === $state_of_origin ? 'selected': '').' value="'.$origin.'">'.$origin.'</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="udb-edit-profile-field">
                        <label>Gender:</label>
                        <?php 
                        $gender = !empty($gender) ? esc_attr($gender) : '';
                        ?>
                        <select name="gender" required>
                            <option value="" <?php echo (empty($gender)) ? 'selected' : ''; ?>>Select Gender</option>
                            <option value="Male" <?php echo ($gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($gender === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>


                    <div class="udb-form-actions">
                        <button type="submit" class="udb-btn">Save Changes</button>
                    </div>
                </form>
            <?php elseif($_GET['pac'] === 'loadform'): ?>
                <h3>Loan Application Form</h3>
                <hr>
                <?php 
                echo '<div class="membership-form">';
                if(get_user_meta( $current_user->ID, 'loan_payment_status', true ) === 'paid'){
                    echo "<div class='success-message'><p class='success'>Your loan application has been recieved. We will inform you if you have been shortlisted for the next stage of the appraisal process.</p></div>";
                }else{
                    echo '<div class="error-messages"><p class="error">Apply for our soft loan here.</p></div>';
                    echo '<form id="membership-loan-form" method="post">
                        <h3>Payment Due: NGN '.$settings['loan_amount'].'</h3>
                        <input type="submit" value="Pay Now" name="pay_now" class="loanform-action button-primary">
                    </form>';
                }
                echo '</div>';
                
                elseif($_GET['pac'] === 'id-card'): ?>
                <div class="user-dashboard idcard-wrapper">
                    <div class="user-id-card">
                        <div class="id-header">
                            <img width="270px" src="<?php echo plugin_dir_url( dirname(__FILE__) ) ?>assets/logo.png" alt="">
                        </div>
                        <div class="id-body">
                            <div class="idmeta">
                                <h3>MEMBERSHIP ID CARD</h3>
                                <p>Member Since <?php echo !empty($current_user->user_registered) ? date("F j, Y", strtotime($current_user->user_registered)) : '—'; ?></p>
                            </div>

                            <div class="user-avatar-box">
                                <div class="user-sate-code">
                                    <span>State Code:</span>
                                    <?php echo !empty($state_code) ? esc_html($state_code) : '—'; ?>
                                </div>
                                <div class="user-avatar">
                                    <img src="<?php echo esc_url(get_avatar_url($current_user->ID)); ?>" alt="">
                                </div>
                            </div>
                        </div>
                        <div class="id-footer">
                            <h3 class="username">Name: <span><?php echo esc_html($fullname ?: '—'); ?></span></h3>
                            <h3 class="userlevel">Status: <span><?php echo esc_html(!empty($user_level) ? $user_level : '—'); ?></span></h3>
                        </div>
                    </div>
                    <div class="idcard_preview"></div>
                    <div class="downloadcard-btn hidden-view">
                        <button id="download-image-card" class="button download-id-card">Download As Image</button>
                        <button id="download-pdf-card" class="button download-id-card">Download As PDF</button>
                    </div>
                </div>
            <?php endif;
        else: ?>
            <div class="udb-profile-details">
                <div class="udb-profile-detail">
                    <span class="udb-detail-label">State Code</span>
                    <span class="udb-detail-value"><?php echo !empty($state_code) ? esc_attr($state_code) : '—'; ?></span>
                </div>
                
                <div class="udb-profile-detail">
                    <span class="udb-detail-label">Status</span>
                    <span class="udb-detail-value"><?php echo !empty($user_level) ? esc_attr($user_level) : '—'; ?></span>
                </div>

                <div class="udb-profile-detail">
                    <span class="udb-detail-label">Email</span>
                    <span class="udb-detail-value"><?php echo !empty($current_user->user_email) ? esc_attr($current_user->user_email) : '—'; ?></span>
                </div>

                <div class="udb-profile-detail">
                    <span class="udb-detail-label">First Name</span>
                    <span class="udb-detail-value"><?php echo !empty($first_name) ? esc_attr($first_name) : '—'; ?></span>
                </div>

                <div class="udb-profile-detail">
                    <span class="udb-detail-label">Last Name</span>
                    <span class="udb-detail-value"><?php echo !empty($last_name) ? esc_attr($last_name) : '—'; ?></span>
                </div>

                <div class="udb-profile-detail">
                    <span class="udb-detail-label">State of Origin</span>
                    <span class="udb-detail-value"><?php echo !empty($state_of_origin) ? esc_attr($state_of_origin) : ''; ?></span>
                </div>

                <div class="udb-profile-detail">
                    <span class="udb-detail-label">Gender</span>
                    <span class="udb-detail-value"><?php echo !empty($gender) ? esc_attr($gender) : ''; ?></span>
                </div>
            </div>
        <?php endif; ?>        
    </div>
</div>