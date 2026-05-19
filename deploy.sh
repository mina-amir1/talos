#!/usr/bin/env bash
# ══════════════════════════════════════════════════════════════════════════════
#  Talos CMS · Smart Deployment Script
#  Supports: Ubuntu 20+, Debian 11+, CentOS/RHEL/AlmaLinux/Rocky 8+
#  Run as root (or sudo) on the target server.
# ══════════════════════════════════════════════════════════════════════════════
set -euo pipefail
IFS=$'\n\t'

# ── Repository ────────────────────────────────────────────────────────────────
TALOS_REPO="https://github.com/mina-amir1/talos.git"
TALOS_BRANCH="main"

# Detect whether we're being piped from curl (no real script file on disk)
if [[ -f "${BASH_SOURCE[0]:-}" ]]; then
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    RUNNING_FROM_PIPE=false
else
    SCRIPT_DIR=""
    RUNNING_FROM_PIPE=true
fi

STATE_DIR="/var/lib/talos-deploy"
LOG_FILE="/tmp/talos-deploy-$(date +%Y%m%d-%H%M%S).log"

# ── ANSI colors ──────────────────────────────────────────────────────────────
RED='\033[0;31m';    GREEN='\033[0;32m';  YELLOW='\033[1;33m'
BLUE='\033[0;34m';   CYAN='\033[0;36m';   MAGENTA='\033[0;35m'
BOLD='\033[1m';      DIM='\033[2m';       RESET='\033[0m'

# ── State / tracking ──────────────────────────────────────────────────────────
STEP=0
TOTAL_STEPS=8
WARNINGS=()
SPINNER_PID=''

# ── Collected config (filled during input phase) ──────────────────────────────
APP_NAME=''
DOMAIN=''
INSTALL_DIR=''
SOURCE_TYPE=''      # "local" | "git"
GIT_URL=''
PHP_VER=''
WEB_USER=''
SSL_METHOD=''       # "letsencrypt" | "selfsigned" | "none"
ADMIN_PANEL_PREFIX='talos'

# ── Spinner ───────────────────────────────────────────────────────────────────
_spinner() {
    local msg="$1"
    local frames=('⠋' '⠙' '⠹' '⠸' '⠼' '⠴' '⠦' '⠧' '⠇' '⠏')
    local i=0
    tput civis 2>/dev/null || true
    while true; do
        printf "\r  ${CYAN}%s${RESET}  %s   " "${frames[$((i % 10))]}" "$msg"
        i=$((i+1)); sleep 0.08
    done
}
start_spinner() {
    _spinner "$1" &
    SPINNER_PID=$!
    disown "$SPINNER_PID" 2>/dev/null || true
}
stop_spinner() {
    if [[ -n "$SPINNER_PID" ]]; then
        kill "$SPINNER_PID" 2>/dev/null || true
        wait "$SPINNER_PID" 2>/dev/null || true
        SPINNER_PID=''
        tput cnorm 2>/dev/null || true
        printf "\r\033[K"
    fi
}

# ── Output helpers ────────────────────────────────────────────────────────────
banner() {
    clear
    echo -e "${BOLD}${BLUE}"
    echo '  ╔══════════════════════════════════════════════╗'
    echo '  ║                                              ║'
    echo '  ║      Talos CMS  ·  Deployment Script         ║'
    echo '  ║                                              ║'
    echo '  ╚══════════════════════════════════════════════╝'
    echo -e "${RESET}"
    echo -e "  ${DIM}Log: ${LOG_FILE}${RESET}"
    echo
}

step() {
    STEP=$((STEP+1))
    echo
    echo -e "${BOLD}${BLUE}  ── Step ${STEP}/${TOTAL_STEPS}: $1 ${RESET}"
    echo -e "${DIM}  ────────────────────────────────────────────────${RESET}"
}

ok()     { stop_spinner; echo -e "  ${GREEN}✔${RESET}  $*"; }
warn()   { stop_spinner; echo -e "  ${YELLOW}⚠${RESET}  $*"; WARNINGS+=("$*"); }
info()   { echo -e "  ${CYAN}→${RESET}  $*"; }
detail() { echo -e "  ${DIM}   $*${RESET}"; }
fail()   {
    stop_spinner
    echo
    echo -e "  ${RED}╔══════════════════════════════════════════════╗${RESET}"
    echo -e "  ${RED}║  ✘  FATAL ERROR${RESET}"
    echo -e "  ${RED}║${RESET}  $*"
    echo -e "  ${RED}╚══════════════════════════════════════════════╝${RESET}"
    echo
    echo -e "  ${DIM}Full log: ${LOG_FILE}${RESET}"
    exit 1
}

run() {
    # run a command, log output, surface errors
    "$@" >>"$LOG_FILE" 2>&1 || {
        stop_spinner
        echo -e "  ${RED}✘${RESET}  Command failed: ${BOLD}$*${RESET}"
        echo -e "  ${DIM}Last log lines:${RESET}"
        tail -10 "$LOG_FILE" | sed 's/^/    /'
        fail "See log for details."
    }
}

