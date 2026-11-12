# MaxMind GeoIP Module for Control Web Panel (CWP)

MaxMind GeoLite2 IP lookup and geolocation module for [Control Web Panel (CWP)](https://control-webpanel.com/).

---

![Screenshot](screenshot.png)

---

## Features

- Local IP geolocation lookups
- Detailed location and network data
- Single and bulk IP address analysis
- Server-wide PHP helper function
- Database update management

---

## Database

This module uses free [MaxMind GeoLite2](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data) databases (`City`, `Country`, `ASN`) in `.mmdb` format and is fully compatible with commercial MaxMind GeoIP2 databases. Updates are performed automatically without requiring an API key.

---

## Installation

Run on your CWP server as `root`:

```bash
curl -fsSL https://raw.githubusercontent.com/QadirAdamson/CWP-Module-MaxMind-GeoIP/main/install.sh | bash
```

Access in CWP Admin Panel: **Developer → MaxMind GeoIP**

---

## Usage

Any PHP application on the server can query geolocation locally:

```php
<?php
require_once '/usr/share/MaxMindGeoIP/maxmind_geoip.php';
$geoip = maxmind_geoip('8.8.8.8');
// print_r($geoip);
```

---

## Uninstallation

Run on your CWP server as `root`:

```bash
curl -fsSL https://raw.githubusercontent.com/QadirAdamson/CWP-Module-MaxMind-GeoIP/main/uninstall.sh | bash
```

---

## License

MIT License
