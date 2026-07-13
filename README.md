# Latch-plugins

Official distributable plugins for [Latch](https://github.com/YeOK/Latch) — install with `php bin/latch plugin install` (local directory or release `.zip`). Each plugin ships with `plugin.json`, `src/Plugin.php`, and a README.

**Requires:** Latch **0.4.0+** (some plugins need newer core — see `min_latch_version` in each `plugin.json`). Always run `plugin-audit` before `plugin enable`.

## Catalog (v1.0.9)

| Plugin | Version | Summary |
|--------|---------|---------|
| [forum-stats](forum-stats/) | 1.1.0 | Home page totals — fragment guest cache |
| [image-upload](image-upload/) | 1.1.0 | Cloudflare R2 presigned PUT + compose **Image** button + post lightbox |
| [word-filter](word-filter/) | 1.0.0 | Block or mask profanity on `post.before_save` |
| [spam-bridge](spam-bridge/) | 1.0.2 | Akismet + Stop Forum Spam; `spam_log` in plugin SQLite |
| [slack-notify](slack-notify/) | 1.0.0 | Slack/Discord incoming webhook on posts and registrations |
| [link-preview](link-preview/) | 1.0.3 | Onebox link cards + lazy YouTube/Vimeo embeds for standalone URLs |
| [privacy-analytics](privacy-analytics/) | 1.0.1 | Plausible or Matomo analytics in the page head |
| [git-release](git-release/) | 1.1.7 | GitHub release card on home — client-mode cache (Latch **0.4.6+**) |

Machine-readable index: [`catalog.json`](catalog.json).

## Install

### From a release zip (recommended)

Download the plugin zip from [GitHub Releases](https://github.com/YeOK/Latch-plugins/releases), then on your Latch server:

```bash
cd /var/www/latch/source
php bin/latch plugin install ./forum-stats-1.1.0.zip
php bin/latch plugin-audit forum-stats
php bin/latch plugin enable forum-stats
```

Enable/disable clears guest page cache automatically (CLI and admin).

### From a git clone

```bash
git clone https://github.com/YeOK/Latch-plugins.git
cd latch/source
php bin/latch plugin install /path/to/Latch-plugins/forum-stats
php bin/latch plugin-audit forum-stats
php bin/latch plugin enable forum-stats
```

### Bundle (all tier-1 plugins)

```bash
php bin/latch plugin install ./latch-plugins-1.0.9.zip
# installs each slug under plugins/ — still disabled until you enable individually
```

## Build release zips (maintainers)

See **[docs/RELEASE.md](docs/RELEASE.md)** for the full checklist.

```bash
./scripts/publish-release.sh v1.0.9   # build, upload, verify (use this for releases)
./scripts/check-release.sh            # audit local + GitHub assets anytime
```

## Security

Every plugin is scanned by `php bin/latch plugin-audit` before enable. Declare `permissions.network` for outbound HTTP and `permissions.filesystem` for extra writable paths. See [Latch docs/PLUGINS.md](https://github.com/YeOK/Latch/blob/main/source/docs/PLUGINS.md) for hooks, guest cache modes, and **plugin assets** (CSS/JS serving patterns).

## License

MIT — see [LICENSE](LICENSE).