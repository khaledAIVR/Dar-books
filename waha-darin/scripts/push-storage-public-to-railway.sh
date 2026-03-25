#!/usr/bin/env bash
# Stream storage/app/public to Railway over native SSH (Mac → container, no third-party file host).
# Prerequisites: railway CLI linked to this project; SSH key registered (railway ssh keys add);
# ~/.ssh/config for ssh.railway.com if your gateway host key rotates often.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SERVICE_NAME="${RAILWAY_WEB_SERVICE_NAME:-darin-web}"
IDENTITY="${RAILWAY_SSH_IDENTITY:-$HOME/.ssh/id_ed25519}"

if ! command -v railway >/dev/null 2>&1; then
  echo "Install Railway CLI: npm i -g @railway/cli" >&2
  exit 1
fi

SID="$(
  railway status --json | python3 -c "
import json, sys
name = sys.argv[1]
d = json.load(sys.stdin)
for e in d.get('environments', {}).get('edges', []):
    for si in e['node'].get('serviceInstances', {}).get('edges', []):
        n = si['node']
        if n.get('serviceName') == name:
            print(n['id'])
            sys.exit(0)
sys.exit(1)
" "$SERVICE_NAME"
)"

if [[ -z "$SID" ]]; then
  echo "Could not find service instance id for service name: $SERVICE_NAME" >&2
  echo "Set RAILWAY_WEB_SERVICE_NAME to your web service name from: railway status --json" >&2
  exit 1
fi

TARGET="${SID}@ssh.railway.com"
REMOTE_DIR="/var/www/html/storage/app/public"

echo "Pushing $ROOT/storage/app/public -> $TARGET:$REMOTE_DIR"
echo "(service: $SERVICE_NAME)"

cd "$ROOT/storage/app/public"
export COPYFILE_DISABLE=1
tar czf - . | ssh \
  -i "$IDENTITY" \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile=/dev/null \
  "$TARGET" "mkdir -p $REMOTE_DIR && cd $REMOTE_DIR && tar xzf -"

echo "Fixing ownership for www-data..."
railway ssh --native "chown -R www-data:www-data /var/www/html/storage"

echo "Done."
