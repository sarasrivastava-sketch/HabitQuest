<?php
/*
Plugin Name: HabitQuest Tracker
Description: Per-user habits + daily completion + XP/level dashboard + progress + awards (shortcodes: [hq_dashboard], [hq_progress], [hq_awards]).
Version: 1.9
*/

if (!defined('ABSPATH')) exit;

/* =========================
   Tables + Categories
========================= */
function hq_tracker_table_habits() {
    global $wpdb;
    return $wpdb->prefix . 'hq_habits';
}
function hq_tracker_table_logs() {
    global $wpdb;
    return $wpdb->prefix . 'hq_habit_logs';
}

/**
 * Predefined categories with fixed XP (anti-cheat).
 */
function hq_tracker_categories() {
    return array(
        'Study'        => array('label' => 'Study',        'xp' => 10, 'icon' => '📚'),
        'Fitness'      => array('label' => 'Fitness',      'xp' => 15, 'icon' => '🏃'),
        'Mind'         => array('label' => 'Mind',         'xp' => 12, 'icon' => '🧠'),
        'Lifestyle'    => array('label' => 'Lifestyle',    'xp' => 8,  'icon' => '✨'),
        'Hydration'    => array('label' => 'Hydration',    'xp' => 6,  'icon' => '💧'),
        'Sleep'        => array('label' => 'Sleep',        'xp' => 9,  'icon' => '🌙'),
        'Productivity' => array('label' => 'Productivity', 'xp' => 11, 'icon' => '✅'),
        'Creativity'   => array('label' => 'Creativity',   'xp' => 13, 'icon' => '🎨'),
    );
}

/* =========================
   Install / Update tables
========================= */
function hq_tracker_install_tables() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $habits = hq_tracker_table_habits();
    $logs   = hq_tracker_table_logs();

    $sql1 = "CREATE TABLE $habits (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        title VARCHAR(140) NOT NULL,
        description TEXT NULL,
        category VARCHAR(60) NULL,
        xp INT(11) NOT NULL DEFAULT 10,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    $sql2 = "CREATE TABLE $logs (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        habit_id BIGINT(20) UNSIGNED NOT NULL,
        completed_date DATE NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY user_habit_day (user_id, habit_id, completed_date),
        KEY habit_id (habit_id),
        KEY user_id (user_id)
    ) $charset_collate;";

    dbDelta($sql1);
    dbDelta($sql2);

    update_option('hq_tracker_db_version', '1.0');
}

function hq_tracker_tables_exist() {
    global $wpdb;
    $habits = hq_tracker_table_habits();
    $logs   = hq_tracker_table_logs();

    $habits_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $habits));
    $logs_exists   = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $logs));

    return (!empty($habits_exists) && !empty($logs_exists));
}

function hq_tracker_maybe_install() {
    $ver = get_option('hq_tracker_db_version');
    if ($ver !== '1.0' || !hq_tracker_tables_exist()) {
        hq_tracker_install_tables();
    }
}
add_action('init', 'hq_tracker_maybe_install');
register_activation_hook(__FILE__, 'hq_tracker_install_tables');


/* =========================
   Helpers (URLs + Redirect)
========================= */
function hq_tracker_get_page_url($slugs, $fallback = '/') {
    foreach ((array)$slugs as $slug) {
        $page = get_page_by_path($slug);
        if ($page) return get_permalink($page->ID);
    }
    return home_url($fallback);
}
function hq_tracker_url_login() {
    return hq_tracker_get_page_url(array('login'), '/login/');
}

/**
 * Redirect back to the same page with params (PRG pattern).
 */
function hq_tracker_redirect_with_params($params = array()) {
    $ref = wp_get_referer();
    $url = $ref ? $ref : (is_singular() ? get_permalink() : home_url('/'));
    $url = remove_query_arg(array('hq_notice', 'hq_xp'), $url);
    if (!empty($params)) $url = add_query_arg($params, $url);
    wp_safe_redirect($url);
    exit;
}


