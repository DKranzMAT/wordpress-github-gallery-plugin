<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GHRG_Shortcode {

	public function __construct() {
		add_shortcode( 'gh_gallery', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$opts = GHRG_Settings::get_options();

		$atts = shortcode_atts( array(
			'theme' => $opts['default_theme'],
			'view'  => $opts['default_view'],
			'sort'  => 'updated',
			'limit' => 0,
			'title' => '',
			'repos' => '',
		), $atts, 'gh_gallery' );

		$theme = in_array( $atts['theme'], array( 'default', 'constitutional', 'portfolio' ), true ) ? $atts['theme'] : 'default';
		$view  = in_array( $atts['view'], array( 'grid', 'list' ), true ) ? $atts['view'] : 'grid';
		$sort  = in_array( $atts['sort'], array( 'updated', 'stars', 'name', 'created' ), true ) ? $atts['sort'] : 'updated';
		$limit = intval( $atts['limit'] );

		$repos = GHRG_API::get_repos();

		wp_enqueue_style( 'ghrg-style' );
		wp_enqueue_script( 'ghrg-script' );

		if ( is_wp_error( $repos ) ) {
			return $this->render_error( $repos );
		}

		if ( empty( $repos ) ) {
			return '<div class="ghrg-empty">No repositories found.</div>';
		}

		if ( ! empty( $atts['repos'] ) ) {
			$repos = $this->filter_specific_repos( $repos, $atts['repos'] );
		} else {
			$repos = $this->sort_repos( $repos, $sort );
		}

		if ( $limit > 0 ) {
			$repos = array_slice( $repos, 0, $limit );
		}

		$languages = $this->get_unique_languages( $repos );

		$header_html = $this->render_header( $atts['title'], $opts['github_username'] );

		ob_start();
		?>
		<div class="ghrg-wrap" data-theme="<?php echo esc_attr( $theme ); ?>">

			<?php echo $header_html; ?>

			<div class="ghrg-toolbar">
				<div class="ghrg-toolbar-group">
					<input type="text" class="ghrg-search" placeholder="Search repositories&hellip;" aria-label="Search repositories" />

					<select class="ghrg-filter-language" aria-label="Filter by language">
						<option value="">All languages</option>
						<?php foreach ( $languages as $lang ) : ?>
							<option value="<?php echo esc_attr( $lang ); ?>"><?php echo esc_html( $lang ); ?></option>
						<?php endforeach; ?>
					</select>

					<select class="ghrg-sort" aria-label="Sort repositories">
						<option value="updated" <?php selected( $sort, 'updated' ); ?>>Sort: Recently updated</option>
						<option value="stars" <?php selected( $sort, 'stars' ); ?>>Sort: Most stars</option>
						<option value="name" <?php selected( $sort, 'name' ); ?>>Sort: Name (A&ndash;Z)</option>
						<option value="created" <?php selected( $sort, 'created' ); ?>>Sort: Created date</option>
					</select>
				</div>

				<div class="ghrg-view-toggle" role="group" aria-label="Toggle view">
					<button type="button" class="ghrg-view-btn <?php echo ( 'grid' === $view ) ? 'active' : ''; ?>" data-view="grid">&#9638; Grid</button>
					<button type="button" class="ghrg-view-btn <?php echo ( 'list' === $view ) ? 'active' : ''; ?>" data-view="list">&#9776; List</button>
				</div>
			</div>

			<div class="ghrg-gallery-grid <?php echo ( 'grid' !== $view ) ? 'ghrg-hidden' : ''; ?>">
				<?php foreach ( $repos as $repo ) : ?>
					<?php echo $this->render_card( $repo ); ?>
				<?php endforeach; ?>
			</div>

			<div class="ghrg-gallery-list <?php echo ( 'list' !== $view ) ? 'ghrg-hidden' : ''; ?>">
				<?php foreach ( $repos as $repo ) : ?>
					<?php echo $this->render_row( $repo ); ?>
				<?php endforeach; ?>
			</div>

			<div class="ghrg-empty-filtered ghrg-hidden">No repositories match your filters.</div>

			<div class="ghrg-footer">
				<span class="ghrg-cache-info"><?php echo esc_html( $this->cache_age_label() ); ?></span>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	private function render_header( $title, $username ) {
		if ( empty( $title ) ) {
			return '';
		}
		ob_start();
		?>
		<div class="ghrg-header">
			<h2 class="ghrg-title"><?php echo esc_html( $title ); ?></h2>
			<?php if ( ! empty( $username ) ) : ?>
				<div class="ghrg-header-links">
					<a class="ghrg-follow-btn" href="<?php echo esc_url( 'https://github.com/' . $username ); ?>" target="_blank" rel="noopener noreferrer">Follow me on GitHub</a>
					<a class="ghrg-view-all" href="<?php echo esc_url( 'https://github.com/' . $username ); ?>" target="_blank" rel="noopener noreferrer">View All &rarr;</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function filter_specific_repos( $repos, $repos_attr ) {
		$wanted = array_filter( array_map( 'trim', explode( ',', $repos_attr ) ) );

		$repo_map = array();
		foreach ( $repos as $repo ) {
			$repo_map[ strtolower( $repo['name'] ) ] = $repo;
		}

		$filtered = array();
		foreach ( $wanted as $name ) {
			$key = strtolower( $name );
			if ( isset( $repo_map[ $key ] ) ) {
				$filtered[] = $repo_map[ $key ];
			}
		}

		return $filtered;
	}

	private function render_error( $error ) {
		$message = $error->get_error_message();
		return '<div class="ghrg-error">Unable to load repositories: ' . esc_html( $message ) . '</div>';
	}

	private function sort_repos( $repos, $sort ) {
		switch ( $sort ) {
			case 'stars':
				usort( $repos, function( $a, $b ) {
					return $b['stars'] <=> $a['stars'];
				} );
				break;
			case 'name':
				usort( $repos, function( $a, $b ) {
					return strcasecmp( $a['name'], $b['name'] );
				} );
				break;
			case 'created':
				usort( $repos, function( $a, $b ) {
					return strcmp( $b['created_at'], $a['created_at'] );
				} );
				break;
			case 'updated':
			default:
				usort( $repos, function( $a, $b ) {
					return strcmp( $b['updated_at'], $a['updated_at'] );
				} );
				break;
		}
		return $repos;
	}

	private function get_unique_languages( $repos ) {
		$languages = array();
		foreach ( $repos as $repo ) {
			if ( ! empty( $repo['language'] ) && ! in_array( $repo['language'], $languages, true ) ) {
				$languages[] = $repo['language'];
			}
		}
		sort( $languages );
		return $languages;
	}

	private function render_card( $repo ) {
		ob_start();
		?>
		<div class="ghrg-repo-card"
			data-name="<?php echo esc_attr( strtolower( $repo['name'] ) ); ?>"
			data-description="<?php echo esc_attr( strtolower( $repo['description'] ) ); ?>"
			data-language="<?php echo esc_attr( $repo['language'] ); ?>"
		>
			<div class="ghrg-card-top">
				<a href="<?php echo esc_url( $repo['url'] ); ?>" class="ghrg-repo-name" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $repo['name'] ); ?></a>
				<span class="ghrg-repo-stars">&#9733; <?php echo esc_html( $repo['stars'] ); ?></span>
			</div>

			<?php if ( ! empty( $repo['description'] ) ) : ?>
				<p class="ghrg-repo-desc"><?php echo esc_html( $repo['description'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $repo['topics'] ) ) : ?>
				<div class="ghrg-repo-topics">
					<?php foreach ( array_slice( $repo['topics'], 0, 4 ) as $topic ) : ?>
						<span class="ghrg-topic-tag"><?php echo esc_html( $topic ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="ghrg-repo-meta">
				<?php if ( ! empty( $repo['language'] ) ) : ?>
					<span class="ghrg-lang-badge"><span class="ghrg-lang-dot" style="background-color: <?php echo esc_attr( $this->language_color( $repo['language'] ) ); ?>"></span><?php echo esc_html( $repo['language'] ); ?></span>
				<?php else : ?>
					<span></span>
				<?php endif; ?>
				<span class="ghrg-updated"><?php echo esc_html( $this->relative_time( $repo['updated_at'] ) ); ?></span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_row( $repo ) {
		ob_start();
		?>
		<div class="ghrg-repo-row"
			data-name="<?php echo esc_attr( strtolower( $repo['name'] ) ); ?>"
			data-description="<?php echo esc_attr( strtolower( $repo['description'] ) ); ?>"
			data-language="<?php echo esc_attr( $repo['language'] ); ?>"
		>
			<div class="ghrg-row-name">
				<a href="<?php echo esc_url( $repo['url'] ); ?>" class="ghrg-repo-name" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $repo['name'] ); ?></a>
				<?php if ( ! empty( $repo['description'] ) ) : ?>
					<span class="ghrg-row-desc"><?php echo esc_html( $repo['description'] ); ?></span>
				<?php endif; ?>
			</div>

			<div class="ghrg-repo-topics">
				<?php foreach ( array_slice( $repo['topics'], 0, 3 ) as $topic ) : ?>
					<span class="ghrg-topic-tag"><?php echo esc_html( $topic ); ?></span>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $repo['language'] ) ) : ?>
				<span class="ghrg-lang-badge"><span class="ghrg-lang-dot" style="background-color: <?php echo esc_attr( $this->language_color( $repo['language'] ) ); ?>"></span><?php echo esc_html( $repo['language'] ); ?></span>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

			<div class="ghrg-row-meta">
				<span>&#9733; <?php echo esc_html( $repo['stars'] ); ?></span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function language_color( $language ) {
		$colors = array(
			'TypeScript' => '#3178c6',
			'JavaScript' => '#f1e05a',
			'PHP'        => '#4f5d95',
			'Python'     => '#3572A5',
			'HTML'       => '#e34c26',
			'CSS'        => '#563d7c',
			'Twig'       => '#c1d026',
			'Vue'        => '#41b883',
			'Shell'      => '#89e051',
		);
		return isset( $colors[ $language ] ) ? $colors[ $language ] : '#888888';
	}

	private function relative_time( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}
		$timestamp = strtotime( $datetime );
		if ( ! $timestamp ) {
			return '';
		}
		return 'Updated ' . human_time_diff( $timestamp, current_time( 'timestamp' ) ) . ' ago';
	}

	private function cache_age_label() {
		$opts = GHRG_Settings::get_options();
		$cache_key = 'ghrg_repos_' . md5( $opts['github_username'] );

		$timeout_key = '_transient_timeout_' . $cache_key;
		$timeout = get_option( $timeout_key );

		if ( ! $timeout ) {
			return 'Live data';
		}

		$duration = max( 1, intval( $opts['cache_duration'] ) ) * HOUR_IN_SECONDS;
		$set_at = $timeout - $duration;
		$age = current_time( 'timestamp' ) - $set_at;

		if ( $age < 60 ) {
			return 'Cached just now';
		}

		return 'Cached ' . human_time_diff( $set_at, current_time( 'timestamp' ) ) . ' ago';
	}
}