run_ok() {
    # run silently, then print ok message on success
    local msg="$1"; shift
    start_spinner "$msg"
    run "$@"
    stop_spinner
    ok "$msg"
}

ask() {
    local prompt="$1" default="${2:-}" var_name="$3"
    local hint=''
    [[ -n "$default" ]] && hint=" ${DIM}[${default}]${RESET}"
    printf "  ${BOLD}%s${RESET}%b: " "$prompt" "$hint"
    read -r _input </dev/tty
    local value="${_input:-$default}"
    [[ -z "$value" ]] && fail "\"$prompt\" is required."
    printf -v "$var_name" '%s' "$value"
}

ask_optional() {
    local prompt="$1" default="${2:-}" var_name="$3"
    local hint=''
    [[ -n "$default" ]] && hint=" ${DIM}[${default}]${RESET}"
    printf "  ${BOLD}%s${RESET}%b: " "$prompt" "$hint"
    read -r _input </dev/tty
    printf -v "$var_name" '%s' "${_input:-$default}"
}

ask_secret() {
    local prompt="$1" var_name="$2"
    printf "  ${BOLD}%s${RESET}: " "$prompt"
    read -rs _sec </dev/tty; echo
    [[ -z "$_sec" ]] && fail "\"$prompt\" cannot be empty."
    printf -v "$var_name" '%s' "$_sec"
}

confirm() {
    local prompt="$1" default="${2:-y}"
    local yn='[Y/n]'; [[ "$default" == "n" ]] && yn='[y/N]'
    printf "  ${BOLD}%s${RESET} ${DIM}%s${RESET}: " "$prompt" "$yn"
    read -r _ans </dev/tty
    _ans="${_ans:-$default}"
    [[ "$_ans" =~ ^[Yy] ]]
}

choose() {
    # choose VAR "prompt" opt1 opt2 opt3 ...
    local var_name="$1" prompt="$2"; shift 2
    local opts=("$@")
    echo -e "  ${BOLD}${prompt}${RESET}"
    for i in "${!opts[@]}"; do
        echo -e "    ${DIM}$((i+1)).${RESET} ${opts[$i]}"
    done
    while true; do
        printf "  Choice [1-%d]: " "${#opts[@]}"
        read -r _c </dev/tty
        if [[ "$_c" =~ ^[0-9]+$ ]] && (( _c >= 1 && _c <= ${#opts[@]} )); then
            printf -v "$var_name" '%s' "${opts[$((_c-1))]}"
            return
        fi
        echo -e "  ${YELLOW}Please enter a number between 1 and ${#opts[@]}.${RESET}"
    done
}

# ── OS / environment detection ────────────────────────────────────────────────
detect_os() {
    if [[ -f /etc/os-release ]]; then
        # shellcheck disable=SC1091
        . /etc/os-release
        case "${ID:-}" in
            ubuntu|linuxmint|pop) echo "debian" ;;
            debian)               echo "debian" ;;
            centos|rhel|almalinux|rocky|fedora) echo "rhel" ;;
            alpine)               echo "alpine" ;;
            *)                    echo "unknown" ;;
        esac
    else
        echo "unknown"
    fi
}

OS=$(detect_os)
[[ "$OS" == "unknown" ]] && fail "Unsupported OS. This script supports Ubuntu, Debian, CentOS, RHEL, AlmaLinux, Rocky Linux."

case "$OS" in
    debian) WEB_USER="www-data" ;;
    rhel)   WEB_USER="nginx"    ;;
esac

get_server_ip() {
    hostname -I 2>/dev/null | awk '{print $1}' \
        || curl -s --max-time 3 https://api.ipify.org 2>/dev/null \
        || echo "unknown"
}

domain_resolves_here() {
    local domain="$1"
    local server_ip; server_ip=$(get_server_ip)
    local domain_ip
    domain_ip=$(getent hosts "$domain" 2>/dev/null | awk '{print $1}' | head -1 || true)
    if [[ -z "$domain_ip" ]] && command -v dig &>/dev/null; then
        domain_ip=$(dig +short "$domain" 2>/dev/null | grep -E '^[0-9]+\.' | tail -1 || true)
    fi
    [[ -n "$domain_ip" && "$domain_ip" == "$server_ip" ]]
}

port_available() {
    ! (ss -tlnp 2>/dev/null | grep -q ":${1} " \
    || netstat -tlnp 2>/dev/null | grep -q ":${1} ")
}

# ── PHP requirements ──────────────────────────────────────────────────────────
PHP_MIN_VER="8.2"       # minimum required by Laravel 12 / Talos CMS
PHP_INSTALL_VER="8.3"   # version to install when none is present

# ── PHP helpers ───────────────────────────────────────────────────────────────
# Returns the highest installed PHP version that satisfies PHP_MIN_VER, or ""
find_php() {
    for ver in 8.4 8.3 8.2; do
        if command -v "php${ver}" &>/dev/null; then
            echo "$ver"; return
        fi
    done
    # Generic `php` binary — check it meets minimum
    if command -v php &>/dev/null; then
        local v; v=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
        if php -r "exit(version_compare('${v}', '${PHP_MIN_VER}', '>=') ? 0 : 1);"; then
            echo "$v"; return
        fi
    fi
    echo ""
}

