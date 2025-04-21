<div>
    <!-- Modal trigger for both adding and editing -->
    <flux:modal.trigger name="mdl-firm-branding">
        <flux:button variant="primary" class="bg-blue-500 text-white px-4 py-2 mb-4 rounded-md">
            Add Firm Branding
        </flux:button>
    </flux:modal.trigger>

    <!-- Modal Start -->
    <flux:modal name="mdl-firm-branding" @cancel="resetForm" position="right" class="max-w-none">
        <form wire:submit.prevent="store">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        @if($isEditing) Edit Firm Branding @else Add Firm Branding @endif
                    </flux:heading>
                    <flux:subheading>
                        @if($isEditing) Update @else Add new @endif firm branding details.
                    </flux:subheading>
                </div>

                <!-- Grid layout for form fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:select label="Firm" wire:model="formData.firm_id">
                        <flux:select.option value="">-- Select Firm --</flux:select.option>
                        @foreach($this->listsForFields['firms'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{$value}}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input
                        label="Brand Name"
                        wire:model="formData.brand_name"
                        placeholder="Brand Name"
                    />
                    <flux:input
                        label="Brand Slogan"
                        wire:model="formData.brand_slogan"
                        placeholder="Brand Slogan"
                    />
                    <flux:input
                        label="Website"
                        wire:model="formData.website"
                        placeholder="Website URL"
                        type="url"
                    />
                    <flux:input
                        label="Email"
                        wire:model="formData.email"
                        placeholder="Email Address"
                        type="email"
                    />
                    <flux:input
                        label="Phone"
                        wire:model="formData.phone"
                        placeholder="Phone Number"
                    />
                    <flux:input
                        label="Facebook"
                        wire:model="formData.facebook"
                        placeholder="Facebook URL"
                        type="url"
                    />
                    <flux:input
                        label="LinkedIn"
                        wire:model="formData.linkedin"
                        placeholder="LinkedIn URL"
                        type="url"
                    />
                    <flux:input
                        label="Instagram"
                        wire:model="formData.instagram"
                        placeholder="Instagram URL"
                        type="url"
                    />
                    <flux:input
                        label="YouTube"
                        wire:model="formData.youtube"
                        placeholder="YouTube URL"
                        type="url"
                    />
                    <flux:input
                        label="Color Scheme"
                        wire:model="formData.color_scheme"
                        type="color"
                    />
                    <flux:input
                        label="Logo"
                        wire:model="formData.logo"
                        placeholder="Logo URL"
                    />
                    <flux:input
                        label="Dark Logo"
                        wire:model="formData.logo_dark"
                        placeholder="Dark Logo URL"
                    />
                    <flux:input
                        label="Favicon"
                        wire:model="formData.favicon"
                        placeholder="Favicon URL"
                    />
                </div>

                <!-- Legal Information -->
                <flux:separator>Legal Information</flux:separator>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:input
                        label="Legal Entity Type"
                        wire:model="formData.legal_entity_type"
                        placeholder="Legal Entity Type"
                    />
                    <flux:input
                        label="Legal Registration Certificate"
                        wire:model="formData.legal_reg_certificate"
                        placeholder="Legal Registration Certificate"
                    />
                    <flux:input
                        label="Legal Certificate Number"
                        wire:model="formData.legal_certificate_number"
                        placeholder="Legal Certificate Number"
                    />
                    <flux:input
                        label="Tax Registration Certificate"
                        wire:model="formData.tax_reg_certificate"
                        placeholder="Tax Registration Certificate"
                    />
                    <flux:input
                        label="Tax Certificate Number"
                        wire:model="formData.tax_certificate_no"
                        placeholder="Tax Certificate Number"
                    />
                </div>

                <flux:switch
                    wire:model.live="formData.is_inactive"
                    label="Mark as Inactive"
                />

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <flux:button type="submit" variant="primary">
                        Save
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Table Start-->
    <flux:table :paginate="$this->list" class="">
        <flux:table.columns class="bg-zinc-200 dark:bg-zinc-800 border-b dark:border-zinc-700">
            <flux:table.column>Brand Name</flux:table.column>
            <flux:table.column>Firm</flux:table.column>
            <flux:table.column>Website</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->list as $firmBranding)
                <flux:table.row :key="$firmBranding->id" class="border-b">
                    <flux:table.cell class="table-cell-wrap">
                        <div class="flex items-center gap-2">
                            @if($firmBranding->color_scheme)
                                <div class="w-3 h-3 rounded-full" style="background-color: {{ $firmBranding->color_scheme }}"></div>
                            @endif
                            {{ $firmBranding->brand_name }}
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $firmBranding->firm->name }}</flux:table.cell>
                    <flux:table.cell>{{ $firmBranding->website }}</flux:table.cell>
                    <flux:table.cell>{{ $firmBranding->email }}</flux:table.cell>
                    <flux:table.cell>{{ $firmBranding->phone }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:switch
                            wire:model="statuses.{{ $firmBranding->id }}"
                            wire:click="toggleStatus({{ $firmBranding->id }})"
                        />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex space-x-2">
                            <flux:button
                                variant="primary"
                                size="sm"
                                icon="pencil"
                                wire:click="edit({{ $firmBranding->id }})"
                            />

                            <flux:modal.trigger name="delete-{{ $firmBranding->id }}">
                                <flux:button variant="danger" size="sm" icon="trash"/>
                            </flux:modal.trigger>
                        </div>

                        <!-- Delete Modal -->
                        <flux:modal name="delete-{{ $firmBranding->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Delete Firm Branding?</flux:heading>
                                    <flux:text class="mt-2">
                                        <p>You're about to delete this firm branding. This action cannot be undone.</p>
                                    </flux:text>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer/>
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                        type="submit"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="delete({{ $firmBranding->id }})"
                                    />
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div> 