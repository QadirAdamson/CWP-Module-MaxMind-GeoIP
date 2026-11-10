#!/usr/bin/env bash
# ==============================================================================
# https://github.com/QadirAdamson/CWP-Module-MaxMind-GeoIP
# ==============================================================================

GEOIP_DIR="/usr/share/GeoIP"
LOG_FILE="/var/log/geoip_update.log"
DATE=$(date "+%Y-%m-%d %H:%M:%S")

mkdir -p "$GEOIP_DIR"
echo "[$DATE] Starting GeoIP database update..." >> "$LOG_FILE"

update_db() {
    local name="$1"
    local target="$GEOIP_DIR/$name.mmdb"
    local tmp_file="/tmp/$name.mmdb.tmp"
    local mirror_url="https://github.com/P3TERX/GeoLite.mmdb/raw/download/$name.mmdb"

    if curl -fsSL -L --retry 3 --connect-timeout 10 -o "$tmp_file" "$mirror_url"; then
        if [ -s "$tmp_file" ] && [ $(stat -c%s "$tmp_file" 2>/dev/null || echo 0) -gt 100000 ]; then
            mv -f "$tmp_file" "$target"
            echo "[$DATE] $name updated successfully." >> "$LOG_FILE"
        else
            rm -f "$tmp_file"
        fi
    fi
}

update_db "GeoLite2-City"
update_db "GeoLite2-Country"
update_db "GeoLite2-ASN"

chown -R cwpsrv:cwpsrv "$GEOIP_DIR" 2>/dev/null || true
chmod 755 "$GEOIP_DIR" 2>/dev/null || true
chmod 644 "$GEOIP_DIR"/*.mmdb 2>/dev/null || true
echo "[$DATE] GeoIP database update finished." >> "$LOG_FILE"
