<?php
/*
Plugin Name: HabitQuest Tips
Description: Quote/Tip box + pre-designed Tips sections for HabitQuest. Shortcodes: [hq_tip_box], [hq_quote_box], [hq_tips_sections] (alias: [hq_tips_rest]), [hq_tips_page]
Version: 1.5
*/

if (!defined('ABSPATH')) exit;

/* =========================
   Quote sources
========================= */

function hq_tips_local_list() {
	return array(
		array('text' => 'Consistency beats intensity. Every time.', 'author' => 'HabitQuest'),
		array('text' => 'Make it repeatable, not impressive.', 'author' => 'HabitQuest'),
		array('text' => 'Start messy. Improve later.', 'author' => 'HabitQuest'),
		array('text' => 'If it’s too hard to repeat, it’s too big.', 'author' => 'HabitQuest'),
		array('text' => 'Design your environment. Don’t rely on mood.', 'author' => 'HabitQuest'),
		array('text' => 'A “bad day” still counts if you don’t disappear.', 'author' => 'HabitQuest'),
		array('text' => 'Lower the goal, not the standard.', 'author' => 'HabitQuest'),
		array('text' => 'Small habits compound quietly.', 'author' => 'HabitQuest'),
		array('text' => 'Don’t break the chain. If you do, rebuild it fast.', 'author' => 'HabitQuest'),
		array('text' => 'One done is better than ten planned.', 'author' => 'HabitQuest'),
	);
}

function hq_tips_fetch_online() {
	$url = 'https://api.quotable.io/random?maxLength=120';

	$res = wp_remote_get($url, array(
		'timeout' => 3,
		'headers' => array('Accept' => 'application/json'),
	));

	if (is_wp_error($res)) return false;

	$code = wp_remote_retrieve_response_code($res);
	if ($code !== 200) return false;

	$body = wp_remote_retrieve_body($res);
	$data = json_decode($body, true);

	if (!is_array($data) || empty($data['content'])) return false;

	return array(
		'text'   => sanitize_text_field($data['content']),
		'author' => sanitize_text_field($data['author'] ?? 'Unknown'),
		'source' => 'web'
	);
}

function hq_tips_pick($prefer_web = true) {
	if ($prefer_web) {
		$q = hq_tips_fetch_online();
		if ($q) return $q;
	}

	$list = hq_tips_local_list();
	$idx = wp_rand(0, count($list) - 1);

	return array(
		'text'   => $list[$idx]['text'],
		'author' => $list[$idx]['author'],
		'source' => 'local'
	);
}

/* =========================
   AJAX: get a new tip
========================= */
function hq_ajax_get_tip() {
	check_ajax_referer('hq_tips_nonce', 'nonce');

	$q = hq_tips_pick(true);

	wp_send_json_success(array(
		'text'   => $q['text'],
		'author' => $q['author'],
		'source' => $q['source'],
	));
}
add_action('wp_ajax_hq_get_tip', 'hq_ajax_get_tip');
add_action('wp_ajax_nopriv_hq_get_tip', 'hq_ajax_get_tip');


