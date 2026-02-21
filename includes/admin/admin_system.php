<!-- Dangerous Operations -->
<div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6 mb-8">
    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-4">⚠️ Опасные операции</h3>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
            <h4 class="font-medium text-red-700 dark:text-red-300 mb-2">Инициализация БД</h4>
            <p class="text-sm text-red-600 dark:text-red-400 mb-3">
                Пересоздаёт файл базы данных. Все записи о загрузках будут удалены. Файлы в папке Accounts не удаляются.
            </p>
            <form method="post" class="inline" onsubmit="return confirm('Вы уверены? Все записи в БД будут удалены.');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                <input type="hidden" name="action" value="reinit_db">
                <label class="flex items-center gap-2 mb-3">
                    <input type="checkbox" name="confirm" value="1" class="rounded border-red-300 dark:border-red-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Я понимаю, что все данные в БД будут удалены</span>
                </label><br>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                    Выполнить инициализацию БД
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
            <h4 class="font-medium text-red-700 dark:text-red-300 mb-2">Очистка папки Accounts</h4>
            <p class="text-sm text-red-600 dark:text-red-400 mb-3">
                Удаляет все файлы из папки загрузок. Записи в БД остаются (история будет ссылаться на несуществующие файлы).
            </p>
            <form method="post" class="inline" onsubmit="return confirm('Вы уверены? Все файлы в папке Accounts будут удалены.');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                <input type="hidden" name="action" value="clear_accounts">
                <label class="flex items-center gap-2 mb-3">
                    <input type="checkbox" name="confirm" value="1" class="rounded border-red-300 dark:border-red-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Я понимаю, что все файлы в папке будут удалены</span>
                </label><br>
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                    Очистить папку Accounts
                </button>
            </form>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h4 class="font-medium text-blue-700 dark:text-blue-300 mb-2">Пересчитать формулы</h4>
            <p class="text-sm text-blue-600 dark:text-blue-400 mb-3">
                Обновляет поле «Ссылка для Google Таблицы» для всех записей по текущему BASE_URL.
            </p>
            <form method="post" class="inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                <input type="hidden" name="action" value="recalc_formulas">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
                    Пересчитать формулы
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">📊 Статистика</h3>
    <div class="flex flex-wrap items-center gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">
            <span class="text-blue-800 dark:text-blue-200 font-medium">Всего файлов:</span>
            <span class="text-blue-900 dark:text-blue-100 font-bold text-lg ml-2"><?php echo $stats['total_count']; ?></span>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-lg">
            <span class="text-green-800 dark:text-green-200 font-medium">Общий размер:</span>
            <span class="text-green-900 dark:text-green-100 font-bold text-lg ml-2"><?php echo formatFileSize($stats['total_size']); ?></span>
        </div>
        <a href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['export' => 'csv']))); ?>" 
           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Скачать CSV
        </a>
        <a href="?download_db=1" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Скачать БД
        </a>
    </div>
</div>