php_ext_ok() {
    local ver="$1" ext="$2"
    "php${ver}" -r "exit(extension_loaded('${ext}') ? 0 : 1);" 2>/dev/null
}

fpm_sock() {
    local ver="$1"
    case "$OS" in
        debian) echo "/var/run/php/php${ver}-fpm.sock" ;;
        rhel)   echo "/var/run/php-fpm/www.sock" ;;
    esac
}

fpm_service() {
    local ver="$1"
    case "$OS" in
        debian) echo "php${ver}-fpm" ;;
        rhel)   echo "php-fpm" ;;
    esac
}

nginx_conf_path() {
    case "$OS" in
        debian) echo "/etc/nginx/sites-available/talos-${DOMAIN}.conf" ;;
        rhel)   echo "/etc/nginx/conf.d/talos-${DOMAIN}.conf" ;;
    esac
}

state_file() { echo "${STATE_DIR}/$(echo "$DOMAIN" | tr '.' '-').state"; }

state_set()  {
    mkdir -p "$STATE_DIR"
    echo "$1" >> "$(state_file)"
}

state_has()  {
    [[ -f "$(state_file)" ]] && grep -qx "$1" "$(state_file)"
}

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 0 — Preflight
# ══════════════════════════════════════════════════════════════════════════════
banner

# Root check
[[ "$EUID" -eq 0 ]] || fail "Please run as root: sudo bash deploy.sh"

touch "$LOG_FILE"
echo "=== Talos CMS Deploy Log — $(date) ===" > "$LOG_FILE"
echo "OS: $OS" >> "$LOG_FILE"

echo -e "  ${DIM}Detected OS: ${BOLD}$(. /etc/os-release && echo "${PRETTY_NAME:-$OS}")${RESET}"
echo -e "  ${DIM}Server IP:   ${BOLD}$(get_server_ip)${RESET}"
echo

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 1 — Collect configuration
# ══════════════════════════════════════════════════════════════════════════════
step "Collecting configuration"

# ── Source ────────────────────────────────────────────────────────────────────
if [[ "$RUNNING_FROM_PIPE" == false && -f "${SCRIPT_DIR}/artisan" && -f "${SCRIPT_DIR}/composer.json" ]]; then
    info "Project detected in current directory."
    SOURCE_TYPE="local"
    SOURCE_PATH="$SCRIPT_DIR"
else
    # Running from curl pipe or outside the project — clone from GitHub
    SOURCE_TYPE="git"
    GIT_URL="$TALOS_REPO"
    info "Source: ${GIT_URL} (branch: ${TALOS_BRANCH})"
fi

# ── App name & domain ─────────────────────────────────────────────────────────
echo
ask "Application name (display)"    "Talos CMS"       APP_NAME
ask "Domain name"                   ""                DOMAIN
ask "Install directory"             "/var/www/$(echo "$DOMAIN" | tr '.' '-')" INSTALL_DIR

# Strip trailing slash
INSTALL_DIR="${INSTALL_DIR%/}"

# Warn if directory already exists
if [[ -d "$INSTALL_DIR" ]]; then
    warn "Directory ${INSTALL_DIR} already exists."
    if ! confirm "Continue and update existing installation?"; then
        fail "Aborted by user."
    fi
fi

# ── SSL ───────────────────────────────────────────────────────────────────────
echo
SERVER_IP=$(get_server_ip)
echo -e "  ${DIM}Checking if ${DOMAIN} resolves to this server (${SERVER_IP})...${RESET}"

DOMAIN_RESOLVES=false
if domain_resolves_here "$DOMAIN"; then
    DOMAIN_RESOLVES=true
    ok "Domain ${DOMAIN} points to this server."
else
    warn "Domain ${DOMAIN} does not appear to point to this server (${SERVER_IP})."
    warn "Let's Encrypt will fail — DNS must propagate first."
fi

echo
echo -e "  ${BOLD}SSL certificate method:${RESET}"
echo -e "    ${DIM}1.${RESET} Let's Encrypt (free, auto-renewing) ${GREEN}← recommended if DNS is ready${RESET}"
echo -e "    ${DIM}2.${RESET} Self-signed (instant, browser warning)"
echo -e "    ${DIM}3.${RESET} Skip SSL (HTTP only)"
printf "  Choice [1-3]: "
read -r _ssl_choice </dev/tty
case "${_ssl_choice:-1}" in
    1)
        if ! $DOMAIN_RESOLVES; then
            warn "DNS not ready — falling back to self-signed. You can run certbot manually later."
            SSL_METHOD="selfsigned"
        else
            SSL_METHOD="letsencrypt"
        fi
        ;;
    2) SSL_METHOD="selfsigned" ;;
    3) SSL_METHOD="none" ;;
    *) SSL_METHOD="selfsigned" ;;
esac

# ── Admin email (for Let's Encrypt) ──────────────────────────────────────────
CERTBOT_EMAIL=''
if [[ "$SSL_METHOD" == "letsencrypt" ]]; then
    ask "Email for Let's Encrypt notifications" "" CERTBOT_EMAIL
fi

