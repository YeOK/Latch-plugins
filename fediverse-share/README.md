# Fediverse share

Share Latch topics to the Fediverse from the topic header — instance picker, remembered host, Mastodon/Misskey share URLs, copy link, and Web Share on mobile.

**Requires:** Latch **0.4.0+** (`topic.actions` + theme asset hooks)  
**Slug:** `fediverse-share`  
**Version:** 1.0.1

## Install

```bash
php bin/latch plugin install ./fediverse-share-1.0.0.zip
# or directory: php bin/latch plugin install /path/to/Latch-plugins/fediverse-share
php bin/latch plugin-audit fediverse-share
php bin/latch plugin enable fediverse-share
```

Fedora/RPM: `sudo latch plugin install …` then enable.

## Behaviour

- Adds a **Share** control next to watch/mod actions on topic pages.
- Panel shows a preview of the share text, an **instance** field (datalist of suggestions), and action buttons.
- Last instance is stored in **localStorage** (`latch.fediverse.instance`) in the browser.
- Opens `https://{instance}/share?text=…` for Mastodon/Akkoma-style and Misskey-style shares (user leaves the forum; no server-side outbound HTTP).
- Admin settings: default instance, text template (`{title}`, `{url}`, `{site}`), presets, toggle each action.

## Privacy / security

- No network permission — the **member’s browser** navigates to their instance.
- Instance hostnames are validated client- and server-side (hostname shape only).
- Plugin does **not** implement ActivityPub or remote follow.

## Settings

| Key | Default | Purpose |
|-----|---------|---------|
| `enabled` | on | Show control |
| `default_instance` | empty | Pre-fill host |
| `share_template` | `{title}\n{url}` | Share body |
| `preset_instances` | built-in list | Datalist suggestions |
| `show_mastodon` / `show_misskey` / `show_copy_link` / `show_web_share` | on | Actions |

## License

MIT — same as Latch-plugins.
