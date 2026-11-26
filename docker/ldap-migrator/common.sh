#!/bin/bash

# log LEVEL MESSAGE [EVENT] — print a timestamped message to stdout.
# If EVENT is provided, also send a key-value event to syslog.
#
# Events:
#   ldapsearch_passed             — LDAP export succeeded; all LDIF files are nonempty.
#   ldapsearch_failed             — LDAP export failed or LDIF files are missing/empty.
#   no_users_ldif_exists          — No usable LDAP, CSV, or fallback input was found.
#   users_ldif_fresh_selected     — Final LDIF was generated from LDAP or CSV input.
#   users_ldif_fallback_selected  — Existing final LDIF is used without processing.
#   migration_failed              — An unhandled command error triggered the ERR trap.
log() {
  local priority

  echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$1] $2"

  if [ -z "${3:-}" ]; then
    return 0
  fi

  if [ ! -S /dev/log ]; then
    return 0
  fi

  case "$1" in
    ERROR) priority=err ;;
    WARN) priority=warning ;;
    *) priority=info ;;
  esac

  logger --socket-errors=on -p "user.$priority" -t phishingator-ldap-migrator -- \
    "application=phishingator" "component=ldap-migrator" "org=${ORG:-unknown}" \
    "event=$3" "description=\"$2\"" \
    || echo "Failed to send syslog event: $3" >&2
}

# check_ldif_files DIR — succeed if DIR contains LDIF files and all are nonempty.
check_ldif_files() {
  local f

  for f in "$1"/*.ldif; do
    [ -s "$f" ] || return 1
  done
}

# check_json_files DIR — succeed if DIR contains JSON files and all are nonempty and valid.
check_json_files() {
  local f

  for f in "$1"/*.json; do
    [ -s "$f" ] || return 1
    jq -e . "$f" >/dev/null 2>&1 || return 1
  done
}