#!/bin/bash

set -e

source "$(dirname "${BASH_SOURCE[0]}")/common.sh"

# Log unhandled migration errors.
trap 'log ERROR "LDIF migration failed." migration_failed' ERR

DATA_SOURCE=""
mkdir -p ldap/data

if [ -x ./run-ldapsearch.sh ]; then
  log INFO "Running ldapsearch..."

  if ./run-ldapsearch.sh && check_ldif_files "ldap/data"; then
    log INFO "ldapsearch completed successfully." ldapsearch_passed
    DATA_SOURCE=ldap
  else
    log WARN "ldapsearch failed." ldapsearch_failed
  fi
else
  log INFO "No run-ldapsearch.sh script found, skipping LDAP export."
fi

if [ "$DATA_SOURCE" = ldap ]; then
  log INFO "Using freshly exported LDAP data as input (ldap/data/*.ldif)."
elif [ -s ldap/data/users.csv ]; then
  log INFO "Using CSV file as input (ldap/data/users.csv)."
  DATA_SOURCE="csv"
elif [ -s ldap/users.ldif ]; then
  log WARN "Using fallback LDAP data as input from previous build (ldap/users.ldif)."
  DATA_SOURCE="fallback"
else
  log ERROR "No LDAP/CSV input data found." no_users_ldif_exists
  exit 1
fi

if [ "$DATA_SOURCE" = "ldap" ] || [ "$DATA_SOURCE" = "csv" ]; then
  log INFO "Processing input data and generating final LDIF file..."

  # TODO: Add your own data processing here to generate the final LDIF from input LDIF or CSV

  log INFO "LDAP/CSV data processed and final LDIF file generated (ldap/users.ldif)." users_ldif_fresh_selected
else
  log WARN "Skipping processing – using existing final LDIF (ldap/users.ldif)." users_ldif_fallback_selected
fi