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

## Storage

- `storage/plugins/spam-bridge/plugin.sqlite` — `spam_log` table (migration `001_spam_log.sql`)
- `storage/plugins/spam-bridge/settings.json` — non-secret toggles

Purge on remove: `php bin/latch plugin remove spam-bridge --confirm --purge-storage`