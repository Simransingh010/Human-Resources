<div class="space-y-6">
    <!-- Heading Start -->
    <div class="flex justify-between">
        @livewire('panel.component-heading')
       
    </div>
    <flux:separator class="mt-2 mb-2" />
    
    <!-- Data Table -->
    <flux:table :paginate="$this->list" class="w-full">
        <flux:table.columns>
            @foreach($fieldConfig as $field => $cfg)
                @if(in_array($field, $visibleFields))
                    <flux:table.column>{{ $cfg['label'] }}</flux:table.column>
                @endif
            @endforeach

        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->list as $item)
                <flux:table.row :key="$item->id">
                    @foreach($fieldConfig as $field => $cfg)
                        @if(in_array($field, $visibleFields))
                            <flux:table.cell class="table-cell-wrap">
                                @switch($field)
                                    @case('leave_balance_id')
                                        {{ $item->emp_leave_balance->id ?? 'N/A' }}
                                        @break
                                    @case('transaction_type')
                                        {{ $listsForFields['transaction_types'][$item->transaction_type] ?? 'N/A' }}
                                        @break
                                    @case('created_by')
                                        {{ $item->user->name ?? 'N/A' }}
                                        @break
                                    @case('transaction_date')
                                        {{ $item->transaction_date ? $item->transaction_date->format('jS F Y') : 'N/A' }}
                                        @break
                                    @case('created_at')
                                        {{ $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                                        @break
                                    @case('updated_at')
                                        {{ $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : 'N/A' }}
                                        @break
                                    @case('amount')
                                        {{ number_format($item->amount, 2) }}
                                        @break
                                    @default
                                        {{ $item->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                        

                        <!-- Delete Confirmation Modal -->
                        
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 