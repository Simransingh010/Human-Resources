<div class="w-full p-0 m-0">
    <div class="flex justify-between">
        @livewire('panel.component-heading')
        <div class="flex">
            <flux:modal.trigger name="bulk-upload-modal">
                <flux:button variant="primary" icon="arrow-up-on-square-stack">
                    Bulk Upload Students
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>
    <flux:separator class="mt-2 mb-2"/>

    <!-- Batch List Table -->
    <flux:card size="sm" class="bg-white dark:bg-gray-800">
        <div class="mb-4">
            <flux:heading size="lg">All Batches</flux:heading>
            <flux:subheading>
                View and manage your student upload batches.
            </flux:subheading>
        </div>
        <div class="overflow-x-auto">
            <flux:table class="w-full">
                <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                    <flux:table.column>Batch ID</flux:table.column>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($batches as $batch)
                        <flux:table.row :key="$batch->id" class="border-b">
                            <flux:table.cell>{{ $batch->id }}</flux:table.cell>
                            <flux:table.cell>{{ $batch->title }}</flux:table.cell>
                            <flux:table.cell>{{ $batch->created_at->format('d M Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex space-x-2">
                                    <flux:modal.trigger name="batch-details-modal">
                                        <flux:button size="sm" variant="primary" icon="eye"
                                                     wire:click="selectBatch({{ $batch->id }})"
                                                     wire:loading.attr="disabled">
                                            View
                                        </flux:button>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger name="rollback-confirmation-modal">
                                        <flux:button size="sm" variant="danger" icon="trash"
                                                     wire:click="confirmRollback({{ $batch->id }})"
                                                     wire:loading.attr="disabled">
                                            Rollback
                                        </flux:button>
                                    </flux:modal.trigger>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Bulk Upload Modal -->
    <flux:modal name="bulk-upload-modal" class="max-w-none">
        <div class="space-y-6">
            <!-- Progress Indicator -->
            @if($isUploading)
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800"
                     wire:poll.1s="$refresh">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                                <svg class="w-6 h-6 absolute top-3 left-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Uploading Students...</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400" id="current-student-text">
                                    Processing: <span class="font-medium text-blue-600 dark:text-blue-400" id="current-student-name">{{ $currentStudentName ?: 'Preparing...' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="progress-percentage">{{ $currentProgress }}%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400" id="progress-count">
                                {{ $processedCount }} / {{ $totalRecords }} records
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-4 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-300 ease-out" 
                             id="progress-bar"
                             style="width: {{ $currentProgress }}%">
                        </div>
                    </div>
                    
                    <!-- Timer and Stats -->
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Time Remaining</div>
                            <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400" id="time-remaining">
                                <span id="time-minutes">0</span>m <span id="time-seconds">0</span>s
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Success</div>
                            <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ $totalSuccess }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Errors</div>
                            <div class="text-lg font-bold text-red-600 dark:text-red-400">{{ $totalErrors }}</div>
                        </div>
                    </div>
                    
                    <!-- Recent Students List -->
                    <div class="mt-4 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 max-h-32 overflow-y-auto">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">Recently Processed:</div>
                        <div class="space-y-1" id="recent-students">
                            <div class="text-sm text-gray-700 dark:text-gray-300 flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span id="recent-student-list">Starting upload...</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @if($uploadSuccess)
                <flux:card size="sm" class="bg-green-50 dark:bg-green-900/20">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-800 dark:text-green-200">All records have been successfully imported.</span>
                    </div>
                </flux:card>
            @endif

            @if(count($uploadErrors) > 0)
                <flux:card size="sm" class="bg-red-50 dark:bg-red-900/20">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span class="text-red-800 dark:text-red-200">Some records failed to import. Please check the errors below.</span>
                    </div>
                    <ul class="list-disc pl-5 mt-2 text-red-700 dark:text-red-300 text-sm max-h-60 overflow-y-auto">
                        @foreach($uploadErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif

            <flux:card size="sm" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg mt-2">
                <div class="">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bulk Upload Students</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload multiple students at once using a CSV file</p>
                        </div>
                        <div class="px-2">
                            <button wire:click="downloadTemplate"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20"
                                     fill="currentColor">
                                    <path fill-rule="evenodd"
                                          d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                          clip-rule="evenodd"/>
                                </svg>
                                <span wire:loading.remove wire:target="downloadTemplate">Download Template</span>
                                <span wire:loading wire:target="downloadTemplate">Downloading...</span>
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <form wire:submit.prevent="uploadStudents">
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select CSV File</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors duration-200"
                                         x-data="{ isDragging: false }"
                                         x-on:dragover.prevent="isDragging = true"
                                         x-on:dragleave.prevent="isDragging = false"
                                         x-on:drop.prevent="isDragging = false; $wire.upload('csvFile', $event.dataTransfer.files[0])"
                                         :class="{ 'border-indigo-500': isDragging }">
                                        <label for="file-upload" class="w-full cursor-pointer">
                                            <div class="space-y-1 text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center">
                                                    <span class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">Upload a file</span>
                                                    <p class="pl-1">or drag and drop</p>
                                                </div>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    CSV files only
                                                </p>
                                                @if($csvFile)
                                                    <p class="text-sm text-indigo-600 dark:text-indigo-400 mt-2">
                                                        Selected file: {{ $csvFile->getClientOriginalName() }}
                                                    </p>
                                                @endif
                                            </div>
                                            <input id="file-upload" 
                                                   name="file-upload" 
                                                   type="file" 
                                                   wire:model="csvFile"
                                                   accept=".csv"
                                                   class="hidden">
                                        </label>
                                    </div>
                                    @error('csvFile')
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Mandatory Fields Info -->
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-2">Required Fields (marked with *)</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs text-amber-700 dark:text-amber-300">
                                        <div>• Player Name*</div>
                                        <div>• Gender*</div>
                                        <div>• DOB (Optional)</div>
                                        <div>• Mobile No*</div>
                                        <div>• Aadhar*</div>
                                        <div>• Father Name*</div>
                                    </div>
                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                        Note: Player Name will be automatically split into First Name, Middle Name, and Last Name. DOB is optional; if provided, use format YYYY-MM-DD (e.g., 2000-01-15)
                                    </p>
                                </div>

                                <div class="flex justify-end">
                                    <flux:button type="submit"
                                                 variant="primary"
                                                 class="px-6 py-2.5"
                                                 wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="uploadStudents" class="flex">
                                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            Upload Students
                                        </span>
                                        <span wire:loading wire:target="uploadStudents" class="flex">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                        stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Uploading...
                                        </span>
                                    </flux:button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </flux:card>
        </div>
    </flux:modal>

    <!-- Batch Details Modal -->
    <flux:modal name="batch-details-modal" class="max-w-3xl">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            @if($selectedBatch)
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <h3>Batch Information</h3>
                        <p>
                            Batch ID: {{ $selectedBatch->id }} |
                            Created: {{ $selectedBatch->created_at->format('d M Y H:i') }}
                        </p>
                    </div>
                    <flux:table>
                        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
                            <flux:table.column>Operation</flux:table.column>
                            <flux:table.column>Model Type</flux:table.column>
                            <flux:table.column>Model ID</flux:table.column>
                            <flux:table.column>Created At</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($selectedBatch->items as $item)
                                <flux:table.row :key="$item->id" class="border-b">
                                    <flux:table.cell>{{ ucfirst($item->operation) }}</flux:table.cell>
                                    <flux:table.cell>{{ class_basename($item->model_type) }}</flux:table.cell>
                                    <flux:table.cell>{{ $item->model_id }}</flux:table.cell>
                                    <flux:table.cell>{{ $item->created_at->format('d M Y H:i:s') }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        </div>
    </flux:modal>

    <!-- Rollback Confirmation Modal -->
    <flux:modal name="rollback-confirmation-modal" class="max-w-lg">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="space-y-6">
                <div>
                    <h3>Rollback Batch?</h3>
                    <flux:text class="mt-2">
                        <p>You're about to rollback Batch ID: {{ $selectedBatchId }}</p>
                        <p class="text-red-500">This action cannot be reversed.</p>
                    </flux:text>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button type="button"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-500 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                    wire:click="rollbackBatchById({{ $selectedBatchId }})"
                    wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="rollbackBatchById">Rollback</span>
                <span wire:loading wire:target="rollbackBatchById">Rolling back...</span>
            </button>
        </div>
    </flux:modal>

    <script>
        document.addEventListener('livewire:init', () => {
            let recentStudents = [];
            const maxRecentStudents = 5;
            
            Livewire.on('progress-updated', (data) => {
                // Update progress bar
                const progressBar = document.getElementById('progress-bar');
                const progressPercentage = document.getElementById('progress-percentage');
                const progressCount = document.getElementById('progress-count');
                const currentStudentName = document.getElementById('current-student-name');
                const timeMinutes = document.getElementById('time-minutes');
                const timeSeconds = document.getElementById('time-seconds');
                
                if (progressBar) {
                    progressBar.style.width = data.progress + '%';
                }
                if (progressPercentage) {
                    progressPercentage.textContent = Math.round(data.progress) + '%';
                }
                if (progressCount) {
                    progressCount.textContent = data.processed + ' / ' + data.total + ' records';
                }
                if (currentStudentName && data.currentStudent) {
                    currentStudentName.textContent = data.currentStudent;
                    
                    // Add to recent students
                    if (data.currentStudent && !recentStudents.includes(data.currentStudent)) {
                        recentStudents.unshift(data.currentStudent);
                        if (recentStudents.length > maxRecentStudents) {
                            recentStudents.pop();
                        }
                        updateRecentStudentsList();
                    }
                }
                
                // Update timer
                if (data.timeRemaining !== undefined) {
                    const minutes = Math.floor(data.timeRemaining / 60);
                    const seconds = Math.floor(data.timeRemaining % 60);
                    if (timeMinutes) timeMinutes.textContent = minutes;
                    if (timeSeconds) timeSeconds.textContent = seconds.toString().padStart(2, '0');
                }
            });
            
            function updateRecentStudentsList() {
                const recentList = document.getElementById('recent-student-list');
                if (recentList && recentStudents.length > 0) {
                    recentList.innerHTML = recentStudents.map((name, index) => {
                        const animationDelay = index * 100;
                        return `<div class="text-sm text-gray-700 dark:text-gray-300 flex items-center animate-fade-in" style="animation-delay: ${animationDelay}ms">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            ${name}
                        </div>`;
                    }).join('');
                }
            }
            
            // Countdown timer animation
            let countdownInterval;
            Livewire.on('progress-updated', (data) => {
                if (countdownInterval) clearInterval(countdownInterval);
                
                if (data.timeRemaining > 0) {
                    let remaining = data.timeRemaining;
                    countdownInterval = setInterval(() => {
                        remaining = Math.max(0, remaining - 1);
                        const minutes = Math.floor(remaining / 60);
                        const seconds = Math.floor(remaining % 60);
                        const timeMinutes = document.getElementById('time-minutes');
                        const timeSeconds = document.getElementById('time-seconds');
                        if (timeMinutes) timeMinutes.textContent = minutes;
                        if (timeSeconds) timeSeconds.textContent = seconds.toString().padStart(2, '0');
                        
                        if (remaining <= 0) {
                            clearInterval(countdownInterval);
                        }
                    }, 1000);
                }
            });
        });
    </script>
    
    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out forwards;
        }
    </style>
</div>

