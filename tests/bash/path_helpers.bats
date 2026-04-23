#!/usr/bin/env bats

setup() {
  source "$BATS_TEST_DIRNAME/../../scripts/path_helpers.sh"
}

# ── macOS ─────────────────────────────────────────────────────────────────────

@test "appends /artisan unchanged on macOS" {
  uname() { echo "Darwin"; }
  export -f uname

  run resolve_artisan_path "/Users/user/.local/share/zoho-mcp"
  [ "$status" -eq 0 ]
  [ "$output" = "/Users/user/.local/share/zoho-mcp/artisan" ]
}

# ── Linux ─────────────────────────────────────────────────────────────────────

@test "appends /artisan unchanged on Linux" {
  uname() { echo "Linux"; }
  export -f uname

  run resolve_artisan_path "/home/user/.local/share/zoho-mcp"
  [ "$status" -eq 0 ]
  [ "$output" = "/home/user/.local/share/zoho-mcp/artisan" ]
}

# ── Windows / Git Bash ────────────────────────────────────────────────────────

@test "converts to windows path via cygpath on MINGW" {
  uname() { echo "MINGW64_NT-10.0-22621"; }
  cygpath() { echo "C:\\Users\\User\\.local\\share\\zoho-mcp\\artisan"; }
  export -f uname cygpath

  run resolve_artisan_path "/c/Users/User/.local/share/zoho-mcp"
  [ "$status" -eq 0 ]
  [ "$output" = "C:\\Users\\User\\.local\\share\\zoho-mcp\\artisan" ]
}

@test "converts to windows path via cygpath on MSYS" {
  uname() { echo "MSYS_NT-10.0-22621"; }
  cygpath() { echo "C:\\Users\\User\\.local\\share\\zoho-mcp\\artisan"; }
  export -f uname cygpath

  run resolve_artisan_path "/c/Users/User/.local/share/zoho-mcp"
  [ "$status" -eq 0 ]
  [ "$output" = "C:\\Users\\User\\.local\\share\\zoho-mcp\\artisan" ]
}

@test "converts to windows path via cygpath on Cygwin" {
  uname() { echo "CYGWIN_NT-10.0"; }
  cygpath() { echo "C:\\Users\\User\\.local\\share\\zoho-mcp\\artisan"; }
  export -f uname cygpath

  run resolve_artisan_path "/c/Users/User/.local/share/zoho-mcp"
  [ "$status" -eq 0 ]
  [ "$output" = "C:\\Users\\User\\.local\\share\\zoho-mcp\\artisan" ]
}

@test "falls back to unix path on MINGW when cygpath is not available" {
  uname() { echo "MINGW64_NT-10.0-22621"; }
  # Unset cygpath so command -v cygpath fails
  unset -f cygpath
  export -f uname

  # Override command so cygpath appears missing
  command() {
    if [[ "$1" == "-v" && "$2" == "cygpath" ]]; then return 1; fi
    builtin command "$@"
  }
  export -f command

  run resolve_artisan_path "/c/Users/User/.local/share/zoho-mcp"
  [ "$status" -eq 0 ]
  [ "$output" = "/c/Users/User/.local/share/zoho-mcp/artisan" ]
}
