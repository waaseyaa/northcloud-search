# CLAUDE.md — northcloud-search

Search portal for [northcloud.one](https://northcloud.one), built with the Waaseyaa framework.

## What This Is

A Waaseyaa PHP app that subscribes to North Cloud's Redis pub/sub pipeline and indexes all content (articles, recipes, jobs, RFPs) into SQLite FTS5 for full-text search with faceted filtering.

## Quick Reference

```bash
# Local development
docker compose up -d                    # Start web + subscriber + Redis
docker compose up -d --build web        # Rebuild after code changes
WAASEYAA_DEBUG=true docker compose up -d --build web  # With debug errors
docker compose logs -f subscriber       # Watch indexing
vendor/bin/phpunit                       # Run tests

# Production (current: Docker on northcloud.one)
docker build -t jonesrussell/northcloud-search:latest .
docker push jonesrussell/northcloud-search:latest
# Then SSH to prod and docker run (see deploy notes below)
```

## Architecture

```
North Cloud Publisher → Redis pub/sub → SubscribeCommand → FTS5 Indexer → SQLite
                                                                            ↓
                                        User → Caddy → PHP web server → Controllers → Twig
```

**Two containers**: web (PHP built-in server, port 3003) + subscriber (long-running `app:subscribe` command). Both share a SQLite volume.

## Routes

| Route | Controller | Purpose |
|---|---|---|
| `/` | HomeController | Recent content, type counts |
| `/search?q=&type=&topic=` | SearchController | FTS5 search with facets |
| `/content/{id}` | ContentController | Content detail page |
| `/api/suggest?q=` | SuggestController | FTS5 prefix autocomplete |
| `/health` | HealthController | Health check |

## Key Files

| File | Purpose |
|---|---|
| `src/Document/ContentDocument.php` | Maps Redis messages to `SearchIndexableInterface` |
| `src/Command/SubscribeCommand.php` | Redis pub/sub consumer, indexes into FTS5 |
| `src/Provider/AppServiceProvider.php` | DI bindings, routes, CLI commands |
| `templates/_autocomplete.html.twig` | Shared autocomplete JS (included by home + search) |

## Gotchas

- **Predis pub/sub requires `read_write_timeout=0`** — without it, connection times out and crashes with `Cannot use object of type Predis\Response\Error as array`
- **Redis auth**: parse URL with `parse_url()` into array config — Predis URL format doesn't always parse auth reliably
- **`SsrResponse` constructor**: parameter is `statusCode`, not `status`
- **Waaseyaa `database` config**: `null` is valid — falls back to `storage/waaseyaa.sqlite`
- **Routes need `.render()`** for SSR dispatch of string controllers. Without it, the controller still executes but the response isn't sent through the SSR pipeline.
- **`boot()` runs eagerly, singletons are lazy** — use `boot()` to ensure FTS5 schema exists before any controller runs

## Deployment

**Current**: Docker containers on northcloud.one (manual deploy). **Planned**: PHP Deployer via GitHub Actions (#16).

**Production Redis** requires password — pass via `--redis-url=tcp://:PASSWORD@redis:6379`.

**Caddy config**: managed by `northcloud-ansible` role, proxies `northcloud.one` → `localhost:3003`.

## Dependencies

- **Waaseyaa framework** `^0.1` (currently alpha.68+) — requires the #725 fix for `DatabaseInterface` controller injection
- **predis/predis** `^2.0` — Redis pub/sub client (v3 has pub/sub regression)
- **North Cloud pipeline** — content flows via Redis pub/sub channels (`content:*`)
