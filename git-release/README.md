# git-release

Operator plugin — shows the latest [GitHub release](https://docs.github.com/en/rest/releases) for a repository on the home page in a styled release card (tag badge, stable/pre label, excerpt, repository link).

Uses **client-mode** guest cache (`guest_page: client`): cached guest HTML contains only a placeholder; browsers fetch `/plugin/git-release/widget.json` for fresh release data without busting the whole site cache.

## Install

Not in the public catalog — install from this directory or a release zip:

```bash
php bin/latch plugin install /path/to/git-release
php bin/latch plugin-audit git-release
php bin/latch plugin enable git-release
```

## Settings

**Admin → Plugins → Git release widget → Settings**

| Key | Default | Purpose |
|-----|---------|---------|
| `github_repo` | `YeOK/Latch` | `owner/repo` for GitHub API |
| `heading` | `Latest release` | Card title |
| `max_age_seconds` | `300` | Browser cache TTL for `widget.json` and server-side GitHub API cache |

## Cache

```json
"cache": {
  "guest_page": "client",
  "invalidate_on": ["plugin"],
  "client": "/plugin/git-release/widget.json"
}
```

Renders above the board list via `home.before_boards` (showcase and default home templates).

GitHub release data is cached under `storage/plugins/git-release/cache/` for the same TTL as `max_age_seconds`, so repeat `widget.json` requests avoid calling `api.github.com` on every home page visit. If GitHub is unreachable, the last cached release is served when available.

Enable/disable busts `tagPlugin:git-release`. Core loads `plugin-clients.js` when any client-mode plugin is enabled.

## Requirements

- Latch **0.4.1+** (manifest cache / PR-P6)
- Outbound HTTPS to `api.github.com`