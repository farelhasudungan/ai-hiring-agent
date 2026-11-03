# Dev VPS and CI/CD

This setup targets Ubuntu 24.04, Nginx, PHP 8.4-FPM, PostgreSQL 16, and
GitHub Actions. The deployment branch is `dev`; `main` is not deployed.
Keep all branch conditions in `.github/workflows/ci-cd.yml` consistent if changed.

Every push and pull request runs Composer validation, Pint, migrations and Pest
against a disposable PostgreSQL 16 database, and a Vite build using Node 24.
Successful `dev` pushes package the committed application, runtime PHP
dependencies, and compiled assets. When enabled, the deployment job transfers
that artifact over SSH and activates it on the VPS. Node, Composer, and a GitHub
repository key are not needed on the server.

The existing `phpunit.xml` SQLite defaults remain available locally; the CI job's
environment variables override them. Never point tests at the shared dev database.

## Using only a VPS IP (HTTP)

A domain and HTTPS are optional for the initial dev deployment. You can access
the app at `http://VPS_IP`. The deployment scripts already accept HTTP health
URLs, and SSH deployment still uses encrypted connections.

Replace `203.0.113.10` below with your VPS's public IPv4 address:

| Setting | IP-only HTTP value |
| --- | --- |
| Server `.env`: `APP_URL` | `http://203.0.113.10` |
| Server `.env`: `SESSION_SECURE_COOKIE` | `false` |
| Server `.env`: `SESSION_DOMAIN` | `null` |
| Nginx: `server_name` | `203.0.113.10` |
| GitHub environment variable: `DEV_HEALTH_URL` | `http://203.0.113.10/up` |
| GitHub secret: `DEV_SSH_HOST` | `203.0.113.10` |

Follow the numbered steps below, skip DNS and the optional HTTPS/Certbot step,
and allow port 80 in both UFW and the VPS provider's firewall. Keep
`APP_DEBUG=false`. Use test credentials and sample CVs while using HTTP: browser
traffic, including passwords and uploads, is not encrypted.

## 1. Prepare the VPS

For IP-only HTTP, no DNS setup is needed. For a domain, point its DNS A record
at the VPS and replace `dev.example.com` throughout these examples. Add an AAAA
record only if IPv6 actually works on the server.

From your workstation, copy the templates:

```bash
scp -r deploy/vps ADMIN_USER@VPS_IP:/tmp/ai-hiring-vps
```

On the VPS, as an administrator:

```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y nginx postgresql-16 postgresql-client-16 supervisor \
  php8.4-cli php8.4-fpm php8.4-pgsql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-intl php8.4-bcmath \
  curl ca-certificates unzip ufw
sudo systemctl enable --now nginx postgresql php8.4-fpm supervisor
php8.4 --version
```

Ensure files created by PHP-FPM remain writable by the deployment user and queue
worker through their shared `www-data` group:

```bash
sudo mkdir -p /etc/systemd/system/php8.4-fpm.service.d
sudo nano /etc/systemd/system/php8.4-fpm.service.d/umask.conf
```

Save these contents, then reload systemd and restart FPM:

```ini
[Service]
UMask=0007
```

```bash
sudo systemctl daemon-reload
sudo systemctl restart php8.4-fpm
```

