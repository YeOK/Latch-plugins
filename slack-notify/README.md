# Slack notify

Posts forum activity to a **Slack** or **Discord** incoming webhook.

## Enable

```bash
php bin/latch plugin-audit slack-notify
php bin/latch plugin enable slack-notify
```

## Webhook URL (`config/local.php`)

```php
'plugins' => [
    'slack_notify' => [
        'webhook_url' => 'https://hooks.slack.com/services/XXX/YYY/ZZZ',
    ],
],
```

Discord example:

```php
'webhook_url' => 'https://discord.com/api/webhooks/ID/TOKEN',
```

Admin → Plugins → **Slack notify** → Settings shows whether the webhook is configured (never displays the URL).

## Settings

| Setting | Default | Notes |
|---------|---------|-------|
| Bot display name | Latch | Webhook username in chat |
| Notify on | New topics, new replies | Optional: new registrations |
| Include post excerpt | on | Short plain-text preview |
| Notify on pending approval | off | Also ping for mod-queue posts |

## Hooks

- `post.after_save` — new topics and replies (not edits)
- `user.register` — optional registration pings

Complements core **Admin → Webhooks** (signed JSON to your own endpoints). This plugin is for quick team chat setup with vendor incoming webhooks.

## Security

- Webhook URL stays in `local.php` only
- Outbound URLs pass core `OutboundUrlGuard` (HTTPS, no private IPs)
- Fail-open: delivery errors do not block forum actions