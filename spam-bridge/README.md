# Spam bridge

External spam checks via **Akismet** and/or **Stop Forum Spam** for posts and new registrations.

## Enable

```bash
php bin/latch plugin-audit spam-bridge
php bin/latch plugin enable spam-bridge
```

Distributed via [Latch-plugins](https://github.com/YeOK/Latch-plugins). Install with `plugin install`, audit, then enable.

## Secrets (`config/local.php`)

Akismet is required when provider is **Akismet** or **Both**. Stop Forum Spam lookups do not need an API key.

```php
'plugins' => [
    'spam_bridge' => [
        'akismet_api_key' => 'your-key-from-akismet.com/account',
    ],
],
```

Admin → Plugins → **Spam bridge** → Settings shows whether the Akismet key is configured (never displays the value).

## Settings (`/admin/plugins/spam-bridge/settings`)

| Setting | Default | Notes |
|---------|---------|-------|
| Provider | Akismet | Akismet, Stop Forum Spam, or Both |
| Strictness | 1 | `0` = log only; `1`–`3` = increasing SFS sensitivity |
| SFS min confidence % | 90 | Used when strictness is `2` |
| Staff bypass | on | Admins/mods skip checks |
| Check registrations | on | Bans spam signups when strictness > 0 |
| Log rejects | on | Writes to `storage/plugins/spam-bridge/plugin.sqlite` → `spam_log` |

## Hooks

- `post.before_save` — Akismet `comment-check` / SFS lookup; rejects before save when strictness > 0
- `user.register` — signup check after account creation; bans user when flagged (strictness > 0)

## Fail-open

If an API is unreachable or Akismet is not configured, that provider is skipped — posts are not blocked solely due to network errors.

## Troubleshooting

**Provider set to Stop Forum Spam but nothing happens**

1. **Staff bypass** (default: on) — admins and moderators skip all external checks. Test with a member account, or turn off **Staff bypass** while validating.
2. **Strictness** — at `1` (default), SFS only blocks blacklist hits (`frequency` 255). Use `2` (confidence threshold) or `3` (any SFS hit) for broader coverage.
3. **Outbound HTTPS** — the server must reach `api.stopforumspam.org` (PHP `curl` or `allow_url_fopen`). Failed lookups fail open; check `spam_log` for `sfs:unavailable` reasons when logging is on.
4. **Settings file** — confirm `storage/plugins/spam-bridge/settings.json` exists and contains `"provider": "stop_forum_spam"` after save. If the file is missing, the admin save failed (usually `storage/plugins/spam-bridge/` owned by root after `sudo php bin/latch plugin enable`). Fix: `sudo chown -R apache:apache storage/plugins/spam-bridge` then save again in admin (or run `plugin enable` as `sudo -u apache`).

## Storage

- `storage/plugins/spam-bridge/plugin.sqlite` — `spam_log` table (migration `001_spam_log.sql`)
- `storage/plugins/spam-bridge/settings.json` — non-secret toggles

Purge on remove: `php bin/latch plugin remove spam-bridge --confirm --purge-storage`