# ── Summary before proceeding ─────────────────────────────────────────────────
echo
echo -e "  ${BOLD}${BLUE}── Configuration Summary ──────────────────────────${RESET}"
echo -e "  ${DIM}App name:${RESET}      ${APP_NAME}"
echo -e "  ${DIM}Domain:${RESET}        ${DOMAIN}"
echo -e "  ${DIM}Install dir:${RESET}   ${INSTALL_DIR}"
echo -e "  ${DIM}PHP version:${RESET}   ${PHP_VER} ${DIM}(auto)${RESET}"
echo -e "  ${DIM}SSL:${RESET}           ${SSL_METHOD}"
echo -e "  ${DIM}Source:${RESET}        ${SOURCE_TYPE}${GIT_URL:+ (${GIT_URL})}"
echo -e "  ${DIM}Web user:${RESET}      ${WEB_USER}"
echo

confirm "Proceed with deployment?" "y" || fail "Aborted by user."

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 2 — Dependencies
# ══════════════════════════════════════════════════════════════════════════════
step "Installing dependencies"

# ── Update package index (once) ───────────────────────────────────────────────
if ! state_has "pkg_updated"; then
    start_spinner "Updating package index"
    case "$OS" in
        debian) run apt-get update -y ;;
        rhel)   run dnf makecache -y  ;;
    esac
    stop_spinner; ok "Package index updated"
    state_set "pkg_updated"
fi

# ── Git ───────────────────────────────────────────────────────────────────────
if ! command -v git &>/dev/null; then
    start_spinner "Installing Git"
    case "$OS" in
        debian) run apt-get install -y git ;;
        rhel)   run dnf install -y git     ;;
    esac
    stop_spinner; ok "Git installed"
else
    ok "Git $(git --version | awk '{print $3}') already installed"
fi

# ── PHP ───────────────────────────────────────────────────────────────────────
# Resolve which PHP version to use — auto-detect or auto-install, never ask.
PHP_VER=$(find_php)

if [[ -n "$PHP_VER" ]]; then
    ok "PHP ${PHP_VER} detected (satisfies minimum ${PHP_MIN_VER})"
else
    # No compatible PHP found — install the preferred version automatically
    PHP_VER="$PHP_INSTALL_VER"
    info "No PHP >= ${PHP_MIN_VER} found. Installing PHP ${PHP_VER}..."

    start_spinner "Setting up PHP ${PHP_VER} repository"
    case "$OS" in
        debian)
            run apt-get install -y software-properties-common
            run add-apt-repository -y ppa:ondrej/php
            run apt-get update -y
            ;;
        rhel)
            RHEL_VER=$(rpm -E '%{rhel}')
            run dnf install -y "https://rpms.remirepo.net/enterprise/remi-release-${RHEL_VER}.rpm" 2>/dev/null || true
            run dnf module reset php -y 2>/dev/null || true
            run dnf module enable "php:remi-${PHP_VER}" -y 2>/dev/null || true
            ;;
    esac
    stop_spinner; ok "PHP ${PHP_VER} repository ready"

    PHP_PKGS=()
    case "$OS" in
        debian)
            PHP_PKGS=(
                "php${PHP_VER}" "php${PHP_VER}-fpm" "php${PHP_VER}-cli"
                "php${PHP_VER}-sqlite3" "php${PHP_VER}-mbstring" "php${PHP_VER}-xml"
                "php${PHP_VER}-curl" "php${PHP_VER}-zip" "php${PHP_VER}-bcmath"
                "php${PHP_VER}-fileinfo" "php${PHP_VER}-tokenizer" "php${PHP_VER}-ctype"
                "php${PHP_VER}-json" "php${PHP_VER}-intl"
            )
            ;;
        rhel)
            PHP_PKGS=(
                "php${PHP_VER//./}" "php${PHP_VER//./}-fpm" "php${PHP_VER//./}-cli"
                "php${PHP_VER//./}-pdo" "php${PHP_VER//./}-sqlite3" "php${PHP_VER//./}-mbstring"
                "php${PHP_VER//./}-xml" "php${PHP_VER//./}-curl" "php${PHP_VER//./}-zip"
                "php${PHP_VER//./}-bcmath" "php${PHP_VER//./}-fileinfo" "php${PHP_VER//./}-intl"
            )
            ;;
    esac

    start_spinner "Installing PHP ${PHP_VER} with all required extensions"
    case "$OS" in
        debian) run apt-get install -y "${PHP_PKGS[@]}" ;;
        rhel)   run dnf install -y "${PHP_PKGS[@]}" ;;
    esac
    stop_spinner; ok "PHP ${PHP_VER} installed"
fi

# Verify required extensions
start_spinner "Verifying PHP extensions"
MISSING_EXTS=()
for ext in pdo pdo_sqlite sqlite3 mbstring xml ctype json bcmath fileinfo curl tokenizer; do
    php_ext_ok "$PHP_VER" "$ext" 2>/dev/null || MISSING_EXTS+=("$ext")
done
stop_spinner

