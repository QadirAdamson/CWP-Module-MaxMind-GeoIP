<?php
/**
 * Global MaxMind GeoIP Helper for Control Web Panel (CWP)
 * Location: /usr/share/MaxMindGeoIP/maxmind_geoip.php
 *
 * Usage in any PHP project on this server:
 *   require_once '/usr/share/MaxMindGeoIP/maxmind_geoip.php';
 *   $geoip = maxmind_geoip('8.8.8.8');
 */

if (!function_exists('maxmind_geoip')) {
    function maxmind_geoip($ip) {
        $ip = trim((string)$ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        $res = [
            'ip'           => $ip,
            'country_name' => 'Unknown',
            'country_code' => '',
            'city'         => '',
            'postal'       => '',
            'latitude'     => null,
            'longitude'    => null,
            'timezone'     => '',
            'asn'          => '',
            'org'          => '',
        ];

        // 1. Resolve City / Country / Coords via City MMDB
        $cityDb = file_exists('/usr/share/MaxMindGeoIP/GeoLite2-City.mmdb')
            ? '/usr/share/MaxMindGeoIP/GeoLite2-City.mmdb'
            : '/usr/share/GeoIP/GeoLite2-City.mmdb';

        $phar = file_exists('/usr/share/MaxMindGeoIP/geoip2.phar')
            ? '/usr/share/MaxMindGeoIP/geoip2.phar'
            : '/usr/share/GeoIP/geoip2.phar';

        if (file_exists($cityDb) && is_readable($cityDb)) {
            if (file_exists($phar) && extension_loaded('phar')) {
                try {
                    require_once $phar;
                    $reader = new \GeoIp2\Database\Reader($cityDb);
                    $record = $reader->city($ip);

                    $res['country_name'] = $record->country->name ?? 'Unknown';
                    $res['country_code'] = $record->country->isoCode ?? '';
                    $res['city']         = $record->city->name ?? '';
                    $res['postal']       = $record->postal->code ?? '';
                    $res['latitude']     = $record->location->latitude ?? null;
                    $res['longitude']    = $record->location->longitude ?? null;
                    $res['timezone']     = $record->location->timeZone ?? '';
                } catch (\Exception $e) {
                    // Fallback to native mmdblookup
                }
            }

            // Fallback via native mmdblookup if phar failed or fields empty
            if ($res['country_name'] === 'Unknown' && is_executable('/usr/bin/mmdblookup')) {
                $cmd = sprintf('/usr/bin/mmdblookup --file %s --ip %s 2>/dev/null', escapeshellarg($cityDb), escapeshellarg($ip));
                $raw = @shell_exec($cmd);
                if ($raw) {
                    if (preg_match('/"country"\s*<map>\s*.*?names"\s*<map>\s*.*?"en":\s*"([^"]+)"/s', $raw, $m)) $res['country_name'] = $m[1];
                    if (preg_match('/"country"\s*<map>\s*.*?"iso_code":\s*"([^"]+)"/s', $raw, $m)) $res['country_code'] = $m[1];
                    if (preg_match('/"city"\s*<map>\s*.*?names"\s*<map>\s*.*?"en":\s*"([^"]+)"/s', $raw, $m)) $res['city'] = $m[1];
                    if (preg_match('/"postal"\s*<map>\s*.*?"code":\s*"([^"]+)"/s', $raw, $m)) $res['postal'] = $m[1];
                    if (preg_match('/"latitude":\s*([0-9\.\-]+)/', $raw, $m)) $res['latitude'] = (float)$m[1];
                    if (preg_match('/"longitude":\s*([0-9\.\-]+)/', $raw, $m)) $res['longitude'] = (float)$m[1];
                    if (preg_match('/"time_zone":\s*"([^"]+)"/', $raw, $m)) $res['timezone'] = $m[1];
                }
            }
        }

        // 2. Resolve ASN & Organization if ASN MMDB exists
        $asnDb = file_exists('/usr/share/MaxMindGeoIP/GeoLite2-ASN.mmdb')
            ? '/usr/share/MaxMindGeoIP/GeoLite2-ASN.mmdb'
            : '/usr/share/GeoIP/GeoLite2-ASN.mmdb';

        if (file_exists($asnDb) && is_readable($asnDb)) {
            if (file_exists($phar) && extension_loaded('phar')) {
                try {
                    require_once $phar;
                    $asnReader = new \GeoIp2\Database\Reader($asnDb);
                    $asnRec = $asnReader->asn($ip);
                    if (!empty($asnRec->autonomousSystemNumber)) {
                        $res['asn'] = 'AS' . $asnRec->autonomousSystemNumber;
                    }
                    if (!empty($asnRec->autonomousSystemOrganization)) {
                        $res['org'] = $asnRec->autonomousSystemOrganization;
                    }
                } catch (\Exception $e) {
                    // Fallback
                }
            }

            if (empty($res['asn']) && is_executable('/usr/bin/mmdblookup')) {
                $cmd = sprintf('/usr/bin/mmdblookup --file %s --ip %s 2>/dev/null', escapeshellarg($asnDb), escapeshellarg($ip));
                $raw = @shell_exec($cmd);
                if ($raw) {
                    if (preg_match('/"autonomous_system_number":\s*([0-9]+)/', $raw, $m)) $res['asn'] = 'AS' . $m[1];
                    if (preg_match('/"autonomous_system_organization":\s*"([^"]+)"/', $raw, $m)) $res['org'] = $m[1];
                }
            }
        }

        return $res;
    }
}
