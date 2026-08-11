<?php
declare(strict_types=1);

/*
 * London TfL Route Planner V11
 * Secure server-side proxy for TfL StopPoint requests.
 *
 * IMPORTANT:
 * Put the TfL credential in the VPS environment, NOT in this file and NOT
 * in index.html.
 *
 * Required environment variable:
 *   TFL_APP_KEY
 *
 * Optional:
 *   TFL_APP_SECRET
 *
 * TfL's StopPoint endpoint uses the subscription key (app_key). If your
 * TfL account provides another credential for a different API product,
 * keep that credential server-side too and adapt the upstream request
 * accordingly. Never send secrets to the browser.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lon = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lon === false || $lat === null || $lon === null ||
    $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$appKey = getenv('TFL_APP_KEY') ?: '';

if ($appKey === '') {
    error_log('TfL proxy: TFL_APP_KEY is not configured.');
    http_response_code(500);
    echo json_encode(['error' => 'TfL service is not configured on the server.']);
    exit;
}

$query = http_build_query([
    'lat' => (string)$lat,
    'lon' => (string)$lon,
    'radius' => '3000',
    'stopTypes' => 'NaptanMetroStation,NaptanRailStation',
    'modes' => 'tube,dlr,overground,elizabeth-line',
    'useStopPointHierarchy' => 'true',
    'returnLines' => 'true',
    'app_key' => $appKey,
]);

$url = 'https://api.tfl.gov.uk/StopPoint?' . $query;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'User-Agent: London-TfL-Route-Planner/11.0'
    ],
]);

$body = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($body === false || $error !== '') {
    error_log('TfL proxy curl error: ' . $error);
    http_response_code(502);
    echo json_encode(['error' => 'Unable to reach TfL right now.']);
    exit;
}

if ($status < 200 || $status >= 300) {
    error_log('TfL proxy upstream HTTP ' . $status);
    http_response_code($status === 401 || $status === 403 ? 502 : $status);
    echo json_encode([
        'error' => 'TfL returned an upstream error.',
        'status' => $status
    ]);
    exit;
}

echo $body;
