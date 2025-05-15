<div class="space-y-6">
    <!-- Heading Start -->
    @foreach($this->list->pluck('emp_leave_request.employee.fname')->unique() as $name)
        {{ $name ?? 'N/A' }}<br>
    @endforeach

    <flux:separator class="mt-2 mb-2" />
    <!-- Heading End -->

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
                                    @case('emp_leave_request_id')
                                        {{ $item->emp_leave_request->employee->fname ?? 'N/A' }}
                                        @break
                                    @case('user_id')
                                        {{ $item->user->name ?? 'N/A' }}
                                        @break
                                    @case('created_at')
                                        @php
                                            $date = $item->created_at;
                                            if ($date instanceof \Carbon\Carbon) {
                                                echo $date->format('Y-m-d H:i:s');
                                            } else {
                                                echo $date ? date('Y-m-d H:i:s', strtotime($date)) : 'N/A';
                                            }
                                        @endphp
                                        @break
                                    @case('deleted_at')
                                        @php
                                            $date = $item->deleted_at;
                                            if ($date instanceof \Carbon\Carbon) {
                                                echo $date->format('Y-m-d H:i:s');
                                            } else {
                                                echo $date ? date('Y-m-d H:i:s', strtotime($date)) : 'N/A';
                                            }
                                        @endphp
                                        @break
                                    @default
                                        {{ $item->$field }}
                                @endswitch
                            </flux:table.cell>
                        @endif
                    @endforeach
                    <flux:table.cell>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
