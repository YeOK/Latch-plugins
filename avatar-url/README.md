# Custom avatar URL

Members set an **HTTPS** avatar image URL on **Profile** (when allowed). Empty URL keeps Gravatar / identicon.

## Operator setup

1. Install and enable from **Admin → Plugins → Catalog** (or CLI).
2. **Admin → Plugins → Custom avatar URL → Settings** — add allowed hosts, one per line:

   ```
   images.example.com
   *.githubusercontent.com
   ```

3. Members open **Profile**, set **Custom avatar URL**, save.

## Security

- HTTPS only; private/loopback hosts blocked (`OutboundUrlGuard`).
- Host must match the allowlist (exact or `*.suffix`).
- Raster images preferred (blocks obvious `.svg` / `.html` / `.php` paths).
- Stored in core `users.avatar_url`; GDPR cookie gate still defers third-party images until consent.
- CSP `img-src` extended for allowlisted hosts.

## Hooks

`avatar.resolve`, `profile.form`, `profile.before_save`, `csp.img_src`

Requires Latch **0.4.8+** (`ProfileSaveContext` avatar fields + `UserRepository::updateAvatarUrl`).
