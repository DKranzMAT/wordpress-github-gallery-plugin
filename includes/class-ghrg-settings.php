<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GHRG_Settings {

	const OPTION_KEY = 'ghrg_options';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_ghrg_clear_cache', array( $this, 'handle_clear_cache' ) );
	}

	public static function get_options() {
		$defaults = array(
			'github_username' => '',
			'github_token'    => '',
			'cache_duration'  => 1, // hours
			'default_theme'   => 'default',
			'default_view'    => 'grid',
		);
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $saved, $defaults );
	}

	public function add_settings_page() {
		add_options_page(
			'GH Repo Gallery',
			'GH Repo Gallery',
			'manage_options',
			'ghrg-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'ghrg_settings_group', self::OPTION_KEY, array( $this, 'sanitize_options' ) );

		add_settings_section( 'ghrg_main', 'GitHub Source', null, 'ghrg-settings' );

		add_settings_field( 'github_username', 'GitHub Username', array( $this, 'field_github_username' ), 'ghrg-settings', 'ghrg_main' );
		add_settings_field( 'github_token', 'Personal Access Token (optional)', array( $this, 'field_github_token' ), 'ghrg-settings', 'ghrg_main' );
		add_settings_field( 'cache_duration', 'Cache Duration (hours)', array( $this, 'field_cache_duration' ), 'ghrg-settings', 'ghrg_main' );

		add_settings_section( 'ghrg_display', 'Display Defaults', null, 'ghrg-settings' );

		add_settings_field( 'default_theme', 'Default Theme', array( $this, 'field_default_theme' ), 'ghrg-settings', 'ghrg_display' );
		add_settings_field( 'default_view', 'Default View', array( $this, 'field_default_view' ), 'ghrg-settings', 'ghrg_display' );
	}

	public function sanitize_options( $input ) {
		$output = array();
		$output['github_username'] = isset( $input['github_username'] ) ? sanitize_text_field( $input['github_username'] ) : '';
		$output['github_token']    = isset( $input['github_token'] ) ? sanitize_text_field( $input['github_token'] ) : '';

		$duration = isset( $input['cache_duration'] ) ? intval( $input['cache_duration'] ) : 1;
		$output['cache_duration'] = max( 1, $duration );

		$theme = isset( $input['default_theme'] ) ? sanitize_key( $input['default_theme'] ) : 'default';
		$output['default_theme'] = in_array( $theme, array( 'default', 'constitutional', 'portfolio' ), true ) ? $theme : 'default';

		$view = isset( $input['default_view'] ) ? sanitize_key( $input['default_view'] ) : 'grid';
		$output['default_view'] = in_array( $view, array( 'grid', 'list' ), true ) ? $view : 'grid';

		// If the username changed, clear the cache so it doesn't show stale/wrong data.
		$existing = self::get_options();
		if ( $existing['github_username'] !== $output['github_username'] ) {
			delete_transient( 'ghrg_repos_' . md5( $existing['github_username'] ) );
		}

		return $output;
	}

	public function field_github_username() {
		$opts = self::get_options();
		printf(
			'<input type="text" name="%s[github_username]" value="%s" class="regular-text" placeholder="DKranzMAT" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $opts['github_username'] )
		);
	}

	public function field_github_token() {
		$opts = self::get_options();
		printf(
			'<input type="password" name="%s[github_token]" value="%s" class="regular-text" autocomplete="off" />
			<p class="description">Optional. Increases the GitHub API rate limit. Needs no scopes for public repos &mdash; a basic PAT with no permissions is sufficient.</p>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $opts['github_token'] )
		);
	}

	public function field_cache_duration() {
		$opts = self::get_options();
		printf(
			'<input type="number" min="1" max="24" name="%s[cache_duration]" value="%d" class="small-text" /> hours',
			esc_attr( self::OPTION_KEY ),
			intval( $opts['cache_duration'] )
		);
	}

	public function field_default_theme() {
		$opts = self::get_options();
		$themes = array(
			'default'        => 'Default (clean)',
			'constitutional' => 'Constitutional Elegance',
			'portfolio'      => 'Portfolio (high-contrast)',
		);
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_theme]">';
		foreach ( $themes as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $opts['default_theme'], $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function field_default_view() {
		$opts = self::get_options();
		$views = array(
			'grid' => 'Grid',
			'list' => 'List',
		);
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_view]">';
		foreach ( $views as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $opts['default_view'], $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function render_settings_page() {
		$opts = self::get_options();
		?>
		<div class="wrap">
			<h1>GH Repo Gallery</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ghrg_settings_group' );
				do_settings_sections( 'ghrg-settings' );
				submit_button( 'Save Settings' );
				?>
			</form>

			<hr />

			<h2>Cache</h2>
			<p>
				Repos are cached for <?php echo intval( $opts['cache_duration'] ); ?> hour(s).
				If data looks stale, clear it manually below.
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ghrg_clear_cache" />
				<?php wp_nonce_field( 'ghrg_clear_cache_nonce' ); ?>
				<?php submit_button( 'Clear Cache Now', 'secondary' ); ?>
			</form>

			<hr />

			<h2>Usage</h2>
			<p>Add the shortcode to any page or post:</p>
			<code>[gh_gallery]</code>
			<p>Optional attributes: <code>theme</code> (default / constitutional / portfolio), <code>view</code> (grid / list), <code>sort</code> (updated / stars / name / created), <code>limit</code> (number).</p>
			<p>Example: <code>[gh_gallery theme="portfolio" view="list" sort="stars" limit="6"]</code></p>
		</div>
		<?php
	}

	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		check_admin_referer( 'ghrg_clear_cache_nonce' );

		$opts = self::get_options();
		delete_transient( 'ghrg_repos_' . md5( $opts['github_username'] ) );

		wp_safe_redirect( add_query_arg( array(
			'page'        => 'ghrg-settings',
			'cache_cleared' => '1',
		), admin_url( 'options-general.php' ) ) );
		exit;
	}
}