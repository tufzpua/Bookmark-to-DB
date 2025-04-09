<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['dbHost']) || empty($data['dbName']) || empty($data['dbUser'])) {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных для подключения']);
    exit;
}

try {
    $dsn = "mysql:host={$data['dbHost']};port={$data['dbPort']};dbname={$data['dbName']};charset=utf8mb4";
    $pdo = new PDO($dsn, $data['dbUser'], $data['dbPass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Проверка существования таблицы
    $tableExists = $pdo->query("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = '{$data['dbName']}' 
        AND table_name = '{$data['dbTable']}'
    ")->fetchColumn();
    
    if (!$tableExists) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Таблица не существует, но подключение успешно'
        ]);
        exit;
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Подключение успешно установлено'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка подключения: ' . $e->getMessage()
    ]);
}
?>