/* =========================
   Styles only when shortcode used
========================= */
function hq_tips_print_styles_if_needed() {
	if (!is_singular()) return;
	global $post;
	if (!$post) return;

	$need =
		has_shortcode($post->post_content, 'hq_tip_box') ||
		has_shortcode($post->post_content, 'hq_quote_box') ||
		has_shortcode($post->post_content, 'hq_tips_sections') ||
		has_shortcode($post->post_content, 'hq_tips_rest') ||
		has_shortcode($post->post_content, 'hq_tips_page');

	if (!$need) return;

	echo '<style>
	:root{
		--hq-dark: #003135;
		--hq-teal: #024950;
		--hq-light: #D7EEF8;
		--hq-ice:  #F3FBFD;
		--hq-border: rgba(2,73,80,.14);
		--hq-rust: #964734;
	}

	/* centered like the rest of your pages */
	.hq-tips-wrap{
		max-width: 1050px;
		margin: 0 auto;
		padding: 6px 16px 60px;
	}

	/* section backgrounds */
	.hq-section-card{
		background: var(--hq-light);
		border-radius: 28px;
		padding: 22px;
		border: 1px solid rgba(2,73,80,.10);
		box-shadow: 0 14px 34px rgba(0,0,0,.05);
		margin: 18px 0;
	}
	.hq-section-card-alt{ background:#fff; }

	/* section content centered */
	.hq-section-inner{
		max-width: 920px;
		margin: 0 auto;
		text-align: center;
	}

	/* headings: keep theme sizing, only control spacing + color */
	.hq-kicker{
		margin: 0 0 6px;
		color: var(--hq-rust);
		font-size: 12px;
		letter-spacing: .4px;
		text-transform: uppercase;
	}
	.hq-h2{
		margin: 0;
		color: var(--hq-dark);
	}
	.hq-sub{
		margin: 8px 0 0;
		color: var(--hq-teal);
		opacity: .92;
		font-size: 14px;
	}

	.hq-pill{
		display:inline-flex;
		align-items:center;
		gap:8px;
		background: var(--hq-ice);
		border: 1px solid var(--hq-border);
		color: var(--hq-teal);
		padding: 7px 10px;
		border-radius: 999px;
		font-size: 12px;
		white-space:nowrap;
	}

	/* grids */
	.hq-grid-3{
		display:grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
		gap: 14px;
		margin-top: 14px;
	}
	.hq-grid-2{
		display:grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 14px;
		margin-top: 14px;
	}

	/* inner cards */
	.hq-card{
		background:#fff;
		border: 1px solid var(--hq-border);
		border-radius: 18px;
		padding: 16px;
		box-shadow: 0 10px 26px rgba(0,0,0,.05);
		text-align: left;
	}
	.hq-power{
		display:flex;
		gap:12px;
		align-items:flex-start;
	}
	.hq-icon{
		width:44px;height:44px;
		border-radius: 14px;
		display:flex;
		align-items:center;
		justify-content:center;
		background: var(--hq-ice);
		border: 1px solid var(--hq-border);
		font-size: 20px;
		flex:0 0 auto;
	}

	/* keep normal-looking heading weights (don’t force heavy bold) */
	.hq-card h3{
		margin:0 0 6px;
		color: var(--hq-dark);
		font-size: 16px;
	}
	.hq-card p{
		margin:0;
		color: var(--hq-teal);
		opacity:.92;
		font-size: 13px;
		line-height: 1.35;
	}

	/* accordion */
	.hq-accordion{ text-align:left; }
	.hq-accordion details{
		background:#fff;
		border: 1px solid var(--hq-border);
		border-radius: 18px;
		padding: 14px 16px;
		box-shadow: 0 10px 26px rgba(0,0,0,.05);
		margin-top: 12px;
	}
	.hq-accordion details[open]{
		border-color: rgba(15,164,175,.35);
		box-shadow: 0 12px 28px rgba(15,164,175,.10);
	}
	.hq-accordion summary{
		cursor:pointer;
		list-style:none;
		display:flex;
		align-items:center;
		justify-content:space-between;
		gap:10px;
		color: var(--hq-dark);
	}
	.hq-accordion summary::-webkit-details-marker{ display:none; }
	.hq-acc-pill{
		display:inline-flex;
		align-items:center;
		gap:6px;
		background: var(--hq-ice);
		border: 1px solid var(--hq-border);
		color: var(--hq-teal);
		padding: 6px 10px;
		border-radius: 999px;
		font-size: 12px;
		white-space:nowrap;
	}
	.hq-acc-body{
		margin-top: 10px;
		color: var(--hq-teal);
		opacity: .95;
	}
	.hq-acc-body ul{ margin: 10px 0 0 18px; }
	.hq-acc-body li{ margin: 6px 0; }

	/* formulas */
	.hq-code{
		font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
		color: var(--hq-dark);
		background: var(--hq-ice);
		border: 1px solid var(--hq-border);
		border-radius: 14px;
		padding: 10px 12px;
		margin: 0 0 10px;
		display:block;
	}

	/* quote box */
	.hq-tip-card{
		background: #fff;
		border: 1px solid var(--hq-border);
		border-radius: 18px;
		padding: 16px;
		box-shadow: 0 10px 26px rgba(0,0,0,.05);
		margin: 0 0 14px;
	}
	.hq-tip-top{
		display:flex;
		justify-content:space-between;
		align-items:center;
		gap:12px;
		flex-wrap:wrap;
		margin-bottom: 10px;
	}
	.hq-tip-title{ color: var(--hq-dark); }
	.hq-tip-pill{
		background: var(--hq-ice);
		border: 1px solid var(--hq-border);
		padding: 6px 10px;
		border-radius: 999px;
		font-size: 12px;
		color: var(--hq-teal);
		white-space:nowrap;
	}
	.hq-tip-text{
		color: var(--hq-dark);
		font-size: 18px;
		line-height: 1.35;
		margin: 6px 0 10px;
	}
	.hq-tip-author{
		color: var(--hq-teal);
		opacity:.92;
		font-size: 13px;
		margin: 0 0 12px;
	}
	.hq-tip-actions{
		display:flex;
		gap:10px;
		flex-wrap:wrap;
		align-items:center;
	}
	.hq-tip-btn{
		background: var(--hq-dark);
		color: #fff;
		border: 0;
		padding: 10px 14px;
		border-radius: 14px;
		cursor:pointer;
	}
	.hq-tip-btn:hover{ background: var(--hq-teal); }
	.hq-tip-btn:disabled{ opacity:.7; cursor: wait; }
	.hq-tip-hint{
		font-size: 12px;
		color: var(--hq-teal);
		opacity:.85;
	}

	@media (max-width: 900px){
		.hq-grid-3{ grid-template-columns: 1fr; }
		.hq-grid-2{ grid-template-columns: 1fr; }
	}
	</style>';
}
add_action('wp_head', 'hq_tips_print_styles_if_needed');


/* =========================
   Shortcode: [hq_tip_box] (Alias: [hq_quote_box])
========================= */
function hq_tip_box_shortcode() {
	$q = hq_tips_pick(true);
	$nonce = wp_create_nonce('hq_tips_nonce');
	$ajax_url = admin_url('admin-ajax.php');

	$source_label = ($q['source'] === 'web') ? 'From web' : 'Local backup';
	$uid = 'hq_tip_' . wp_rand(1000, 9999);

	ob_start();
	?>
	<div class="hq-tip-card" id="<?php echo esc_attr($uid); ?>">
		<div class="hq-tip-top">
			<div class="hq-tip-title">Quote Drop</div>
			<div class="hq-tip-pill" id="<?php echo esc_attr($uid); ?>_source"><?php echo esc_html($source_label); ?></div>
		</div>

		<div class="hq-tip-text" id="<?php echo esc_attr($uid); ?>_text">“<?php echo esc_html($q['text']); ?>”</div>
		<div class="hq-tip-author" id="<?php echo esc_attr($uid); ?>_author">— <?php echo esc_html($q['author']); ?></div>

		<div class="hq-tip-actions">
			<button class="hq-tip-btn" type="button" id="<?php echo esc_attr($uid); ?>_btn">New quote</button>
			<div class="hq-tip-hint">Click for a fresh one anytime.</div>
		</div>
	</div>

	<script>
	(function(){
		const btn = document.getElementById('<?php echo esc_js($uid); ?>_btn');
		const textEl = document.getElementById('<?php echo esc_js($uid); ?>_text');
		const authorEl = document.getElementById('<?php echo esc_js($uid); ?>_author');
		const sourceEl = document.getElementById('<?php echo esc_js($uid); ?>_source');

		if(!btn || !textEl || !authorEl || !sourceEl) return;

		btn.addEventListener('click', async function(){
			btn.disabled = true;
			const old = btn.textContent;
			btn.textContent = 'Loading...';

			try{
				const body = new URLSearchParams();
				body.append('action', 'hq_get_tip');
				body.append('nonce', '<?php echo esc_js($nonce); ?>');

				const res = await fetch('<?php echo esc_url($ajax_url); ?>', {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
					body: body.toString()
				});

				const json = await res.json();
				if(json && json.success && json.data){
					textEl.textContent = '“' + json.data.text + '”';
					authorEl.textContent = '— ' + (json.data.author || 'Unknown');
					sourceEl.textContent = (json.data.source === 'web') ? 'From web' : 'Local backup';
				} else {
					btn.textContent = 'Try again';
				}
			} catch(e){
				btn.textContent = 'Try again';
			}

			setTimeout(() => {
				btn.disabled = false;
				btn.textContent = old;
			}, 450);
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode('hq_tip_box', 'hq_tip_box_shortcode');
add_shortcode('hq_quote_box', 'hq_tip_box_shortcode');


/* =========================
   Shortcode: [hq_tips_sections]
========================= */
function hq_tips_sections_shortcode() {
	ob_start();
	?>
	<div class="hq-tips-wrap">

		<!-- POWER-UPS (light blue) -->
		<section class="hq-section-card">
			<div class="hq-section-inner">
				<p class="hq-kicker">Power‑Ups</p>
				<h2 class="hq-h2">Quick boosts that actually help</h2>
				<p class="hq-sub">Pick one power‑up and run it for 3 days.</p>
				<div style="margin-top:10px;"><span class="hq-pill">🧪 deck</span></div>

				<div class="hq-grid-3">
					<div class="hq-card">
						<div class="hq-power">
							<div class="hq-icon">⏱️</div>
							<div>
								<h3>Timer Potion</h3>
								<p>Set 8 minutes. Start. Stop when it ends. Still counts.</p>
							</div>
						</div>
					</div>

					<div class="hq-card">
						<div class="hq-power">
							<div class="hq-icon">🧷</div>
							<div>
								<h3>Habit Anchor</h3>
								<p>Attach the habit to something you already do daily.</p>
							</div>
						</div>
					</div>

					<div class="hq-card">
						<div class="hq-power">
							<div class="hq-icon">🧼</div>
							<div>
								<h3>Make it visible</h3>
								<p>Put the item where you’ll see it. Visibility = reminder.</p>
							</div>
						</div>
					</div>

					<div class="hq-card">
						<div class="hq-power">
							<div class="hq-icon">🎧</div>
							<div>
								<h3>Playlist Trigger</h3>
								<p>One playlist means the habit starts. Same cue every time.</p>
							</div>
						</div>
					</div>

					<div class="hq-card">
						<div class="hq-power">
							<div class="hq-icon">🧱</div>
							<div>
								<h3>Friction Flip</h3>
								<p>Make good habits easy, bad habits annoying (add steps).</p>
							</div>
						</div>
					</div>

					<div class="hq-card">
						<div class="hq-power">
							<div class="hq-icon">🤝</div>
							<div>
								<h3>Buddy Check</h3>
								<p>One friend. One message. “Did you do it?” Keep it simple.</p>
							</div>
						</div>
					</div>
				</div>

			</div>
		</section>

		<!-- RECOVERY GUIDE (white alternating) -->
		<section class="hq-section-card hq-section-card-alt">
			<div class="hq-section-inner">
				<p class="hq-kicker">Recovery Guide</p>
				<h2 class="hq-h2">When you mess up (no drama)</h2>
				<p class="hq-sub">Use a protocol. Don’t overthink it.</p>
				<div style="margin-top:10px;"><span class="hq-pill">🛠️ reset</span></div>

				<div class="hq-accordion">
					<details>
						<summary>
							<span>😵 I missed a day</span>
							<span class="hq-acc-pill">quick reset</span>
						</summary>
						<div class="hq-acc-body">
							<ul>
								<li>Don’t restart the whole plan. Continue tomorrow.</li>
								<li>Do the smallest version once to reconnect.</li>
							</ul>
						</div>
					</details>

					<details>
						<summary>
							<span>📚 Exams / deadlines / too busy</span>
							<span class="hq-acc-pill">survival</span>
						</summary>
						<div class="hq-acc-body">
							<ul>
								<li>Use your easiest mode for a week.</li>
								<li>Protect one habit only. That’s your anchor.</li>
							</ul>
						</div>
					</details>

					<details>
						<summary>
							<span>🔁 I keep quitting the same habit</span>
							<span class="hq-acc-pill">rewrite</span>
						</summary>
						<div class="hq-acc-body">
							<ul>
								<li>The habit is probably too big or too vague.</li>
								<li>Rewrite it: <strong>When X happens, I do Y for Z minutes.</strong></li>
							</ul>
						</div>
					</details>
				</div>

			</div>
		</section>

		<!-- FORMULAS (light blue) -->
		<section class="hq-section-card">
			<div class="hq-section-inner">
				<p class="hq-kicker">Guidebook Templates</p>
				<h2 class="hq-h2">Copy‑paste habit formulas</h2>
				<p class="hq-sub">Make habits clear, repeatable, and easy to start.</p>
				<div style="margin-top:10px;"><span class="hq-pill">📜 templates</span></div>

				<div class="hq-grid-2">
					<div class="hq-card">
						<span class="hq-code">After I ___, I will ___ for ___.</span>
						<p>Example: After lunch, I walk for 10 minutes.</p>
					</div>

					<div class="hq-card">
						<span class="hq-code">Before I ___, I will ___.</span>
						<p>Example: Before sleep, I read 5 pages.</p>
					</div>

					<div class="hq-card">
						<span class="hq-code">If I feel ___, I will ___.</span>
						<p>Example: If I feel stressed, I breathe for 60 seconds.</p>
					</div>

					<div class="hq-card">
						<span class="hq-code">On ___ days, I will ___.</span>
						<p>Example: On Mon/Wed/Fri, I do a short workout.</p>
					</div>
				</div>

			</div>
		</section>

		<!-- REWARDS (white alternating) -->
		<section class="hq-section-card hq-section-card-alt">
			<div class="hq-section-inner">
				<p class="hq-kicker">Reward Menu</p>
				<h2 class="hq-h2">Rewards that don’t feel cringe</h2>
				<p class="hq-sub">Keep it small. Make it consistent.</p>
				<div style="margin-top:10px;"><span class="hq-pill">🎁 reward</span></div>

				<div class="hq-grid-2">
					<div class="hq-card">
						<h3>Instant rewards</h3>
						<p style="margin-bottom:10px;">Quick wins right after you complete a habit.</p>
						<ul style="margin:0 0 0 18px; color: var(--hq-teal);">
							<li>1 episode (timer on)</li>
							<li>music break</li>
							<li>one snack</li>
							<li>10 minutes scrolling (then stop)</li>
						</ul>
					</div>

					<div class="hq-card">
						<h3>Weekly rewards</h3>
						<p style="margin-bottom:10px;">Something fun if you stay consistent.</p>
						<ul style="margin:0 0 0 18px; color: var(--hq-teal);">
							<li>café drink / treat</li>
							<li>movie night</li>
							<li>new playlist</li>
							<li>buy a small thing under ₹___</li>
						</ul>
					</div>
				</div>

			</div>
		</section>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('hq_tips_sections', 'hq_tips_sections_shortcode');
add_shortcode('hq_tips_rest', 'hq_tips_sections_shortcode');


/* =========================
   Shortcode: [hq_tips_page]
========================= */
function hq_tips_page_shortcode() {
	ob_start();
	?>
	<div class="hq-tips-wrap">
		<?php echo hq_tip_box_shortcode(); ?>
		<?php echo hq_tips_sections_shortcode(); ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode('hq_tips_page', 'hq_tips_page_shortcode');