<?php
/**
 * MaxMind GeoIP Module for Control Web Panel (CWP)
 * High-Performance MMDB Lookup & PHP Integration Guide
 */

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

$last_updated_time = null;
foreach ($db_paths as $file_path) {
    if (file_exists($file_path)) {
        $mtime = filemtime($file_path);
        if ($mtime && ($last_updated_time === null || $mtime > $last_updated_time)) {
            $last_updated_time = $mtime;
        }
    }
}
$last_updated_str = $last_updated_time ? date('Y-m-d H:i:s', $last_updated_time) : 'Never';

$client_ipv4 = '';
$client_ipv6 = '';

$ip_candidates = [
    $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
    $_SERVER['HTTP_X_REAL_IP'] ?? null,
    $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
    $_SERVER['REMOTE_ADDR'] ?? null,
];

foreach ($ip_candidates as $cand) {
    if (!$cand) continue;
    $parts = explode(',', $cand);
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '127.0.0.1' || $p === '::1') continue;
        if (filter_var($p, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && empty($client_ipv4)) {
            $client_ipv4 = $p;
        } elseif (filter_var($p, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && empty($client_ipv6)) {
            $client_ipv6 = $p;
        }
    }
}

if (empty($client_ipv4) && empty($client_ipv6)) {
    $client_ipv4 = '8.8.8.8';
}

