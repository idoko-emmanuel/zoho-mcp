#!/usr/bin/env bash

# resolve_artisan_path INSTALL_DIR
#
# Returns the artisan path in the format the current OS needs:
#   - Git Bash / MSYS / Cygwin on Windows: converts to a Windows path via cygpath
#   - Everything else: appends /artisan as-is
resolve_artisan_path() {
  local path="$1/artisan"
  case "$(uname -s)" in
    MINGW*|MSYS*|CYGWIN*)
      if command -v cygpath &>/dev/null; then
        path="$(cygpath -w "$path")"
      fi ;;
  esac
  echo "$path"
}
