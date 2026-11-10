#!/usr/bin/env bash
# ==============================================================================
# https://github.com/QadirAdamson/CWP-Module-MaxMind-GeoIP
# ==============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}  CWP MaxMind GeoIP Module Installer                  ${NC}"
echo -e "${BLUE}======================================================${NC}"

if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}[ERROR] This script must be run as root.${NC}"
  exit 1
fi

CWP_DIR="/usr/local/cwpsrv/htdocs/resources/admin"
if [ ! -d "$CWP_DIR" ]; then
  echo -e "${RED}[ERROR] Control Web Panel directory not found at $CWP_DIR${NC}"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GEOIP_DB_DIR="/usr/share/GeoIP"
MAXMIND_SHARE_DIR="/usr/share/MaxMindGeoIP"
REPO_RAW="https://raw.githubusercontent.com/QadirAdamson/CWP-Module-MaxMind-GeoIP/main"

echo -e "\n${YELLOW}[1/5] Preparing directories and dependencies...${NC}"
mkdir -p "$GEOIP_DB_DIR"
mkdir -p "$MAXMIND_SHARE_DIR"
mkdir -p "$CWP_DIR/include"
mkdir -p "$CWP_DIR/modules/geoip_lib"

# Clean up legacy files/directories if present
rm -f "$GEOIP_DB_DIR/geoip.php"
rm -rf "/usr/share/Maxmind_GeoIP"

if ! command -v mmdblookup >/dev/null 2>&1; then
    dnf install -y libmaxminddb-devel 2>/dev/null || yum install -y libmaxminddb-devel 2>/dev/null || true
fi

echo -e "\n${YELLOW}[2/5] Deploying MaxMind GeoIP module files to CWP...${NC}"
chattr -i "/usr/local/cwpsrv/htdocs/admin/admin/geoip_ajax.php" 2>/dev/null || true
chattr -i "/usr/local/cwpsrv/htdocs/admin/admin" 2>/dev/null || true

if [ -f "$SCRIPT_DIR/upload/admin/modules/maxmind-geoip.php" ]; then
    cp -rf "$SCRIPT_DIR/upload/admin/include/geoip_menu.php" "$CWP_DIR/include/"
    cp -rf "$SCRIPT_DIR/upload/admin/modules/maxmind-geoip.php" "$CWP_DIR/modules/"
    cp -rf "$SCRIPT_DIR/upload/admin/modules/geoip_ajax.php" "/usr/local/cwpsrv/htdocs/admin/admin/"
    cp -rf "$SCRIPT_DIR/upload/admin/modules/geoip_lib/update_geoip.sh" "$CWP_DIR/modules/geoip_lib/"
    cp -rf "$SCRIPT_DIR/upload/share/MaxMindGeoIP/maxmind_geoip.php" "$MAXMIND_SHARE_DIR/maxmind_geoip.php"
else
    echo -e "${BLUE}Fetching files directly from GitHub repository...${NC}"
    curl -fsSL "$REPO_RAW/upload/admin/include/geoip_menu.php" -o "$CWP_DIR/include/geoip_menu.php"
    curl -fsSL "$REPO_RAW/upload/admin/modules/maxmind-geoip.php" -o "$CWP_DIR/modules/maxmind-geoip.php"
    curl -fsSL "$REPO_RAW/upload/admin/modules/geoip_ajax.php" -o "/usr/local/cwpsrv/htdocs/admin/admin/geoip_ajax.php"
    curl -fsSL "$REPO_RAW/upload/admin/modules/geoip_lib/update_geoip.sh" -o "$CWP_DIR/modules/geoip_lib/update_geoip.sh"
    curl -fsSL "$REPO_RAW/upload/share/MaxMindGeoIP/maxmind_geoip.php" -o "$MAXMIND_SHARE_DIR/maxmind_geoip.php"
fi