$initial_ip = !empty($client_ipv4) ? $client_ipv4 : $client_ipv6;
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style type="text/css">
.pdns-card {
    background: #fff;
    border: 1px solid #dcdfe6;
    border-radius: 6px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    margin-bottom: 25px;
}
.pdns-table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #e4e7ed;
    font-size: 13px;
}
.pdns-table thead tr {
    background: #f5f7fa;
}
.pdns-table thead th {
    padding: 10px 12px;
    font-weight: 700;
    color: #495057;
    border: 1px solid #e4e7ed;
    vertical-align: middle;
}
.pdns-table tbody tr {
    transition: background 0.1s ease;
}
.pdns-table tbody tr:hover {
    background: #f9fbfd !important;
}
.pdns-table tbody tr:nth-child(even) {
    background: #fafbfd;
}
.pdns-table tbody td {
    padding: 9px 12px;
    border: 1px solid #e4e7ed;
    color: #333;
    vertical-align: middle;
}
#geoMap {
    height: 235px;
    width: 100%;
    border-radius: 4px;
    border: 1px solid #dcdfe6;
}
.flag-icon {
    display: inline-block;
    width: 22px;
    vertical-align: middle;
    margin-right: 6px;
    border-radius: 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.btn-action-view {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    background: #2f80ed !important;
    color: #ffffff !important;
    border: none;
    border-radius: 4px;
    text-decoration: none !important;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}
.btn-action-view i {
    color: #ffffff !important;
    margin-right: 4px;
}
.btn-action-view:hover {
    background: #1b5fc0 !important;
    color: #ffffff !important;
}
.nav-tab-link {
    padding: 10px 18px;
    display: block;
    text-decoration: none;
    border-radius: 4px 4px 0 0;
    font-weight: bold;
    cursor: pointer;
}
.nav-tab-link.active {
    background: #2f80ed !important;
    color: #fff !important;
}
.nav-tab-link.active i {
    color: #ffffff !important;
}
.nav-tab-link.inactive {
    background: #f8f9fa !important;
    color: #333 !important;
    border: 1px solid #ddd !important;
    border-bottom: none !important;
}
</style>

<div class="row" style="margin-bottom:20px;">
    <div class="col-md-12">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; padding:0 2px;">
            <div style="display:flex; align-items:center; flex-wrap:wrap; gap:12px;">
                <h3 style="margin:0; font-size:20px; font-weight:bold; color:#17283a; display:inline-flex; align-items:center;">
                    <i class="icon16 icomoon-icon-earth" style="color:#2f80ed; margin-right:6px;"></i> MaxMind GeoIP
                </h3>
                <span style="display:inline-flex; align-items:center; gap:5px; background:#f4f6f9; border:1px solid #e2e8f0; border-radius:4px; padding:4px 10px; font-size:12px; color:#64748b;">
                    <i class="icon12 icomoon-icon-clock" style="color:#64748b;"></i>
                    <span>Last Updated: <strong style="color:#334155; font-weight:600;"><?= htmlspecialchars($last_updated_str) ?></strong></span>
                </span>
            </div>
            <div>
                <button type="button" id="btnOneClickUpdate" onclick="triggerDBUpdate()" class="btn btn-success" style="background:#28a745; border:none; padding:7px 16px; font-weight:bold; cursor:pointer; border-radius:4px; display:inline-flex; align-items:center; gap:6px;">
                    <i class="icon16 icomoon-icon-download"></i> <span id="btnUpdateText">Update Database</span>
                </button>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs" style="margin-bottom:20px; border-bottom:2px solid #ddd; display:flex; list-style:none; padding:0;">
    <li style="margin-right:8px;">
        <a id="tab-btn-single" class="nav-tab-link active" onclick="showTab('single')">
            <i class="icon16 icomoon-icon-earth"></i> Single IP Lookup
        </a>
    </li>
    <li style="margin-right:8px;">
        <a id="tab-btn-bulk" class="nav-tab-link inactive" onclick="showTab('bulk')">
            <i class="icon16 icomoon-icon-list"></i> Bulk IP Lookup
        </a>
    </li>
    <li style="margin-right:8px;">
        <a id="tab-btn-docs" class="nav-tab-link inactive" onclick="showTab('docs')">
            <i class="icon16 icomoon-icon-file"></i> Documentation
        </a>
    </li>
</ul>

<div class="tab-content-area" id="tab-single">
    <div class="pdns-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:8px;">
            <h4 style="margin:0; font-size:16px; font-weight:700; color:#17283a;">
                <i class="icon16 icomoon-icon-search"></i> Query IP Address
            </h4>
            <div style="display:inline-flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <button type="button" id="btn_my_ipv6" class="btn btn-default btn-xs" 
                        style="border: 1px solid #ced4da; font-weight:600; <?= empty($client_ipv6) ? 'display:none;' : '' ?>" 
                        onclick="lookupClientIP($('#val_my_ipv6').text())">
                    My IPv6: <span id="val_my_ipv6"><?= htmlspecialchars($client_ipv6) ?></span>
                </button>
                <button type="button" id="btn_my_ipv4" class="btn btn-default btn-xs" 
                        style="border: 1px solid #ced4da; font-weight:600; <?= empty($client_ipv4) ? 'display:none;' : '' ?>" 
                        onclick="lookupClientIP($('#val_my_ipv4').text())">
                    My IP: <span id="val_my_ipv4"><?= htmlspecialchars($client_ipv4) ?></span>
                </button>
            </div>
        </div>

        <form id="singleLookupForm" onsubmit="event.preventDefault(); executeLookup();">
            <div class="input-group" style="margin-bottom: 20px;">
                <input type="text" id="target_ip" class="form-control input-lg" 
                       placeholder="Enter IPv4 or IPv6" 
                       value="<?= htmlspecialchars($initial_ip) ?>" style="font-size: 15px; font-weight: bold; height:42px;">
                <span class="input-group-btn">
                    <button class="btn btn-primary btn-lg" type="submit" id="btnLookup" style="background:#2f80ed; border-color:#2f80ed; height:42px; font-weight:bold; padding:0 22px;">
                        Lookup Details
                    </button>
                </span>
            </div>
        </form>

        <div id="lookupSpinner" style="display: none; text-align: center; padding: 25px;">
            <i class="icon24 icomoon-icon-spinner-2 animate-spin"></i> Resolving IP details...
        </div>

        <div id="lookupResult" style="display: none;">
            <div class="row">
                
                <div class="col-md-6 col-sm-12">
                    <table class="pdns-table" style="margin-bottom:0;">
                        <tbody>
                            <tr>
                                <th style="width: 38%; background:#f8fafc; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; padding:10px 14px; border-bottom:1px solid #e2e8f0;">IP Address</th>
                                <td style="padding:10px 14px; font-weight:700; color:#17283a; font-size:13px; border-bottom:1px solid #e2e8f0;" id="res_ip">-</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; padding:10px 14px; border-bottom:1px solid #e2e8f0;">Country</th>
                                <td style="padding:10px 14px; font-weight:600; color:#17283a; font-size:13px; border-bottom:1px solid #e2e8f0;" id="res_country">-</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; padding:10px 14px; border-bottom:1px solid #e2e8f0;">City / Region</th>
                                <td style="padding:10px 14px; font-weight:600; color:#17283a; font-size:13px; border-bottom:1px solid #e2e8f0;" id="res_city">-</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; padding:10px 14px; border-bottom:1px solid #e2e8f0;">Postal & Timezone</th>
                                <td style="padding:10px 14px; font-weight:600; color:#17283a; font-size:13px; border-bottom:1px solid #e2e8f0;" id="res_zone">-</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; padding:10px 14px; border-bottom:1px solid #e2e8f0;">ASN & Org</th>
                                <td style="padding:10px 14px; font-weight:600; color:#17283a; font-size:13px; border-bottom:1px solid #e2e8f0;" id="res_asn">-</td>
                            </tr>
                            <tr>
                                <th style="background:#f8fafc; color:#64748b; font-size:12px; font-weight:700; text-transform:uppercase; padding:10px 14px;">Coordinates</th>
                                <td style="padding:10px 14px; font-weight:600; color:#17283a; font-size:13px;" id="res_coords">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6 col-sm-12">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px;">
                        <div style="margin: 0 0 8px; font-size:12px; font-weight: 700; color:#17283a; display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="icon16 icomoon-icon-location" style="color: #2f80ed; margin-right: 5px;"></i> Geographic Location</span>
                            <span style="display:inline-flex; align-items:center; gap:8px;">
                                <a id="googleMapsLink" href="#" target="_blank" rel="noopener noreferrer" style="color: #2f80ed; text-decoration: none; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px;">
                                    <i class="icon12 icomoon-icon-new-tab"></i> Google Maps
                                </a>
                                <small style="color: #64748b; font-weight: normal; font-size: 11px;">OpenStreetMap</small>
                            </span>
                        </div>
                        <div id="geoMap"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-content-area" id="tab-bulk" style="display: none;">
    <div class="pdns-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h4 style="margin:0; font-size:16px; font-weight:700; color:#17283a;">
                <i class="icon16 icomoon-icon-list"></i> Bulk IP Lookup
            </h4>
            <span class="badge" style="background:#17a2b8; color:#fff; font-size:11px; padding:3px 8px;">Max 100 IPs</span>
        </div>

        <p style="color: #6c757d; font-size: 13px;">Enter a list of IP addresses (one IP per line) to analyze together:</p>
        <textarea id="bulk_ips" class="form-control" rows="5" placeholder="8.8.8.8&#10;1.1.1.1&#10;2001:4860:4860::8888&#10;2606:4700:4700::1111" style="font-family:monospace; font-size:13px; margin-bottom:15px;"></textarea>
        <div>
            <button class="btn btn-primary" type="button" onclick="executeBulkLookup()" style="background:#2f80ed; border-color:#2f80ed; font-weight:bold; padding:8px 20px;">
                Run Bulk Lookup
            </button>
        </div>

        <div id="bulkSpinner" style="display: none; text-align: center; padding: 25px;">
            <i class="icon24 icomoon-icon-spinner-2 animate-spin"></i> Processing IP list...
        </div>

        <div id="bulkResultsArea" style="margin-top: 25px; display: none;">
            <table class="pdns-table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>Country</th>
                        <th>City / Region</th>
                        <th>Postal</th>
                        <th>ASN & Organization</th>
                        <th>Timezone</th>
                        <th style="width: 100px; text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody id="bulkTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div class="tab-content-area" id="tab-docs" style="display: none;">
    <div class="pdns-card" style="margin-bottom: 20px;">
        <h4 style="margin:0 0 12px 0; font-size:16px; font-weight:700; color:#17283a;">
            <i class="icon16 icomoon-icon-file"></i> PHP Integration Guide
        </h4>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 15px; line-height: 1.6;">
            To query IP geolocation in any project or website hosted on this server, simply include <code>/usr/share/MaxMindGeoIP/maxmind_geoip.php</code> and call <code>maxmind_geoip($ip)</code>:
        </p>

        <pre style="background:#1e293b; color:#e2e8f0; padding:18px; border-radius:6px; font-family:monospace; font-size:13px; line-height:1.6; overflow-x:auto;">&lt;?php
require_once '/usr/share/MaxMindGeoIP/maxmind_geoip.php';

$geoip = maxmind_geoip('8.8.8.8');

echo "IP:           " . $geoip['ip'] . "\n";
echo "Country:      " . $geoip['country_name'] . " (" . $geoip['country_code'] . ")\n";
echo "City:         " . $geoip['city'] . "\n";
echo "Postal:       " . $geoip['postal'] . "\n";
echo "Coordinates:  " . $geoip['latitude'] . ", " . $geoip['longitude'] . "\n";
echo "Timezone:     " . $geoip['timezone'] . "\n";
echo "ASN / ISP:    " . $geoip['asn'] . " - " . $geoip['org'] . "\n";
?&gt;</pre>
    </div>
</div>

<script type="text/javascript">
var mapInstance = null;
var mapMarker = null;

function getAjaxUrl() {
    return 'geoip_ajax.php';
}

function showTab(tabId) {
    $('.tab-content-area').hide();
    $('.nav-tab-link').removeClass('active').addClass('inactive');
    
    $('#tab-' + tabId).fadeIn(150);
    $('#tab-btn-' + tabId).removeClass('inactive').addClass('active');
}

function lookupClientIP(ip) {
    $('#target_ip').val(ip);
    executeLookup();
}

function getFlagImg(iso) {
    if (!iso || iso === 'LAN' || iso.length !== 2) return '';
    return '<img src="https://flagcdn.com/24x18/' + iso.toLowerCase() + '.png" class="flag-icon" alt="' + iso + '"> ';
}

function executeLookup() {
    var ip = $('#target_ip').val().trim();
    if (!ip) return;

    $('#lookupSpinner').show();
    $('#lookupResult').hide();

    $.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: { ajax_action: 'lookup', ip: ip },
        dataType: 'json',
        success: function(resp) {
            $('#lookupSpinner').hide();
            if (resp.success && resp.data) {
                var d = resp.data;
                $('#res_ip').text(d.ip);
                $('#res_country').html(getFlagImg(d.country_code) + (d.country_name || 'Unknown') + (d.country_code ? ' (' + d.country_code + ')' : ''));
                $('#res_city').text((d.city !== '-' && d.city !== 'Unknown' ? d.city + ', ' : '') + d.subdivision);
                $('#res_zone').text(d.postal + ' | ' + d.timezone);
                $('#res_asn').text(d.asn + ' ' + (d.org !== '-' ? '(' + d.org + ')' : ''));
                
                if (d.latitude && d.longitude) {
                    var gmapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(d.latitude + ',' + d.longitude);
                    var coordsHtml = d.latitude + ', ' + d.longitude + ' <a href="' + gmapsUrl + '" target="_blank" rel="noopener noreferrer" style="margin-left:6px; color:#2f80ed; text-decoration:none; font-size:11px; font-weight:600;"><i class="icon12 icomoon-icon-new-tab"></i> Google Maps</a>';
                    $('#res_coords').html(coordsHtml);
                    $('#googleMapsLink').attr('href', gmapsUrl).show();
                    $('#lookupResult').show();
                    initOrUpdateMap(d.latitude, d.longitude, (d.city !== '-' && d.city !== 'Unknown' ? d.city + ', ' : '') + d.country_name);
                } else {
                    $('#res_coords').text('Not Available');
                    $('#googleMapsLink').hide();
                    $('#lookupResult').show();
                    if (mapInstance) {
                        $('#geoMap').hide();
                    }
                }
            } else {
                alert('Lookup error: ' + (resp.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            $('#lookupSpinner').hide();
            alert('Server communication error: ' + (error || status));
        }
    });
}

function initOrUpdateMap(lat, lng, popupText) {
    $('#geoMap').show();
    setTimeout(function() {
        if (!mapInstance) {
            mapInstance = L.map('geoMap').setView([lat, lng], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapInstance);
            mapMarker = L.marker([lat, lng]).addTo(mapInstance);
        } else {
            mapInstance.setView([lat, lng], 10);
            mapMarker.setLatLng([lat, lng]);
        }
        mapMarker.bindPopup('<b>' + popupText + '</b><br>Lat: ' + lat + ', Lng: ' + lng).openPopup();
        mapInstance.invalidateSize();
    }, 200);
}

function executeBulkLookup() {
    var ips = $('#bulk_ips').val().trim();
    if (!ips) {
        alert('Please enter at least one IP address.');
        return;
    }

    $('#bulkSpinner').show();
    $('#bulkResultsArea').hide();

    $.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: { ajax_action: 'bulk_lookup', ips: ips },
        dataType: 'json',
        success: function(resp) {
            $('#bulkSpinner').hide();
            if (resp.success && resp.data) {
                var tbody = $('#bulkTableBody');
                tbody.empty();
                $.each(resp.data, function(idx, item) {
                    var tr = $('<tr></tr>');
                    tr.append('<td><strong>' + item.ip + '</strong></td>');
                    tr.append('<td>' + getFlagImg(item.country_code) + (item.country_name || '-') + '</td>');
                    tr.append('<td>' + item.city + ' / ' + item.subdivision + '</td>');
                    tr.append('<td>' + item.postal + '</td>');
                    tr.append('<td>' + item.asn + ' ' + (item.org !== '-' ? item.org : '') + '</td>');
                    tr.append('<td>' + item.timezone + '</td>');
                    tr.append('<td style="text-align:center;"><button class="btn-action-view" onclick="inspectSingle(\'' + item.ip + '\')"><i class="icon12 icomoon-icon-location" style="color:#ffffff !important; margin-right:4px;"></i> View</button></td>');
                    tbody.append(tr);
                });
                $('#bulkResultsArea').show();
            } else {
                alert('Operation failed: ' + (resp.message || ''));
            }
        },
        error: function(xhr, status, error) {
            $('#bulkSpinner').hide();
            alert('Server communication error: ' + (error || status));
        }
    });
}

