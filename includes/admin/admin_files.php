<?php
// Используем переданные переменные
$uploads = $uploads_for_include ?? [];
$filters = $filters_for_include ?? [];
$userList = $userList_for_include ?? [];
$stats = $stats_for_include ?? [];

// Включаем поддержку UTF-8
mb_internal_encoding('UTF-8');
?>

<!-- Фильтры -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">🔍 Фильтры</h3>
    <form method="get" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Пользователь
                </label>
                <select id="username" name="username" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                    <option value="">Все пользователи</option>
                    <?php foreach ($userList as $user): ?>
                        <option value="<?php echo htmlspecialchars($user); ?>" <?php echo $filters['username'] === $user ? 'selected' : ''; ?>><?php echo htmlspecialchars($user); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Проект
                </label>
                <select id="project" name="project" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                    <option value="">Все проекты</option>
                    <?php foreach ($stats['projects'] ?? [] as $project): ?>
                        <option value="<?php echo htmlspecialchars($project); ?>" <?php echo $filters['project'] === $project ? 'selected' : ''; ?>><?php echo htmlspecialchars($project); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Поиск по имени файла
                </label>
                <input type="text" id="search" name="search" 
                       value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="часть имени или уникального имени"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Дата от
                </label>
                <input type="date" id="date_from" name="date_from" 
                       value="<?php echo htmlspecialchars($filters['date_from']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Дата до
                </label>
                <input type="date" id="date_to" name="date_to" 
                       value="<?php echo htmlspecialchars($filters['date_to']); ?>"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011-1V4a1 1 0 00-1 1H3a1 1 0 00-1 1v2.586a1 1 0 00.293.707l10.414 10.414a1 1 0 00.293.707V4a1 1 0 011-1h16a1 1 0 011-1V4z"></path>
                    </svg>
                    Применить фильтры
                </span>
            </button>
            <a href="admin.php" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Сбросить
                </span>
            </a>
        </div>
    </form>
</div>