/* =========================
   Styles (dashboard + progress + awards)
========================= */
function hq_tracker_print_styles_if_needed() {
    if (!is_singular()) return;
    global $post;
    if (!$post) return;

    $need = has_shortcode($post->post_content, 'hq_dashboard')
         || has_shortcode($post->post_content, 'hq_progress')
         || has_shortcode($post->post_content, 'hq_awards');

    if (!$need) return;

    echo '<style>
    /* Shared base */
    .hq-wrap{max-width:1050px;margin:0 auto;padding:28px 16px 60px;}
    .hq-header{display:flex;align-items:flex-end;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px;}
    .hq-title{margin:0 0 6px;color:#003135;font-size:38px;line-height:1.1;}
    .hq-sub{margin:0;color:#024950;opacity:.95;}

    .hq-chips{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;}
    .hq-chip{display:inline-flex;align-items:center;gap:8px;background:#F3FBFD;border:1px solid rgba(2,73,80,.14);
        color:#024950;padding:8px 12px;border-radius:999px;font-size:13px;font-weight:800;box-shadow:0 8px 18px rgba(0,0,0,.04);white-space:nowrap;}

    .hq-card{background:#fff;border:1px solid rgba(2,73,80,.14);border-radius:18px;padding:18px;
        box-shadow:0 10px 28px rgba(0,0,0,.06);margin:0 0 14px;}

    .hq-xp-card{padding:20px;background:linear-gradient(135deg, rgba(15,164,175,.12), rgba(175,221,229,.28));
        border:1px solid rgba(15,164,175,.28);}
    .hq-xp-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;}
    .hq-level{display:inline-flex;align-items:center;gap:8px;background:#003135;color:#fff;padding:6px 10px;border-radius:999px;
        font-weight:900;letter-spacing:.4px;font-size:12px;text-transform:uppercase;margin-bottom:10px;}
    .hq-xp{color:#003135;font-weight:900;font-size:22px;}
    .hq-muted{color:#024950;opacity:.9;font-size:14px;margin:6px 0 0;}

    .hq-bar{width:100%;height:14px;background:rgba(2,73,80,.14);border-radius:999px;overflow:hidden;margin-top:12px;}
    .hq-bar-fill{height:100%;background:#0FA4AF;border-radius:999px;}

    .hq-section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:18px 0 10px;flex-wrap:wrap;}
    .hq-section-head h3{margin:0;color:#003135;font-size:22px;}

    /* Dashboard list */
    .hq-row{display:flex;gap:14px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;}
    .hq-habit-title{margin:0 0 4px;color:#003135;font-size:18px;}
    .hq-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center;}
    .hq-pill{background:#AFDDE5;color:#003135;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:900;}
    .hq-pill-xp{background:rgba(15,164,175,.18);color:#003135;}
    .hq-streak{color:#024950;font-size:13px;font-weight:900;}

    .hq-actions{display:flex;flex-direction:column;gap:10px;min-width:210px;align-items:flex-end;}

    .hq-btn{
        width:210px;background:linear-gradient(135deg, #0FA4AF, #028c95);
        color:#fff;border:0;padding:12px 12px;border-radius:14px;
        font-weight:950;cursor:pointer;box-shadow:0 10px 22px rgba(15,164,175,.22);
    }
    .hq-btn:hover{filter:saturate(1.05) brightness(.98);}
    .hq-btn:active{transform:translateY(1px);}

    .hq-btn-secondary{
        width:210px;background:#fff;color:#024950;border:1px solid rgba(2,73,80,.25);
        padding:12px 12px;border-radius:14px;font-weight:900;cursor:pointer;
    }
    .hq-btn-secondary:hover{border-color:#0FA4AF;box-shadow:0 0 0 4px rgba(15,164,175,.12);}

    /* New: Archive (danger-ish) button */
    .hq-btn-archive{
        width:210px;
        background:#fff;
        color:#964734;
        border:1px solid rgba(150,71,52,.35);
        padding:12px 12px;
        border-radius:14px;
        font-weight:950;
        cursor:pointer;
    }
    .hq-btn-archive:hover{
        box-shadow:0 0 0 4px rgba(150,71,52,.10);
    }

    .hq-details summary{list-style:none;cursor:pointer;user-select:none;font-weight:900;color:#003135;display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .hq-details summary::-webkit-details-marker{display:none;}
    .hq-details .hq-summary-btn{background:#003135;color:#fff;padding:8px 12px;border-radius:999px;font-size:12px;font-weight:900;}

    .hq-form{margin-top:14px;}
    .hq-form label{display:block;margin:0 0 8px;font-weight:900;color:#024950;font-size:14px;}
    .hq-form input, .hq-form textarea, .hq-form select{
        width:100%;border-radius:14px;border:1px solid #d7e6ea;outline:none;color:#003135;background:#fff;
        box-sizing:border-box;font-size:15px;line-height:1.4;
    }
    .hq-form input, .hq-form select{height:52px;padding:12px 14px;}
    .hq-form textarea{padding:12px 14px;}
    .hq-form select{
        padding-right:48px;
        -webkit-appearance:none;-moz-appearance:none;appearance:none;
        background-color:#fff;
        background-image:
            linear-gradient(45deg, transparent 50%, #0FA4AF 50%),
            linear-gradient(135deg, #0FA4AF 50%, transparent 50%),
            linear-gradient(to right, rgba(15,164,175,.16), rgba(15,164,175,0));
        background-position:
            calc(100% - 22px) 50%,
            calc(100% - 14px) 50%,
            0 0;
        background-size: 7px 7px, 7px 7px, 100% 100%;
        background-repeat:no-repeat;
        font-weight:900;
    }
    .hq-form input:focus, .hq-form textarea:focus, .hq-form select:focus{
        border-color:#0FA4AF;box-shadow:0 0 0 4px rgba(15,164,175,.15);
    }

    .hq-notice{padding:12px 14px;border-radius:14px;margin:0 0 14px;border:1px solid rgba(15,164,175,.35);
        background:rgba(15,164,175,.10);color:#003135;font-weight:900;}
    .hq-alert{padding:12px 14px;border-radius:14px;margin:0 0 14px;border:1px solid rgba(150,71,52,.25);
        background:rgba(150,71,52,.08);color:#5a2418;font-weight:800;}

    /* Toast popup (dashboard) */
    .hq-toast{
        position:fixed;right:18px;bottom:18px;z-index:9999;background:#003135;color:#fff;padding:12px 14px;border-radius:16px;
        border:1px solid rgba(175,221,229,.35);box-shadow:0 18px 55px rgba(0,0,0,.20);font-weight:950;max-width:320px;
        opacity:1;transform:translateY(0);transition:opacity .35s ease, transform .35s ease;
    }
    .hq-toast small{display:block;color:#AFDDE5;font-weight:800;margin-top:4px;}
    .hq-toast.hide{opacity:0;transform:translateY(10px);}

    /* ========= Progress-only redesign ========= */
    .hq-progress .hq-title{font-size:40px;}
    .hq-progress-hero{
        background: linear-gradient(135deg, rgba(2,73,80,.92), rgba(15,164,175,.40));
        border: 1px solid rgba(2,73,80,.18);
        color:#fff;
        border-radius:22px;
        padding:22px;
        box-shadow: 0 18px 55px rgba(0,0,0,.12);
        margin-bottom: 14px;
        display:flex;
        justify-content:space-between;
        align-items:flex-end;
        gap:14px;
        flex-wrap:wrap;
    }
    .hq-progress-hero h2{margin:0;color:#fff;font-size:34px;line-height:1.1;}
    .hq-progress-hero p{margin:6px 0 0;color:rgba(255,255,255,.85);font-weight:700;}
    .hq-progress-badges{display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;}
    .hq-progress-badge{
        background: rgba(255,255,255,.14);
        border: 1px solid rgba(255,255,255,.22);
        color:#fff;
        padding:8px 12px;
        border-radius:999px;
        font-size:13px;
        font-weight:900;
        white-space:nowrap;
    }

    .hq-progress-grid{
        display:grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap:14px;
        margin-bottom: 14px;
    }
    .hq-progress-stat{
        background:#fff;
        border:1px solid rgba(2,73,80,.14);
        border-radius:18px;
        padding:18px;
        box-shadow:0 10px 28px rgba(0,0,0,.06);
        position:relative;
        overflow:hidden;
    }
    .hq-progress-stat:before{
        content:"";
        position:absolute;
        inset:0;
        background: radial-gradient(circle at 10% 0%, rgba(15,164,175,.14), transparent 55%);
        pointer-events:none;
    }
    .hq-progress-stat .label{position:relative;color:#024950;opacity:.9;font-weight:900;font-size:13px;margin:0 0 8px;}
    .hq-progress-stat .value{position:relative;color:#003135;font-weight:950;font-size:28px;margin:0;}
    .hq-progress-stat .mini{position:relative;color:#024950;opacity:.9;font-size:13px;margin:8px 0 0;font-weight:700;}

    .hq-dots{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
    .hq-dot{
        width:38px;height:38px;border-radius:14px;
        border:1px solid rgba(2,73,80,.18);
        background: rgba(2,73,80,.06);
        display:flex;align-items:center;justify-content:center;
        font-weight:950;
        color:#024950;
        font-size:12px;
        position:relative;
        overflow:hidden;
    }
    .hq-dot.on{background: rgba(15,164,175,.20);border-color: rgba(15,164,175,.35);color:#003135;}
    .hq-dot small{position:absolute;bottom:3px;right:6px;font-size:10px;font-weight:900;opacity:.9;}

    .hq-weeklist{display:flex;flex-direction:column;gap:10px;margin-top:12px;}
    .hq-weekrow{display:grid;grid-template-columns: 52px 1fr 44px;gap:10px;align-items:center;}
    .hq-weekrow .d{font-weight:900;color:#024950;}
    .hq-weekrow .c{font-weight:950;color:#003135;text-align:right;}
    .hq-weekbar{height:14px;border-radius:999px;background: rgba(2,73,80,.10);overflow:hidden;}
    .hq-weekbar > div{height:100%;background:#0FA4AF;border-radius:999px;width:0%;}

    .hq-rank{display:flex;flex-direction:column;gap:10px;margin-top:10px;}
    .hq-rankitem{
        display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;
        padding:12px;border-radius:16px;border:1px solid rgba(2,73,80,.12);
        background: rgba(243,251,253,.8);
    }
    .hq-rankleft strong{color:#003135;}
    .hq-rankleft .meta{color:#024950;opacity:.9;font-weight:700;font-size:13px;margin-top:2px;}
    .hq-rankbadge{
        background:#003135;color:#fff;border-radius:999px;
        padding:8px 12px;font-weight:950;font-size:13px;height:fit-content;
    }

    /* ========= Awards-only styling ========= */
    .hq-awards-grid{
        display:grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap:14px;
    }
    .hq-award{
        background:#fff;
        border:1px solid rgba(2,73,80,.14);
        border-radius:18px;
        padding:16px;
        box-shadow:0 10px 28px rgba(0,0,0,.06);
        display:flex;
        gap:12px;
        align-items:flex-start;
    }
    .hq-award-icon{
        width:44px;height:44px;border-radius:14px;
        display:flex;align-items:center;justify-content:center;
        background: rgba(15,164,175,.14);
        border:1px solid rgba(15,164,175,.22);
        font-size:20px;
        flex: 0 0 auto;
    }
    .hq-award.locked{opacity:.72;}
    .hq-award.locked .hq-award-icon{
        background: rgba(2,73,80,.08);
        border-color: rgba(2,73,80,.14);
    }
    .hq-award-top{
        display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;
    }
    .hq-award-title{margin:0;color:#003135;font-weight:950;font-size:16px;}
    .hq-award-desc{margin:6px 0 0;color:#024950;opacity:.92;font-size:13px;font-weight:700;}
    .hq-award-progress{margin:10px 0 0;color:#024950;opacity:.9;font-size:13px;font-weight:800;}

    .hq-status{
        padding:6px 10px;border-radius:999px;font-size:12px;font-weight:950;
        border:1px solid rgba(2,73,80,.14);
        background: rgba(2,73,80,.06);
        color:#024950;
        white-space:nowrap;
    }
    .hq-status.unlocked{
        background: rgba(15,164,175,.16);
        border-color: rgba(15,164,175,.30);
        color:#003135;
    }

    @media (max-width: 900px){
        .hq-progress-grid{grid-template-columns:1fr;}
        .hq-awards-grid{grid-template-columns:1fr;}
    }
    @media (max-width:720px){
        .hq-title{font-size:32px;}
        .hq-actions{align-items:stretch;width:100%;min-width:unset;}
        .hq-btn,.hq-btn-secondary,.hq-btn-archive{width:100%;}
        .hq-chips{justify-content:flex-start;}
        .hq-toast{left:16px;right:16px;bottom:16px;max-width:none;}
    }
    </style>';
}
add_action('wp_head', 'hq_tracker_print_styles_if_needed');


/* =========================
   Streak calculations
========================= */
function hq_tracker_current_streak($user_id, $habit_id) {
    global $wpdb;
    $logs = hq_tracker_table_logs();

    $dates = $wpdb->get_col($wpdb->prepare(
        "SELECT completed_date FROM $logs
         WHERE user_id=%d AND habit_id=%d
         ORDER BY completed_date DESC
         LIMIT 200",
        $user_id, $habit_id
    ));

    if (empty($dates)) return 0;

    $today = current_time('Y-m-d');
    $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
    $set = array_fill_keys($dates, true);

    $start = isset($set[$today]) ? $today : $yesterday;

    $streak = 0;
    $cursor = $start;
    while (isset($set[$cursor])) {
        $streak++;
        $cursor = date('Y-m-d', strtotime($cursor . ' -1 day'));
    }
    return $streak;
}

function hq_tracker_best_streak_all_time($user_id) {
    global $wpdb;
    $logs = hq_tracker_table_logs();

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT habit_id, completed_date
         FROM $logs
         WHERE user_id=%d
         ORDER BY habit_id ASC, completed_date ASC",
        $user_id
    ));

    if (empty($rows)) return 0;

    $best = 0;
    $currentHabit = null;
    $currentRun = 0;
    $maxForHabit = 0;
    $prevDate = null;

    foreach ($rows as $r) {
        $hid = (int)$r->habit_id;
        $d = $r->completed_date;

        if ($currentHabit === null || $hid !== $currentHabit) {
            $best = max($best, $maxForHabit);
            $currentHabit = $hid;
            $currentRun = 1;
            $maxForHabit = 1;
            $prevDate = $d;
            continue;
        }

        $diff = (strtotime($d) - strtotime($prevDate)) / 86400;

        if ($diff == 1) $currentRun++;
        elseif ($diff == 0) { /* ignore */ }
        else $currentRun = 1;

        $maxForHabit = max($maxForHabit, $currentRun);
        $prevDate = $d;
    }

    $best = max($best, $maxForHabit);
    return (int)$best;
}


/* =========================
   Shortcode: [hq_dashboard]
========================= */
function hq_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="hq-wrap"><div class="hq-card">
            <h3 style="margin:0;color:#003135;">Login required</h3>
            <p class="hq-muted">Please log in to see your dashboard.</p>
            <p><a href="' . esc_url(hq_tracker_url_login()) . '" style="color:#0FA4AF;font-weight:900;text-decoration:none;">Go to Login</a></p>
        </div></div>';
    }

    global $wpdb;
    hq_tracker_maybe_install();

    $habits_table = hq_tracker_table_habits();
    $logs_table   = hq_tracker_table_logs();

    $user_id = get_current_user_id();
    $today = current_time('Y-m-d');
    $user = wp_get_current_user();

    $categories = hq_tracker_categories();
    $errors = array();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hq_dash_nonce'])) {
        if (!wp_verify_nonce($_POST['hq_dash_nonce'], 'hq_dash_action')) {
            $errors[] = 'Security check failed. Please refresh and try again.';
        } else {
            $action = sanitize_text_field($_POST['hq_action'] ?? '');

            if ($action === 'add_habit') {
                $title = sanitize_text_field($_POST['title'] ?? '');
                $desc  = sanitize_textarea_field($_POST['description'] ?? '');
                $cat   = sanitize_text_field($_POST['category'] ?? '');

                if (trim($title) === '') $errors[] = 'Please enter a habit title.';
                elseif (!isset($categories[$cat])) $errors[] = 'Please choose a valid category.';
                else {
                    $xp = (int) $categories[$cat]['xp'];

                    $ok = $wpdb->insert(
                        $habits_table,
                        array(
                            'user_id' => $user_id,
                            'title' => $title,
                            'description' => $desc,
                            'category' => $cat,
                            'xp' => $xp,
                            'active' => 1,
                            'created_at' => current_time('mysql'),
                        ),
                        array('%d','%s','%s','%s','%d','%d','%s')
                    );

                    if ($ok === false) {
                        $msg = 'Database error: could not save habit.';
                        if (current_user_can('manage_options') && !empty($wpdb->last_error)) $msg .= ' (' . $wpdb->last_error . ')';
                        $errors[] = $msg;
                    } else {
                        hq_tracker_redirect_with_params(array('hq_notice' => 'habit_added'));
                    }
                }
            }

            if ($action === 'complete') {
                $habit_id = intval($_POST['habit_id'] ?? 0);
                if ($habit_id > 0) {
                    $row = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, xp FROM $habits_table WHERE id=%d AND user_id=%d AND active=1",
                        $habit_id, $user_id
                    ));

                    if ($row) {
                        $wpdb->replace(
                            $logs_table,
                            array(
                                'user_id' => $user_id,
                                'habit_id' => $habit_id,
                                'completed_date' => $today,
                                'created_at' => current_time('mysql'),
                            ),
                            array('%d','%d','%s','%s')
                        );

                        hq_tracker_redirect_with_params(array('hq_notice' => 'completed', 'hq_xp' => (int)$row->xp));
                    }
                }
            }

            if ($action === 'undo') {
                $habit_id = intval($_POST['habit_id'] ?? 0);
                if ($habit_id > 0) {
                    $wpdb->delete(
                        $logs_table,
                        array('user_id' => $user_id, 'habit_id' => $habit_id, 'completed_date' => $today),
                        array('%d','%d','%s')
                    );
                    hq_tracker_redirect_with_params(array('hq_notice' => 'undone'));
                }
            }

            /* NEW: Archive (soft delete) */
            if ($action === 'archive') {
                $habit_id = intval($_POST['habit_id'] ?? 0);
                if ($habit_id > 0) {
                    $wpdb->update(
                        $habits_table,
                        array('active' => 0),
                        array('id' => $habit_id, 'user_id' => $user_id),
                        array('%d'),
                        array('%d','%d')
                    );
                    hq_tracker_redirect_with_params(array('hq_notice' => 'archived'));
                }
            }

            /* NEW: Restore archived habit */
            if ($action === 'restore') {
                $habit_id = intval($_POST['habit_id'] ?? 0);
                if ($habit_id > 0) {
                    $wpdb->update(
                        $habits_table,
                        array('active' => 1),
                        array('id' => $habit_id, 'user_id' => $user_id),
                        array('%d'),
                        array('%d','%d')
                    );
                    hq_tracker_redirect_with_params(array('hq_notice' => 'restored'));
                }
            }
        }
    }

    // Active habits
    $habits = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $habits_table WHERE user_id=%d AND active=1 ORDER BY created_at DESC",
        $user_id
    ));

    // Archived habits
    $archived = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $habits_table WHERE user_id=%d AND active=0 ORDER BY created_at DESC",
        $user_id
    ));

    $habit_count = count($habits);

    $completed_today = $wpdb->get_col($wpdb->prepare(
        "SELECT habit_id FROM $logs_table WHERE user_id=%d AND completed_date=%s",
        $user_id, $today
    ));
    $completed_set = array_fill_keys($completed_today ?: array(), true);
    $completed_count = count($completed_today ?: array());

    $total_xp = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(h.xp),0)
         FROM $logs_table l
         INNER JOIN $habits_table h ON h.id = l.habit_id
         WHERE l.user_id=%d",
        $user_id
    ));

    $xp_today = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(h.xp),0)
         FROM $logs_table l
         INNER JOIN $habits_table h ON h.id = l.habit_id
         WHERE l.user_id=%d AND l.completed_date=%s",
        $user_id, $today
    ));

    $xp_per_level = 100;
    $level = floor($total_xp / $xp_per_level) + 1;
    $xp_into_level = $total_xp % $xp_per_level;
    $xp_to_next = $xp_per_level - $xp_into_level;

    $bar_pct = ($xp_into_level / $xp_per_level) * 100;
    $bar_pct = max(0, min(100, $bar_pct));

    $pretty_date = date_i18n('D, j M', strtotime($today));
    $notice = sanitize_text_field($_GET['hq_notice'] ?? '');
    $toast_xp = intval($_GET['hq_xp'] ?? 0);

    ob_start();
    ?>
    <div class="hq-wrap">

        <div class="hq-header">
            <div>
                <h2 class="hq-title">Today’s Quest</h2>
                <p class="hq-sub">Small steps still count. Keep going.</p>
            </div>

            <div class="hq-chips">
                <span class="hq-chip">👤 <?php echo esc_html($user->user_login); ?></span>
                <span class="hq-chip">📅 <?php echo esc_html($pretty_date); ?></span>
                <span class="hq-chip">✅ <?php echo esc_html($completed_count . '/' . max(0, $habit_count)); ?> done</span>
                <span class="hq-chip">⚡ +<?php echo esc_html($xp_today); ?> XP today</span>
            </div>
        </div>

        <?php if ($notice === 'habit_added'): ?>
            <div class="hq-notice">Habit added. New quest unlocked.</div>
        <?php elseif ($notice === 'undone'): ?>
            <div class="hq-notice">Completion undone.</div>
        <?php elseif ($notice === 'archived'): ?>
            <div class="hq-notice">Habit removed from your list (archived).</div>
        <?php elseif ($notice === 'restored'): ?>
            <div class="hq-notice">Habit restored.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="hq-alert">
                Fix this:
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo esc_html($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($notice === 'completed'): ?>
            <div class="hq-toast" id="hq-toast">
                🎉 Yayy! +<?php echo esc_html($toast_xp); ?> XP
                <small>Quest completed for today.</small>
            </div>
            <script>
            (function () {
                var t = document.getElementById('hq-toast');
                setTimeout(function(){ if(t) t.classList.add('hide'); }, 2200);
                setTimeout(function(){ if(t) t.remove(); }, 3000);
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('hq_notice');
                    url.searchParams.delete('hq_xp');
                    window.history.replaceState({}, '', url.toString());
                } catch(e) {}
            })();
            </script>
        <?php endif; ?>

        <div class="hq-card hq-xp-card">
            <div class="hq-xp-top">
                <div>
                    <div class="hq-level">LEVEL <?php echo esc_html($level); ?> • XP</div>
                    <div class="hq-xp"><?php echo esc_html($xp_into_level . ' / ' . $xp_per_level . ' XP'); ?></div>
                    <p class="hq-muted">Next level in <strong><?php echo esc_html($xp_to_next); ?> XP</strong></p>
                </div>
                <div style="text-align:right;">
                    <p class="hq-muted" style="margin:0;">Total XP</p>
                    <div class="hq-xp" style="font-size:20px;"><?php echo esc_html($total_xp); ?></div>
                </div>
            </div>

            <div class="hq-bar" aria-label="XP progress">
                <div class="hq-bar-fill" style="width: <?php echo esc_attr($bar_pct); ?>%;"></div>
            </div>
        </div>

        <div class="hq-section-head">
            <h3>Your Habits</h3>
        </div>

        <details class="hq-card hq-details" id="hq-add-habit">
            <summary>
                <span>➕ Add a new habit</span>
                <span class="hq-summary-btn">New</span>
            </summary>

            <form method="post" class="hq-form">
                <?php wp_nonce_field('hq_dash_action', 'hq_dash_nonce'); ?>
                <input type="hidden" name="hq_action" value="add_habit">

                <p>
                    <label>Name Your Quest</label>
                    <input type="text" name="title" placeholder="Example: Read 30 pages" required>
                </p>

                <p>
                    <label>Remind Yourself Why You're Doing It</label>
                    <textarea name="description" rows="3" placeholder="Example: Digital detox before bed."></textarea>
                </p>

                <p>
                    <label>Category (Determines how much XP you will earn)</label>
                    <select name="category" required>
                        <option value="" disabled selected>Select a category</option>
                        <?php foreach (hq_tracker_categories() as $key => $c): ?>
                            <option value="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($c['icon'] . ' ' . $c['label'] . ' • ' . (int)$c['xp'] . ' XP'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p><button class="hq-btn" type="submit">Create Habit</button></p>
            </form>
        </details>

        <?php if (empty($habits)): ?>
            <div class="hq-card">
                <h4 class="hq-habit-title" style="font-size:20px;">No active habits</h4>
                <p class="hq-muted">Create a habit above to start tracking or if you accidently deleted it, you can restore one from Archived.</p>
            </div>
        <?php else: ?>
            <?php foreach ($habits as $h): ?>
                <?php
                    $habit_id = (int)$h->id;
                    $done = isset($completed_set[$habit_id]);
                    $streak = hq_tracker_current_streak($user_id, $habit_id);
                    $xp_val = (int)$h->xp;
                ?>
                <div class="hq-card">
                    <div class="hq-row">
                        <div style="flex:1; min-width:260px;">
                            <h4 class="hq-habit-title"><?php echo esc_html($h->title); ?></h4>
                            <?php if (!empty($h->description)): ?>
                                <p class="hq-muted"><?php echo esc_html($h->description); ?></p>
                            <?php else: ?>
                                <p class="hq-muted">Complete it today to earn <strong><?php echo esc_html($xp_val); ?> XP</strong>.</p>
                            <?php endif; ?>
                            <div class="hq-meta">
                                <?php if (!empty($h->category)): ?>
                                    <span class="hq-pill"><?php echo esc_html($h->category); ?></span>
                                <?php endif; ?>
                                <span class="hq-pill hq-pill-xp">⚡ <?php echo esc_html($xp_val); ?> XP</span>
                                <span class="hq-streak">🔥 <?php echo esc_html($streak); ?> day streak</span>
                            </div>
                        </div>

                        <div class="hq-actions">
                            <?php if ($done): ?>
                                <form method="post">
                                    <?php wp_nonce_field('hq_dash_action', 'hq_dash_nonce'); ?>
                                    <input type="hidden" name="hq_action" value="undo">
                                    <input type="hidden" name="habit_id" value="<?php echo esc_attr($habit_id); ?>">
                                    <button class="hq-btn-secondary" type="submit">Completed (Undo)</button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <?php wp_nonce_field('hq_dash_action', 'hq_dash_nonce'); ?>
                                    <input type="hidden" name="hq_action" value="complete">
                                    <input type="hidden" name="habit_id" value="<?php echo esc_attr($habit_id); ?>">
                                    <button class="hq-btn" type="submit">✓ Complete • +<?php echo esc_html($xp_val); ?> XP</button>
                                </form>
                            <?php endif; ?>

                            <!-- NEW: Archive button -->
                            <form method="post" onsubmit="return confirm('Remove this habit from your list? (You can restore it later)');">
                                <?php wp_nonce_field('hq_dash_action', 'hq_dash_nonce'); ?>
                                <input type="hidden" name="hq_action" value="archive">
                                <input type="hidden" name="habit_id" value="<?php echo esc_attr($habit_id); ?>">
                                <button class="hq-btn-archive" type="submit">Remove (Archive)</button>
                            </form>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- NEW: Archived habits section -->
        <details class="hq-card hq-details" <?php echo empty($archived) ? 'style="display:none;"' : ''; ?>>
            <summary>
                <span>🗃️ Archived habits (<?php echo esc_html(count($archived)); ?>)</span>
                <span class="hq-summary-btn">Restore</span>
            </summary>

            <?php if (!empty($archived)): ?>
                <div style="margin-top:14px; display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($archived as $a): ?>
                        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;border:1px solid rgba(2,73,80,.12);border-radius:16px;padding:12px;background:rgba(243,251,253,.7);">
                            <div>
                                <strong style="color:#003135;"><?php echo esc_html($a->title); ?></strong><br>
                                <span class="hq-muted" style="margin:0;">Category: <?php echo esc_html($a->category ?: '—'); ?> • ⚡ <?php echo esc_html((int)$a->xp); ?> XP</span>
                            </div>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('hq_dash_action', 'hq_dash_nonce'); ?>
                                <input type="hidden" name="hq_action" value="restore">
                                <input type="hidden" name="habit_id" value="<?php echo esc_attr((int)$a->id); ?>">
                                <button class="hq-btn-secondary" type="submit">Restore</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </details>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('hq_dashboard', 'hq_dashboard_shortcode');


/* =========================
   Shortcode: [hq_progress]
   (unchanged logic; UI already distinct)
========================= */
function hq_progress_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="hq-wrap"><div class="hq-card">
            <h3 style="margin:0;color:#003135;">Login required</h3>
            <p class="hq-muted">Please log in to see your progress.</p>
            <p><a href="' . esc_url(hq_tracker_url_login()) . '" style="color:#0FA4AF;font-weight:900;text-decoration:none;">Go to Login</a></p>
        </div></div>';
    }

    global $wpdb;
    hq_tracker_maybe_install();

    $habits_table = hq_tracker_table_habits();
    $logs_table   = hq_tracker_table_logs();

    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    $today = current_time('Y-m-d');

    $habit_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $habits_table WHERE user_id=%d AND active=1",
        $user_id
    ));

    $total_completions = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $logs_table WHERE user_id=%d",
        $user_id
    ));

    $total_xp = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(h.xp),0)
         FROM $logs_table l
         INNER JOIN $habits_table h ON h.id = l.habit_id
         WHERE l.user_id=%d",
        $user_id
    ));

    $xp_per_level = 100;
    $level = floor($total_xp / $xp_per_level) + 1;
    $xp_into_level = $total_xp % $xp_per_level;

    $best_streak = hq_tracker_best_streak_all_time($user_id);

    $start7 = date('Y-m-d', strtotime($today . ' -6 days'));
    $rows7 = $wpdb->get_results($wpdb->prepare(
        "SELECT completed_date, COUNT(*) AS c
         FROM $logs_table
         WHERE user_id=%d AND completed_date BETWEEN %s AND %s
         GROUP BY completed_date",
        $user_id, $start7, $today
    ));
    $map7 = array();
    foreach ($rows7 as $r) $map7[$r->completed_date] = (int)$r->c;

    $completions_7 = array_sum($map7);
    $possible_7 = $habit_count * 7;
    $rate_7 = ($possible_7 > 0) ? round(($completions_7 / $possible_7) * 100) : 0;

    $days7 = array();
    for ($i=0; $i<7; $i++) $days7[] = date('Y-m-d', strtotime($start7 . " +$i day"));

    $ts = current_time('timestamp');
    $week_start_ts = strtotime('monday this week', $ts);
    $week_end_ts = strtotime('sunday this week', $ts);
    $week_start = date('Y-m-d', $week_start_ts);
    $week_end = date('Y-m-d', $week_end_ts);

    $rowsW = $wpdb->get_results($wpdb->prepare(
        "SELECT completed_date, COUNT(*) AS c
         FROM $logs_table
         WHERE user_id=%d AND completed_date BETWEEN %s AND %s
         GROUP BY completed_date",
        $user_id, $week_start, $week_end
    ));
    $mapW = array();
    foreach ($rowsW as $r) $mapW[$r->completed_date] = (int)$r->c;

    $weekDays = array();
    for ($i=0; $i<7; $i++) $weekDays[] = date('Y-m-d', strtotime($week_start . " +$i day"));
    $countsW = array_map(function($d) use ($mapW){ return $mapW[$d] ?? 0; }, $weekDays);
    $maxW = max(1, max($countsW));

    $top = $wpdb->get_results($wpdb->prepare(
        "SELECT h.title, h.category, h.xp, COUNT(l.id) AS c
         FROM $habits_table h
         LEFT JOIN $logs_table l
           ON l.habit_id = h.id AND l.user_id = %d
         WHERE h.user_id=%d AND h.active=1
         GROUP BY h.id
         ORDER BY c DESC, h.created_at DESC
         LIMIT 4",
        $user_id, $user_id
    ));

    $pretty_week = date_i18n('j M', strtotime($week_start)) . ' – ' . date_i18n('j M', strtotime($week_end));

    ob_start();
    ?>
    <div class="hq-wrap hq-progress">

        <div class="hq-progress-hero">
            <div>
                <h2>Progress Report</h2>
                <p><?php echo esc_html($user->user_login); ?> • Week: <?php echo esc_html($pretty_week); ?></p>
            </div>
            <div class="hq-progress-badges">
                <span class="hq-progress-badge">🧩 Level <?php echo esc_html($level); ?></span>
                <span class="hq-progress-badge">⚡ <?php echo esc_html($total_xp); ?> XP total</span>
                <span class="hq-progress-badge">🔥 Best streak: <?php echo esc_html($best_streak); ?></span>
            </div>
        </div>

        <div class="hq-progress-grid">
            <div class="hq-progress-stat">
                <p class="label">7‑day completion</p>
                <p class="value"><?php echo esc_html($rate_7); ?>%</p>
                <p class="mini"><?php echo esc_html($completions_7); ?> completions in last 7 days</p>
            </div>

            <div class="hq-progress-stat">
                <p class="label">Total completions</p>
                <p class="value">✅ <?php echo esc_html($total_completions); ?></p>
                <p class="mini">All time</p>
            </div>

            <div class="hq-progress-stat">
                <p class="label">XP this level</p>
                <p class="value"><?php echo esc_html($xp_into_level); ?> / 100</p>
                <p class="mini">Level <?php echo esc_html($level); ?> progress</p>
                <div class="hq-bar" aria-label="Level XP progress">
                    <div class="hq-bar-fill" style="width: <?php echo esc_attr(max(0, min(100, $xp_into_level))); ?>%;"></div>
                </div>
            </div>

            <div class="hq-progress-stat">
                <p class="label">Active habits</p>
                <p class="value">📌 <?php echo esc_html($habit_count); ?></p>
                <p class="mini">More habits = more possible XP</p>
            </div>
        </div>

        <div class="hq-card">
            <div class="hq-section-head" style="margin-top:0;">
                <h3>Last 7 days</h3>
                <span class="hq-chip">Tap‑free snapshot</span>
            </div>

            <div class="hq-dots" aria-label="Last 7 days activity dots">
                <?php
                foreach ($days7 as $d):
                    $count = $map7[$d] ?? 0;
                    $label = date_i18n('D', strtotime($d));
                    $short = substr($label, 0, 1);
                    $on = $count > 0;
                ?>
                    <div class="hq-dot <?php echo $on ? 'on' : ''; ?>" title="<?php echo esc_attr($label . ': ' . $count); ?>">
                        <?php echo esc_html($short); ?>
                        <small><?php echo esc_html($count); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="hq-muted">Each tile shows how many habits you completed on that day.</p>
        </div>

        <div class="hq-card">
            <div class="hq-section-head" style="margin-top:0;">
                <h3>Weekly Activity</h3>
                <span class="hq-chip">Mon → Sun</span>
            </div>

            <div class="hq-weeklist" aria-label="Weekly activity bars">
                <?php
                $labels = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
                for ($i=0; $i<7; $i++):
                    $c = $countsW[$i];
                    $pct = ($c / $maxW) * 100;
                ?>
                    <div class="hq-weekrow">
                        <div class="d"><?php echo esc_html($labels[$i]); ?></div>
                        <div class="hq-weekbar"><div style="width: <?php echo esc_attr($pct); ?>%"></div></div>
                        <div class="c"><?php echo esc_html($c); ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="hq-card">
            <div class="hq-section-head" style="margin-top:0;">
                <h3>Top Habits</h3>
                <span class="hq-chip">Most completed</span>
            </div>

            <?php if (empty($top)): ?>
                <p class="hq-muted">No habits yet—create one in Dashboard to start tracking.</p>
            <?php else: ?>
                <div class="hq-rank">
                    <?php $rank = 1; foreach ($top as $t): ?>
                        <div class="hq-rankitem">
                            <div class="hq-rankleft">
                                <strong><?php echo esc_html($rank . '. ' . $t->title); ?></strong>
                                <div class="meta">
                                    <?php echo esc_html(($t->category ?: '—') . ' • ⚡ ' . (int)$t->xp . ' XP'); ?>
                                </div>
                            </div>
                            <div class="hq-rankbadge">✅ <?php echo esc_html((int)$t->c); ?></div>
                        </div>
                    <?php $rank++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('hq_progress', 'hq_progress_shortcode');


/* =========================
   Shortcode: [hq_awards]
========================= */
function hq_awards_shortcode() {
    if (!is_user_logged_in()) {
        return '<div class="hq-wrap"><div class="hq-card">
            <h3 style="margin:0;color:#003135;">Login required</h3>
            <p class="hq-muted">Please log in to see your awards.</p>
            <p><a href="' . esc_url(hq_tracker_url_login()) . '" style="color:#0FA4AF;font-weight:900;text-decoration:none;">Go to Login</a></p>
        </div></div>';
    }

    global $wpdb;
    hq_tracker_maybe_install();

    $habits_table = hq_tracker_table_habits();
    $logs_table   = hq_tracker_table_logs();
    $user_id = get_current_user_id();

    // total habits created (active + archived) for "First Quest"
    $habit_created_count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $habits_table WHERE user_id=%d",
        $user_id
    ));

    $total_completions = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $logs_table WHERE user_id=%d",
        $user_id
    ));

    $total_xp = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(h.xp),0)
         FROM $logs_table l
         INNER JOIN $habits_table h ON h.id = l.habit_id
         WHERE l.user_id=%d",
        $user_id
    ));

    $xp_per_level = 100;
    $level = floor($total_xp / $xp_per_level) + 1;
    $best_streak = hq_tracker_best_streak_all_time($user_id);

    $ach = array(
        array('icon'=>'⭐','title'=>'First Quest','desc'=>'Create your first habit.','unlocked'=>($habit_created_count>=1),'progress'=>min($habit_created_count,1).'/1 habits created'),
        array('icon'=>'✅','title'=>'First Completion','desc'=>'Complete any habit once.','unlocked'=>($total_completions>=1),'progress'=>min($total_completions,1).'/1 completions'),
        array('icon'=>'🔥','title'=>'On Fire','desc'=>'Reach a 3‑day streak (any habit).','unlocked'=>($best_streak>=3),'progress'=>min($best_streak,3).'/3 best streak'),
        array('icon'=>'🏆','title'=>'Habit Master','desc'=>'Reach a 7‑day streak (any habit).','unlocked'=>($best_streak>=7),'progress'=>min($best_streak,7).'/7 best streak'),
        array('icon'=>'⚡','title'=>'XP Hunter','desc'=>'Earn 100 XP total.','unlocked'=>($total_xp>=100),'progress'=>min($total_xp,100).'/100 XP'),
        array('icon'=>'💪','title'=>'Serious Mode','desc'=>'Complete 10 habits total.','unlocked'=>($total_completions>=10),'progress'=>min($total_completions,10).'/10 completions'),
        array('icon'=>'🧩','title'=>'Level Up','desc'=>'Reach Level 3.','unlocked'=>($level>=3),'progress'=>min($level,3).'/3 level'),
    );

    $unlocked = 0;
    foreach ($ach as $a) if (!empty($a['unlocked'])) $unlocked++;
    $total = count($ach);

    ob_start();
    ?>
    <div class="hq-wrap" style="padding-top: 6px;">
        <div class="hq-card" style="margin-bottom:14px;">
            <div class="hq-section-head" style="margin-top:0;">
                <h3 style="margin:0;">Keep going!</h3>
                <span class="hq-chip">🏅 <?php echo esc_html($unlocked . '/' . $total); ?> unlocked</span>
            </div>
            <p class="hq-muted" style="margin-top:0;">Achievements unlock automatically based on your activity.</p>
        </div>

        <div class="hq-awards-grid">
            <?php foreach ($ach as $a): ?>
                <div class="hq-award <?php echo $a['unlocked'] ? 'unlocked' : 'locked'; ?>">
                    <div class="hq-award-icon"><?php echo esc_html($a['icon']); ?></div>
                    <div style="flex:1;min-width:220px;">
                        <div class="hq-award-top">
                            <div class="hq-award-title"><?php echo esc_html($a['title']); ?></div>
                            <div class="hq-status <?php echo $a['unlocked'] ? 'unlocked' : ''; ?>">
                                <?php echo $a['unlocked'] ? 'UNLOCKED' : 'LOCKED'; ?>
                            </div>
                        </div>
                        <div class="hq-award-desc"><?php echo esc_html($a['desc']); ?></div>
                        <div class="hq-award-progress"><?php echo esc_html($a['progress']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('hq_awards', 'hq_awards_shortcode');