if (( ${#MISSING_EXTS[@]} > 0 )); then
    warn "Missing PHP extensions: ${MISSING_EXTS[*]}"
    info "Attempting to install missing extensions..."
    for ext in "${MISSING_EXTS[@]}"; do
        case "$OS" in
            debian) run apt-get install -y "php${PHP_VER}-${ext}" 2>/dev/null || warn "Could not install php${PHP_VER}-${ext}" ;;
            rhel)   run dnf install -y "php${PHP_VER//./}-${ext}" 2>/dev/null || warn "Could not install php${PHP_VER//./}-${ext}" ;;
        esac
    done
else
    ok "All required PHP extensions present"
fi

# ── SQLite ────────────────────────────────────────────────────────────────────
if ! command -v sqlite3 &>/dev/null; then
    start_spinner "Installing SQLite3"
    case "$OS" in
        debian) run apt-get install -y sqlite3 ;;
        rhel)   run dnf install -y sqlite      ;;
    esac
    stop_spinner; ok "SQLite3 installed"
else
    ok "SQLite3 $(sqlite3 --version | awk '{print $1}') already installed"
fi

# ── Nginx ─────────────────────────────────────────────────────────────────────
if ! command -v nginx &>/dev/null; then
    start_spinner "Installing Nginx"
    case "$OS" in
        debian) run apt-get install -y nginx ;;
        rhel)   run dnf install -y nginx     ;;
    esac
    stop_spinner; ok "Nginx installed"
else
    ok "Nginx $(nginx -v 2>&1 | grep -o '[0-9.]*') already installed"
fi

run systemctl enable nginx
run systemctl start nginx

# ── Composer ─────────────────────────────────────────────────────────────────
if ! command -v composer &>/dev/null; then
    start_spinner "Installing Composer"
    EXPECTED_SIG=$(curl -s https://composer.github.io/installer.sig)
    run php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    ACTUAL_SIG=$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")
    if [[ "$EXPECTED_SIG" != "$ACTUAL_SIG" ]]; then
        fail "Composer installer checksum mismatch — possible tampering. Aborting."
    fi
    run php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
    rm -f /tmp/composer-setup.php
    stop_spinner; ok "Composer $(composer --version --no-ansi | awk '{print $3}') installed"
else
    ok "Composer $(composer --version --no-ansi | awk '{print $3}') already installed"
fi

# ── Certbot (only if Let's Encrypt chosen) ────────────────────────────────────
if [[ "$SSL_METHOD" == "letsencrypt" ]]; then
    if ! command -v certbot &>/dev/null; then
        start_spinner "Installing Certbot"
        case "$OS" in
            debian)
                run apt-get install -y certbot python3-certbot-nginx
                ;;
            rhel)
                run dnf install -y epel-release
                run dnf install -y certbot python3-certbot-nginx
                ;;
        esac
        stop_spinner; ok "Certbot installed"
    else
        ok "Certbot $(certbot --version 2>&1 | awk '{print $2}') already installed"
    fi
fi

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 3 — Application setup
# ══════════════════════════════════════════════════════════════════════════════
step "Setting up application"

# ── Copy / clone source ───────────────────────────────────────────────────────
if ! state_has "source_deployed"; then
    if [[ "$SOURCE_TYPE" == "git" ]]; then
        if [[ -d "$INSTALL_DIR/.git" ]]; then
            run_ok "Pulling latest changes" git -C "$INSTALL_DIR" pull
        else
            run_ok "Cloning repository (branch: ${TALOS_BRANCH})" \
                git clone --branch "$TALOS_BRANCH" --depth 1 "$GIT_URL" "$INSTALL_DIR"
        fi
    else
        if [[ "$SOURCE_PATH" != "$INSTALL_DIR" ]]; then
            start_spinner "Copying application files"
            run mkdir -p "$INSTALL_DIR"
            run rsync -a --exclude='.git' --exclude='node_modules' \
                --exclude='storage/logs/*.log' \
                "${SOURCE_PATH}/" "${INSTALL_DIR}/"
            stop_spinner; ok "Application files copied"
        else
            ok "Using project in-place at ${INSTALL_DIR}"
        fi
    fi
    state_set "source_deployed"
else
    ok "Source already deployed — skipping copy"
fi

cd "$INSTALL_DIR"

# ── .env file ─────────────────────────────────────────────────────────────────
DB_PATH="${INSTALL_DIR}/database/talos.sqlite"

if [[ ! -f "${INSTALL_DIR}/.env" ]]; then
    [[ -f "${INSTALL_DIR}/.env.example" ]] || fail ".env.example not found in ${INSTALL_DIR}"
    run cp "${INSTALL_DIR}/.env.example" "${INSTALL_DIR}/.env"
    ok ".env created from .env.example"
fi

# Patch .env values
set_env() {
    local key="$1" value="$2" file="${INSTALL_DIR}/.env"
    if grep -q "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$file"
    else
        echo "${key}=${value}" >> "$file"
    fi
}

PROTO="https"; [[ "$SSL_METHOD" == "none" ]] && PROTO="http"
APP_URL="${PROTO}://${DOMAIN}"

