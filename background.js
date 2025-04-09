browser.browserAction.onClicked.addListener(async (tab) => {
  // Получаем настройки из хранилища
  const settings = await browser.storage.local.get({
    dbHost: 'localhost',
    dbPort: '3306',
    dbName: '',
    dbUser: '',
    dbPass: '',
    dbTable: 'firefox__bookmarks'
  });

  // Валидация настроек
  if (!settings.dbHost || !settings.dbName || !settings.dbUser) {
    browser.notifications.create({
      type: 'basic',
      title: 'Ошибка настроек',
      message: 'Пожалуйста, настройте подключение к БД',
      iconUrl: 'icons/icon-48.png'
    });
    browser.runtime.openOptionsPage();
    return;
  }

  try {
    // Отправляем данные на сервер
    const response = await fetch('http://localhost/url-saver/save_url.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        ...settings,
        url: tab.url,
        title: tab.title,
        timestamp: new Date().toISOString()
      })
    });

    const result = await response.json();

    // Показываем уведомление
    browser.notifications.create({
      type: 'basic',
      title: result.status === 'success' ? 'Успешно!' : 'Ошибка',
      message: result.message,
      iconUrl: result.status === 'success' ? 'icons/icon-48.png' : 'icons/icon-error.png'
    });

  } catch (error) {
    console.error('Ошибка:', error);
    browser.notifications.create({
      type: 'basic',
      title: 'Ошибка соединения',
      message: 'Не удалось подключиться к серверу',
      iconUrl: 'icons/icon-error.png'
    });
  }
});

// Проверка соединения при сохранении настроек
browser.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === "testConnection") {
    testDatabaseConnection(request.settings).then(sendResponse);
    return true;
  }
});

async function testDatabaseConnection(settings) {
  try {
    const response = await fetch('http://localhost/url-saver/test_connection.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(settings)
    });
    return await response.json();
  } catch (error) {
    return { status: 'error', message: 'Ошибка соединения: ' + error.message };
  }
}
