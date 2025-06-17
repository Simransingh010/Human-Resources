<div class="space-y-6">
    <div class="max-w-7xl mx-auto">
        <flux:heading size="xl" class="text-center mb-6">HRMS Navigation System</flux:heading>
        <div class="flex gap-4 h-96">
            <!-- Column 1: Applications -->
            <flux:card class="w-full flex flex-col transition-all duration-300 ease-in-out" :class="$collapsed[1] ? 'w-12' : 'w-full'">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-blue-50">
                    <span class="font-semibold text-gray-700" :class="$collapsed[1] ? 'hidden' : ''">Applications</span>
                    <flux:button size="xs" icon="chevron-left" variant="ghost" wire:click="toggleCollapse(1)">
                        <span class="transform transition-transform text-lg">{{ $collapsed[1] ? '▶' : '◀' }}</span>
                    </flux:button>
                </div>
                <div class="flex-1 overflow-y-auto p-2" @if($collapsed[1]) style="display:none" @endif>
                    <ul class="space-y-2">
                        @foreach($data as $key => $app)
                            <li class="p-3 bg-blue-50 rounded hover:bg-blue-100 cursor-pointer transition-colors border-l-4 {{ $selectedApplication === $key ? 'border-blue-400 bg-blue-100' : 'border-transparent' }}"
                                wire:click="selectApplication('{{ $key }}')">
                                <div class="font-medium">{{ $app['name'] }}</div>
                                <div class="text-sm text-gray-600">{{ $app['desc'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </flux:card>

            <!-- Column 2: Modules -->
            <flux:card class="w-full flex flex-col transition-all duration-300 ease-in-out" :class="$collapsed[2] ? 'w-12 opacity-50' : ($selectedApplication ? 'w-full' : 'w-full opacity-50')">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-green-50">
                    <span class="font-semibold text-gray-700" :class="$collapsed[2] ? 'hidden' : ''">Modules</span>
                    <flux:button size="xs" icon="chevron-left" variant="ghost" wire:click="toggleCollapse(2)">
                        <span class="transform transition-transform text-lg">{{ $collapsed[2] ? '▶' : '◀' }}</span>
                    </flux:button>
                </div>
                <div class="flex-1 overflow-y-auto p-2" @if($collapsed[2]) style="display:none" @endif>
                    @if($selectedApplication && !empty($data[$selectedApplication]['modules']))
                        <ul class="space-y-2">
                            @foreach($data[$selectedApplication]['modules'] as $key => $mod)
                                <li class="p-3 bg-green-50 rounded hover:bg-green-100 cursor-pointer transition-colors border-l-4 {{ $selectedModule === $key ? 'border-green-400 bg-green-100' : 'border-transparent' }}"
                                    wire:click="selectModule('{{ $key }}')">
                                    <div class="font-medium">{{ $mod['name'] }}</div>
                                    <div class="text-sm text-gray-600">{{ $mod['desc'] }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-gray-400 mt-10">Select an application to view modules</div>
                    @endif
                </div>
            </flux:card>

            <!-- Column 3: Sections -->
            <flux:card class="w-full flex flex-col transition-all duration-300 ease-in-out" :class="$collapsed[3] ? 'w-12 opacity-50' : ($selectedModule ? 'w-full' : 'w-full opacity-50')">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-yellow-50">
                    <span class="font-semibold text-gray-700" :class="$collapsed[3] ? 'hidden' : ''">Sections</span>
                    <flux:button size="xs" icon="chevron-left" variant="ghost" wire:click="toggleCollapse(3)">
                        <span class="transform transition-transform text-lg">{{ $collapsed[3] ? '▶' : '◀' }}</span>
                    </flux:button>
                </div>
                <div class="flex-1 overflow-y-auto p-2" @if($collapsed[3]) style="display:none" @endif>
                    @if($selectedApplication && $selectedModule && !empty($data[$selectedApplication]['modules'][$selectedModule]['sections']))
                        <ul class="space-y-2">
                            @foreach($data[$selectedApplication]['modules'][$selectedModule]['sections'] as $key => $section)
                                <li class="p-3 bg-yellow-50 rounded hover:bg-yellow-100 cursor-pointer transition-colors border-l-4 {{ $selectedSection === $key ? 'border-yellow-400 bg-yellow-100' : 'border-transparent' }}"
                                    wire:click="selectSection('{{ $key }}')">
                                    <div class="font-medium">{{ $section['name'] }}</div>
                                    <div class="text-sm text-gray-600">{{ $section['desc'] }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-gray-400 mt-10">Select a module to view sections</div>
                    @endif
                </div>
            </flux:card>

            <!-- Column 4: Components -->
            <flux:card class="w-full flex flex-col transition-all duration-300 ease-in-out" :class="$collapsed[4] ? 'w-12 opacity-50' : ($selectedSection ? 'w-full' : 'w-full opacity-50')">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-purple-50">
                    <span class="font-semibold text-gray-700" :class="$collapsed[4] ? 'hidden' : ''">Components</span>
                    <flux:button size="xs" icon="chevron-left" variant="ghost" wire:click="toggleCollapse(4)">
                        <span class="transform transition-transform text-lg">{{ $collapsed[4] ? '▶' : '◀' }}</span>
                    </flux:button>
                </div>
                <div class="flex-1 overflow-y-auto p-2" @if($collapsed[4]) style="display:none" @endif>
                    @if($selectedApplication && $selectedModule && $selectedSection && !empty($data[$selectedApplication]['modules'][$selectedModule]['sections'][$selectedSection]['components']))
                        <ul class="space-y-2">
                            @foreach($data[$selectedApplication]['modules'][$selectedModule]['sections'][$selectedSection]['components'] as $component)
                                <li class="p-3 bg-purple-50 rounded hover:bg-purple-100 cursor-pointer transition-colors border-l-4 {{ $selectedComponent === $component ? 'border-purple-400 bg-purple-100' : 'border-transparent' }}"
                                    wire:click="selectComponent('{{ $component }}')">
                                    <div class="font-medium">{{ $component }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-gray-400 mt-10">Select a section to view components</div>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- Breadcrumb -->
        <div class="mt-4 p-3 bg-white rounded-lg shadow-sm border">
            <div class="text-sm text-gray-600 mb-1">Current Selection:</div>
            <div class="text-lg font-medium text-gray-800">
                @if(count($this->breadcrumb))
                    {{ implode(' > ', $this->breadcrumb) }}
                @else
                    Select an application to start
                @endif
            </div>
        </div>
    </div>
</div>
