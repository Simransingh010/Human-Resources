<div>
    <flux:modal.trigger name="mdl-doc">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Document
        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-doc" @close="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="saveDoc">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Document @else Add Document @endif
                    </flux:heading>
                    <flux:subheading>
                        Make changes to the document details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Select Employee" wire:model="docData.employee_id"
                        placeholder="Choose an employee">
                        <option value="">Select an employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee['id'] }}">
                                {{ $employee['name'] }}
                            </option>
                        @endforeach
                    </flux:select>

                    <flux:select label="Document Type" wire:model="docData.document_type_id"
                        placeholder="Select document type">
                        <option value="">Select type</option>
                        @foreach($document_types as $type)
                            <option value="{{ $type->id }}">{{ $type->title }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input label="Document Number" wire:model="docData.document_number"
                        placeholder="Enter document number" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input type="date" label="Issued Date" wire:model="docData.issued_date" />
                    <flux:input type="date" label="Expiry Date" wire:model="docData.expiry_date" />

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            Upload Document
                        </label>
                        <div class="relative">
                            <flux:button variant="ghost"
                                class="w-full border-2 border-dashed rounded-lg p-8 hover:border-primary-500" 
                                x-on:click="$refs.fileInput.click()">
                                <div class="space-y-3 text-center">
                                    <flux:icon name="document-arrow-up" class=" mb-0 mx-auto h-6 w-6 text-gray-400" />
                                    <div class="text-base text-gray-600 dark:text-gray-400">
                                        <label
                                            class=" text-sm relative cursor-pointer rounded-md font-medium text-primary-600 hover:text-primary-500">
                                            <span>Upload a file</span>
                                        </label>
                                    </div>
                                </div>
                            </flux:button>
                            <input x-ref="fileInput" wire:model="document" type="file" class="hidden"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        </div>
                        @error('document')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        @if($document)
                            <div class="mt-4 flex items-center space-x-2">
                                <flux:icon name="document" class="h-6 w-6 text-gray-400" />
                                <span class="text-base text-gray-600 dark:text-gray-400">
                                    {{ $document->getClientOriginalName() }}
                                </span>
                                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('document', null)"
                                    class="text-gray-400 hover:text-red-500" />
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save Changes
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:table :paginate="$this->docsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column class="w-[250px]">Employee</flux:table.column>
            <flux:table.column class="w-[180px]" sortable :sorted="$sortBy === 'document_type_id'"
                :direction="$sortDirection" wire:click="sort('document_type_id')">Document Type</flux:table.column>
            <flux:table.column class="w-[150px]">Document Number</flux:table.column>
            <flux:table.column class="w-[150px]" sortable :sorted="$sortBy === 'expiry_date'"
                :direction="$sortDirection" wire:click="sort('expiry_date')">Expiry Date</flux:table.column>
            <flux:table.column class="w-[120px]">Document</flux:table.column>
            <flux:table.column class="w-[100px]">Status</flux:table.column>
            <flux:table.column class="w-[120px]">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->docsList as $doc)
                <flux:table.row :key="$doc->id" class="border-b">
                    <flux:table.cell class="w-[250px] flex items-center gap-3">
                        {{ $doc->employee->fname . ' ' . $doc->employee->lname }}
                        <span class="text-xs text-gray-500">
                            {{ $doc->employee->email }}
                        </span>
                    </flux:table.cell>
                    <flux:table.cell class="w-[180px]">{{ $doc->document_type->name ?? 'N/A' }}</flux:table.cell>
                    <flux:table.cell class="w-[150px]">{{ $doc->document_number }}</flux:table.cell>
                    <flux:table.cell class="w-[150px]">
                        {{ $doc->expiry_date ? $doc->expiry_date->format('d M Y') : 'N/A' }}
                        @if($doc->expiry_date && $doc->expiry_date->isPast())
                            <flux:badge size="sm" color="red" inset="top bottom">Expired</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        @if($doc->doc_url)
                            <a href="{{ Storage::url($doc->doc_url) }}" target="_blank" class="text-blue-500 hover:underline">
                                View Document
                            </a>
                        @else
                            <span class="text-gray-500">No document</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="w-[100px]">
                        <div class="flex items-center space-x-2">
                            <flux:switch wire:model="docStatuses.{{ $doc->id }}"
                                wire:click="update_rec_status({{$doc->id}})" :checked="!$doc->is_inactive" />
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="w-[120px]">
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil" wire:click="fetchDoc({{ $doc->id }})">
                            </flux:button>
                            <flux:modal.trigger name="delete-doc-{{ $doc->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-doc-{{ $doc->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete document?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this document.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteDoc({{ $doc->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>