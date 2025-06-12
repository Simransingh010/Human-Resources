<div class="p-4">
    <flux:table class="w-full">
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Reason</flux:table.column>
            <flux:table.column>Hold Date</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($salaryHolds as $hold)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $hold->employee->name ?? 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $hold->employee->employee_id ?? 'N/A' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm text-gray-900">{{ $hold->remarks }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm text-gray-900">
                            {{ $hold->created_at ? \Carbon\Carbon::parse($hold->created_at)->format('d M Y') : 'N/A' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge variant="{{ $hold->is_active ? 'success' : 'danger' }}">
                            {{ $hold->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-sm text-gray-500">
                        No salary holds found for this payroll slot.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>