<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
    </div>
    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->
    <!-- Filters Start -->

    <!-- Filter Fields Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-filters" variant="flyout" class="max-w-7xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Filters</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>

                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Columns Show/Hide Modal -->
    <flux:modal name="mdl-show-hide-columns" variant="flyout" position="right">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Show/Hide Columns</flux:heading>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <flux:checkbox.group>
                    @foreach($salcomponents as $salcomponent)
                        <flux:checkbox 
                            value="{{ $salcomponent->id }}"
                            label="{{ $salcomponent->title }} [{{ $salcomponent->nature }}]"
                            wire:model="visibleComponentIds"
                        />
                    @endforeach
                </flux:checkbox.group>
            </div>
        </div>
    </flux:modal>

    <!-- Data Table -->
    <div class="">
        <form wire:submit.prevent="save">
            <table class="min-w-full border text-sm">
                <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Employee Name</th>
                    @foreach ($salcomponents as $salcomponent)
                        @if(in_array($salcomponent->id, $visibleComponentIds))
                            <th class="border p-2 text-left">{{ $salcomponent->title }} <br> [ {{$salcomponent->nature }}]</th>
                        @endif
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach ($this->salcomponentEmployees as $salcomponentEmployee)
                    <tr>
                        <td class="border p-2">{{ $salcomponentEmployee->fname }} {{ $salcomponentEmployee->mname }} {{ $salcomponentEmployee->lname }}</td>
                        @foreach ($salcomponents as $salcomponent)
                            @if(in_array($salcomponent->id, $visibleComponentIds))

                                <td class="border p-2">
                                <flux:input.group>
                                <input type="text"
                                           class="w-full border rounded px-2 py-1"
                                           placeholder="0.00"
                                           value="{{ $entries[$salcomponentEmployee->id][$salcomponent->id] ?? '' }}"
                                           wire:keyup.debounce.500ms="saveSingleEntry('{{ $salcomponentEmployee->id }}', '{{ $salcomponent->id }}', $event.target.value)">

                                <flux:button 
                                    wire:click="prepareRemark('{{ $salcomponentEmployee->id }}', '{{ $salcomponent->id }}')"
                                      >
                                    <i class="fas fa-comment"></i>
                                </flux:button>
                                </flux:input.group>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Save Entries
                </button>
            </div>
        </form>
        
    </div>
    <div class="mt-4">

        {{ $this->salcomponentEmployees->links() }}
    </div>

    <!-- Remark Modal -->
    <flux:modal name="mdl-remark" @cancel="resetRemark">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Add Remark</flux:heading>
            </div>
            <div class="space-y-4">
                <flux:input.group>
                    <flux:input
                        wire:model.live="remark"
                        placeholder="Enter your remark here..."
                    />
                </flux:input.group>
                <div class="flex justify-end space-x-2">
                    <flux:modal.close>
                        <flux:button>Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button wire:click="saveRemark">Save Remark</flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
