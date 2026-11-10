#!/usr/bin/env bash
# ==============================================================================
# Github: https://github.com/QadirAdamson/CWP-Module-MaxMind-GeoIP
# ==============================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${RED}======================================================${NC}"
echo -e "${RED}  CWP MaxMind GeoIP Module Uninstaller                ${NC}"
echo -e "${RED}======================================================${NC}"

if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}[ERROR] This script must be run as root.${NC}"
  exit 1
fi

CWP_DIR="/usr/local/cwpsrv/htdocs/resources/admin"

echo -e "\n${YELLOW}1. Removing module files...${NC}"
chattr -i "/usr/local/cwpsrv/htdocs/admin/admin/geoip_ajax.php" 2>/dev/null || true
chattr -i "/usr/local/cwpsrv/htdocs/admin/admin" 2>/dev/null || true
rm -f "$CWP_DIR/include/geoip_menu.php"
rm -f "$CWP_DIR/modules/maxmind-geoip.php"
rm -f "/usr/local/cwpsrv/htdocs/admin/admin/geoip_ajax.php"
chattr +i "/usr/local/cwpsrv/htdocs/admin/admin" 2>/dev/null || true
rm -rf "$CWP_DIR/modules/geoip_lib"
rm -rf "/usr/share/MaxMindGeoIP"
rm -rf "/usr/share/Maxmind_GeoIP"
rm -f "/usr/share/GeoIP/geoip.php"

echo -e "${YELLOW}2. Cleaning up 3rdparty.php menu injection...${NC}"
THIRDPARTY_FILE="$CWP_DIR/include/3rdparty.php"
if [ -f "$THIRDPARTY_FILE" ]; then
    sed -i '/geoip_menu.php/d' "$THIRDPARTY_FILE" 2>/dev/null || true
fi

echo -e "${YELLOW}3. Reloading cwpsrv...${NC}"
if systemctl is-active --quiet cwpsrv; then
    systemctl restart cwpsrv
fi

echo -e "${GREEN}[OK] MaxMind GeoIP Module uninstalled successfully.${NC}"