<!-- Table -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Оригинальное имя
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Уникальное имя
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Пользователь
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Проект
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Дата загрузки
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Размер
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Ссылка для Google Таблицы
                    </th>
                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Действия
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($uploads['files'] as $file): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-2 py-2">
                            <div class="flex items-center min-w-0">
                                <span class="text-xs mr-1 flex-shrink-0">📎</span>
                                <span class="text-xs font-medium text-gray-900 dark:text-white truncate" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                    <?php echo mb_substr(htmlspecialchars($file['original_name']), 0, 30) . (mb_strlen($file['original_name']) > 30 ? '...' : ''); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-1 rounded text-gray-600 dark:text-gray-300 block truncate" title="<?php echo htmlspecialchars($file['unique_name']); ?>">
                                <?php echo mb_substr(htmlspecialchars($file['unique_name']), 0, 20) . (mb_strlen($file['unique_name']) > 20 ? '...' : ''); ?>
                            </code>
                        </td>
                        <td class="px-2 py-2">
                            <span class="px-1 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 block truncate">
                                <?php echo htmlspecialchars($file['username']); ?>
                            </span>
                        </td>
                        <td class="px-2 py-2">
                            <span class="px-1 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 block truncate">
                                <?php echo htmlspecialchars($file['project']); ?>
                            </span>
                        </td>
                        <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            <?php echo date('d.m.Y H:i', strtotime($file['upload_time'])); ?>
                        </td>
                        <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            <?php echo number_format($file['file_size'] / 1024, 2); ?> KB
                        </td>
                        <td class="px-2 py-2">
                            <div class="tooltip-container">
                                <button
                                    onclick="copyFormula('<?php echo htmlspecialchars($file['hyperlink_formula']); ?>')"
                                    class="px-1 py-1 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-xs font-medium rounded transition-all duration-200 transform hover:scale-105"
                                    title="Нажмите чтобы скопировать формулу"
                                >
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2v0a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Копировать
                                    </span>
                                </button>
                                <div class="tooltip">
                                    <?php echo htmlspecialchars($file['hyperlink_formula']); ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <div class="flex space-x-2 flex-wrap">
                                <?php 
                                // Проверяем существование файла
                                $fileExists = fileExistsInAccounts($file['unique_name']);
                                ?>
                                <?php if ($fileExists): ?>
                                    <a href="<?php echo generateFileUrl($file['unique_name']); ?>" 
                                       target="_blank" 
                                       class="px-2 py-1 bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white text-xs font-medium rounded transition-all duration-200 transform hover:scale-105"
                                       title="Открыть файл в новой вкладке">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <a href="?download=<?php echo $file['id']; ?><?php 
                                    // Добавляем текущие параметры фильтрации к ссылке скачивания
                                    $currentParams = $_GET;
                                    unset($currentParams['download'], $currentParams['delete']);
                                    if (!empty($currentParams)) {
                                        echo '&' . http_build_query($currentParams);
                                    }
                                ?>" 
                                   class="px-2 py-1 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-xs font-medium rounded transition-all duration-200 transform hover:scale-105"
                                   title="Скачать файл">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>
                                <button onclick="deleteFile(<?php echo $file['id']; ?>)" 
                                        class="px-2 py-1 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white text-xs font-medium rounded transition-all duration-200 transform hover:scale-105"
                                        title="Удалить файл">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination Info -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-center">
        <div class="text-gray-600 dark:text-gray-400 mb-2 sm:mb-0">
            <span class="font-medium">Найдено файлов:</span> 
            <span class="font-bold text-lg"><?php echo $uploads['total']; ?></span>
        </div>
        <?php if ($uploads['pages'] > 1): ?>
            <div class="text-blue-700 dark:text-blue-300 mt-2 sm:mt-0">
                Страница <span class="font-medium"><?php echo $uploads['current_page']; ?></span> из <span class="font-medium"><?php echo $uploads['pages']; ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($uploads['pages'] > 1): ?>
    <div class="flex justify-center items-center space-x-2 mt-6">
        <?php if ($uploads['current_page'] > 1): ?>
            <?php 
            $params = $_GET;
            $params['page'] = $uploads['current_page'] - 1;
            $prevUrl = 'admin.php?' . http_build_query($params);
            ?>
            <a href="<?php echo htmlspecialchars($prevUrl); ?>" 
               class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                ← Назад
            </a>
        <?php else: ?>
            <span class="px-3 py-2 border rounded-md text-sm font-medium transition-colors bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed">
                ← Назад
            </span>
        <?php endif; ?>

        <?php
        $start = max(1, $uploads['current_page'] - 2);
        $end = min($uploads['pages'], $uploads['current_page'] + 2);
        
        for ($i = $start; $i <= $end; $i++):
            $params = $_GET;
            $params['page'] = $i;
            $pageUrl = 'admin.php?' . http_build_query($params);
        ?>
            <?php if ($i == $uploads['current_page']): ?>
                <span class="px-3 py-2 border rounded-md text-sm font-medium transition-colors bg-blue-600 dark:bg-blue-500 border-blue-600 dark:border-blue-500 text-white">
                    <?php echo $i; ?>
                </span>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($pageUrl); ?>" 
                   class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <?php echo $i; ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($uploads['current_page'] < $uploads['pages']): ?>
            <?php 
            $params = $_GET;
            $params['page'] = $uploads['current_page'] + 1;
            $nextUrl = 'admin.php?' . http_build_query($params);
            ?>
            <a href="<?php echo htmlspecialchars($nextUrl); ?>" 
               class="px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                Вперед →
            </a>
        <?php else: ?>
            <span class="px-3 py-2 border rounded-md text-sm font-medium transition-colors bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 cursor-not-allowed">
                Вперед →
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function deleteFile(fileId) {
    if (confirm('Вы уверены, что хотите удалить этот файл?')) {
        // Создаем форму для POST-запроса с CSRF-токеном
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        // Добавляем CSRF-токен
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?php echo htmlspecialchars(getCSRFToken()); ?>';
        form.appendChild(csrfToken);
        
        // Добавляем действие
        const action = document.createElement('input');
        action.type = 'hidden';
        action.name = 'action';
        action.value = 'delete_file';
        form.appendChild(action);
        
        // Добавляем ID файла
        const fileIdInput = document.createElement('input');
        fileIdInput.type = 'hidden';
        fileIdInput.name = 'file_id';
        fileIdInput.value = fileId;
        form.appendChild(fileIdInput);
        
        // Сохраняем текущие параметры фильтрации
        const params = new URLSearchParams(window.location.search);
        params.delete('delete'); // Убираем старый параметр
        params.delete('page'); // Сбрасываем на первую страницу после удаления
        
        // Добавляем параметры в форму
        params.forEach((value, key) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
