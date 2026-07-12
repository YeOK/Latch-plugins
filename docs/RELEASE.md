# Latch-plugins release checklist

Admin **Catalog** install downloads `{slug}-{version}.zip` from the GitHub Release named in `catalog.json` → `"release"`. Every per-plugin zip **must** be attached to that release — uploading only the bundle or a subset breaks catalog install.

## Version surfaces (must match)

| Surface | Example |
|---------|---------|
| Catalog tag | `catalog.json` → `"release": "v1.0.3"` |
| Bundle zip | `latch-plugins-1.0.3.zip` |
| Per-plugin zip | `{slug}-{version}.zip` from `catalog.json` + `plugin.json` |
| GitHub Release | Tag `v1.0.3` with **all** zips listed above |

## Release steps

1. **Bump versions** — update each changed `plugin.json` and matching `catalog.json` plugin `version` + `"release"` tag.
2. **Commit** on `main` (catalog.json must match the tag you will publish).
3. **Create GitHub release** (if new tag):
   ```bash
   gh release create v1.0.4 --repo YeOK/Latch-plugins \
     --title "Latch-plugins v1.0.4" --notes "..."
   ```
4. **Build, upload, verify** (one command):
   ```bash
   ./scripts/publish-release.sh v1.0.4
   ```
   This runs `build-zips.sh`, uploads every zip with `--clobber`, then `check-release.sh` (local + GitHub).
5. **Smoke test** on a Latch server — Admin → Plugins → Catalog → install one plugin (e.g. slack-notify).

## Manual / diagnostic commands

```bash
./scripts/build-zips.sh v1.0.3          # build only
./scripts/check-release.sh --local      # verify releases/ dir
./scripts/check-release.sh --github     # verify GitHub assets
./scripts/check-release.sh v1.0.3     # both checks for a tag
```

## Common mistakes

| Mistake | Symptom |
|---------|---------|
| Bump `catalog.json` release but upload only link-preview zip | `Plugin release zip not found: slack-notify-1.0.0.zip` |
| Catalog `version` ≠ `plugin.json` | Wrong zip name built; GitHub 404 |
| Forgot bundle zip | CLI `plugin install latch-plugins-X.zip` fails |
| Tag exists before `catalog.json` bump on `main` | Production fetches old catalog URL pointing at new tag |

## Adding a new plugin

1. Add plugin directory with `plugin.json`.
2. Add entry to `catalog.json` `plugins` array.
3. `build-zips.sh` reads slugs from `catalog.json` automatically — no hardcoded list to edit.
4. Full publish + verify: `./scripts/publish-release.sh vX.Y.Z`