set_env "APP_NAME"       "\"${APP_NAME}\""
set_env "APP_ENV"        "production"
set_env "APP_DEBUG"      "false"
set_env "APP_URL"        "$APP_URL"
set_env "DB_CONNECTION"  "sqlite"
set_env "DB_DATABASE"    "$DB_PATH"
set_env "LOG_CHANNEL"    "single"
set_env "SESSION_DRIVER" "database"
set_env "CACHE_STORE"    "database"
set_env "QUEUE_CONNECTION" "database"
ok ".env configured"

# ── SQLite database file ───────────────────────────────────────────────────────
run mkdir -p "${INSTALL_DIR}/database"
if [[ ! -f "$DB_PATH" ]]; then
    run touch "$DB_PATH"
    ok "SQLite database file created"
else
    ok "SQLite database file already exists"
fi

# ── Composer install ──────────────────────────────────────────────────────────
if ! state_has "composer_installed"; then
    run_ok "Installing PHP dependencies (composer install)" \
        composer install --no-interaction --no-dev --optimize-autoloader --working-dir="$INSTALL_DIR"
    state_set "composer_installed"
else
    ok "Composer dependencies already installed"
fi

# ── App key ───────────────────────────────────────────────────────────────────
CURRENT_KEY=$(grep "^APP_KEY=" "${INSTALL_DIR}/.env" | cut -d= -f2)
if [[ -z "$CURRENT_KEY" || "$CURRENT_KEY" == "" ]]; then
    run_ok "Generating application key" php artisan key:generate --force
else
    ok "Application key already set"
fi

# ── Talos install (migrate + storage:link + super admin) ──────────────────────
if ! state_has "talos_installed"; then
    echo
    echo -e "  ${BOLD}${CYAN}── Create Super Admin Account ─────────────────────${RESET}"
    php artisan talos:install
    state_set "talos_installed"
else
    # Re-run migrations only (idempotent)
    run_ok "Running database migrations" php artisan migrate --force
    ok "talos:install already ran — skipping super admin creation"
fi

# ── Permissions ───────────────────────────────────────────────────────────────
start_spinner "Setting file permissions"
run chown -R "${WEB_USER}:${WEB_USER}" "$INSTALL_DIR"
run chmod -R 755 "$INSTALL_DIR"
run chmod -R 775 "${INSTALL_DIR}/storage" "${INSTALL_DIR}/bootstrap/cache"
run chmod 664 "$DB_PATH"
run chmod 775 "${INSTALL_DIR}/database"
stop_spinner; ok "File permissions set"

# ── Artisan optimise ──────────────────────────────────────────────────────────
run_ok "Caching config, routes, and views" php artisan optimize

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 4 — PHP-FPM
# ══════════════════════════════════════════════════════════════════════════════
step "Configuring PHP-FPM"

FPM_SERVICE=$(fpm_service "$PHP_VER")
FPM_SOCK=$(fpm_sock "$PHP_VER")

run systemctl enable "$FPM_SERVICE"
run systemctl restart "$FPM_SERVICE"

# Wait for socket to appear
SOCK_WAIT=0
while [[ ! -S "$FPM_SOCK" && $SOCK_WAIT -lt 10 ]]; do
    sleep 1; SOCK_WAIT=$((SOCK_WAIT+1))
done

if [[ -S "$FPM_SOCK" ]]; then
    ok "PHP-FPM running (socket: ${FPM_SOCK})"
else
    warn "PHP-FPM socket not found at ${FPM_SOCK} — using TCP 127.0.0.1:9000 fallback"
    FPM_SOCK="127.0.0.1:9000"
fi

# ── PHP-FPM pool: bump upload and execution limits ────────────────────────────
PHP_INI_FPM_POOL="/etc/php/${PHP_VER}/fpm/pool.d/www.conf"
if [[ -f "$PHP_INI_FPM_POOL" ]]; then
    sed -i 's/^;*\s*upload_max_filesize\s*=.*/upload_max_filesize = 64M/' "$PHP_INI_FPM_POOL" 2>/dev/null || true
    sed -i 's/^;*\s*post_max_size\s*=.*/post_max_size = 64M/'             "$PHP_INI_FPM_POOL" 2>/dev/null || true
fi

PHP_INI="/etc/php/${PHP_VER}/fpm/php.ini"
if [[ -f "$PHP_INI" ]]; then
    sed -i 's/^;*\s*upload_max_filesize\s*=.*/upload_max_filesize = 64M/' "$PHP_INI" 2>/dev/null || true
    sed -i 's/^;*\s*post_max_size\s*=.*/post_max_size = 64M/'             "$PHP_INI" 2>/dev/null || true
    sed -i 's/^;*\s*max_execution_time\s*=.*/max_execution_time = 120/'   "$PHP_INI" 2>/dev/null || true
fi

run systemctl reload "$FPM_SERVICE"
ok "PHP-FPM configured (upload_max: 64M)"

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 5 — Nginx virtual host
# ══════════════════════════════════════════════════════════════════════════════
step "Configuring Nginx virtual host"

NGINX_CONF=$(nginx_conf_path)

