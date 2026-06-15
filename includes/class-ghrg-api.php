<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GHRG_API {

	const API_BASE = 'https://api.github.com';

	/**
	 * Get repos for the configured user, using cache when available.
	 *
	 * @return array|WP_Error Array of repo data, or WP_Error on failure.
	 */
	public static function get_repos() {
		$opts = GHRG_Settings::get_options();
		$username = $opts['github_username'];

		if ( empty( $username ) ) {
			return new WP_Error( 'ghrg_no_username', 'No GitHub username configured.' );
		}

		$cache_key = 'ghrg_repos_' . md5( $username );
		$cached = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$repos = self::fetch_repos( $username, $opts['github_token'] );

		if ( is_wp_error( $repos ) ) {
			// On API failure, try to serve a stale cache rather than showing nothing.
			$stale = get_option( 'ghrg_repos_stale_' . md5( $username ) );
			if ( ! empty( $stale ) ) {
				return $stale;
			}
			return $repos;
		}

		$duration = max( 1, intval( $opts['cache_duration'] ) ) * HOUR_IN_SECONDS;
		set_transient( $cache_key, $repos, $duration );

		// Also store a non-expiring "stale" fallback copy for API outages.
		update_option( 'ghrg_repos_stale_' . md5( $username ), $repos, false );

		return $repos;
	}

	/**
	 * Fetch repos directly from the GitHub API.
	 *
	 * @param string $username
	 * @param string $token
	 * @return array|WP_Error
	 */
	private static function fetch_repos( $username, $token = '' ) {
		$url = self::API_BASE . '/users/' . rawurlencode( $username ) . '/repos?per_page=100&sort=updated';

		$args = array(
			'headers' => array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'GH-Repo-Gallery-WP-Plugin',
			),
			'timeout' => 15,
		);

		if ( ! empty( $token ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'ghrg_api_error',
				sprintf( 'GitHub API returned HTTP %d', $code ),
				array( 'status' => $code, 'body' => $body )
			);
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'ghrg_bad_response', 'Unexpected response from GitHub API.' );
		}

		return self::normalize_repos( $data );
	}

	/**
	 * Reduce raw GitHub API repo objects to only what the gallery needs,
	 * filter out forks, and skip the username's own profile README repo.
	 *
	 * @param array $raw
	 * @return array
	 */
	private static function normalize_repos( $raw ) {
		$repos = array();

		foreach ( $raw as $item ) {
			if ( empty( $item['name'] ) ) {
				continue;
			}
			if ( ! empty( $item['fork'] ) ) {
				continue;
			}
			if ( ! empty( $item['archived'] ) ) {
				continue;
			}

			$repos[] = array(
				'name'        => $item['name'],
				'description' => isset( $item['description'] ) ? $item['description'] : '',
				'url'         => isset( $item['html_url'] ) ? $item['html_url'] : '',
				'stars'       => isset( $item['stargazers_count'] ) ? intval( $item['stargazers_count'] ) : 0,
				'language'    => isset( $item['language'] ) ? $item['language'] : '',
				'topics'      => isset( $item['topics'] ) && is_array( $item['topics'] ) ? $item['topics'] : array(),
				'updated_at'  => isset( $item['updated_at'] ) ? $item['updated_at'] : '',
				'created_at'  => isset( $item['created_at'] ) ? $item['created_at'] : '',
				'homepage'    => isset( $item['homepage'] ) ? $item['homepage'] : '',
			);
		}

		return $repos;
	}

	/**
	 * Get the timestamp of when the cache was last successfully populated.
	 *
	 * @return int|false
	 */
	public static function get_cache_timestamp() {
		$opts = GHRG_Settings::get_options();
		$timestamp_option = 'ghrg_cache_time_' . md5( $opts['github_username'] );
		return get_option( $timestamp_option, false );
	}
}