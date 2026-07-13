# Privacy analytics

Tier-2 Latch plugin — inject **Plausible** or **Matomo** into every page `<head>` without putting analytics in core.

## Setup

1. Install from **Admin → Plugins → Catalog** (or `php bin/latch plugin install …`).
2. Run `php bin/latch plugin-audit privacy-analytics` and enable.
3. Open **Admin → Plugins → privacy-analytics → Settings**:
   - **Plausible:** set **Site domain** (e.g. `latch.network`); adjust **Script host** if self-hosted.
   - **Matomo:** set **Matomo base URL** (HTTPS) and **Site ID**.
4. **Guests only** (default on) skips the snippet for signed-in members.

## Hooks

| Hook | Purpose |
|------|---------|
| `layout.head` | Analytics `<script>` tags (CSP nonces applied) |
| `csp.script_src` | Allow the tracker script host |
| `csp.connect_src` | Allow beacon/API calls to the same host |

## Cache

`guest_page: bake` — snippet is part of cached guest HTML. Changing settings busts `tagPlugin:privacy-analytics`.

## Permissions

No server-side network calls; the browser loads scripts from your analytics host only.