# Use socket if available, else TCP
if [[ "$FPM_SOCK" == /* ]]; then
    FPM_PASS="unix:${FPM_SOCK}"
else
    FPM_PASS="$FPM_SOCK"
fi

# Write the initial HTTP-only vhost (SSL added after certbot)
cat > "$NGINX_CONF" <<NGINXCONF
# Talos CMS — ${APP_NAME} — generated by deploy.sh $(date)
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root ${INSTALL_DIR}/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Max upload size
    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # Static assets — long cache
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|woff|woff2|ttf|svg|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location ~ \.php$ {
        fastcgi_pass   ${FPM_PASS};
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Protect SQLite database
    location ~* \.sqlite$ { deny all; }

    access_log /var/log/nginx/talos-${DOMAIN}-access.log;
    error_log  /var/log/nginx/talos-${DOMAIN}-error.log;
}
NGINXCONF

ok "Nginx config written to ${NGINX_CONF}"

# Enable site (Debian/Ubuntu only — RHEL uses conf.d directly)
if [[ "$OS" == "debian" ]]; then
    NGINX_ENABLED="/etc/nginx/sites-enabled/talos-${DOMAIN}.conf"
    [[ -L "$NGINX_ENABLED" ]] && rm -f "$NGINX_ENABLED"
    run ln -s "$NGINX_CONF" "$NGINX_ENABLED"
    # Disable default site if it would conflict
    [[ -f /etc/nginx/sites-enabled/default ]] && {
        run rm -f /etc/nginx/sites-enabled/default
        info "Disabled default Nginx site."
    }
fi

start_spinner "Testing Nginx configuration"
run nginx -t
stop_spinner; ok "Nginx config test passed"

run systemctl reload nginx
ok "Nginx reloaded"

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 6 — SSL
# ══════════════════════════════════════════════════════════════════════════════
step "Setting up SSL / HTTPS"

# ── Let's Encrypt ─────────────────────────────────────────────────────────────
if [[ "$SSL_METHOD" == "letsencrypt" ]]; then
    info "Requesting Let's Encrypt certificate for ${DOMAIN}..."
    start_spinner "Obtaining certificate via ACME HTTP-01 challenge"
    if certbot --nginx \
        --non-interactive \
        --agree-tos \
        --email "$CERTBOT_EMAIL" \
        --domains "$DOMAIN" \
        --redirect \
        >>"$LOG_FILE" 2>&1; then
        stop_spinner
        ok "Let's Encrypt certificate obtained and installed"

        # Auto-renewal cron
        if ! crontab -l 2>/dev/null | grep -q "certbot renew"; then
            (crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet --nginx --post-hook 'systemctl reload nginx'") | crontab -
            ok "Auto-renewal cron added (runs daily at 3am)"
        fi
    else
        stop_spinner
        warn "Let's Encrypt failed — falling back to self-signed certificate."
        SSL_METHOD="selfsigned"
    fi
fi

# ── Self-signed (also used as Let's Encrypt fallback) ─────────────────────────
if [[ "$SSL_METHOD" == "selfsigned" ]]; then
    SSL_CERT="/etc/ssl/talos/${DOMAIN}/cert.pem"
    SSL_KEY="/etc/ssl/talos/${DOMAIN}/key.pem"
    run mkdir -p "/etc/ssl/talos/${DOMAIN}"

    if [[ ! -f "$SSL_CERT" ]]; then
        start_spinner "Generating self-signed certificate (2048-bit RSA, 10 years)"
        run openssl req -x509 -nodes -days 3650 \
            -newkey rsa:2048 \
            -keyout "$SSL_KEY" \
            -out    "$SSL_CERT" \
            -subj   "/C=US/ST=State/L=City/O=${APP_NAME}/CN=${DOMAIN}"
        run chmod 600 "$SSL_KEY"
        stop_spinner
        ok "Self-signed certificate created"
    else
        ok "Self-signed certificate already exists"
    fi

    # Generate DH params for stronger security
    DH_PARAMS="/etc/ssl/talos/dhparam.pem"
    if [[ ! -f "$DH_PARAMS" ]]; then
        run_ok "Generating DH parameters (this takes a minute)" \
            openssl dhparam -out "$DH_PARAMS" 2048
    fi

    # Rewrite Nginx conf with HTTPS
    cat > "$NGINX_CONF" <<NGINXSSL
# Talos CMS — ${APP_NAME} — generated by deploy.sh $(date)
# Self-signed SSL
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${DOMAIN};

    ssl_certificate     ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};
    ssl_dhparam         ${DH_PARAMS};

    ssl_protocols      TLSv1.2 TLSv1.3;
    ssl_ciphers        ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache  shared:SSL:10m;
    ssl_session_timeout 10m;

    root ${INSTALL_DIR}/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    client_max_body_size 64M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~* \.(css|js|jpg|jpeg|png|gif|ico|woff|woff2|ttf|svg|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location ~ \.php$ {
        fastcgi_pass   ${FPM_PASS};
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* { deny all; }
    location ~* \.sqlite$ { deny all; }

    access_log /var/log/nginx/talos-${DOMAIN}-access.log;
    error_log  /var/log/nginx/talos-${DOMAIN}-error.log;
}
NGINXSSL

    run nginx -t
    run systemctl reload nginx
    ok "Nginx reloaded with self-signed SSL"
    warn "Self-signed cert: browsers will show a security warning. Use Let's Encrypt when DNS is ready."
fi

# ── No SSL ────────────────────────────────────────────────────────────────────
if [[ "$SSL_METHOD" == "none" ]]; then
    warn "Running over HTTP only — not recommended for production."
fi

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 7 — Scheduler cron
# ══════════════════════════════════════════════════════════════════════════════
step "Setting up task scheduler"

CRON_CMD="* * * * * ${WEB_USER} php ${INSTALL_DIR}/artisan schedule:run >> /dev/null 2>&1"
CRON_FILE="/etc/cron.d/talos-${DOMAIN//\./-}"

if [[ ! -f "$CRON_FILE" ]]; then
    echo "$CRON_CMD" > "$CRON_FILE"
    chmod 644 "$CRON_FILE"
    ok "Laravel scheduler cron installed (${CRON_FILE})"
else
    ok "Scheduler cron already exists"
fi

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 8 — Final verification
# ══════════════════════════════════════════════════════════════════════════════
step "Final verification"

ERRORS_FOUND=0

# Check Nginx is running
if systemctl is-active --quiet nginx; then
    ok "Nginx is running"
else
    warn "Nginx is NOT running"; ERRORS_FOUND=$((ERRORS_FOUND+1))
fi

# Check PHP-FPM is running
if systemctl is-active --quiet "$FPM_SERVICE"; then
    ok "PHP-FPM (${FPM_SERVICE}) is running"
else
    warn "PHP-FPM is NOT running"; ERRORS_FOUND=$((ERRORS_FOUND+1))
fi

# Check DB readable
if php artisan db:show --no-ansi >>"$LOG_FILE" 2>&1; then
    ok "Database connection verified"
else
    warn "Could not verify database connection"; ERRORS_FOUND=$((ERRORS_FOUND+1))
fi

# Check storage is writable
if [[ -w "${INSTALL_DIR}/storage" ]]; then
    ok "Storage directory is writable"
else
    warn "Storage directory is NOT writable"; ERRORS_FOUND=$((ERRORS_FOUND+1))
fi

# HTTP connectivity check
PROTO="https"; [[ "$SSL_METHOD" == "none" ]] && PROTO="http"
HTTP_CODE=$(curl -sk --max-time 10 -o /dev/null -w "%{http_code}" "${PROTO}://${DOMAIN}/${ADMIN_PANEL_PREFIX}/login" 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" =~ ^(200|301|302)$ ]]; then
    ok "Admin panel responded with HTTP ${HTTP_CODE}"
else
    warn "Admin panel returned HTTP ${HTTP_CODE} — may need DNS propagation"; ERRORS_FOUND=$((ERRORS_FOUND+1))
fi

# ══════════════════════════════════════════════════════════════════════════════
# DONE — Summary
# ══════════════════════════════════════════════════════════════════════════════
echo
echo -e "${BOLD}${GREEN}"
echo '  ╔══════════════════════════════════════════════╗'
echo '  ║         Talos CMS deployed successfully!     ║'
echo '  ╚══════════════════════════════════════════════╝'
echo -e "${RESET}"

PROTO="https"; [[ "$SSL_METHOD" == "none" ]] && PROTO="http"
ADMIN_URL="${PROTO}://${DOMAIN}/${ADMIN_PANEL_PREFIX}"

echo -e "  ${BOLD}Admin panel:${RESET}  ${CYAN}${ADMIN_URL}${RESET}"
echo -e "  ${BOLD}Install dir:${RESET}  ${INSTALL_DIR}"
echo -e "  ${BOLD}Database:${RESET}     ${DB_PATH}"
echo -e "  ${BOLD}PHP:${RESET}          ${PHP_VER}"
echo -e "  ${BOLD}SSL:${RESET}          ${SSL_METHOD}"
echo -e "  ${BOLD}Log:${RESET}          ${LOG_FILE}"
echo

if (( ${#WARNINGS[@]} > 0 )); then
    echo -e "  ${YELLOW}${BOLD}Warnings (${#WARNINGS[@]}):${RESET}"
    for w in "${WARNINGS[@]}"; do
        echo -e "  ${YELLOW}⚠${RESET}  ${w}"
    done
    echo
fi

if [[ "$SSL_METHOD" == "selfsigned" ]]; then
    echo -e "  ${DIM}To upgrade to Let's Encrypt once DNS is pointed here:${RESET}"
    echo -e "  ${CYAN}  certbot --nginx -d ${DOMAIN} --email your@email.com --agree-tos --non-interactive --redirect${RESET}"
    echo
fi

echo -e "  ${DIM}Useful commands:${RESET}"
echo -e "  ${DIM}  php ${INSTALL_DIR}/artisan talos:install    # add another super admin${RESET}"
echo -e "  ${DIM}  systemctl status nginx ${FPM_SERVICE}       # service status${RESET}"
echo -e "  ${DIM}  tail -f /var/log/nginx/talos-${DOMAIN}-error.log${RESET}"
echo -e "  ${DIM}  tail -f ${INSTALL_DIR}/storage/logs/laravel.log${RESET}"
echo
