<div>
    <!-- Add Date Picker -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stats-ds">
        <div class="mb-4">
            <flux:input
                    type="date"
                    wire:model.live="selectedDate"
                    class="w-48"
            />
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 stats-ds">
        <!-- Total Employees Card -->
        <flux:card class=" border-0 overflow-hidden p-[1px]">
            <div class="bg-violet-400/20 dark:bg-gray-800 h-full p-6 border">
                <div class=" flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Total Employees
                        </flux:text>
                        <flux:heading size="xl" class="font-bold text-gray-900 dark:text-white">
                            {{ $totalEmployees }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="blue" class="p-3 rounded-full border border-zinc-800/15">
                        <flux:icon name="users" class="w-6 h-6"/>
                    </flux:badge>
                </div>
            </div>
        </flux:card>

        <!-- Present Today Card -->
        <flux:card class="border-0 overflow-hidden p-[1px]">
            <div class="bg-teal-400/20 dark:bg-gray-800 h-full p-6 border">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Present Today
                        </flux:text>
                        <flux:heading size="xl" class="font-bold text-green-600 dark:text-green-400">
                            {{ $presentToday }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="green" class="p-3 rounded-full border border-zinc-800/15">
                        <flux:icon name="check-circle" class="w-6 h-6"/>
                    </flux:badge>
                </div>
                <div class="mt-2">
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format(($presentToday / max($totalEmployees, 1)) * 100, 1) }}% Present Rate
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <!-- Absent Today Card -->
        <flux:card class=" border-0 overflow-hidden p-[1px]">
            <div class="bg-rose-400/20 dark:bg-gray-800 dark:bg-gray-800 h-full p-6 border">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            Absent Today
                        </flux:text>
                        <flux:heading size="xl" class="font-bold text-red-600 dark:text-red-400">
                            {{ $absentToday }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="red" class="p-3 rounded-full border border-zinc-800/15">
                        <flux:icon name="x-circle" class="w-6 h-6"/>
                    </flux:badge>
                </div>
                <div class="mt-2">
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format(($absentToday / max($totalEmployees, 1)) * 100, 1) }}% Absent Rate
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <!-- On Leave Today Card -->
        <flux:card class="border-0 overflow-hidden p-[1px]">
            <div class="bg-yellow-400/25 dark:bg-gray-800 h-full p-6 border">
                <div class=" flex items-center justify-between">
                    <div>
                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            On Leave Today
                        </flux:text>
                        <flux:heading size="xl" class="font-bold text-yellow-600 dark:text-yellow-400">
                            {{ $onLeaveToday }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="yellow" class="p-3 rounded-full border border-zinc-800/15">
                        <flux:icon name="calendar" class="w-6 h-6"/>
                    </flux:badge>
                </div>
                <div class="mt-2">
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format(($onLeaveToday / max($totalEmployees, 1)) * 100, 1) }}% Leave Rate
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Add Leave Requests Section after the stats cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
        <div class="mt-6">
            <flux:card class="border-0 bg-zinc-200 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg" class="text-gray-900 dark:text-white">
                            Today's Leave Requests
                        </flux:heading>
                    </div>

                @if($this->todayLeaveRequests->count() > 0)
                    <!-- Marquee Container -->
                        <div class="overflow-x-auto">
                            <div class="flex space-x-4 pb-2"> <!-- Added horizontal spacing -->
                                @foreach($this->todayLeaveRequests as $request)
                                    <div class="flex-shrink-0 w-64 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <!-- Employee Avatar -->
                                        <div class="flex items-center space-x-3 mb-3">
                                            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                                <flux:icon name="user" class="w-full h-full p-2 text-gray-400"/>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ $request->employee->fname }} {{ $request->employee->lname }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Leave Details -->
                                        <div class="text-sm space-y-1">
                                            <p class="text-gray-500 dark:text-gray-400">
                                                {{ $request->reason ?: 'No reason provided' }}
                                            </p>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ $request->leave_type->name }}</span>
                                                <div class="mt-1">
                                                    {{ Carbon\Carbon::parse($request->apply_from)->format('M d') }} -
                                                    {{ Carbon\Carbon::parse($request->apply_to)->format('M d, Y') }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Status Badge -->
                                        <div class="mt-3">
                                            @if($request->status === 'approved')
                                                <flux:badge color="green" size="sm">Approved</flux:badge>
                                            @elseif($request->status === 'rejected')
                                                <flux:badge color="red" size="sm">Rejected</flux:badge>
                                            @else
                                                <flux:badge color="yellow" size="sm">Pending</flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                @else
                    <!-- Empty State (unchanged) -->
                        <div class="text-center py-8">
                            <flux:icon name="calendar" class="mx-auto h-12 w-12 text-gray-400"/>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Leave Requests</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                There are no leave requests for this date.
                            </p>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>
</div>