function inspectSingle(ip) {
    $('#target_ip').val(ip);
    showTab('single');
    executeLookup();
}

function triggerDBUpdate() {
    if (!confirm('Download and refresh the latest MaxMind GeoLite2 databases (City, Country, ASN)?')) {
        return;
    }

    var $btn = $('#btnOneClickUpdate');
    var $txt = $('#btnUpdateText');
    $btn.prop('disabled', true).css('opacity', '0.7');
    $txt.text('Updating Databases...');

    $.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: { ajax_action: 'update_databases' },
        dataType: 'json',
        success: function(resp) {
            $btn.prop('disabled', false).css('opacity', '1');
            $txt.text('Update Database');

            if (resp.success) {
                alert('MaxMind databases successfully updated! Page will now reload.');
                window.location.reload();
            } else {
                alert('Update notice: ' + (resp.message || 'Error updating databases'));
            }
        },
        error: function(xhr, status, error) {
            $btn.prop('disabled', false).css('opacity', '1');
            $txt.text('Update Database');
            alert('Update error: ' + (error || status));
        }
    });
}

// Auto-run lookup on initial load and detect client IPs
$(document).ready(function() {
    executeLookup();

    // Detect client IPv4 via browser
    $.ajax({
        url: 'https://api.ipify.org?format=json',
        type: 'GET',
        dataType: 'json',
        timeout: 2500,
        success: function(data) {
            if (data && data.ip) {
                $('#val_my_ipv4').text(data.ip);
                $('#btn_my_ipv4').show();
            }
        }
    });

    // Detect client IPv6 via browser if supported by network
    $.ajax({
        url: 'https://api6.ipify.org?format=json',
        type: 'GET',
        dataType: 'json',
        timeout: 2500,
        success: function(data) {
            if (data && data.ip) {
                $('#val_my_ipv6').text(data.ip);
                $('#btn_my_ipv6').show();
            }
        }
    });
});
</script>
