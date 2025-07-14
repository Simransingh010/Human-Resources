<div xmlns:flux="http://www.w3.org/1999/html">
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column align="right">Total Amount</flux:table.column>
            <flux:table.column align="right">Paid Amount</flux:table.column>
            <flux:table.column align="right">Remaining Amount</flux:table.column>
            <flux:table.column align="center">Details</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($groupedSalaryArrears as $employeeId => $data)
                <flux:table.row wire:key="employee-{{ $employeeId }}">
                    <flux:table.cell>
                        {{ $data['employee']->full_name }} ({{ $data['employee']->employee_id_formatted ?? $data['employee']->id }})
                    </flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($data['total_amount'], 2) }}</flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($data['paid_amount'], 2) }}</flux:table.cell>
                    <flux:table.cell align="right">{{ number_format($data['total_amount'] - $data['paid_amount'], 2) }}</flux:table.cell>
                    <flux:table.cell align="center">
                        <button type="button" wire:click="showEmployeeArrearsModal({{ $employeeId }})" class="btn btn-sm btn-info">I</button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-4">
                        No salary arrears found for this payroll slot.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if($modalEmployeeId)
        <div class="flux-modal" style="position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:9999; display:flex; align-items:center; justify-content:center;">
            <div style="background:white; padding:2rem; border-radius:8px; min-width:350px; max-width:90vw; max-height:90vh; overflow:auto;">
                <h5>Salary Arrears Breakup for {{ $modalEmployeeDetails['employee']->full_name ?? '' }}</h5>
                <table class="table table-bordered table-sm mt-3">
                    <thead>
                        <tr>
                            <th>Salary Component</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining</th>
                            <th>Installments</th>
                            <th>Installment Amount</th>
                            <th>Disburse Slot</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modalEmployeeDetails['arrears'] as $arrear)
                            <tr>
                                <td>{{ $arrear->salary_component->title ?? 'N/A' }}</td>
                                <td>{{ number_format($arrear->total_amount, 2) }}</td>
                                <td>{{ number_format($arrear->paid_amount, 2) }}</td>
                                <td>{{ number_format($arrear->total_amount - $arrear->paid_amount, 2) }}</td>
                                <td>{{ $arrear->installments }}</td>
                                <td>{{ number_format($arrear->installment_amount, 2) }}</td>
                                <td>{{ $arrear->disburseWefPayrollSlot->title ?? 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" wire:click="closeEmployeeArrearsModal" class="btn btn-secondary mt-2">Close</button>
            </div>
        </div>
    @endif
</div>