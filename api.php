<?php
/**
 * MO2 Database Viewer - Server API v2
 * Handles configuration persistence
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$CONFIG_DIR = __DIR__ . '/config/';
if (!is_dir($CONFIG_DIR)) mkdir($CONFIG_DIR, 0755, true);

$VALID_CONFIGS = [
    'icon_overrides' => 'icon_overrides.json',
    'hidden_fields' => 'hidden_fields.json',
    'field_overrides' => 'field_overrides.json',
    'favorites' => 'favorites.json'
];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'load': loadConfig(); break;
    case 'save': saveConfig(); break;
    case 'load_all': loadAllConfigs(); break;
    case 'ping': echo json_encode(['success' => true, 'server' => 'online', 'time' => date('c')]); break;
    default: jsonError('Invalid action');
}

function loadConfig() {
    global $CONFIG_DIR, $VALID_CONFIGS;
    $type = $_GET['type'] ?? '';
    if (!isset($VALID_CONFIGS[$type])) { jsonError('Invalid config type'); return; }
    $filepath = $CONFIG_DIR . $VALID_CONFIGS[$type];
    if (file_exists($filepath)) {
        $data = json_decode(file_get_contents($filepath), true);
        jsonSuccess($data['data'] ?? $data);
    } else {
        jsonSuccess([]);
    }
}

function saveConfig() {
    global $CONFIG_DIR, $VALID_CONFIGS;
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { jsonError('Invalid JSON'); return; }
    $type = $input['type'] ?? '';
    $config = $input['config'] ?? null;
    if (!isset($VALID_CONFIGS[$type])) { jsonError('Invalid config type'); return; }
    if ($config === null) { jsonError('No config data'); return; }
    
    $saveData = ['_meta' => ['updated' => date('c'), 'type' => $type], 'data' => $config];
    $result = file_put_contents($CONFIG_DIR . $VALID_CONFIGS[$type], json_encode($saveData, JSON_PRETTY_PRINT));
    if ($result === false) jsonError('Write failed');
    else jsonSuccess(['saved' => true]);
}

function loadAllConfigs() {
    global $CONFIG_DIR, $VALID_CONFIGS;
    $configs = [];
    foreach ($VALID_CONFIGS as $type => $filename) {
        $filepath = $CONFIG_DIR . $filename;
        if (file_exists($filepath)) {
            $data = json_decode(file_get_contents($filepath), true);
            $configs[$type] = $data['data'] ?? $data;
        } else {
            $configs[$type] = [];
        }
    }
    jsonSuccess($configs);
}

function jsonSuccess($data) { echo json_encode(['success' => true, 'data' => $data]); }
function jsonError($msg) { http_response_code(400); echo json_encode(['success' => false, 'error' => $msg]); }