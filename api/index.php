<?php
/**
 * REST API Entry Point
 * Handles /api/{module} endpoints
 */

// Autoload
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../core/' . $class . '.php',
        __DIR__ . '/../models/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Boot config & DB
$config = require __DIR__ . '/../config/app.php';
date_default_timezone_set($config['timezone']);
ErrorHandler::register($config['debug']);

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Strip base path for subfolder deployment
$basePath = rtrim($config['base_path'] ?? '', '/');
if ($basePath && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}
$parts = explode('/', trim(str_replace('/api', '', $uri), '/'));
$module = $parts[0] ?? '';
$id = $parts[1] ?? null;

// Module => Model mapping
$moduleMap = [
    'farmers' => 'Farmer',
    'land' => 'Land',
    'plantings' => 'Planting',
    'inputs' => 'Input',
    'crews' => 'Crew',
    'packing' => 'Packing',
    'inventory' => 'Inventory',
    'orders' => 'Order',
    'customers' => 'Customer',
    'transactions' => 'Transaction',
];

if (!isset($moduleMap[$module])) {
    http_response_code(404);
    echo json_encode(['error' => 'Unknown module', 'available' => array_keys($moduleMap)]);
    exit;
}

$modelClass = $moduleMap[$module];
$model = new $modelClass();

// Money fields per module (field => currency_code source)
$moneyFields = [
    'inputs' => ['cost'],
    'crews' => ['daily_wage'],
    'packing' => ['packing_cost'],
    'inventory' => ['unit_value'],
    'orders' => ['total_amount'],
    'customers' => ['credit_limit'],
    'transactions' => ['amount'],
];

/**
 * Enrich a record with currency display fields
 */
function enrichWithCurrency(array $record, string $module, array $moneyFields): array
{
    $fields = $moneyFields[$module] ?? [];
    $code = $record['currency_code'] ?? Currency::getDefault();
    foreach ($fields as $field) {
        if (isset($record[$field])) {
            $record[$field . '_display'] = Currency::format((float) $record[$field], $code);
            $record[$field . '_currency'] = $code;
        }
    }
    return $record;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $record = $model->findById((int) $id);
                if (!$record) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Record not found']);
                } else {
                    $record = enrichWithCurrency($record, $module, $moneyFields);
                    echo json_encode(['data' => $record]);
                }
            } else {
                $records = $model->findAll();
                $records = array_map(fn($r) => enrichWithCurrency($r, $module, $moneyFields), $records);
                echo json_encode(['data' => $records, 'count' => count($records)]);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (empty($input)) {
                http_response_code(400);
                echo json_encode(['error' => 'No data provided']);
                break;
            }
            // Default currency_code if not provided
            if (isset($moneyFields[$module]) && !isset($input['currency_code'])) {
                $input['currency_code'] = Currency::getDefault();
            }
            $newId = $model->create($input);
            http_response_code(201);
            echo json_encode(['message' => 'Created', 'id' => $newId, 'currency_code' => $input['currency_code'] ?? null]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required for update']);
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input)) {
                http_response_code(400);
                echo json_encode(['error' => 'No data provided']);
                break;
            }
            $model->update((int) $id, $input);
            echo json_encode(['message' => 'Updated', 'id' => (int) $id]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID required for delete']);
                break;
            }
            $model->delete((int) $id);
            echo json_encode(['message' => 'Deleted', 'id' => (int) $id]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $config['debug'] ? $e->getMessage() : 'Internal error']);
}

