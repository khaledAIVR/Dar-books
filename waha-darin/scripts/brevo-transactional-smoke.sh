#!/usr/bin/env bash
# One-shot Brevo transactional API check (same shape as local curl to /v3/smtp/email).
# Usage:
#   export BREVO_API_KEY='xkeysib-...'
#   export BREVO_TEST_EMAIL='you@example.com'   # sender + recipient (default below)
#   ./scripts/brevo-transactional-smoke.sh
set -euo pipefail
KEY="${BREVO_API_KEY:-${BREVO_KEY:-}}"
if [[ -z "$KEY" ]]; then
  echo "Set BREVO_API_KEY (or BREVO_KEY) to your Brevo key." >&2
  exit 1
fi

EMAIL="${BREVO_TEST_EMAIL:-mohammad.khaled.moslemany@gmail.com}"
NAME="${BREVO_SENDER_NAME:-Dar Books}"

BODY=$(python3 -c "import json,sys; print(json.dumps({
  'sender': {'email': sys.argv[1], 'name': sys.argv[2]},
  'to': [{'email': sys.argv[1]}],
  'subject': 'Brevo API smoke test',
  'textContent': 'Smoke test from scripts/brevo-transactional-smoke.sh',
}))" "$EMAIL" "$NAME")

curl -sS -w "\nHTTP_CODE:%{http_code}\n" \
  -X POST 'https://api.brevo.com/v3/smtp/email' \
  -H "api-key: ${KEY}" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d "$BODY"
