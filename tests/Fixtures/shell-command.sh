#!/usr/bin/env bash
##
# Simple test fixture script.
# @usage: ./shell-command.sh --option1 --option2=value arg1 arg2
#
# shellcheck disable=SC2162

OPTION1=0
OPTION2=""
ARG1=""
ARG2=""
ENV1="${ENV1:-}"
ENV2="${ENV2:-}"

for arg in "$@"; do
  if [[ "$arg" == "--option1" ]]; then
    OPTION1=1
  elif [[ "$arg" == --option2=* ]]; then
    OPTION2="${arg#*=}"
  elif [[ "$arg" != --* ]]; then
    if [[ -z "$ARG1" ]]; then
      ARG1="$arg"
    elif [[ -z "$ARG2" ]]; then
      ARG2="$arg"
    fi
  fi
done

read -p "What is your name? " NAME
read -p "What is your favorite color? " COLOR

echo
echo "OPTION1: ${OPTION1:-not provided}"
echo "OPTION2: ${OPTION2:-not provided}"
echo "ARG1: ${ARG1:-not provided}"
echo "ARG2: ${ARG2:-not provided}"
echo "ENV1: ${ENV1:-not provided}"
echo "ENV2: ${ENV2:-not provided}"
echo "NAME: ${NAME:-not provided}"
echo "COLOR: ${COLOR:-not provided}"
