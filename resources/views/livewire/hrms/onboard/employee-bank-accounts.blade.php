<div>
    <flux:modal.trigger name="mdl-bank-account">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">

            Add Bank Account

        </flux:button>
    </flux:modal.trigger>

    <flux:modal name="mdl-bank-account" @close="resetForm" position="right" class="max-w-6xl">
        <form wire:submit.prevent="saveBankAccount">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Bank Account @else Add Bank Account @endif
                    </flux:heading>
                    <flux:subheading>
                        Configure employee bank account details.
                    </flux:subheading>
                </div>

                <!-- First Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Employee Selection -->
                    <div class="col-span-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Select Employee
                        </label>
                        <select wire:model="bankAccountData.employee_id"
                            class="mt-1 py-2 pl-3 pr-3 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="">Select an employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee['id'] }}">{{ $employee['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <flux:input label="Bank Name" wire:model="bankAccountData.bank_name"
                        placeholder="Enter bank name" />

                    <flux:input label="Branch Name" wire:model="bankAccountData.branch_name"
                        placeholder="Enter branch name" />
                </div>

                <!-- Second Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <flux:input label="IFSC Code" wire:model="bankAccountData.ifsc" placeholder="Enter IFSC code" />

                    <flux:input label="Account Number" wire:model="bankAccountData.bankaccount"
                        placeholder="Enter account number" />


                </div>

                <!-- Branch Address Row -->
                <div class="mb-6">
                    <flux:textarea label="Branch Address" wire:model="bankAccountData.address"
                        placeholder="Enter branch address" rows="3" />
                </div>

                <!-- Status Row -->

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center space-x-4">
                        <flux:checkbox wire:model="bankAccountData.is_primary" label="Primary Account" />
                    </div>
                    <div class="flex items-center space-x-4">
                        <flux:checkbox wire:model="bankAccountData.is_inactive" label="Inactive Account" />
                    </div>

                    <!-- Empty div to maintain grid alignment -->
                    <div></div>
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

    <flux:table :paginate="$this->bankAccountsList" class="w-full">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Employee</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'bank_name'" :direction="$sortDirection"
                wire:click="sort('bank_name')">Bank Name</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'branch_name'" :direction="$sortDirection"
                wire:click="sort('branch_name')">Branch</flux:table.column>
            <flux:table.column>Account Number</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->bankAccountsList as $account)
                <flux:table.row :key="$account->id" class="border-b">
                    <flux:table.cell class="flex items-center gap-3">
                        {{ $account->employee->fname . ' ' . $account->employee->lname }}
                    </flux:table.cell>
                    <flux:table.cell>{{ $account->bank_name }}</flux:table.cell>
                    <flux:table.cell>{{ $account->branch_name }}</flux:table.cell>
                    <flux:table.cell>{{ $account->bankaccount }}</flux:table.cell>
                    <flux:table.cell>
                        @if($account->is_primary)
                            <flux:badge size="sm" color="blue" inset="top bottom">Primary</flux:badge>
                        @elseif($account->is_inactive)
                            <flux:badge size="sm" color="gray" inset="top bottom">Inactive</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch wire:model="bankAccountStatuses.{{ $account->id }}"
                            wire:click="toggleStatus({{ $account->id }})" :checked="!$account->is_inactive" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-center space-x-2">
                            <flux:button variant="primary" size="sm" icon="pencil"
                                wire:click="fetchBankAccount({{ $account->id }})"></flux:button>
                            <flux:modal.trigger name="delete-bank-account-{{ $account->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"></flux:button>
                            </flux:modal.trigger>
                        </div>
                        <flux:modal name="delete-bank-account-{{ $account->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete bank account?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this bank account.</p>
                                        <p>This action cannot be reversed.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button type="submit" variant="danger" icon="trash"
                                        wire:click="deleteBankAccount({{ $account->id }})"></flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>