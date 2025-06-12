<div class="w-full p-0 m-0">
    <!-- Bulk Upload Modal Trigger Button -->
    <div class="flex justify-between mb-4">
        <div>
            <flux:heading size="lg">Bulk Upload Attendance</flux:heading>
            <flux:subheading>
                Upload multiple attendance records using a CSV file.
            </flux:subheading>
        </div>
        <flux:modal.trigger name="bulk-upload-modal">
            <flux:button variant="primary" icon="arrow-up-on-square-stack">
                Bulk Upload Attendance
            </flux:button>
        </flux:modal.trigger>
    </div>
    <div class="flex mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full">
            <!-- Date Range -->
            <div class="md:col-span-3">
                <flux:date-picker class="mt-1" label="Attendance Period" with-today mode="range" with-presets wire:model="filters.date_range"/>
                @error('filters.date_range') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Employee -->
            <div>
                <flux:select label="Employee" variant="listbox" multiple searchable wire:model.defer="filters.employee_id">
                    <flux:select.option value="">All</flux:select.option>
                    @foreach($listsForFields['employees'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Department -->
            <div>
                <flux:select label="Department" variant="listbox" multiple searchable wire:model.defer="filters.department_id">
                    <flux:select.option value="">All</flux:select.option>
                    @foreach($listsForFields['departments'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Employment Type -->
            <div>
                <flux:select label="Employment Type" variant="listbox" multiple searchable wire:model.defer="filters.employment_type_id">
                    <flux:select.option value="">All</flux:select.option>
                    @foreach($listsForFields['employment_types'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end">
                <flux:button wire:click="export" variant="primary">
                    Export to Excel
                </flux:button>
                @error('filters.date_range.start') 
                    <p class="mt-1 text-sm text-red-600">Please select date range first</p>
                @enderror
            </div>

            <div class="flex items-end">
                <button wire:click="downloadTemplate"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    <span wire:loading.remove wire:target="downloadTemplate">Download Template</span>
                    <span wire:loading wire:target="downloadTemplate">Downloading...</span>
                </button>
                @error('filters.date_range.start') 
                    <p class="mt-1 text-sm text-red-600">Please select date range first</p>
                @enderror
            </div>
        </div>
    </div>
    <!-- Batch List Table -->
    <flux:card size="sm" class="bg-white dark:bg-gray-800">
        <div class="mb-4">
            <h3>All Batches</h3>
        </div>
        <div class="overflow-x-auto">
            <flux:table class="w-full">
                <flux:table.columns>
                    <flux:table.column>Batch ID</flux:table.column>
                    <flux:table.column>Title</flux:table.column>
                    <flux:table.column>Created At</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($batches as $batch)
                        <flux:table.row :key="$batch->id">
                            <flux:table.cell>{{ $batch->id }}</flux:table.cell>
                            <flux:table.cell>{{ $batch->title }}</flux:table.cell>
                            <flux:table.cell>{{ $batch->created_at->format('d M Y H:i:s') }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-2">
                                    <flux:modal.trigger name="batch-details">
                                        <flux:button size="sm" variant="primary" icon="eye"
                                            wire:click="selectBatch({{ $batch->id }})">
                                            View
                                        </flux:button>
                                    </flux:modal.trigger>
                                    <flux:modal.trigger name="rollback-batch-{{ $batch->id }}">
                                        <flux:button size="sm" variant="danger" icon="trash">Rollback</flux:button>
                                    </flux:modal.trigger>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:card>

    <!-- Rollback Confirmation Modal -->
    @foreach($batches as $batch)
        <flux:modal name="rollback-batch-{{ $batch->id }}" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Rollback Batch?</flux:heading>
                    <flux:text class="mt-2">
                        <p>You're about to rollback Batch ID: {{ $batch->id }}</p>
                        <p class="text-red-500">This action cannot be reversed.</p>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer/>
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" icon="trash" wire:click="rollbackBatchById({{ $batch->id }})">Rollback</flux:button>
                </div>
            </div>
        </flux:modal>
    @endforeach

    <!-- Bulk Upload Modal -->
    <flux:modal name="bulk-upload-modal" class="max-w-none">
        <div class="space-y-6">
            @if($uploadSuccess)
                <flux:card size="sm" class="bg-green-50 dark:bg-green-900/20">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-green-800 dark:text-green-200">All records have been successfully imported.</span>
                    </div>
                </flux:card>
            @endif

            @if(count($uploadErrors) > 0)
                <flux:card size="sm" class="bg-red-50 dark:bg-red-900/20">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-red-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-red-800 dark:text-red-200">Some records failed to import. Please check the errors below.</span>
                    </div>
                    <ul class="list-disc pl-5 mt-2 text-red-700 dark:text-red-300 text-sm">
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
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bulk Upload Attendance</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload multiple attendance records at once using a CSV file</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <form wire:submit.prevent="uploadAttendance">
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

                                <div class="flex justify-end">
                                    <flux:button type="submit"
                                                 variant="primary"
                                                 class="px-6 py-2.5"
                                                 wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="uploadAttendance" class="flex">
                                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            Upload Attendance
                                        </span>
                                        <span wire:loading wire:target="uploadAttendance" class="flex">
                                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
    <flux:modal name="batch-details" title="Batch Information" class="max-w-3xl">
        @if($selectedBatch)
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <h3>Batch Information</h3>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    Batch ID: {{ $selectedBatch->id }} | Created: {{ $selectedBatch->created_at->format('d M Y H:i') }}
                </div>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Operation</flux:table.column>
                        <flux:table.column>Model Type</flux:table.column>
                        <flux:table.column>Model ID</flux:table.column>
                        <flux:table.column>Created At</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($selectedBatch->items as $item)
                            <flux:table.row :key="$item->id">
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
    </flux:modal>
</div> 