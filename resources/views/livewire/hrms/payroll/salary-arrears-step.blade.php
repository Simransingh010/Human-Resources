<div xmlns:flux="http://www.w3.org/1999/html">
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column>Salary Component</flux:table.column>
            <flux:table.column align="right">Total Amount</flux:table.column>
            <flux:table.column align="right">Paid Amount</flux:table.column>
            <flux:table.column align="right">Remaining Amount</flux:table.column>
            <flux:table.column align="right">Installments</flux:table.column>
            <flux:table.column align="right">Installment Amount</flux:table.column>
            <flux:table.column>Disburse Slot</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($salaryArrears as $arrear)
                <flux:table.row wire:key="{{ $arrear->id }}">
                    <flux:table.cell>
                        {{ $arrear->employee->full_name }} ({{ $arrear->employee->employee_id_formatted }})
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $arrear->salary_component->title ?? 'N/A' }}
                    </flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($arrear->total_amount, 2) }}</flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($arrear->paid_amount, 2) }}</flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($arrear->total_amount - $arrear->paid_amount, 2) }}
                    </flux:table.cell>
                    <flux:table.cell align="right">{{ $arrear->installments }}</flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($arrear->installment_amount, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ $arrear->disburseWefPayrollSlot->title ?? 'N/A' }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center py-4">
                        No salary arrears found for this payroll slot.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>