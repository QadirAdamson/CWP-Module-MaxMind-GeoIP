# MaxMind GeoIP Module for Control Web Panel (CWP)

A high-performance, native MaxMind GeoLite2 (`.mmdb`) IP lookup and geolocation module designed for [Control Web Panel (CWP)](https://control-webpanel.com/).

---

## ✨ Key Features

- **Local MMDB Engine**: Query local `.mmdb` binary databases via native `mmdblookup` with zero external API rate limits.
- **Deep IP Geolocation**: Inspect country, flag, city, region, postal code, timezone, ASN, organization, and coordinates.
- **Interactive Map**: Embedded OpenStreetMap preview with direct coordinates navigation links.
- **Bulk IP Lookup**: Query up to 100 IPv4 and IPv6 addresses simultaneously in a responsive data table.
- **Server-Wide PHP Integration**: Seamless `maxmind_geoip($ip)` helper function accessible to all hosted websites and applications.
- **Automated Database Updates**: One-click updates for City, Country, and ASN databases directly from the admin panel.

---

## 🚀 Installation

Run on your CWP server as `root`:

```bash
curl -fsSL https://raw.githubusercontent.com/QadirAdamson/CWP-Module-MaxMind-GeoIP/main/install.sh | bash
```

Access in CWP Admin Panel: 👉 **Developer → MaxMind GeoIP**

---

## 💻 Server-Wide PHP Usage

Any PHP application or site hosted on the server can query geolocation locally:

```php
<?php
require_once '/usr/share/MaxMindGeoIP/maxmind_geoip.php';

$geoip = maxmind_geoip('8.8.8.8');

echo "Country: " . $geoip['country_name'] . " (" . $geoip['country_code'] . ")\n";
echo "City:    " . $geoip['city'] . "\n";
echo "ASN:     " . $geoip['asn'] . " " . $geoip['org'] . "\n";
```

---

## 🗑️ Uninstallation

Run on your CWP server as `root`:

```bash
curl -fsSL https://raw.githubusercontent.com/QadirAdamson/CWP-Module-MaxMind-GeoIP/main/uninstall.sh | bash
```

---

## 📄 License

This project is open-source and licensed under the [MIT License](LICENSE).
