<?php
/**
 * Страница истории загрузок файлов
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

// Проверяем авторизацию
requireAuth();

// Инициализируем БД при первом запуске
initDatabase();

// Получаем параметры фильтрации и пагинации
$page = max(1, intval($_GET['page'] ?? 1));
$projectFilter = trim($_GET['project'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$searchQuery = trim($_GET['search'] ?? '');
$sortBy = $_GET['sort'] ?? 'upload_time';
$sortOrder = $_GET['order'] ?? 'DESC';

// Валидация параметров сортировки
$allowedSort = ['original_name', 'project', 'upload_time', 'file_size'];
$allowedOrder = ['ASC', 'DESC'];
if (!in_array($sortBy, $allowedSort)) $sortBy = 'upload_time';
if (!in_array($sortOrder, $allowedOrder)) $sortOrder = 'DESC';

$filters = [];
if ($projectFilter !== '') $filters['project'] = $projectFilter;
if ($dateFrom !== '') $filters['date_from'] = $dateFrom;
if ($dateTo !== '') $filters['date_to'] = $dateTo;
if ($searchQuery !== '') $filters['search'] = $searchQuery;

// Получаем историю загрузок
// Используем getAdminUploads с фильтром по текущему пользователю
$adminFilters = [
    'username' => getCurrentUser(),
    'project' => $projectFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'search' => $searchQuery
];

$history = getAdminUploads($adminFilters, $sortBy, $sortOrder, $page);

$pageTitle = 'История загрузок - FileDownloader';
?>
<?php include __DIR__ . '/includes/header.php'; ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                История загрузок
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                Просмотр и управление загруженными файлами
            </p>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Фильтры</h3>
            <form method="GET" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Поиск по имени файла
                        </label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="Введите часть имени файла"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                    </div>
                    <div>
                        <label for="project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Проект
                        </label>
                        <select id="project" name="project" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                            <option value="">Все проекты</option>
                            <?php 
                            $projects = getActiveProjects();
                            foreach ($projects as $proj): ?>
                                <option value="<?php echo htmlspecialchars($proj); ?>" <?php echo $projectFilter === $proj ? 'selected' : ''; ?>><?php echo htmlspecialchars($proj); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Дата от
                        </label>
                        <input type="date" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($dateFrom); ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Дата до
                        </label>
                        <input type="date" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($dateTo); ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                            </svg>
                            Применить фильтры
                        </span>
                    </button>
                    <a href="history.php" 
                       class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
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

        <?php if ($history['total'] > 0): ?>
            <!-- Stats -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-blue-800 dark:text-blue-200">
                        <span class="font-medium">Найдено файлов:</span> 
                        <span class="font-bold text-lg"><?php echo $history['total']; ?></span>
                    </div>
                    <?php if ($history['pages'] > 1): ?>
                        <div class="text-blue-700 dark:text-blue-300 mt-2 sm:mt-0">
                            Страница <span class="font-medium"><?php echo $history['current_page']; ?></span> из <span class="font-medium"><?php echo $history['pages']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/7">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'original_name', 'order' => ($sortBy === 'original_name' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                       class="flex items-center hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                        Оригинальное имя
                                        <?php if ($sortBy === 'original_name'): ?>
                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <?php if ($sortOrder === 'ASC'): ?>
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                <?php else: ?>
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/8">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'project', 'order' => ($sortBy === 'project' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                       class="flex items-center hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                        Проект
                                        <?php if ($sortBy === 'project'): ?>
                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <?php if ($sortOrder === 'ASC'): ?>
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                <?php else: ?>
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/8">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'upload_time', 'order' => ($sortBy === 'upload_time' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                       class="flex items-center hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                        Дата загрузки
                                        <?php if ($sortBy === 'upload_time'): ?>
                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <?php if ($sortOrder === 'ASC'): ?>
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                <?php else: ?>
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/8">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'file_size', 'order' => ($sortBy === 'file_size' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'])); ?>" 
                                       class="flex items-center hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                        Размер
                                        <?php if ($sortBy === 'file_size'): ?>
                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <?php if ($sortOrder === 'ASC'): ?>
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                <?php else: ?>
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/8">
                                    Ссылка для Google Таблицы
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/8">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($history['files'] as $file): 
                                $formula = !empty($file['hyperlink_formula']) ? $file['hyperlink_formula'] : generateHyperlinkFormula(generateFileUrl($file['unique_name']), $file['original_name']);
                                $fileExists = fileExistsInAccounts($file['unique_name']);
                            ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-2 py-2">
                                        <div class="flex items-center min-w-0">
                                            <span class="text-xs mr-1 flex-shrink-0"><?php echo getFileIcon($file['original_name']); ?></span>
                                            <span class="text-xs font-medium text-gray-900 dark:text-white truncate" title="<?php echo htmlspecialchars($file['original_name']); ?>">
                                                <?php echo htmlspecialchars(mb_substr($file['original_name'], 0, 150) . (mb_strlen($file['original_name']) > 150? '...' : '')); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2">
                                        <span class="px-1 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 block truncate">
                                            <?php echo htmlspecialchars($file['project']); ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        <?php echo date('d.m.Y H:i', strtotime($file['upload_time'])); ?>
                                    </td>
                                    <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        <?php echo formatFileSize($file['file_size']); ?>
                                    </td>
                                    <td class="px-2 py-2">
                                        <div class="tooltip-container">
                                            <button type="button" 
                                                    class="btn-copy-formula px-1 py-1 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-xs font-medium rounded transition-all duration-200 transform hover:scale-105"
                                                    data-formula="<?php echo htmlspecialchars($formula); ?>"
                                                    title="Нажмите чтобы скопировать формулу">
                                                <span class="flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Копировать
                                                </span>
                                            </button>
                                            <div class="tooltip">
                                                <?php echo htmlspecialchars($formula); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 text-xs">
                                        <?php if ($fileExists): ?>
                                            <a href="<?php echo generateFileUrl($file['unique_name']); ?>" 
                                               target="_blank" 
                                               class="inline-flex items-center px-1 py-1 bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white text-xs font-medium rounded transition-all duration-200 transform hover:scale-105">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                                Открыть
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-1 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-xs font-medium rounded">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Нет
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($history['pages'] > 1): ?>
                <div class="flex justify-center items-center space-x-2 mt-6">
                    <?php if ($history['current_page'] > 1): ?>
                        <?php 
                        $params = $_GET;
                        $params['page'] = $history['current_page'] - 1;
                        $prevUrl = 'history.php?' . http_build_query($params);
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
                    $start = max(1, $history['current_page'] - 2);
                    $end = min($history['pages'], $history['current_page'] + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                        $params = $_GET;
                        $params['page'] = $i;
                        $pageUrl = 'history.php?' . http_build_query($params);
                    ?>
                        <?php if ($i == $history['current_page']): ?>
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

                    <?php if ($history['current_page'] < $history['pages']): ?>
                        <?php 
                        $params = $_GET;
                        $params['page'] = $history['current_page'] + 1;
                        $nextUrl = 'history.php?' . http_build_query($params);
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
        <?php else: ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Файлы не найдены</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    <?php if (!empty($filters)): ?>
                        Попробуйте изменить параметры фильтрации
                    <?php else: ?>
                        У вас пока нет загруженных файлов
                    <?php endif; ?>
                </p>
                <?php if (!empty($filters)): ?>
                    <a href="history.php" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Показать все файлы
                    </a>
                <?php else: ?>
                    <a href="index.php" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Загрузить первый файл
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Copy formula functionality
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('.btn-copy-formula');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const formula = this.getAttribute('data-formula');
                if (formula) {
                    copyToClipboard(formula, 'Формула скопирована в буфер обмена!');
                }
            });
        });
    });
    </script>
<?php include __DIR__ . '/includes/footer.php'; ?>