Use PHP 8.4.1 or newer: the current lockfile requires it. PHP 8.3, Ubuntu 24.04's
default, does not satisfy this lockfile. The PHP installation above uses the
third-party repository described in [PHP's Ubuntu instructions](https://www.php.net/downloads.php?os=linux&osvariant=linux-ubuntu&usage=web&version=8.4).

Create a dedicated deployment user with no sudo privileges:

```bash
sudo adduser --disabled-password --gecos '' deploy
sudo usermod -aG www-data deploy
sudo install -d -o deploy -g www-data -m 2750 /var/www/ai-hiring-dev
sudo install -d -o deploy -g www-data -m 2750 \
  /var/www/ai-hiring-dev/releases /var/www/ai-hiring-dev/incoming \
  /var/www/ai-hiring-dev/shared
sudo install -d -o deploy -g www-data -m 2770 \
  /var/www/ai-hiring-dev/shared/storage
```

Allow your actual SSH port before enabling the firewall. For the default port 22:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

Keep PostgreSQL listening locally. Do not expose port 5432 in UFW or the provider
firewall. The web application connects to `127.0.0.1`.

## 2. Create the PostgreSQL database

Run these once on the VPS. `createuser` prompts for the new role's password:

```bash
sudo -u postgres createuser --pwprompt ai_hiring_dev
sudo -u postgres createdb --owner=ai_hiring_dev ai_hiring_dev
```

This creates an application role without superuser or database-creation privileges.
Confirm password authentication over TCP:

```bash
psql -h 127.0.0.1 -U ai_hiring_dev -d ai_hiring_dev -W -c 'select version();'
```

## 3. Configure the server environment

```bash
sudo install -o deploy -g www-data -m 0640 /tmp/ai-hiring-vps/.env.example \
  /var/www/ai-hiring-dev/shared/.env
php8.4 -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
sudo -u deploy nano /var/www/ai-hiring-dev/shared/.env
```

Set `APP_KEY` to the generated key and `DB_PASSWORD` to the role's password.
For IP-only HTTP, override the template's HTTPS settings:

```dotenv
APP_URL=http://203.0.113.10
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
```

Use your actual VPS IP. `SESSION_SECURE_COOKIE=false` allows the browser to send
the session cookie over HTTP; leaving it `true` can prevent login sessions from
persisting. For HTTPS, use your real `https://dev.example.com` URL and keep
`SESSION_SECURE_COOKIE=true`.

Keep `APP_DEBUG=false`, even on this public dev server. Use `APP_ENV=staging`, so
development-only providers are not loaded.
Keep this file and its stable application key on the server across deployments.
The dev mailer writes to logs. Session, cache, and queue tables use PostgreSQL;
the deployment runs the repository's migrations to create them.

## 4. Configure Nginx (HTTPS optional)

```bash
sudo cp /tmp/ai-hiring-vps/nginx.conf /etc/nginx/sites-available/ai-hiring-dev
sudo nano /etc/nginx/sites-available/ai-hiring-dev
```

For IP-only HTTP, replace the template's `server_name` directive with your VPS IP:

```nginx
server_name 203.0.113.10;
```

For a domain, use `server_name dev.example.com;` with your actual domain.
Nginx uses the request's `Host` header to select the matching site; see
[Nginx's request processing guide](https://nginx.org/en/docs/http/request_processing.html).
Keep the template's port-80 listeners, then enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/ai-hiring-dev /etc/nginx/sites-enabled/ai-hiring-dev
sudo nginx -t
sudo systemctl reload nginx
```

For IP-only HTTP, stop here and continue to step 5. The app will be available at
`http://VPS_IP` after the first deployment. If you see the default Nginx welcome
page, check that `server_name` matches the IP used in your browser and reload
Nginx after editing it. Nginx may return 404 until the first release exists.
Each release's real filesystem path is passed to PHP-FPM so releases get separate
OPcache keys.

### Optional: enable HTTPS with a domain

Once DNS resolves to this VPS and ports 80/443 are reachable:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d dev.example.com --redirect
```

Use your actual domain in both Nginx and the Certbot command. If upgrading an
existing HTTP deployment, also set `APP_URL=https://YOUR_DOMAIN` and
`SESSION_SECURE_COOKIE=true` in `shared/.env`, keep `SESSION_DOMAIN=null`, and
change GitHub's `DEV_HEALTH_URL` to `https://YOUR_DOMAIN/up`. Redeploy to refresh
Laravel's cached configuration. Existing browser sessions on the IP address do
not transfer to the new domain; log in again there.

## 5. Set up GitHub's SSH access

On your workstation, generate a dedicated key with an empty passphrase:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/ai-hiring-dev-actions -C github-actions-dev
```

On the VPS, install the public key, never the private key:

```bash
sudo install -d -o deploy -g deploy -m 0700 /home/deploy/.ssh
sudo -u deploy nano /home/deploy/.ssh/authorized_keys
sudo chmod 0600 /home/deploy/.ssh/authorized_keys
```

Paste the contents of `~/.ssh/ai-hiring-dev-actions.pub` as one line prefixed with
`restrict `, for example `restrict ssh-ed25519 AAAA... github-actions-dev`.
This permits deployment commands and file transfers while disabling forwarding
and interactive terminals. Test from your workstation:

```bash
ssh -i ~/.ssh/ai-hiring-dev-actions deploy@VPS_IP 'id'
```

Collect the SSH host key on your workstation:

```bash
ssh-keyscan -H -t ed25519 -p 22 VPS_IP > /tmp/ai-hiring-dev-known-hosts
ssh-keygen -lf /tmp/ai-hiring-dev-known-hosts
```

Compare that fingerprint with this command through your trusted VPS console:

```bash
sudo ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub
```

Only use the collected key after the fingerprints match. Deployment verifies it
with `StrictHostKeyChecking=yes`; it does not automatically trust a network scan.
For a custom SSH port, use the same port for scanning, testing, and GitHub.

## 6. Configure GitHub Actions

Create a GitHub environment named `development` under repository **Settings →
Environments**, and restrict its deployment branches to `dev`.
[Environment availability depends on repository visibility and GitHub plan](https://docs.github.com/en/actions/how-tos/deploy/configure-and-manage-deployments/manage-environments).
If your private repository's plan does not support environments, remove
`environment: development` from the workflow and use repository secrets and
variables instead.

Add these environment secrets:

| Secret | Value |
| --- | --- |
| `DEV_SSH_HOST` | VPS IP or SSH hostname (IPv4 or DNS) |
| `DEV_SSH_PORT` | SSH port; optional, defaults to `22` |
| `DEV_SSH_USER` | `deploy` |
| `DEV_SSH_PRIVATE_KEY` | Entire dedicated private key, including header/footer |
| `DEV_SSH_KNOWN_HOSTS` | Contents of the verified known-hosts file |

Add environment variable `DEV_HEALTH_URL` with the URL that matches your setup:

```dotenv
# IP-only HTTP (replace with your actual VPS IP):
DEV_HEALTH_URL=http://203.0.113.10/up
# Or HTTPS with your actual domain:
# DEV_HEALTH_URL=https://dev.example.com/up
```

Add **repository** Actions variable `DEV_DEPLOY_ENABLED=true` only when the
server is ready. It must be a repository variable because the job's condition
is evaluated before environment variables are loaded. Leave it absent or set to
`false` to run CI without deployment.

Create this variable under **Settings → Secrets and variables → Actions →
Variables → New repository variable**, with name `DEV_DEPLOY_ENABLED` and value
`true` (lowercase, without quotes). A secret with this name, or a variable only
under the `development` environment, does not satisfy the deploy job's condition.
On `dev` pushes, CI's **Explain deployment status** step reports when this gate
is disabled. A failed CI job also prevents deployment. After changing the
workflow, commit and push to `dev` to run the updated version; rerunning an old
run uses its original workflow revision.

Application/database secrets belong only in the VPS `.env`, not the artifact or
GitHub build environment. Only the deploy job receives SSH secrets. Pull requests
run checks without deployment access.

## 7. First deployment and background processes

Commit and push the setup to your chosen branch using your normal review flow.
Merge it into `dev` (create that branch if it does not exist). A successful
push to `dev` deploys automatically when enabled. You can also select
`dev` in **Actions → CI and dev deployment → Run workflow**.

After the first successful deployment, install the worker and scheduler on the VPS:

```bash
sudo cp /tmp/ai-hiring-vps/queue.conf /etc/supervisor/conf.d/ai-hiring-dev.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
sudo install -o root -g root -m 0644 /tmp/ai-hiring-vps/scheduler.cron \
  /etc/cron.d/ai-hiring-dev
curl --fail http://VPS_IP/up
```

Replace `VPS_IP` with your actual IP, or use `https://YOUR_DOMAIN/up` for HTTPS.

The worker uses a 60-second timeout, below the queue's 90-second reservation
timeout. Adjust both together for longer AI jobs. Supervisor restarts workers
after deployments; cron invokes the scheduler once per minute.

If the deployment job or the final `curl` fails, continue with step 8.

## 8. Troubleshoot the first deployment

Work through these checks in order. Each one names the exact message you will
see. After fixing the cause, open the failed run under **Actions** and choose
**Re-run failed jobs**; do not start a new push for every attempt.

Start with the four logs that explain almost every failure:

```bash
sudo tail -n 30 /var/log/nginx/error.log
sudo journalctl -u php8.4-fpm --no-pager -n 30
sudo tail -n 30 /var/www/ai-hiring-dev/shared/storage/logs/laravel.log
sudo tail -n 30 /var/www/ai-hiring-dev/shared/storage/logs/worker.log
```

### 8.1 The job stops before any SSH output

Messages such as these come from `deploy/vps/upload.sh`, which validates every
input before connecting:

```text
upload.sh: line 5: DEPLOY_HOST: Set DEV_SSH_HOST
upload.sh: line 7: DEPLOY_SSH_KEY: Set DEV_SSH_PRIVATE_KEY
upload.sh: line 8: DEPLOY_KNOWN_HOSTS: Set DEV_SSH_KNOWN_HOSTS
upload.sh: line 9: DEPLOY_HEALTH_URL: Set DEV_HEALTH_URL, including /up
```

A missing **or empty** value fails the same way. The deploy job declares
`environment: development`, so these belong under **Settings → Environments →
development**, not at repository level:

| Name | Kind | Where |
| --- | --- | --- |
| `DEV_SSH_HOST` | Secret | environment `development` |
| `DEV_SSH_PORT` | Secret (optional, defaults to `22`) | environment `development` |
| `DEV_SSH_USER` | Secret | environment `development` |
| `DEV_SSH_PRIVATE_KEY` | Secret | environment `development` |
| `DEV_SSH_KNOWN_HOSTS` | Secret | environment `development` |
| `DEV_HEALTH_URL` | Variable | environment `development` |

`DEV_DEPLOY_ENABLED` is the single exception: it is a **repository** variable,
as described in step 6. Re-run the job after saving; the values are read at the
start of each job.

### 8.2 `No ED25519 host key is known for ...`

Deployment pins the host key with `StrictHostKeyChecking=yes`, so the entry must
match the host **and** port the job uses. Scan with the same values you stored in
`DEV_SSH_HOST` and `DEV_SSH_PORT`:

```bash
HOST=VPS_IP
PORT=22
ssh-keyscan -H -t ed25519 -p "$PORT" "$HOST" > /tmp/ai-hiring-dev-known-hosts
ssh-keygen -lf /tmp/ai-hiring-dev-known-hosts
```

Compare those fingerprints with the server's own key, read through a trusted
console:

```bash
sudo ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub
```

Replace `DEV_SSH_KNOWN_HOSTS` with the **entire file**, including the hashed host
prefix and the `ssh-ed25519 ...` key. Do not paste the `SHA256:` fingerprint, add
quotes, or trim blank lines. If the file is empty, the scan never reached the
host. A hostname scanned as `dev.example.com` does not match a job dialing the
same machine by IP address.

### 8.3 Health check times out: `curl: (28)`

```text
curl: (28) Connection timed out after 10002 milliseconds
```

The deploy script fetches `DEV_HEALTH_URL` **from the VPS itself** after
activating the release, so a blocked inbound port fails the deployment. Check in
this order:

1. Host firewall: `sudo ufw status` must show `Nginx Full` (ports 80 and 443).
2. Provider firewall. On Azure, open **VM → Networking → Networking settings →
   Create port rule → Inbound**, protocol TCP, destination port `80`, action
   Allow, source `Any`. Repeat for `443` when using HTTPS. The rule must exist on
   both the network interface and the subnet, and the VM needs a public IP.
3. Listener: `sudo ss -lntp | grep -E ':80|:443'` should show Nginx.
4. From your workstation: `curl -I --max-time 10 http://VPS_IP`.

Once step 4 returns headers instead of a timeout, re-run the deployment.

### 8.4 Health check returns 404

```text
curl: (22) The requested URL returned error: 404
```

A failed **first** deployment rolls back by removing `current`, because there is
no previous release to restore. Nginx therefore keeps returning 404 until a
deployment succeeds. Do not create the symlink by hand: fix the cause below and
re-run.

Reproduce the check manually to see which component answered:

```bash
BASE=/var/www/ai-hiring-dev
NEWEST=$(ls -1dt "$BASE"/releases/*/ | head -1 | sed 's:/$::')
sudo -u deploy ln -sfn "$NEWEST" "$BASE/current"
curl -i --max-time 10 http://VPS_IP/up
```

This temporary symlink mirrors what activation does; the next deployment replaces
it. Compare the response body:

| Body | Meaning | Next step |
| --- | --- | --- |
| `File not found.` | Nginx reached PHP-FPM, which could not open `index.php` | See 8.5 |
| Nginx's own page, footer `nginx/1.24.0 (Ubuntu)` | The request matched a different server block | Set `server_name` to the same IP or host as `DEV_HEALTH_URL`, then `sudo nginx -t` and `sudo systemctl reload nginx` |
| Laravel's `404` page | The app runs, but the route is missing from the cached release | `cd /var/www/ai-hiring-dev/current && sudo -u deploy php8.4 artisan route:list --path=up`; it must list `GET\|HEAD up`, which `bootstrap/app.php` registers through `health: '/up'` |

### 8.5 `Permission denied`, `Primary script unknown`, or "File not found."

`/var/log/nginx/error.log` shows the release is not traversable by the web user:

```text
stat() "/var/www/ai-hiring-dev/current/public/up" failed (13: Permission denied)
realpath() "/var/www/ai-hiring-dev/current/public" failed (13: Permission denied)
FastCGI sent in stderr: "Primary script unknown" while reading response header
```

Nginx and PHP-FPM run as `www-data`, while releases are owned by `deploy`. Access
must therefore flow through the shared group. Confirm it:

```bash
ls -ld /var/www/ai-hiring-dev/releases/*/ | tail -n 3
sudo -u www-data test -r /var/www/ai-hiring-dev/current/public/index.php \
  && echo readable
namei -l /var/www/ai-hiring-dev/current/public/index.php
```

A release root showing `drwx------` (mode `0700`) is the fault: `chgrp` fixes the
group, but the mode still denies the group. Repair the affected release:

```bash
NEWEST=$(ls -1dt /var/www/ai-hiring-dev/releases/*/ | head -1 | sed 's:/$::')
sudo chgrp -R www-data "$NEWEST"
sudo chmod 2750 "$NEWEST"
sudo chmod -R g+rX,o-rwx "$NEWEST"
```

Cause: the packaging step built the artifact inside `mktemp -d`, which is `0700`.
`tar` recorded that mode for the `.` entry and extraction restored it on the
release root, so only `deploy` could enter the directory. The scripts now handle
this: `deploy/vps/package.sh` sets the build directory to `0755`, and
`deploy/vps/deploy.sh` normalizes the extracted release to `2750` with
`g+rX,o-rwx` before restoring group write access to `bootstrap/cache`. CI's
`deploy/vps/tests/deploy_test.py` asserts the root stays `2750`. Deployments made
from an older checkout must apply both changes, or the same failure returns on
the next release.

## Release behavior, backups, and recovery

The server keeps immutable application releases in `releases/`, uploads and logs
in `shared/storage/`, and secrets in `shared/.env`. The `current` symlink selects
the active release. Deployments are serialized in GitHub and locked on the VPS.

Each deployment installs shared symlinks, runs migrations, caches configuration,
routes, and views, atomically switches `current`, checks the configured `/up` URL,
and restarts queue workers. Failures before activation leave the current
application selected. Failures after activation restore the previous application
symlink when one exists. `/up` checks Laravel boot; migrations also verify the
database connection, but this is not a full business-flow smoke test.

**Application rollback does not undo database migrations.** Use additive,
backward-compatible migrations so the previous release can still work. Take a
backup before deploying schema changes to a populated database, for example on
the VPS:

```bash
pg_dump -h 127.0.0.1 -U ai_hiring_dev -d ai_hiring_dev -W -Fc \
  -f "$HOME/ai-hiring-dev-before-deploy.dump"
chmod 0600 "$HOME/ai-hiring-dev-before-deploy.dump"
```

For routine backups, schedule `pg_dump` using a mode-0600 `.pgpass`, retain copies
off the VPS, and test restores into a separate database. Back up shared uploads
and `.env` too. Releases are kept for recovery: monitor disk space and remove old
inactive releases as an administrator after checking the `current` target. Do not
remove the previous release while an old worker is finishing a job.

To roll back code manually, first check that the database remains compatible,
then run as `deploy`, substituting an existing release directory:

```bash
cd /var/www/ai-hiring-dev
flock deploy.lock bash -c 'ln -s "$1" current.manual && mv -Tf current.manual current && /usr/bin/php8.4 current/artisan queue:restart' \
  bash /var/www/ai-hiring-dev/releases/RELEASE_ID
curl --fail http://VPS_IP/up
```

Use your actual IP or the HTTPS domain, matching `DEV_HEALTH_URL`.

For failures during setup or deployment, start from step 8.

The deployment choices follow [Laravel's deployment guidance](https://laravel.com/docs/13.x/deployment).
CI's PostgreSQL service follows [GitHub's service container guidance](https://docs.github.com/en/actions/tutorials/use-containerized-services/create-postgresql-service-containers).
