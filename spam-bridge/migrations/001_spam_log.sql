CREATE TABLE IF NOT EXISTS spam_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    created_at TEXT NOT NULL,
    kind TEXT NOT NULL,
    provider TEXT NOT NULL,
    user_id INTEGER,
    post_id INTEGER,
    reason TEXT NOT NULL,
    payload TEXT
);

CREATE INDEX IF NOT EXISTS idx_spam_log_created_at ON spam_log(created_at);