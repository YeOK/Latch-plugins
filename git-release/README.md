# git-release

Operator plugin — shows the latest [GitHub release](https://docs.github.com/en/rest/releases) for a repository on the home page in a styled release card (tag badge, stable/pre label, excerpt, repository link).

Uses **client-mode** guest cache (`guest_page: client`): cached guest HTML contains only a placeholder; browsers fetch `/plugin/git-release/widget.json` for fresh release data without busting the whole site cache.

## Install

Install from this directory, a release zip, or the Latch-plugins catalog:

```bash
php bin/latch plugin install /path/to/git-release
php bin/latch plugin-audit git-release
php bin/latch plugin enable git-release
```

## Settings

**Admin → Plugins → Git release widget → Settings**

| Key | Default | Purpose |
|-----|---------|---------|
| `github_repo` | `YeOK/Latch` | Public GitHub repo as `owner/name` — any public repository works |
| `heading` | `Latest release` | Card title |
| `max_age_seconds` | `300` (5 min) | Server + browser cache TTL for release data |

### Pointing at another GitHub repository

Set **GitHub repository** to any public repo in `owner/name` form, for example `nginx/nginx` or `your-org/your-forum-theme`. The plugin calls `https://api.github.com/repos/{owner}/{name}/releases/latest` — no GitHub token is required for public repos. After changing the repository, use **Purge release cache** on the settings page so the widget does not serve a stale entry for the previous repo.

Private repositories are not supported in v1 (would need a token and manifest network/secret changes).

## Cache

```json
"cache": {
  "guest_page": "client",
  "invalidate_on": ["plugin"],
  "client": "/plugin/git-release/widget.json"
}
```

Renders above the board list via `home.before_boards` (showcase and default home templates).

GitHub release data is cached under `storage/plugins/git-release/cache/` for the TTL selected in settings, so repeat `widget.json` requests avoid calling `api.github.com` on every home page visit. If GitHub is unreachable, the last cached release is served when available.

**Purge release cache** on the settings page clears all files in that directory and forces a fresh GitHub fetch on the next widget load.

Enable/disable busts `tagPlugin:git-release`. Core loads `plugin-clients.js` when any client-mode plugin is enabled.

## Assets

Widget CSS is loaded via `theme.assets` (`/plugin/git-release/widget.css`) so guest page cache includes the stylesheet in `<head>`. Widget HTML hydrates via client-mode JSON. Requires Latch **0.4.6+** (client-mode plugins still run `theme.assets` / `theme.scripts`).

## Requirements

- Latch **0.4.6+** (client-mode asset hooks + `home.before_boards`)
- Outbound HTTPS to `api.github.com`