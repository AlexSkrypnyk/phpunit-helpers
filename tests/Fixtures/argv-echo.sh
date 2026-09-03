#!/usr/bin/env bash
##
# Prints every received argument on its own line, prefixed by its position.
#
# @usage: ./argv-echo.sh --flag -- positional

set -e

position=0
for arg in "$@"; do
  position=$((position + 1))
  echo "ARGV[${position}]=${arg}"
done
