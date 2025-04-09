<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Получаем данные из POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Проверка обязательных полей
if (empty($data['dbHost']) || empty($data['dbName']) || empty($data['dbUser']) || empty($data['url'])) {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных']);
    exit;
}

try {
    // Подключение к базе данных
    $dsn = "mysql:host={$data['dbHost']};port={$data['dbPort']};dbname={$data['dbName']};charset=utf8mb4";
    $pdo = new PDO($dsn, $data['dbUser'], $data['dbPass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Проверка существования URL
    $checkStmt = $pdo->prepare("SELECT ID FROM {$data['dbTable']} WHERE URI = ?");
    $checkStmt->execute([$data['url']]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'exists', 'message' => 'Этот URL уже сохранен']);
        exit;
    }

    // Вставка новой записи
    $insertStmt = $pdo->prepare("
        INSERT INTO {$data['dbTable']} 
        (Title, URI, DATE__Create, DATE__Update) 
        VALUES (:title, :url, CURDATE(), NOW())
    ");
    
    $insertStmt->execute([
        ':title' => $data['title'] ?? '',
        ':url' => $data['url']
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Закладка успешно сохранена',
        'id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>