# Download official standalone geoip2.phar helper
if [ ! -f "$GEOIP_DB_DIR/geoip2.phar" ] || [ $(stat -c%s "$GEOIP_DB_DIR/geoip2.phar" 2>/dev/null || echo 0) -lt 100000 ]; then
    echo -e "${BLUE}Downloading official geoip2.phar library...${NC}"
    curl -fsSL -L --retry 3 --connect-timeout 10 -o "$GEOIP_DB_DIR/geoip2.phar" "https://github.com/maxmind/GeoIP2-php/releases/download/v2.13.0/geoip2.phar" || true
fi
ln -sf "$GEOIP_DB_DIR/geoip2.phar" "$MAXMIND_SHARE_DIR/geoip2.phar" 2>/dev/null || true

chmod +x "$CWP_DIR/modules/geoip_lib/update_geoip.sh"
chown -R cwpsrv:cwpsrv "$CWP_DIR/include/geoip_menu.php" "$CWP_DIR/modules/maxmind-geoip.php" "$CWP_DIR/modules/geoip_lib" "/usr/local/cwpsrv/htdocs/admin/admin/geoip_ajax.php"
chmod 644 "$CWP_DIR/include/geoip_menu.php" "$CWP_DIR/modules/maxmind-geoip.php" "/usr/local/cwpsrv/htdocs/admin/admin/geoip_ajax.php"
chmod 644 "$MAXMIND_SHARE_DIR/maxmind_geoip.php" "$GEOIP_DB_DIR/geoip2.phar" 2>/dev/null || true
chmod 755 "$GEOIP_DB_DIR" "$MAXMIND_SHARE_DIR"
chmod -R 755 "$CWP_DIR/modules/geoip_lib"
chattr +i "/usr/local/cwpsrv/htdocs/admin/admin" 2>/dev/null || true
echo -e "${GREEN}[OK] Files deployed successfully.${NC}"

echo -e "\n${YELLOW}[3/5] Injecting MaxMind GeoIP into CWP sidebar (3rdparty.php)...${NC}"
MENU_INJECT="include_once('/usr/local/cwpsrv/htdocs/resources/admin/include/geoip_menu.php');"
THIRDPARTY_FILE="$CWP_DIR/include/3rdparty.php"

if [ -f "$THIRDPARTY_FILE" ]; then
    if ! grep -q "geoip_menu.php" "$THIRDPARTY_FILE"; then
        echo -e "\n<?php $MENU_INJECT ?>" >> "$THIRDPARTY_FILE"
        echo -e "${GREEN}[OK] Injected geoip_menu.php into 3rdparty.php${NC}"
    else
        echo -e "${GREEN}[OK] Menu injection already present in 3rdparty.php${NC}"
    fi
else
    echo -e "<?php\n$MENU_INJECT\n?>" > "$THIRDPARTY_FILE"
    chmod 644 "$THIRDPARTY_FILE"
    echo -e "${GREEN}[OK] Created 3rdparty.php with menu injection${NC}"
fi

echo -e "\n${YELLOW}[4/5] Downloading MaxMind GeoLite2 databases (City, Country, ASN)...${NC}"
bash "$CWP_DIR/modules/geoip_lib/update_geoip.sh"
echo -e "${GREEN}[OK] Databases downloaded and verified.${NC}"

echo -e "\n${YELLOW}[5/5] Reloading cwpsrv web service...${NC}"
if systemctl is-active --quiet cwpsrv; then
    systemctl restart cwpsrv
    echo -e "${GREEN}[OK] cwpsrv restarted.${NC}"
fi

echo -e "\n${GREEN}======================================================${NC}"
echo -e "${GREEN}  MaxMind GeoIP Module Installed Successfully!        ${NC}"
echo -e "${GREEN}======================================================${NC}"
echo -e "Access the module directly in your CWP Admin Panel:"
echo -e "👉 ${BLUE}https://YOUR_SERVER_IP:2031/admin/index.php?module=maxmind-geoip${NC}"
echo -e "Or find ${YELLOW}'Developer -> MaxMind GeoIP'${NC} under the left navigation menu."
