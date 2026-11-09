# MaxMind GeoIP Module for Control Web Panel (CWP)

A native MaxMind GeoLite2 IP lookup and geolocation module designed for [Control Web Panel (CWP)](https://control-webpanel.com/).

---

## ✨ Features

- **Local Geolocation**: Perform fast local IP lookups without external API limits or extra costs.
- **Comprehensive IP Data**: View country, city, postal code, timezone, ISP, and coordinates.
- **Interactive Map**: Preview locations directly on an embedded map with navigation links.
- **Bulk IP Lookup**: Analyze multiple IPv4 and IPv6 addresses at once in a searchable table.
- **Server-Wide PHP Support**: Access geolocation functions in any website hosted on your server.
- **Database Updates**: Easily update geolocation databases directly from the panel.

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
// print_r($geoip);
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
