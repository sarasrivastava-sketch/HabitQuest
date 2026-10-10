<?php
/*
Plugin Name: HabitQuest Auth
Description: Login/Register shortcodes + page protection for HabitQuest.
Version: 1.1
*/

if (!defined('ABSPATH')) exit;

/* -------------------------
   Helpers: find page URLs safely
--------------------------*/
function hq_get_page_url_by_slugs($slugs, $fallback = '/') {
    foreach ((array)$slugs as $slug) {
        $page = get_page_by_path($slug);
        if ($page) {
            return get_permalink($page->ID);
        }
    }
    return home_url($fallback);
}

function hq_url_login() {
    return hq_get_page_url_by_slugs(array('login'), '/login/');
}
function hq_url_signup() {
    return hq_get_page_url_by_slugs(array('sign-up', 'signup', 'register'), '/sign-up/');
}
function hq_url_dashboard() {
    return hq_get_page_url_by_slugs(array('dashboard'), '/dashboard/');
}

/* -------------------------
   Minimal CSS for auth cards (printed only on auth pages)
--------------------------*/
function hq_auth_print_styles() {
    if (!is_page(array('login', 'sign-up', 'signup', 'register'))) return;

    echo '<style>
    .hq-auth-wrap{
        min-height:70vh;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:48px 16px;
    }
    .hq-card{
        width:100%;
        max-width:520px;
        background:#fff;
        border-radius:22px;
        padding:28px;
        border:1px solid rgba(2,73,80,.18);
        box-shadow:0 18px 50px rgba(0,0,0,.08);
    }
    .hq-card h2{
        margin:0 0 6px;
        color:#003135;
        font-size:32px;
        line-height:1.15;
    }
    .hq-card .hq-sub{
        margin:0 0 18px;
        color:#024950;
        opacity:.9;
        font-size:15px;
    }

    /* WP login form fields */
    .hq-card form p{ margin:0 0 14px; }
    .hq-card label{
        display:block;
        margin:0 0 8px;
        font-weight:600;
        color:#024950;
        font-size:14px;
    }
    .hq-card input[type="text"],
    .hq-card input[type="email"],
    .hq-card input[type="password"]{
        width:100%;
        padding:12px 14px;
        border-radius:14px;
        border:1px solid #d7e6ea;
        background:#ffffff;
        color:#003135;
        outline:none;
    }
    .hq-card input[type="text"]:focus,
    .hq-card input[type="email"]:focus,
    .hq-card input[type="password"]:focus{
        border-color:#0FA4AF;
        box-shadow:0 0 0 4px rgba(15,164,175,.15);
    }

    .hq-card .login-remember{
        display:flex;
        align-items:center;
        gap:8px;
        margin-top:6px;
    }
    .hq-card .login-remember label{
        margin:0;
        font-weight:500;
        color:#024950;
    }

    .hq-card input[type="submit"],
    .hq-card button{
        width:100%;
        background:#0FA4AF;
        color:#fff;
        border:0;
        padding:12px 16px;
        border-radius:14px;
        font-weight:700;
        cursor:pointer;
    }
    .hq-card input[type="submit"]:hover,
    .hq-card button:hover{
        background:#024950;
    }

    .hq-card .hq-links{
        margin-top:14px;
        display:flex;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
        font-size:14px;
    }
    .hq-card a{
        color:#0FA4AF;
        text-decoration:none;
        font-weight:600;
    }
    .hq-card a:hover{
        text-decoration:underline;
    }
    .hq-card .hq-bottom{
        margin-top:18px;
        padding-top:14px;
        border-top:1px solid #e7f1f3;
        text-align:center;
        color:#024950;
        font-size:14px;
    }

    .hq-alert{
        padding:12px 14px;
        border-radius:14px;
        margin:0 0 16px;
        border:1px solid rgba(150,71,52,.25);
        background:rgba(150,71,52,.08);
        color:#5a2418;
    }
    .hq-alert ul{ margin:8px 0 0 18px; }
    </style>';
}
add_action('wp_head', 'hq_auth_print_styles');


