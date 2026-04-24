# Deployment Guide

This guide covers deploying CardStack to a production server with Laravel Forge, preserving board and user data across deployments using a symlink-to-shared directory.

## Strategy

User data lives in a persistent `shared/` directory outside of Forge's release dirs. Each deploy symlinks the running release's data paths into `shared/`, so new releases pick up the same data and data is never wiped on deploy.

Persisted via symlink:

- `data/boards/` — board JSON files
- `data/users.json` — user accounts
- `public/uploads/` — uploaded files

## Prerequisites

- Laravel Forge (or similar deployment platform)
- GitHub repository for your CardStack instance

## Forge Setup

1. Create the site in Forge pointing at your CardStack repo (web directory `/public`, PHP 8.1+).
2. Paste the deploy script below into the site's Deploy Script, replacing `boards.yourdomain.com` with your domain.
3. Deploy Now.

No SSH setup required — the script bootstraps the shared directory on first run.

## Deploy Script

```bash
DOMAIN="boards.yourdomain.com"
SHARED="/home/forge/$DOMAIN/shared"

# Bootstrap shared dirs and migrate existing data (idempotent)
mkdir -p $SHARED/data/boards
mkdir -p $SHARED/public/uploads
if [ ! -f $SHARED/data/users.json ] && [ -f /home/forge/$DOMAIN/current/data/users.json ]; then
  cp /home/forge/$DOMAIN/current/data/users.json $SHARED/data/users.json
fi
if [ -d /home/forge/$DOMAIN/current/data/boards ] && [ -z "$(ls -A $SHARED/data/boards 2>/dev/null)" ]; then
  cp -a /home/forge/$DOMAIN/current/data/boards/. $SHARED/data/boards/
fi
if [ -d /home/forge/$DOMAIN/current/public/uploads ] && [ -z "$(ls -A $SHARED/public/uploads 2>/dev/null)" ]; then
  cp -a /home/forge/$DOMAIN/current/public/uploads/. $SHARED/public/uploads/
fi
if [ ! -f $SHARED/data/users.json ]; then
  [ -f /home/forge/$DOMAIN/current/data/_example/users.json ] && \
    cp /home/forge/$DOMAIN/current/data/_example/users.json $SHARED/data/users.json
fi
chmod -R 775 $SHARED/data
chmod -R 775 $SHARED/public/uploads

$CREATE_RELEASE()
cd $FORGE_RELEASE_DIRECTORY

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Symlink release data paths into shared
rm -rf data/boards
ln -s $SHARED/data/boards data/boards

rm -f data/users.json
ln -s $SHARED/data/users.json data/users.json

rm -rf public/uploads
ln -s $SHARED/public/uploads public/uploads

$ACTIVATE_RELEASE()
```

## How It Works

### First Deployment

1. Bootstrap block creates `/home/forge/<domain>/shared/data/boards/` and `public/uploads/`.
2. If `current/` has pre-existing data, it's copied into `shared/` (one-time migration).
3. `users.json` is seeded from `_example/users.json` if nothing exists to migrate.
4. The release activates with `data/boards`, `data/users.json`, and `public/uploads` as symlinks into `shared/`.
5. Visit the site and run the setup wizard to create the admin user.

### Subsequent Deployments

1. Bootstrap block runs but is a no-op (shared is already populated).
2. New release is cloned fresh from git — with whatever stub `data/boards`, `users.json`, and `uploads` are in the repo.
3. Those paths are deleted from the release and replaced with symlinks into `shared/`.
4. After activation, the new release reads and writes user data from the same shared location.

### Verifying

```bash
ls -la /home/forge/<domain>/current/data/boards
```

Should show `data/boards -> /home/forge/<domain>/shared/data/boards`.

## Backups

Data lives only on the server — no git history. For backups, consider a cron job to archive `/home/forge/<domain>/shared/` and upload off-site (e.g. S3):

```bash
0 3 * * * tar -czf /tmp/cardstack-$(date +\%Y\%m\%d).tar.gz /home/forge/<domain>/shared && aws s3 cp /tmp/cardstack-$(date +\%Y\%m\%d).tar.gz s3://your-bucket/ && rm /tmp/cardstack-*.tar.gz
```

## Troubleshooting

### Data disappears after deploy

Check that `data/boards` is actually a symlink:

```bash
ls -la /home/forge/<domain>/current/data/boards
```

If it's a regular directory, the symlink step failed — check the deploy log for errors around `rm -rf data/boards` or `ln -s`.

### Permission errors writing boards

Ensure `shared/` is group-writable by the web user:

```bash
chmod -R 775 /home/forge/<domain>/shared
```
