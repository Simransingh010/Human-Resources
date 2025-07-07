<?php
use Carbon\Carbon;
?>
<div class="space-y-6">
    <!-- Header Section -->
    <div
        class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <flux:date-picker wire:model.live="selectedDate" class="w-full md:w-64" />
        </div>

    </div>

    <!-- Primary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Present Today Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <flux:text class="text-sm font-medium text-gray-500 dark:text-gray-400">Present Today</flux:text>
                    <flux:badge color="green"
                        class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                        <flux:icon name="check-circle" class="w-4 h-4" />
                    </flux:badge>
                </div>
                <div class="flex items-baseline justify-between">
                    <flux:heading class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $presentToday }}
                    </flux:heading>
                    <flux:text class="text-sm font-medium text-green-600 dark:text-green-400">
                        {{ number_format(($presentToday / max($totalEmployees, 1)) * 100, 1) }}%
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <!-- Absent Today Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <flux:text class="text-sm font-medium text-gray-500 dark:text-gray-400">Absent Today</flux:text>
                    <flux:badge color="red" class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                        <flux:icon name="x-circle" class="w-4 h-4" />
                    </flux:badge>
                </div>
                <div class="flex items-baseline justify-between">
                    <flux:heading class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $absentToday }}
                    </flux:heading>
                    <flux:text class="text-sm font-medium text-red-600 dark:text-red-400">
                        {{ number_format(($absentToday / max($totalEmployees, 1)) * 100, 1) }}%
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <!-- On Leave Today Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <flux:text class="text-sm font-medium text-gray-500 dark:text-gray-400">On Leave Today</flux:text>
                    <flux:badge color="yellow"
                        class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">
                        <flux:icon name="calendar" class="w-4 h-4" />
                    </flux:badge>
                </div>
                <div class="flex items-baseline justify-between">
                    <flux:heading class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $onLeaveToday }}
                    </flux:heading>
                    <flux:text class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                        {{ number_format(($onLeaveToday / max($totalEmployees, 1)) * 100, 1) }}%
                    </flux:text>
                </div>
            </div>
        </flux:card>

        <!-- Not Marked Today Card -->
        <flux:tooltip>
            <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:text class="text-sm font-medium text-gray-500 dark:text-gray-400">Not Marked Today
                        </flux:text>
                        <flux:badge color="gray"
                            class="bg-gray-100 dark:bg-gray-900/30 text-gray-700 dark:text-gray-300">
                            <flux:icon name="question-mark-circle" class="w-4 h-4" />
                        </flux:badge>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <flux:heading class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $notMarkedToday }}
                        </flux:heading>
                        <flux:text class="text-sm font-medium text-gray-600 dark:text-gray-400">
                            {{ number_format(($notMarkedToday / max($totalEmployees, 1)) * 100, 1) }}%
                        </flux:text>
                    </div>
                </div>
            </flux:card>
            <flux:tooltip.content
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-4 w-72">
                <flux:text class="text-lg font-bold dark:text-white mb-2">Employees Not Marked</flux:text>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    @forelse ($this->employeesNotMarked as $employee)
                        <div class="flex items-center space-x-3 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                            @if ($employee->emp_personal_detail && $employee->emp_personal_detail->getFirstMediaUrl('employee_image'))
                                <img src="{{ $employee->emp_personal_detail->getFirstMediaUrl('employee_image') }}"
                                    alt="{{ $employee->fname }} {{ $employee->lname }}"
                                    class="w-8 h-8 rounded-full object-cover">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <flux:icon name="user" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </div>
                            @endif
                            <div>
                                <flux:text class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $employee->fname }} {{ $employee->lname }}
                                </flux:text>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <flux:text class="text-gray-500 dark:text-gray-400">
                                All employees are accounted for.
                            </flux:text>
                        </div>
                    @endforelse
                </div>
            </flux:tooltip.content>
        </flux:tooltip>
        <!-- Pending Leave Requests Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class="p-2 bg-gradient-to-br from-orange-500/10 to-orange-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <flux:heading class="font-bold text-gray-900 dark:text-white">Pending Leave Requests
                        </flux:heading>
                    </div>
                    <flux:badge color="orange"
                        class="bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">
                        <flux:icon name="exclamation-triangle" class="w-4 h-4" />
                    </flux:badge>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <flux:text class="text-gray-600 dark:text-gray-400">Total Pending</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-white">{{ $pendingLeaveRequestsCount }}
                        </flux:text>
                    </div>
                </div>
            </div>
        </flux:card>

    </div>

    <!-- Analytics Section -->
    <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading class="text-xl font-bold text-gray-900 dark:text-white">Monthly Attendance Overview
                    </flux:heading>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Last 30 days attendance trends
                    </flux:text>
                </div>
                <div class="flex items-center gap-4">
                    <flux:chart.summary class="flex gap-8">
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Today</flux:text>
                            <flux:heading class="text-lg font-semibold text-gray-900 dark:text-white">
                                <flux:chart.summary.value field="present" />
                            </flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Yesterday</flux:text>
                            <flux:heading class="text-lg font-semibold text-gray-900 dark:text-white">
                                <flux:chart.summary.value field="yesterday" />
                            </flux:heading>
                        </div>
                    </flux:chart.summary>
                </div>
            </div>
            <flux:chart wire:model="data" class="aspect-[3/1]" style="height: 365px;">
                <flux:chart.svg>
                    <flux:chart.line field="present" class="text-green-500" />
                    <flux:chart.point field="present" class="text-green-400" />
                    <flux:chart.line field="absent" class="text-red-500" />
                    <flux:chart.point field="absent" class="text-red-400" />
                    <flux:chart.line field="leave" class="text-yellow-500" />
                    <flux:chart.point field="leave" class="text-yellow-400" />
                    <flux:chart.line field="notMarked" class="text-gray-500" />
                    <flux:chart.point field="notMarked" class="text-gray-400" />
                    <flux:chart.axis axis="x" field="date">
                        <flux:chart.axis.tick class="text-gray-600 dark:text-gray-400" />
                        <flux:chart.axis.line class="stroke-gray-300 dark:stroke-gray-600" />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid class="stroke-gray-200 dark:stroke-gray-700" />
                        <flux:chart.axis.tick class="text-gray-600 dark:text-gray-400" />
                    </flux:chart.axis>
                    <flux:chart.cursor />
                </flux:chart.svg>

                <!-- Graph Legend -->
                <div
                    class="mt-6 flex flex-wrap justify-end gap-x-8 gap-y-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-green-500"></div>
                        <span>Present Days</span>
                    </div>
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-red-500"></div>
                        <span>Absent Days</span>
                    </div>
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                        <span>Leave Days</span>
                    </div>
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-gray-500"></div>
                        <span>Not Marked Days</span>
                    </div>
                </div>

            </flux:chart>
        </div>
    </flux:card>

    <!-- Details Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
        <!-- Employees on Leave Today -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class="h-full p-6 bg-gradient-to-br from-yellow-500/10 to-yellow-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:text size="lg"
                            class="text-lg font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Employees on Leave Today
                        </flux:text>
                        <flux:heading class="font-extrabold text-yellow-600 dark:text-yellow-400 mt-1">
                            {{ $onLeaveToday }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="yellow"
                        class="p-3 rounded-full bg-yellow-500/20 dark:bg-yellow-700/30 border border-yellow-600/30 dark:border-yellow-400/20 shadow-md">
                        <flux:icon name="calendar" class="w-6 h-6 text-yellow-700 dark:text-yellow-300" />
                    </flux:badge>
                </div>

                <div class="mt-4 pt-4 space-y-3 border-t border-gray-200 dark:border-gray-700/50">
                    @foreach($this->todayLeaveRequests() as $leave)
                        <div
                            class="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-3">
                                @if($leave->employee->emp_personal_detail && $leave->employee->emp_personal_detail->getFirstMediaUrl('employee_image'))
                                    <img src="{{ $leave->employee->emp_personal_detail->getFirstMediaUrl('employee_image') }}"
                                        alt="{{ $leave->employee->fname }} {{ $leave->employee->lname }}"
                                        class="w-8 h-8 rounded-full object-cover" />
                                @else
                                    <flux:icon name="user" class="w-8 h-8 text-gray-500" />
                                @endif
                                <flux:text class="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {{ $leave->employee->fname }} {{ $leave->employee->lname }}
                                </flux:text>
                                <flux:tooltip>
                                    <flux:button size='sm' variant="ghost" icon="information-circle"
                                        class="text-gray-500 dark:text-gray-400 p-0 m-0" />
                                    <flux:tooltip.content
                                        class="bg-gray-900 text-white text-xs p-3 rounded-lg shadow-xl border border-gray-700">
                                        <p class="mb-1"><strong>Phone:</strong> {{ $leave->employee->phone ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Email:</strong> {{ $leave->employee->email ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Department:</strong>
                                            {{ $leave->employee->emp_job_profile->department->title ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Designation:</strong>
                                            {{ $leave->employee->emp_job_profile->designation->title ?? 'N/A' }}</p>
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <flux:badge size="md" color="yellow"
                                class="font-semibold bg-yellow-500/20 dark:bg-yellow-700/30 text-yellow-700 dark:text-yellow-300">
                                {{ $leave->leave_type->leave_title }}
                            </flux:badge>
                        </div>
                    @endforeach

                    @if($this->todayLeaveRequests()->isEmpty())
                        <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                            No employees on leave today.
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Today's Birthdays -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class="h-full p-6 bg-gradient-to-br from-pink-500/10 to-pink-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:text size="lg"
                            class="text-lg font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Today's Birthdays
                        </flux:text>
                        <flux:heading class="font-extrabold text-pink-600 dark:text-pink-400 mt-1">
                            {{ $this->todayBirthdays()->count() }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="pink"
                        class="p-3 rounded-full bg-pink-500/20 dark:bg-pink-700/30 border border-pink-600/30 dark:border-pink-400/20 shadow-md">
                        <flux:icon name="cake" class="w-6 h-6 text-pink-700 dark:text-pink-300" />
                    </flux:badge>
                </div>

                <div class="mt-4 pt-4 space-y-3 border-t border-gray-200 dark:border-gray-700/50">
                    @foreach($this->todayBirthdays() as $birthday)
                        <div
                            class="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-3">
                                @if($birthday->getFirstMediaUrl('employee_image'))
                                    <img src="{{ $birthday->getFirstMediaUrl('employee_image') }}"
                                        alt="{{ $birthday->employee->fname }} {{ $birthday->employee->lname }}"
                                        class="w-8 h-8 rounded-full object-cover" />
                                @else
                                    <flux:icon name="user" class="w-8 h-8 text-gray-500" />
                                @endif
                                <flux:text class="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {{ $birthday->employee->fname }} {{ $birthday->employee->lname }}
                                </flux:text>
                                <flux:tooltip>
                                    <flux:button size='sm' variant="ghost" icon="information-circle"
                                        class="text-gray-500 dark:text-gray-400 p-0 m-0" />
                                    <flux:tooltip.content
                                        class="bg-gray-900 text-white text-xs p-3 rounded-lg shadow-xl border border-gray-700">
                                        <p class="mb-1"><strong>Department:</strong>
                                            {{ $birthday->employee->emp_job_profile->department->title ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Designation:</strong>
                                            {{ $birthday->employee->emp_job_profile->designation->title ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Age:</strong>
                                            {{ Carbon::parse($birthday->dob)->age }} years</p>
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <flux:badge size="md" color="pink"
                                class="font-semibold bg-pink-500/20 dark:bg-pink-700/30 text-pink-700 dark:text-pink-300">
                                Birthday
                            </flux:badge>
                        </div>
                    @endforeach

                    @if($this->todayBirthdays()->isEmpty())
                        <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                            No birthdays today.
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Work Anniversaries -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class="h-full p-6 bg-gradient-to-br from-blue-500/10 to-blue-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:text size="lg"
                            class="text-lg font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Work Anniversaries
                        </flux:text>
                        <flux:heading class="font-extrabold text-blue-600 dark:text-blue-400 mt-1">
                            {{ $this->todayWorkAnniversaries()->count() }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="blue"
                        class="p-3 rounded-full bg-blue-500/20 dark:bg-blue-700/30 border border-blue-600/30 dark:border-blue-400/20 shadow-md">
                        <flux:icon name="trophy" class="w-6 h-6 text-blue-700 dark:text-blue-300" />
                    </flux:badge>
                </div>

                <div class="mt-4 pt-4 space-y-3 border-t border-gray-200 dark:border-gray-700/50">
                    @foreach($this->todayWorkAnniversaries() as $anniversary)
                        <div
                            class="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-3">
                                @if($anniversary->getFirstMediaUrl('employee_image'))
                                    <img src="{{ $anniversary->getFirstMediaUrl('employee_image') }}"
                                        alt="{{ $anniversary->employee->fname }} {{ $anniversary->employee->lname }}"
                                        class="w-8 h-8 rounded-full object-cover" />
                                @else
                                    <flux:icon name="user" class="w-8 h-8 text-gray-500" />
                                @endif
                                <flux:text class="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {{ $anniversary->employee->fname }} {{ $anniversary->employee->lname }}
                                </flux:text>
                                <flux:tooltip>
                                    <flux:button size='sm' variant="ghost" icon="information-circle"
                                        class="text-gray-500 dark:text-gray-400 p-0 m-0" />
                                    <flux:tooltip.content
                                        class="bg-gray-900 text-white text-xs p-3 rounded-lg shadow-xl border border-gray-700">
                                        <p class="mb-1"><strong>Department:</strong>
                                            {{ $anniversary->employee->emp_job_profile->department->title ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Designation:</strong>
                                            {{ $anniversary->employee->emp_job_profile->designation->title ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Years:</strong>
                                            {{ floor(Carbon::parse($anniversary->doa)->diffInYears(Carbon::now())) }} years
                                        </p>
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </div>
                            <flux:badge size="md" color="blue"
                                class="font-semibold bg-blue-500/20 dark:bg-blue-700/30 text-blue-700 dark:text-blue-300">
                                {{ floor(Carbon::parse($anniversary->doa)->diffInYears(Carbon::now())) }} Years
                            </flux:badge>
                        </div>
                    @endforeach

                    @if($this->todayWorkAnniversaries()->isEmpty())
                        <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                            No work anniversaries today.
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Employees Without Policies/Shifts -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class="h-full p-6 bg-gradient-to-br from-red-500/10 to-red-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <flux:text size="lg"
                            class="text-lg font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Missing Assignments
                        </flux:text>
                        <flux:heading class="font-extrabold text-red-600 dark:text-red-400 mt-1">
                            {{ $this->employeesWithoutAttendancePolicy()->count() + $this->employeesWithoutWorkShift()->count() }}
                        </flux:heading>
                    </div>
                    <flux:badge size="lg" color="red"
                        class="p-3 rounded-full bg-red-500/20 dark:bg-red-700/30 border border-red-600/30 dark:border-red-400/20 shadow-md">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6 text-red-700 dark:text-red-300" />
                    </flux:badge>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700/50 overflow-y-auto"
                    style="height: 400px;">
                    <flux:accordion transition class="w-full space-y-2">
                        <flux:accordion.item expanded>
                            <flux:accordion.heading>
                                <div class="flex justify-between items-center w-full">
                                    <span>Without Attendance Policy
                                        ({{ $this->employeesWithoutAttendancePolicy()->count() }})</span>
                                </div>
                            </flux:accordion.heading>
                            <flux:accordion.content class="pl-2">
                                <div class="space-y-2">
                                    @foreach($this->employeesWithoutAttendancePolicy() as $employee)
                                        <div
                                            class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center space-x-3">
                                                @if($employee->emp_personal_detail && $employee->emp_personal_detail->getFirstMediaUrl('employee_image'))
                                                    <img src="{{ $employee->emp_personal_detail->getFirstMediaUrl('employee_image') }}"
                                                        alt="{{ $employee->fname }} {{ $employee->lname }}"
                                                        class="w-6 h-6 rounded-full object-cover" />
                                                @else
                                                    <flux:icon name="user" class="w-6 h-6 text-gray-500" />
                                                @endif
                                                <flux:text class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                    {{ $employee->fname }} {{ $employee->lname }}
                                                </flux:text>
                                            </div>
                                            <flux:badge size="sm" color="red" class="text-xs">
                                                No Policy
                                            </flux:badge>
                                        </div>
                                    @endforeach
                                </div>
                            </flux:accordion.content>
                        </flux:accordion.item>
                        <flux:accordion.item>
                            <flux:accordion.heading>
                                <div class="flex justify-between items-center w-full">
                                    <span>Without Work Shift ({{ $this->employeesWithoutWorkShift()->count() }})</span>
                                </div>
                            </flux:accordion.heading>
                            <flux:accordion.content class="pl-2">
                                <div class="space-y-2">
                                    @foreach($this->employeesWithoutWorkShift() as $employee)
                                        <div
                                            class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                                            <div class="flex items-center space-x-3">
                                                @if($employee->emp_personal_detail && $employee->emp_personal_detail->getFirstMediaUrl('employee_image'))
                                                    <img src="{{ $employee->emp_personal_detail->getFirstMediaUrl('employee_image') }}"
                                                        alt="{{ $employee->fname }} {{ $employee->lname }}"
                                                        class="w-6 h-6 rounded-full object-cover" />
                                                @else
                                                    <flux:icon name="user" class="w-6 h-6 text-gray-500" />
                                                @endif
                                                <flux:text class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                    {{ $employee->fname }} {{ $employee->lname }}
                                                </flux:text>
                                            </div>
                                            <flux:badge size="sm" color="red" class="text-xs">
                                                No Shift
                                            </flux:badge>
                                        </div>
                                    @endforeach
                                </div>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    </flux:accordion>
                </div>
            </div>
        </flux:card>

        <!-- Calendar Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class=" p-6 bg-gradient-to-br from-blue-500/10 to-blue-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <flux:text size="lg" class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4"> Calendar
                </flux:text>
                <div class="flex justify-center items-center h-full">
                    <flux:calendar wire:model.live="selectedDate" size="2xl"  selectable-header class="w-full max-w-sm" />
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700/50">
                    <flux:text size="lg" class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Holidays
                        this {{ Carbon::parse($selectedDate)->format('F Y') }}
                    </flux:text>
                    @if($holidays)
                        <ul class="space-y-2">
                            @foreach($holidays as $holiday)
                                @if(Carbon::parse($holiday['date'])->format('Y-m') == Carbon::parse($selectedDate)->format('Y-m'))
                                    <li
                                        class="flex items-center justify-between p-2 bg-white dark:bg-gray-700 rounded-lg shadow-sm">
                                        <span
                                            class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ Carbon::parse($holiday['date'])->format('M d') }}</span>
                                        <flux:tooltip>
                                            <span
                                                class="text-sm font-medium text-blue-600 dark:text-blue-400 cursor-help">{{ $holiday['title'] }}</span>
                                            <flux:tooltip.content class="bg-gray-900 text-white text-xs p-2 rounded-md shadow-lg">
                                                {{ $holiday['title'] }}
                                            </flux:tooltip.content>
                                        </flux:tooltip>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-gray-500 dark:text-gray-400 py-2">
                            No holidays in this month.
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Current Payroll Cycle Card -->
        <flux:card
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-xl transition-shadow duration-300">
            <div
                class="h-full p-6 bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-pink-500/5 dark:from-gray-700/10 dark:via-gray-800/5 dark:to-gray-900/5">
                <div class="flex items-center justify-between mb-6">
                    <div class="space-y-1">
                        <flux:text size="lg"
                            class="text-xl font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Current Payroll Cycle
                        </flux:text>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">
                            Active payroll processing status
                        </flux:text>
                    </div>
                    <div class="flex items-center space-x-2">
                        <flux:badge size="lg" color="indigo"
                            class="px-4 py-2 rounded-full bg-indigo-500/20 dark:bg-indigo-700/30 border border-indigo-600/30 dark:border-indigo-400/20 shadow-md backdrop-blur-sm">
                            <flux:icon name="currency-dollar" class="w-5 h-5 text-indigo-700 dark:text-indigo-300" />
                        </flux:badge>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700/50 space-y-4">
                    <div
                        class="flex items-center justify-between p-3 rounded-lg bg-white/50 dark:bg-gray-800/50 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <flux:text class="text-base text-gray-600 dark:text-gray-400">
                            Payroll Cycle
                        </flux:text>
                        <flux:text class="text-base font-semibold text-indigo-700 dark:text-indigo-300">
                            {{ $currentPayrollCycleName }}
                        </flux:text>
                    </div>

                    <div
                        class="flex items-center justify-between p-3 rounded-lg bg-white/50 dark:bg-gray-800/50 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <flux:text class="text-base text-gray-600 dark:text-gray-400">
                            Status
                        </flux:text>
                        <div class="flex items-center space-x-2">
                            <div
                                class="w-2 h-2 rounded-full {{ $currentPayrollStatus === 'Completed' ? 'bg-green-500' : ($currentPayrollStatus === 'Started' ? 'bg-blue-500' : 'bg-yellow-500') }}">
                            </div>
                            <flux:text class="text-base font-semibold text-indigo-700 dark:text-indigo-300">
                                {{ $currentPayrollStatus }}
                            </flux:text>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between p-3 rounded-lg bg-white/50 dark:bg-gray-800/50 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <flux:text class="text-base text-gray-600 dark:text-gray-400">
                            Period
                        </flux:text>
                        <flux:text class="text-base font-semibold text-indigo-700 dark:text-indigo-300">
                            {{ $currentPayrollPeriod }}
                        </flux:text>
                    </div>

                    <div
                        class="flex items-center justify-between p-3 rounded-lg bg-white/50 dark:bg-gray-800/50 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <flux:text class="text-base text-gray-600 dark:text-gray-400">
                            Total Employees
                        </flux:text>
                        <div class="flex items-center space-x-2">
                            <flux:icon name="users" class="w-5 h-5 text-indigo-700 dark:text-indigo-300" />
                            <flux:text class="text-base font-semibold text-indigo-700 dark:text-indigo-300">
                                {{ $currentPayrollEmployeesCount }}
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Quick Stats Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div
                class="p-6 bg-gradient-to-br from-purple-500/10 to-purple-400/10 dark:from-gray-700/10 dark:to-gray-800/10">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <flux:heading class="text-xl font-bold text-gray-900 dark:text-white">Quick Stats</flux:heading>
                        <flux:text class="text-sm text-gray-500 dark:text-gray-400">Overall company metrics</flux:text>
                    </div>
                    <flux:badge color="purple"
                        class="bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                        <flux:icon name="chart-bar" class="w-4 h-4" />
                    </flux:badge>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <flux:text class="text-gray-600 dark:text-gray-400">Total Employees</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-white">{{ $totalEmployees }}</flux:text>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <flux:text class="text-gray-600 dark:text-gray-400">Total Departments</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-white">{{ $totalDepartments }}</flux:text>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <flux:text class="text-gray-600 dark:text-gray-400">Total Male Employees</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-white">{{ $totalMaleEmployees }}
                        </flux:text>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <flux:text class="text-gray-600 dark:text-gray-400">Total Female Employees</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-white">{{ $totalFemaleEmployees }}
                        </flux:text>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <flux:text class="text-gray-600 dark:text-gray-400">Active Payroll Cycles</flux:text>
                        <flux:text class="font-medium text-gray-900 dark:text-white">{{ $activePayrollCyclesCount }}
                        </flux:text>
                    </div>

                </div>
            </div>
        </flux:card>

    </div>
</div>