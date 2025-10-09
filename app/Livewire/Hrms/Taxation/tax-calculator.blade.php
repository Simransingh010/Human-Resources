<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Income Tax Calculator</flux:heading>
            <flux:subheading class="text-base">New Regime · AY-aware · Matches e-portal slabs and relief</flux:subheading>
        </div>
    </div>
{{--    <flux:separator class="mt-2 mb-2" />--}}

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Inputs -->
        <div class="lg:col-span-2 space-y-6">
            <flux:card class="shadow-lg">
                <div class="p-1">
                    <flux:heading size="lg">Taxpayer Details</flux:heading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <flux:input label="PAN" wire:model.live="form.pan" placeholder="ABCDE1234F" />
                    <flux:input label="Name of the Taxpayer" wire:model.live="form.name" />

                    <div>
                        <flux:select label="Assessment Year" wire:model.live="form.assessment_year">
                            @foreach($this->assessmentYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:select label="Age Type" wire:model.live="form.age_type">
                            @foreach($this->ageTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <flux:input disabled label="Tax Regime" value="New Regime (default)" />
                        <div class="flex items-end">
                            <div class="text-sm md:text-base text-gray-600">Standard deduction auto-applied as per AY.</div>
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="shadow-lg">
                <div class="p-1">
                    <flux:heading size="lg">Income Details</flux:heading>
                    <flux:subheading class="text-base">Enter amounts in ₹</flux:subheading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-2">
                    <flux:input type="number" step="0.01" label="Income under Salaries" wire:model.live="form.income_salary" />
                    <flux:input type="number" step="0.01" label="Income under House Property" wire:model.live="form.income_house_property" />
                    <flux:input type="number" step="0.01" label="Income under Capital Gains (total)" wire:model.live="form.income_capital_gains" />
                    <flux:input type="number" step="0.01" label="Income under Business/Profession" wire:model.live="form.income_business" />
                    <flux:input type="number" step="0.01" label="Income under Other Sources" wire:model.live="form.income_other_sources" />
                </div>
            </flux:card>

            <flux:card class="shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Deductions</flux:heading>
                        <flux:subheading class="text-base">Standard deduction applied automatically</flux:subheading>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model.live="form.allow_deductions" />
                        <span class="text-base text-gray-700">Enable optional Chapter VIA / Employer NPS</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
                    <flux:input type="number" step="0.01" label="Additional Deductions (optional)" wire:model.live="form.deductions" :disabled="!$form['allow_deductions']" />
                </div>
                <div class="text-sm md:text-base text-gray-600 mt-2">
                    AY rules: ₹50,000 for AY 2024-25 · ₹75,000 for AY 2025-26 (capped at salary+pension).
                </div>
            </flux:card>

            <flux:card class="shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="lg">Special Rates</flux:heading>
                        <flux:subheading class="text-base">User-supplied or Advanced breakdown</flux:subheading>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:switch wire:model.live="form.advanced" />
                        <span class="text-base text-gray-700">Advanced mode</span>
                    </div>
                </div>

                <div class="mt-4" x-data="{open: false}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" x-show="!$wire.form.advanced">
                        <flux:input type="number" step="0.01" label="Tax at Special Rates (user-supplied)" wire:model.live="form.special_tax_user" />
                    </div>

                    <div class="border rounded mt-4" x-show="$wire.form.advanced">
                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <flux:input type="number" step="0.01" label="STCG (Sec 111A) amount" wire:model.live="form.stcg_111a" />
                            <flux:input type="number" step="0.01" label="LTCG (Sec 112A) amount" wire:model.live="form.ltcg_112a" />
                            <flux:input type="number" step="0.01" label="Other LTCG amount" wire:model.live="form.other_ltcg" />
                            <flux:input type="number" step="0.01" label="Lottery/Winnings amount" wire:model.live="form.lottery" />
                        </div>
                        <div class="px-4 pb-4 text-sm md:text-base text-gray-600">
                            Special incomes can affect rebate and surcharge differently; this calculator applies simplified rates and does not model exemptions for 112A/ indexation etc.
                        </div>
                    </div>
                </div>
            </flux:card>

            <flux:card class="shadow-lg">
                <div class="p-1">
                    <flux:heading size="lg">Taxes Paid and Interests</flux:heading>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-2">
                    <flux:input type="number" step="0.01" label="TDS + TCS + MAT/AMT credit utilized" wire:model.live="form.tds_tcs_mat_amt" />
                    <flux:input type="number" step="0.01" label="Self-Assessment / Advance Tax" wire:model.live="form.self_assessment_advance_tax" />
                    <flux:input type="number" step="0.01" label="Interest u/s 234A" wire:model.live="form.interest_234a" />
                    <flux:input type="number" step="0.01" label="Interest u/s 234B" wire:model.live="form.interest_234b" />
                    <flux:input type="number" step="0.01" label="Interest u/s 234C" wire:model.live="form.interest_234c" />
                    <flux:input type="number" step="0.01" label="Fees u/s 234F" wire:model.live="form.fee_234f" />
                </div>
            </flux:card>
        </div>

        <!-- Right: Summary -->
        <div class="lg:col-span-1">
            <div class="sticky top-4">
                <flux:card class="shadow-xl border-blue-200">
                    <div class="flex items-end justify-between">
                        <div>
                            <flux:heading size="lg">Summary</flux:heading>
                            <flux:subheading class="text-base">Live computation</flux:subheading>
                        </div>
                        <div class="text-right">
                            <div class="text-xs uppercase text-gray-500">Total Tax & Interest</div>
                            <div class="text-2xl md:text-3xl font-extrabold text-blue-600">₹ {{ number_format($result['total_tax_and_interest_payable'] ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <flux:separator class="my-3" />
                    <div class="mt-1 space-y-3 text-base">
                        <div class="flex justify-between"><span class="font-medium">Gross Total Income</span><span class="font-semibold">{{ number_format($result['gross_total_income'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Standard Deduction</span><span class="font-semibold text-rose-600">- {{ number_format($result['standard_deduction'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Other Deductions</span><span class="font-semibold text-rose-600">- {{ number_format($result['deductions'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-semibold">Taxable Income</span><span class="font-extrabold">{{ number_format($result['taxable_income'] ?? 0, 2) }}</span></div>
                        <flux:separator class="my-2" />
                        <div class="flex justify-between"><span class="font-medium">1. Tax at Normal Rates</span><span class="font-semibold">{{ number_format($result['tax_at_normal_rates'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">2. Tax at Special Rates</span><span class="font-semibold">{{ number_format($result['tax_at_special_rates'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Total Tax before Rebate</span><span class="font-semibold">{{ number_format($result['tax_before_rebate'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Less: Rebate u/s 87A</span><span class="font-semibold text-rose-600">- {{ number_format($result['rebate_87a'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Net Tax after Rebate</span><span class="font-semibold">{{ number_format($result['net_tax_after_rebate'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Add: Surcharge</span><span class="font-semibold">{{ number_format($result['surcharge'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Add: Health & Education Cess (4%)</span><span class="font-semibold">{{ number_format($result['cess'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-semibold">Total Tax on Income</span><span class="font-extrabold">{{ number_format($result['total_tax_on_income'] ?? 0, 2) }}</span></div>
                        <flux:separator class="my-2" />
                        <div class="flex justify-between"><span class="font-medium">Balance Tax Payable / Refundable</span><span class="font-semibold">{{ number_format($result['balance_tax_payable'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Add: Interest u/s 234A</span><span class="font-semibold">{{ number_format($result['interest_234a'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Add: Interest u/s 234B</span><span class="font-semibold">{{ number_format($result['interest_234b'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Add: Interest u/s 234C</span><span class="font-semibold">{{ number_format($result['interest_234c'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Add: Fee u/s 234F</span><span class="font-semibold">{{ number_format($result['fee_234f'] ?? 0, 2) }}</span></div>
                    </div>
                </flux:card>
            </div>
        </div>
    </div>
</div>

