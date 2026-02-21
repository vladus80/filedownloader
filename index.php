<?php
/**
 * Главная страница - форма загрузки файлов
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

initDatabase();
requireAuth();

// Получаем проекты для формы
$projects = getActiveProjects();

if (isset($_GET['logout'])) {
    logout();
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';
$formula = '';
$fileUrl = '';

if (isset($_GET['success']) && isset($_GET['formula'])) {
    $message = 'Файл успешно загружен!';
    $messageType = 'success';
    $formula = urldecode($_GET['formula']);
    $fileUrl = isset($_GET['url']) ? urldecode($_GET['url']) : '';
}
if (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $messageType = 'error';
}

$pageTitle = 'Загрузка файлов - FileDownloader';
?>
<?php include __DIR__ . '/includes/header.php'; ?>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                Загрузка файлов
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                Загрузите файлы для вашего проекта и получите формулу для вставки в Google Таблицы
            </p>
        </div>

        <!-- Message Block -->
        <?php if ($message): ?>
            <div class="mb-6 animate-fade-in">
                <div class="rounded-lg p-4 <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($messageType === 'success'): ?>
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            <?php else: ?>
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
            <div class="p-6">
                <form id="upload-form" method="POST" action="upload.php" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCSRFToken()); ?>">
                    <!-- Project Selection -->
                    <div>
                        <label for="project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Выберите проект
                        </label>
                        <select id="project" name="project" required 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white transition-colors">
                            <option value="">-- Выберите проект --</option>
                            <?php foreach ($projects as $proj): ?>
                                <option value="<?php echo htmlspecialchars($proj); ?>"><?php echo htmlspecialchars($proj); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- File Upload Area -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Выберите файл для загрузки
                        </label>
                        <div id="drop-zone" class="drop-zone border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-blue-500 dark:hover:border-blue-400 transition-colors cursor-pointer bg-gray-50 dark:bg-gray-700/50">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="text-lg text-gray-600 dark:text-gray-400 mb-2">
                                Перетащите файл сюда или нажмите для выбора
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                Максимальный размер: <?php echo (int)(MAX_FILE_SIZE / (1024 * 1024)); ?> МБ
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500">
                                Разрешенные типы: <?php echo implode(', ', ALLOWED_EXTENSIONS); ?>
                            </p>
                            <input type="file" id="file" name="file" required class="hidden" accept=".<?php echo implode(',.', ALLOWED_EXTENSIONS); ?>">
                        </div>
                        
                        <!-- Selected File Info -->
                        <div id="file-info" class="hidden mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <span id="file-icon" class="text-2xl"></span>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200" id="file-name"></p>
                                    <p class="text-sm text-blue-600 dark:text-blue-300" id="file-size"></p>
                                </div>
                                <button type="button" id="clear-file" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar Container -->
                    <div id="progress-container" class="hidden">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-2 overflow-hidden">
                            <div id="progress-bar" class="progress-bar bg-blue-600 dark:bg-blue-400 h-full rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="text-center text-sm text-gray-600 dark:text-gray-400">
                            <span id="progress-text">0%</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center">
                        <button type="submit" id="submit-btn" 
                                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Загрузить файл
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Result Section -->
        <div id="result-section" class="hidden animate-slide-up">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <svg class="h-6 w-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Файл успешно загружен!
                        </h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Формула для вставки в Google Таблицу:
                            </label>
                            <div class="relative">
                                <input type="text" id="formulaInput" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 text-green-700 dark:text-green-400 font-mono text-sm" 
                                       value="<?php echo htmlspecialchars($formula); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            <button type="button" id="btn-copy-formula" 
                                    class="flex-1 px-3 py-2 sm:px-4 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105 text-xs sm:text-sm">
                                <span class="flex items-center justify-center">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Скопировать формулу</span>
                                    <span class="sm:hidden">Формулу</span>
                                </span>
                            </button>
                            
                            <button type="button" id="btn-copy-url" 
                                    class="flex-1 px-3 py-2 sm:px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105 text-xs sm:text-sm">
                                <span class="flex items-center justify-center">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Скопировать ссылку</span>
                                    <span class="sm:hidden">Ссылку</span>
                                </span>
                            </button>
                            
                            <a href="#" id="btn-open-file" target="_blank" 
                               class="hidden flex-1 px-3 py-2 sm:px-4 bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105 text-xs sm:text-sm">
                                <span class="flex items-center justify-center">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Открыть файл</span>
                                    <span class="sm:hidden">Файл</span>
                                </span>
                            </a>
                            
                            <a href="history.php" 
                               class="flex-1 px-3 py-2 sm:px-4 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-lg shadow-sm transition-all duration-200 transform hover:scale-105 text-xs sm:text-sm">
                                <span class="flex items-center justify-center">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="hidden sm:inline">Перейти в историю</span>
                                    <span class="sm:hidden">История</span>
                                </span>
                            </a>
                        </div>
                        
                        <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                            💡 Скопируйте формулу выше и вставьте её в нужную ячейку реестра платежей в Google Таблице.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const form = document.getElementById('upload-form');
        const resultSection = document.getElementById('result-section');
        const formulaInput = document.getElementById('formulaInput');
        const btnOpenFile = document.getElementById('btn-open-file');
        const submitBtn = document.getElementById('submit-btn');
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file');
        const fileInfo = document.getElementById('file-info');
        const clearFileBtn = document.getElementById('clear-file');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');

        // Initialize drag and drop
        setupDragAndDrop(dropZone, fileInput, function(files) {
            handleFileSelect(files[0]);
        });

        // Click to select file
        dropZone.addEventListener('click', function() {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                handleFileSelect(this.files[0]);
            }
        });

        // Clear file selection
        clearFileBtn.addEventListener('click', function() {
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            dropZone.classList.remove('border-blue-500', 'dark:border-blue-400');
        });

        // Handle file selection
        function handleFileSelect(file) {
            const fileName = file.name;
            const fileSize = formatFileSize(file.size);
            const fileIcon = getFileIcon(fileName);

            document.getElementById('file-name').textContent = fileName;
            document.getElementById('file-size').textContent = fileSize;
            document.getElementById('file-icon').textContent = fileIcon;
            
            fileInfo.classList.remove('hidden');
            dropZone.classList.add('border-blue-500', 'dark:border-blue-400');
        }

        // Copy formula button
        document.getElementById('btn-copy-formula').addEventListener('click', function() {
            copyToClipboard(formulaInput.value, 'Формула скопирована! Вставьте её в ячейку Google Таблицы.');
        });

        // Copy URL button
        document.getElementById('btn-copy-url').addEventListener('click', function() {
            if (btnOpenFile.href && btnOpenFile.href !== '#') {
                copyToClipboard(btnOpenFile.href, 'Ссылка на файл скопирована!');
            } else {
                showToast('Ссылка на файл недоступна', 'error');
            }
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!fileInput.files || !fileInput.files[0]) {
                showToast('Выберите файл.', 'error');
                return;
            }

            const formData = new FormData(form);
            
            // Show progress bar
            progressContainer.classList.remove('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="flex items-center">
                    <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Загрузка...
                </span>
            `;

            // Simulate progress (in real app, use XMLHttpRequest with progress events)
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                
                progressBar.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';
            }, 200);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                clearInterval(progressInterval);
                
                // Complete progress
                progressBar.style.width = '100%';
                progressText.textContent = '100%';
                
                setTimeout(() => {
                    progressContainer.classList.add('hidden');
                    progressBar.style.width = '0%';
                    progressText.textContent = '0%';
                }, 1000);

                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Загрузить файл
                    </span>
                `;

                if (data.ok) {
                    formulaInput.value = data.formula;
                    resultSection.classList.remove('hidden');
                    
                    if (data.url) {
                        btnOpenFile.href = data.url;
                        btnOpenFile.classList.remove('hidden');
                    }
                    
                    showToast('Файл успешно загружен!', 'success');
                    
                    // Scroll to result
                    resultSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    showToast(data.error || 'Ошибка загрузки', 'error');
                }
            })
            .catch(function() {
                clearInterval(progressInterval);
                progressContainer.classList.add('hidden');
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                
                submitBtn.disabled = false;
                submitBtn.innerHTML = `
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        Загрузить файл
                    </span>
                `;
                
                showToast('Ошибка сети или сервера.', 'error');
            });
        });

        // Show result section if formula exists
        <?php if ($formula): ?>
        resultSection.classList.remove('hidden');
        if ('<?php echo addslashes(htmlspecialchars($fileUrl)); ?>') {
            btnOpenFile.href = '<?php echo addslashes(htmlspecialchars($fileUrl)); ?>';
            btnOpenFile.classList.remove('hidden');
        }
        <?php endif; ?>
    })();
    </script>
<?php include __DIR__ . '/includes/footer.php'; ?>
