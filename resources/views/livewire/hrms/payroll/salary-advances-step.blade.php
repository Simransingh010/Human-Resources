<div class="p-4">
    <flux:table class="w-full">
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Advance Date</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Installments</flux:table.column>
            <flux:table.column>Inst. Amount</flux:table.column>
            <flux:table.column>Recovered</flux:table.column>
            <flux:table.column>Remaining</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Disburse Slot</flux:table.column>
            <flux:table.column>Recovery Slot</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($salaryAdvances as $advance)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $advance->employee->name ?? 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $advance->employee->employee_id ?? 'N/A' }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $advance->advance_date->format('d M Y') }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($advance->amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $advance->installments }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($advance->installment_amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($advance->recovered_amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ number_format($advance->amount - $advance->recovered_amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge
                            variant="{{ $advance->advance_status === 'active' ? 'success' : ($advance->advance_status === 'pending' ? 'warning' : 'danger') }}">
                            {{ App\Models\Hrms\SalaryAdvance::$advanceStatuses[$advance->advance_status] ?? $advance->advance_status }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $advance->disbursePayrollSlot->title ?? 'N/A' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $advance->recoveryWefPayrollSlot->title ?? 'N/A' }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10" class="text-center text-sm text-gray-500">
                        No salary advances found for this payroll slot.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>