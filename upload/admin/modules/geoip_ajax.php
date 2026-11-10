<?php
/**
 * https://github.com/QadirAdamson/CWP-Module-MaxMind-GeoIP
 */

header('Content-Type: application/json; charset=utf-8');

// Authenticate via CWP Admin Session
if (session_status() === PHP_SESSION_NONE) {
    if (is_dir('/root/.cwp_sessions')) {
        session_save_path('/root/.cwp_sessions');
    }
    foreach ($_COOKIE as $k => $v) {
        if (strpos($k, 'cwpsrv-') === 0 || $k === 'PHPSESSID') {
            session_name($k);
            break;
        }
    }
    @session_start();
}

if (empty($_SESSION['username']) || empty($_SESSION['logged'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Valid CWP admin session required.']);
    exit;
}

$default_db_dir = '/usr/share/GeoIP';

function get_db_path($dir, $types = ['City', 'Country', 'ASN']) {
    $paths = [];
    foreach ($types as $type) {
        $candidates = [
            "$dir/GeoLite2-$type.mmdb",
            "$dir/GeoIP2-$type.mmdb",
            "/usr/share/GeoIP/GeoLite2-$type.mmdb",
            "/var/lib/GeoIP/GeoLite2-$type.mmdb"
        ];
        foreach ($candidates as $cand) {
            if (file_exists($cand) && is_readable($cand) && filesize($cand) > 1024) {
                $paths[$type] = $cand;
                break;
            }
        }
    }
    return $paths;
}

$db_paths = get_db_path($default_db_dir);

function query_mmdb_val($db_path, $ip, $field_path = []) {
    if (!$db_path || !file_exists($db_path)) return null;
    $cmd = "/usr/bin/mmdblookup --file " . escapeshellarg($db_path) . " --ip " . escapeshellarg($ip);
    foreach ($field_path as $f) {
        $cmd .= " " . escapeshellarg($f);
    }
    $cmd .= " 2>/dev/null";
    $output = @shell_exec($cmd);
    if (!$output) return null;

    if (preg_match('/^\s*"([^"]+)"\s*<[a-z0-9_\-]+>/m', $output, $m)) {
        return $m[1];
    }
    if (preg_match('/^\s*([0-9\.\-]+)\s*<[a-z0-9_\-]+>/m', $output, $m)) {
        return is_numeric($m[1]) ? (strpos($m[1], '.') !== false ? floatval($m[1]) : intval($m[1])) : $m[1];
    }
    return null;
}

function resolve_ip_geo($ip, $db_paths) {
    $ip = trim($ip);
    $result = [
        'ip' => $ip,
        'valid' => filter_var($ip, FILTER_VALIDATE_IP) ? true : false,
        'country_name' => 'Unknown',
        'country_code' => '',
        'city' => 'Unknown',
        'postal' => '-',
        'subdivision' => '-',
        'latitude' => null,
        'longitude' => null,
        'timezone' => '-',
        'asn' => '-',
        'org' => '-',
        'is_private' => false,
        'error' => null
    ];

    if (!$result['valid']) {
        $result['error'] = 'Invalid IP address';
        return $result;
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        $result['is_private'] = true;
        $result['country_name'] = 'Private / Local Network (RFC 1918)';
        $result['country_code'] = 'LAN';
        return $result;
    }

    $city_db = $db_paths['City'] ?? ($db_paths['Country'] ?? null);
    if ($city_db) {
        $country = query_mmdb_val($city_db, $ip, ['country', 'names', 'en']);
        if (!$country) {
            $country = query_mmdb_val($city_db, $ip, ['registered_country', 'names', 'en']);
        }
        $iso = query_mmdb_val($city_db, $ip, ['country', 'iso_code']);
        if (!$iso) {
            $iso = query_mmdb_val($city_db, $ip, ['registered_country', 'iso_code']);
        }
        $city = query_mmdb_val($city_db, $ip, ['city', 'names', 'en']);
        $subdiv = query_mmdb_val($city_db, $ip, ['subdivisions', '0', 'names', 'en']);
        $postal = query_mmdb_val($city_db, $ip, ['postal', 'code']);
        $lat = query_mmdb_val($city_db, $ip, ['location', 'latitude']);
        $lng = query_mmdb_val($city_db, $ip, ['location', 'longitude']);
        $tz = query_mmdb_val($city_db, $ip, ['location', 'time_zone']);

        if ($country) $result['country_name'] = $country;
        if ($iso) $result['country_code'] = $iso;
        if ($city) $result['city'] = $city;
        if ($subdiv) $result['subdivision'] = $subdiv;
        if ($postal) $result['postal'] = $postal;
        if ($lat !== null) $result['latitude'] = $lat;
        if ($lng !== null) $result['longitude'] = $lng;
        if ($tz) $result['timezone'] = $tz;
    }

    if (!empty($db_paths['ASN'])) {
        $asn = query_mmdb_val($db_paths['ASN'], $ip, ['autonomous_system_number']);
        $org = query_mmdb_val($db_paths['ASN'], $ip, ['autonomous_system_organization']);

        if ($asn) $result['asn'] = 'AS' . $asn;
        if ($org) $result['org'] = $org;
    }

    return $result;
}

$action = $_REQUEST['ajax_action'] ?? '';

if ($action === 'lookup') {
    $ip = trim($_POST['ip'] ?? '');
    $data = resolve_ip_geo($ip, $db_paths);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'bulk_lookup') {
    $bulk_text = trim($_POST['ips'] ?? '');
    $lines = preg_split('/[\r\n,;\s]+/', $bulk_text, -1, PREG_SPLIT_NO_EMPTY);
    $results = [];
    $count = 0;
    foreach ($lines as $line) {
        if ($count >= 100) break;
        if (filter_var($line, FILTER_VALIDATE_IP)) {
            $results[] = resolve_ip_geo($line, $db_paths);
            $count++;
        }
    }
    echo json_encode(['success' => true, 'count' => count($results), 'data' => $results]);
    exit;
}

if ($action === 'update_databases') {
    $updater_script = '/usr/local/cwpsrv/htdocs/resources/admin/modules/geoip_lib/update_geoip.sh';
    if (file_exists($updater_script)) {
        $cmd = "bash " . escapeshellarg($updater_script) . " 2>&1";
        $out = @shell_exec($cmd);
        echo json_encode([
            'success' => true,
            'log' => explode("\n", trim($out)),
            'message' => 'GeoIP databases update process finished.'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Updater script not found.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