/* -------------------------
   Shortcode: [hq_login]
--------------------------*/
function hq_login_shortcode() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $logout_url = wp_logout_url(hq_url_login());

        return '<div class="hq-auth-wrap"><div class="hq-card">
                    <h2>You\'re in</h2>
                    <p class="hq-sub">Logged in as <strong>' . esc_html($user->user_login) . '</strong>.</p>
                    <div class="hq-links">
                        <a href="' . esc_url(hq_url_dashboard()) . '">Go to Dashboard</a>
                        <a href="' . esc_url($logout_url) . '">Log out</a>
                    </div>
                </div></div>';
    }

    // WP’s built-in login form (we style it with CSS above)
    $args = array(
        'echo' => false,
        'redirect' => hq_url_dashboard(),
        'form_id' => 'hq-loginform',
        'label_username' => 'Username or Email',
        'label_password' => 'Password',
        'label_remember' => 'Remember me',
        'label_log_in' => 'Log In',
        'remember' => true
    );

    $signup_url = hq_url_signup();
    $lost_url   = wp_lostpassword_url(hq_url_login());

    $out  = '<div class="hq-auth-wrap"><div class="hq-card">';
    $out .= '<h2>Welcome back</h2>';
    $out .= '<p class="hq-sub">Log in to continue your quests.</p>';
    $out .= wp_login_form($args);

    // Links INSIDE the card
    $out .= '<div class="hq-links">
                <a href="' . esc_url($lost_url) . '">Forgot password?</a>
                <a href="' . esc_url($signup_url) . '">Create an account</a>
            </div>';

    $out .= '<div class="hq-bottom">New here? <a href="' . esc_url($signup_url) . '">Sign up</a> in under a minute.</div>';
    $out .= '</div></div>';

    return $out;
}
add_shortcode('hq_login', 'hq_login_shortcode');


/* -------------------------
   Shortcode: [hq_register]
--------------------------*/
function hq_register_shortcode() {
    if (is_user_logged_in()) {
        return '<div class="hq-auth-wrap"><div class="hq-card">
                    <h2>Already logged in</h2>
                    <p class="hq-sub">Go to your dashboard to continue.</p>
                    <p><a href="' . esc_url(hq_url_dashboard()) . '">Go to Dashboard</a></p>
                </div></div>';
    }

    $errors = array();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hq_register_nonce'])) {
        if (!wp_verify_nonce($_POST['hq_register_nonce'], 'hq_register_action')) {
            $errors[] = 'Security check failed. Please try again.';
        } else {
            $username = sanitize_user($_POST['hq_username'] ?? '');
            $email    = sanitize_email($_POST['hq_email'] ?? '');
            $pass1    = $_POST['hq_pass1'] ?? '';
            $pass2    = $_POST['hq_pass2'] ?? '';

            if (empty($username) || empty($email) || empty($pass1) || empty($pass2)) {
                $errors[] = 'Please fill in all fields.';
            }
            if (!is_email($email)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if ($pass1 !== $pass2) {
                $errors[] = 'Passwords do not match.';
            }
            if (username_exists($username)) {
                $errors[] = 'That username is already taken.';
            }
            if (email_exists($email)) {
                $errors[] = 'That email is already registered.';
            }

            if (empty($errors)) {
                $user_id = wp_create_user($username, $pass1, $email);

                if (is_wp_error($user_id)) {
                    $errors[] = $user_id->get_error_message();
                } else {
                    // Auto login
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);

                    wp_safe_redirect(hq_url_dashboard());
                    exit;
                }
            }
        }
    }

    $login_url = hq_url_login();

    ob_start();
    echo '<div class="hq-auth-wrap"><div class="hq-card">';
    echo '<h2>Create account</h2>';
    echo '<p class="hq-sub">Start tracking habits like quests.</p>';

    if (!empty($errors)) {
        echo '<div class="hq-alert"><strong>Fix this:</strong><ul>';
        foreach ($errors as $e) echo '<li>' . esc_html($e) . '</li>';
        echo '</ul></div>';
    }
    ?>

    <form method="post">
        <?php wp_nonce_field('hq_register_action', 'hq_register_nonce'); ?>

        <p>
            <label for="hq_username">Username</label>
            <input id="hq_username" type="text" name="hq_username" required>
        </p>

        <p>
            <label for="hq_email">Email</label>
            <input id="hq_email" type="email" name="hq_email" required>
        </p>

        <p>
            <label for="hq_pass1">Password</label>
            <input id="hq_pass1" type="password" name="hq_pass1" required>
        </p>

        <p>
            <label for="hq_pass2">Confirm Password</label>
            <input id="hq_pass2" type="password" name="hq_pass2" required>
        </p>

        <p>
            <button type="submit">Create Account</button>
        </p>

        <div class="hq-bottom">
            Already have an account? <a href="<?php echo esc_url($login_url); ?>">Log in</a>
        </div>
    </form>

    <?php
    echo '</div></div>';
    return ob_get_clean();
}
add_shortcode('hq_register', 'hq_register_shortcode');


/* -------------------------
   Protect HabitQuest pages (edit slugs if needed)
--------------------------*/
function hq_protect_pages() {
    $protected = array('dashboard', 'my-habits', 'progress', 'awards', 'tips');

    if (is_page($protected) && !is_user_logged_in()) {
        wp_safe_redirect(hq_url_login());
        exit;
    }
}
add_action('template_redirect', 'hq_protect_pages');