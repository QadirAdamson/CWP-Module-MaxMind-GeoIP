# MaxMind GeoIP Module for Control Web Panel (CWP)

A native MaxMind GeoLite2 (`.mmdb`) IP lookup and geolocation module designed for [Control Web Panel (CWP)](https://control-webpanel.com/).

---

## ✨ Features

- **🚀 Ultra-Fast MMDB Engine**: Queries local `.mmdb` binary databases via native `mmdblookup` in microseconds with zero API rate limits.
- **🔍 Comprehensive IP Geolocation**: Inspect Country & Flag, City & Region, Postal code, Timezone, ASN & ISP Organization, and exact Coordinates.
- **🗺️ Interactive Map & Direct Links**: Visual pin via OpenStreetMap and direct external 1-click link to Google Maps.
- **📋 Bulk IP Lookup**: Analyze up to 100 IPv4/IPv6 addresses simultaneously in a responsive data table.
- **⚡ Server-Wide PHP Integration**: Single-line helper function accessible across all hosted websites on the server.
- **🔄 1-Click Database Updates**: Refresh City, Country, and ASN databases directly from the panel header with `Last Updated` timestamp.

---

## 🚀 Installation

Run on your CWP server as `root`:

```bash
curl -fsSL https://raw.githubusercontent.com/QadirAdamson/CWP_Module-MaxMind_GeoIP/main/install.sh | bash
```

Access in CWP Admin Panel: 👉 **Developer → MaxMind GeoIP**

---

## 💻 PHP Integration

```php
require_once '/usr/share/MaxMindGeoIP/maxmind_geoip.php';
$geoip = maxmind_geoip('8.8.8.8');
// print_r($geoip);
```

---

## 🗑️ Uninstallation

```bash
curl -fsSL https://raw.githubusercontent.com/QadirAdamson/CWP_Module-MaxMind_GeoIP/main/uninstall.sh | bash
```

---

## 📄 License

This project is open-source and licensed under the [MIT License](LICENSE).
