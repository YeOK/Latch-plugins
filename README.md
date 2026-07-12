# Latch-plugins

Official distributable plugins for [Latch](https://github.com/YeOK/Latch) — install with `php bin/latch plugin install` (local directory or release `.zip`). Each plugin ships with `plugin.json`, `src/Plugin.php`, and a README.

**Requires:** Latch **0.4.0+** (some plugins need **0.4.0** for settings DB, plugin SQLite, or image-upload secrets split). Always run `plugin-audit` before `plugin enable`.

## Catalog (v1.0.3)

| Plugin | Version | Summary |
|--------|---------|---------|
| [forum-stats](forum-stats/) | 1.0.0 | Home page totals — posts, topics, members |
| [image-upload](image-upload/) | 1.1.0 | Cloudflare R2 presigned PUT + compose **Image** button + post lightbox |
| [word-filter](word-filter/) | 1.0.0 | Block or mask profanity on `post.before_save` |
| [spam-bridge](spam-bridge/) | 1.0.2 | Akismet + Stop Forum Spam; `spam_log` in plugin SQLite |
| [slack-notify](slack-notify/) | 1.0.0 | Slack/Discord incoming webhook on posts and registrations |
| [link-preview](link-preview/) | 1.0.1 | Onebox link cards + lazy YouTube/Vimeo embeds for standalone URLs |

Machine-readable index: [`catalog.json`](catalog.json).

## Install

### From a release zip (recommended)

Download the plugin zip from [GitHub Releases](https://github.com/YeOK/Latch-plugins/releases), then on your Latch server:

```bash
cd /var/www/latch/source
php bin/latch plugin install ./forum-stats-1.0.0.zip
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
php bin/latch plugin install ./latch-plugins-1.0.3.zip
# installs each slug under plugins/ — still disabled until you enable individually
```

## Build release zips (maintainers)

```bash
./scripts/build-zips.sh v1.0.3
./scripts/publish-release.sh v1.0.3   # build + upload all zips to GitHub
```

Writes per-plugin zips (`{slug}-{version}.zip`) and a bundle to `releases/`. **Every** per-plugin zip must be attached to the GitHub Release — admin catalog install downloads `{slug}-{version}.zip` from that tag. The `release` field in `catalog.json` must match the tag; each plugin `version` must match its zip filename.

## Not in this catalog

- **md-import** — operator-only Markdown importer; ships with private/operator Latch trees, not public catalog.
- **docs/plugins/** fixtures (`example`, `badexample`, `warnexample`, `dbexample`) — live in the core repo for audit and framework docs.

## Security

Every plugin is scanned by `php bin/latch plugin-audit <slug>` before enable. Critical findings block enable unless `--force` (logged to `audit_log`). See `docs/PLUGINS.md` in the Latch core repo.

## License

MIT — same as Latch core. Individual plugins may note additional data files (e.g. word-filter blocked-word list).