<div class="w-full p-0 m-0">
    <!-- Bulk Upload Modal Trigger Button -->
    <div class="flex justify-between mb-4">
        <div>
            <flux:heading size="lg">Bulk Upload Employees</flux:heading>
            <flux:subheading>
                Upload multiple employee records using a CSV file.
            </flux:subheading>
        </div>
        <flux:modal.trigger name="bulk-upload-modal">
            <flux:button variant="primary" icon="arrow-up-on-square-stack">
                Bulk Upload Employees
            </flux:button>
        </flux:modal.trigger>
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
    <flux:modal name="bulk-upload-modal" title="Bulk Upload Employees" class="max-w-5xl">
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

            <flux:card size="sm" class="bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                <div class="flex items-center justify-between">
                    <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                        Select fields for the CSV template
                    </flux:text>
                    <button wire:click="downloadTemplate" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Download Template
                    </button>
                </div>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($availableFields as $key => $label)
                        <div class="flex items-center">
                            <input type="checkbox" 
                                id="field_{{ $key }}"
                                wire:click="toggleField('{{ $key }}')"
                                @if(in_array($key, $selectedFields)) checked @endif
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="field_{{ $key }}" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">
                                {{ $label }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </flux:card>

            <form wire:submit.prevent="uploadEmployees">
                <flux:card size="sm" class="bg-zinc-50 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    <div class="space-y-4">
                        <flux:input type="file"
                            wire:model="csvFile"
                            accept=".csv"
                            label="Select CSV File"
                            help="Please upload a CSV file with employee details"
                        />
                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary">
                                Upload Employees
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            </form>
        </div>
    </flux:modal>

    <!-- Batch Details Modal -->
    <flux:modal name="batch-details" title="Batch Information" class="max-w-3xl">
        @if($selectedBatch)
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <h3>Batch Information</h3>
{{--                    <flux:modal.close>--}}
{{--                        <flux:button variant="ghost" icon="x-mark" />--}}
{{--                    </flux:modal.close>--}}
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