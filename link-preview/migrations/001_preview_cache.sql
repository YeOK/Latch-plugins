CREATE TABLE IF NOT EXISTS preview_cache (
    url_hash TEXT PRIMARY KEY,
    url TEXT NOT NULL,
    kind TEXT NOT NULL DEFAULT 'generic',
    title TEXT,
    description TEXT,
    image_url TEXT,
    site_name TEXT,
    extra_json TEXT,
    fetched_at TEXT NOT NULL,
    expires_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_preview_cache_expires ON preview_cache (expires_at);