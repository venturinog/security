#!/bin/bash
#
# Reference DNS-01 hook for the HTTPS Certificate module.
#
# Copy this file, for example to my-dns.sh, make it executable and declare it
# in hooks.json. Keep the real secrets in a separate file with mode 0600
# root:root.
#
set -euo pipefail

CONFIG=/etc/letsencrypt/hooks/my-dns.env
if [ -r "$CONFIG" ]; then
    # shellcheck disable=SC1090
    . "$CONFIG"
fi

RECORD="_acme-challenge.${CERTBOT_DOMAIN}."

case "${1:-}" in
    auth)
        # Publish CERTBOT_VALIDATION as a TXT record. Do not delete other
        # values at the same name: example.com and *.example.com share it.
        nsupdate -y "hmac-sha256:${TSIG_NAME}:${TSIG_SECRET}" <<EOF
server ${DNS_SERVER}
update add ${RECORD} 60 TXT "${CERTBOT_VALIDATION}"
send
EOF
        ;;
    cleanup)
        nsupdate -y "hmac-sha256:${TSIG_NAME}:${TSIG_SECRET}" <<EOF
server ${DNS_SERVER}
update delete ${RECORD} TXT "${CERTBOT_VALIDATION}"
send
EOF
        ;;
    *)
        echo "Usage: $0 auth|cleanup" >&2
        exit 2
        ;;
esac
