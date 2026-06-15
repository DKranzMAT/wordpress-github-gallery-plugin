# GH Repo Gallery

A WordPress plugin that displays a filterable, sortable gallery of your GitHub repositories — with grid/list views, three built-in themes, and reliable caching that won't show "no repositories found" when GitHub's API has a hiccup.

## Features

- **Reliable caching** — repos are cached for a configurable duration, with a permanent stale-data fallback so a temporary GitHub API failure never results in an empty gallery.

- **Three themes** — Default (clean light), Constitutional Elegance (navy/gold serif), and Portfolio (white cards with pink borders on a dark background).

- **Grid and list views** — user-selectable via toggle, with search, language filter, and sort controls.

- **Optional header** — title plus "Follow me on GitHub" and "View All" links to your profile.

- **Specific repo selection** — show exactly the repos you want, in the order you want, instead of everything sorted by recency.

## Installation

1. Download or clone this repo into `wp-content/plugins/gh-repo-gallery`.

2. Activate "GH Repo Gallery" from the WordPress Plugins screen.

3. Go to **Settings → GH Repo Gallery** and enter your GitHub username.

## Settings

- **GitHub Username** — required. The account whose public repos will be displayed.

- **Personal Access Token** — optional. Increases the GitHub API rate limit; no scopes needed for public repos.

- **Cache Duration** — how long (in hours) repo data is cached before refreshing.

- **Default Theme / Default View** — fallback values used when the shortcode doesn't specify them.

A **Clear Cache Now** button is available on the settings page to force a refresh.

## Usage

Add the shortcode to any page or post: `[gh_gallery]`

### Attributes

| Attribute | Values | Description |
|---|---|---|
| `theme` | `default`, `constitutional`, `portfolio` | Visual theme. |
| `view` | `grid`, `list` | Layout. |
| `sort` | `updated`, `stars`, `name`, `created` | Sort order (ignored if `repos` is set). |
| `limit` | number | Max repos to show (ignored if `repos` is set). |
| `title` | text | If set, shows a header with this title plus Follow/View All links. |
| `repos` | comma-separated repo names | Show only these repos, in this order. |

### Examples

Show 6 most recently updated repos in the portfolio theme:

`[gh_gallery theme="portfolio" limit="6"]`

Show a curated set with a header:

`[gh_gallery theme="portfolio" title="GitHub Projects" repos="cpd-rink-hud, clean-drupal10-install, xc-training-log, shotmat, marketpulse-mini, Interactive-Product-Filter-Display"]`

## Themes

- **default** — light background, blue accent, sans-serif.

- **constitutional** — dark navy background, gold accent, Playfair Display / EB Garamond serif.

- **portfolio** — dark page background, white cards with pink (#ff3d81) borders and dark green text, Inter sans-serif.
