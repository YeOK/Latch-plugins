# Link preview

Rich **onebox** cards and optional **YouTube / Vimeo embeds** for standalone URLs in posts.

Requires **Latch 0.4.4.0+** (`post.format.link`, `csp.frame_src`).

## Behaviour

When a URL is the **only content in a paragraph** (bare `https://…`, `[url]…[/url]`, markdown link, etc.), the plugin replaces the default link with:

- **YouTube / Vimeo** — inline player when *Embed YouTube and Vimeo* is enabled (`embed.js` mounts the iframe client-side); otherwise a thumbnail card
- **Other HTTPS links** — Open Graph card (title, description, site name, proxied thumbnail via `/plugin/link-preview/image/{hash}`)

Inline URLs in prose stay normal links. Up to **N** previews per post (default 3) — configure in **Admin → Plugins → Link preview**.

## Settings

| Setting | Default | Notes |
|---------|---------|-------|
| Enable link previews | on | Master toggle |
| Embed YouTube and Vimeo | on | Adds CSP `frame-src` for nocookie YouTube + Vimeo player |
| Max previews per post | 3 | Additional standalone URLs stay `<a>` links |
| Fetch timeout | 5s | OG HTML fetch |
| Cache TTL | 168h | Metadata in `plugin.sqlite` |

## Install

```bash
bin/latch plugin install /path/to/link-preview-1.0.0.zip
bin/latch plugin audit link-preview
bin/latch plugin enable link-preview
```

Or install from **Admin → Plugins → Catalog** when the catalog release includes this plugin.

## Security

- HTTPS only; private/reserved IPs blocked (SSRF-safe)
- Remote images served same-origin via plugin image route (`img-src 'self'`)
- Video embeds use client-side `embed.js` (no `<iframe>` in PHP) so `plugin-audit` stays clean

## Data

- `storage/plugins/link-preview/plugin.sqlite` — `preview_cache` table
- `storage/plugins/link-preview/thumbs/` — cached OG images