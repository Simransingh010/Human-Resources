<?php
use Carbon\Carbon;
?>
<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div>
            <flux:heading class="text-3xl font-bold text-gray-900 dark:text-white mb-1">Welcome back, {{ auth()->user()->name }}!</flux:heading>
            <flux:text class="text-lg text-gray-600 dark:text-gray-400">Here's your attendance overview for this month</flux:text>
        </div>
        <div class="flex items-center gap-4">
            <flux:date-picker wire:model.live="selectedDate" class="w-full md:w-64" />
        </div>
    </div>

    <!-- Key Statistics Cards -->
    <div class="gap-6 grid grid-cols-3">
        <!-- Present Days Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <flux:text class="text-lg font-medium text-gray-500 dark:text-gray-400">Present Days</flux:text>
                <flux:badge color="green" class="text-sm px-3 py-1">
                    <flux:icon name="check-circle" class="w-5 h-5" />
                </flux:badge>
            </div>
            <div class="flex items-baseline justify-between">
                <flux:heading class="text-4xl font-bold text-gray-900 dark:text-white">
                    {{ $presentDays }}/{{ $workingDays }}
                </flux:heading>
                <flux:text class="text-base font-medium text-green-600 dark:text-green-400">
                    {{ number_format(($presentDays / max($workingDays, 1)) * 100, 1) }}% 
                    <!-- <span class="ml-1">+2%</span> -->
                </flux:text>
            </div>
        </flux:card>

        <!-- Average Hours Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <flux:text class="text-lg font-medium text-gray-500 dark:text-gray-400">Average Hours</flux:text>
                <flux:badge color="blue" class="text-sm px-3 py-1">
                    <flux:icon name="clock" class="w-5 h-5" />
                </flux:badge>
            </div>
            <div class="flex items-baseline justify-between">
                <flux:heading class="text-4xl font-bold text-gray-900 dark:text-white">
                    {{ $averageHours }} hrs
                </flux:heading>
                
            </div>
        </flux:card>

        <!-- This Week Card -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <flux:text class="text-lg font-medium text-gray-500 dark:text-gray-400">This Week Attendance</flux:text>
                <flux:badge color="purple" class="text-sm px-3 py-1">
                    <flux:icon name="calendar" class="w-5 h-5" />
                </flux:badge>
            </div>
            <div class="flex items-baseline justify-between">
                <flux:heading class="text-4xl font-bold text-gray-900 dark:text-white">
                    {{ $thisWeekPresent }}/{{ $thisWeekTotal }}
                </flux:heading>
                <flux:text class="text-base font-medium text-purple-600 dark:text-purple-400">
                    {{ number_format(($thisWeekPresent / max($thisWeekTotal, 1)) * 100, 0) }}%
                   
                </flux:text>
            </div>
        </flux:card>

      
    </div>

    <!-- Main Content Area -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Calendar View -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div class="p-6">
                <flux:text size="lg" class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-6">Monthly Calendar</flux:text>
                
                <!-- Flux Calendar Component -->
                <div class="w-full flex justify-center">
                    <flux:calendar wire:model.live="selectedDate" class="w-full" />
                </div>

                <!-- Calendar Legend -->
                <div class="mt-6 flex justify-center space-x-8">
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-full bg-green-500"></div>
                        <span class="text-base text-gray-600 dark:text-gray-400">Present</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                        <span class="text-base text-gray-600 dark:text-gray-400">Late</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-4 h-4 rounded-full bg-red-500"></div>
                        <span class="text-base text-gray-600 dark:text-gray-400">Absent</span>
                    </div>
                </div>
            </div>
        </flux:card>

        <!-- Recent Activity -->
        <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <flux:text size="lg" class="text-2xl font-bold text-gray-800 dark:text-gray-200">Recent Activity</flux:text>
                    <!-- <flux:button size="sm" variant="outline" color="gray" class="px-4 py-2">View All</flux:button> -->
                </div>
                <div class="space-y-6 max-h-[600px] overflow-y-auto">
                    @foreach($recentActivities as $activity)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl shadow-sm border border-gray-200 dark:border-gray-600 p-4">
                        <!-- Date and Status Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex flex-col">
                                <flux:text class="text-xl font-semibold text-gray-900 dark:text-white">{{ $activity['date_label'] }}</flux:text>
                                <flux:text class="text-base text-gray-600 dark:text-gray-400">{{ $activity['date'] }}</flux:text>
                            </div>
                            <div>
                                @if($activity['status'] === 'Present')
                                <flux:badge color="green" class="text-base px-4 py-1">Present</flux:badge>
                                @elseif($activity['status'] === 'Late')
                                <flux:badge color="yellow" class="text-base px-4 py-1">Late</flux:badge>
                                @else
                                <flux:badge color="red" class="text-base px-4 py-1">{{ $activity['status'] }}</flux:badge>
                                @endif
                            </div>
                        </div>
                        
                        <!-- First In and Last Out Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mb-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="flex flex-col">
                                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">First In</flux:text>
                                    <div class="flex items-center mt-1">
                                        <flux:icon name="arrow-right-circle" class="w-5 h-5 text-green-500 mr-2" />
                                        <flux:text class="text-lg font-medium">{{ $activity['check_in'] }}</flux:text>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Last Out</flux:text>
                                    <div class="flex items-center mt-1">
                                        <flux:icon name="arrow-left-circle" class="w-5 h-5 text-red-500 mr-2" />
                                        <flux:text class="text-lg font-medium">{{ $activity['check_out'] }}</flux:text>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Total Hours</flux:text>
                                    <flux:text class="text-lg font-medium mt-1">{{ $activity['hours'] }} hrs</flux:text>
                                </div>
                            </div>
                        </div>

                        <!-- All Punches -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                            <flux:text class="text-base font-medium text-gray-700 dark:text-gray-300 mb-3">All Punches</flux:text>
                            <div class="space-y-3">
                                @foreach($activity['all_punches'] as $punch)
{{--                                {{dd($punch)}}--}}
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <div class="flex items-center space-x-3">
                                        @if($punch['type'] === 'In')
                                        <flux:icon name="arrow-right-circle" class="w-5 h-5 text-green-500" />
                                        @else
                                        <flux:icon name="arrow-left-circle" class="w-5 h-5 text-red-500" />
                                        @endif
                                        <div>
                                            <flux:text class="text-base font-medium">{{ $punch['time'] }}</flux:text>
                                            <flux:text class="text-sm text-gray-500">{{ $punch['type'] }}</flux:text>
                                        </div>
                                    </div>
                                    <flux:tooltip text="{{ $punch['location'] }}">
                                        <flux:icon name="map-pin" class="w-5 h-5 text-gray-400 hover:text-gray-600" />
                                    </flux:tooltip>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Analytics Section -->
    <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <flux:heading class="text-xl font-bold text-gray-900 dark:text-white">Monthly Attendance Trends</flux:heading>
                    <flux:text class="text-sm text-gray-500 dark:text-gray-400">Your attendance patterns over time</flux:text>
                </div>
                <div class="flex items-center gap-4">
                    <flux:chart.summary class="flex gap-8">
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">This Month</flux:text>
                            <flux:heading class="text-lg font-semibold text-gray-900 dark:text-white">
                                <flux:chart.summary.value field="present" />
                            </flux:heading>
                        </div>
                        <div>
                            <flux:text class="text-sm text-gray-500 dark:text-gray-400">Last Month</flux:text>
                            <flux:heading class="text-lg font-semibold text-gray-900 dark:text-white">
                                <flux:chart.summary.value field="lastMonth" />
                            </flux:heading>
                        </div>
                    </flux:chart.summary>
                </div>
            </div>
            <flux:chart wire:model="chartData" class="aspect-[3/1]" style="height: 300px;">
                <flux:chart.svg>
                    <flux:chart.line field="present" class="text-green-500" />
                    <flux:chart.point field="present" class="text-green-400" />
                    <flux:chart.line field="late" class="text-yellow-500" />
                    <flux:chart.point field="late" class="text-yellow-400" />
                    <flux:chart.line field="absent" class="text-red-500" />
                    <flux:chart.point field="absent" class="text-red-400" />
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
                <div class="mt-6 flex flex-wrap justify-end gap-x-8 gap-y-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-green-500"></div>
                        <span>Present</span>
                    </div>
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                        <span>Late</span>
                    </div>
                    <div class="flex items-center space-x-2 px-2">
                        <div class="w-4 h-4 rounded-full bg-red-500"></div>
                        <span>Absent</span>
                    </div>
                </div>
            </flux:chart>
        </div>
    </flux:card>

    <!-- Last 6 Months Attendance Table Section -->
    <flux:card class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 mt-8">
        <div class="p-6">
            <div class="mb-4">
                <flux:heading class="text-xl font-bold text-gray-900 dark:text-white">Last 6 Months Attendance</flux:heading>
                <flux:text class="text-sm text-gray-500 dark:text-gray-400">Your attendance for each day of the last 6 months</flux:text>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs">Month</th>
                            @if(!empty($lastSixMonthsAttendance))
                                @php $maxDays = collect($lastSixMonthsAttendance)->max('daysInMonth'); @endphp
                                @for ($day = 1; $day <= $maxDays; $day++)
                                    <th class="p-1 text-center text-xs">{{ $day }}</th>
                                @endfor
                                <th class="px-4 py-3 text-center">Present</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($lastSixMonthsAttendance as $month)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="p-1 font-medium text-xs">{{ $month['label'] }}</td>
                                @for ($day = 1; $day <= $maxDays; $day++)
                                    @php $dayData = $month['attendance'][$day-1] ?? null; @endphp
                                    @if($day > $month['daysInMonth'])
                                        <td class="px-2 py-3 text-center text-gray-400">-</td>
                                    @else
                                    <td class="px-2 py-3 text-center">
                                        @if($dayData && isset($dayData['status']) && $dayData['status'])
                                            @switch($dayData['status'])
                                                @case('P')
                                                <flux:tooltip content="Present">
                                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('A')
                                                <flux:tooltip content="Absent">
                                                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('HD')
                                                <flux:tooltip content="Half Day">
                                                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 536 512">
                                                        <path d="M508.55 171.51L362.18 150.2 296.77 17.81C290.89 5.98 279.42 0 267.95 0c-11.4 0-22.79 5.9-28.69 17.81l-65.43 132.38-146.38 21.29c-26.25 3.8-36.77 36.09-17.74 54.59l105.89 103-25.06 145.48C86.98 495.33 103.57 512 122.15 512c4.93 0 10-1.17 14.87-3.75l130.95-68.68 130.94 68.7c4.86 2.55 9.92 3.71 14.83 3.71 18.6 0 35.22-16.61 31.66-37.4l-25.03-145.49 105.91-102.98c19.04-18.5 8.52-50.8-17.73-54.6zm-121.74 123.2l-18.12 17.62 4.28 24.88 19.52 113.45-102.13-53.59-22.38-11.74.03-317.19 51.03 103.29 11.18 22.63 25.01 3.64 114.23 16.63-82.65 80.38z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('PW')
                                                <flux:tooltip content="Partial Working">
                                                    <svg class="w-4 h-4 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10V2z"/>
                                                        <path d="M12 7v5l2.5 2.5.75-1.25-1.75-1.75V7H12z" fill="white"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('L')
                                                <flux:tooltip content="Leave">
                                                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 640 512">
                                                        <path d="M624 448H16c-8.84 0-16 7.16-16 16v32c0 8.84 7.16 16 16 16h608c8.84 0 16-7.16 16-16v-32c0-8.84-7.16-16-16-16zM80.55 341.27c6.28 6.84 15.1 10.72 24.33 10.71l130.54-.18a65.62 65.62 0 0 0 29.64-7.12l290.96-147.65c26.74-13.57 50.71-32.94 67.02-58.31 18.31-28.48 20.3-49.09 13.07-63.65-7.21-14.57-24.74-25.27-58.25-27.45-29.85-1.94-59.54 5.92-86.28 19.48l-98.51 49.99-218.7-82.06a17.799 17.799 0 0 0-18-1.11L90.62 67.29c-10.67 5.41-13.25 19.65-5.17 28.53l156.22 98.1-103.21 52.38-72.35-36.47a17.804 17.804 0 0 0-16.07.02L9.91 230.22c-10.44 5.3-13.19 19.12-5.57 28.08l76.21 82.97z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('WFR')
                                                <flux:tooltip content="Work from Remote">
                                                    <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('CW')
                                                <flux:tooltip content="Compensatory Work">
                                                    <svg class="w-4 h-4 text-pink-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('OD')
                                                <flux:tooltip content="On Duty">
                                                    <svg class="w-4 h-4 text-teal-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('H')
                                                <flux:tooltip content="Holiday">
                                                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M20 8.69V4h-4.69L12 .69 8.69 4H4v4.69L.69 12 4 15.31V20h4.69L12 23.31 15.31 20H20v-4.69L23.31 12 20 8.69zM12 18c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6zm0-10c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('W')
                                                <flux:tooltip content="Week Off">
                                                    <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('S')
                                                <flux:tooltip content="Suspended">
                                                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @case('POW')
                                                <flux:tooltip content="Persent on Work Off">
                                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z"/>
                                                    </svg>
                                                </flux:tooltip>
                                                @break
                                                @default
                                                <flux:tooltip content="Not Marked">
                                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                                        <path d="M12 6v2m0 8v2M6 12h2m8 0h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                    </svg>
                                                </flux:tooltip>
                                            @endswitch
                                        @else
                                            <flux:tooltip content="Not Marked">
                                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                                    <path d="M12 6v2m0 8v2M6 12h2m8 0h2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                            </flux:tooltip>
                                        @endif
                                    </td>
                                    @endif
                                @endfor
                                <td class="px-4 py-3 text-center font-medium">{{ $month['present_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </flux:card>

<!-- Calendar Scripts -->

<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    // Transform PHP data to FullCalendar event format
    var rawEvents = @json($chartDataJson);
    var events = rawEvents.map(function(day) {
        var color = '';
        if (day.status === 'Present') {
            color = '#22c55e'; // green-500
        } else if (day.status === 'Late') {
            color = '#eab308'; // yellow-500
        } else if (day.status === 'Absent') {
            color = '#ef4444'; // red-500
        }
        return {
            title: day.status !== 'Unknown' ? day.status : '',
            start: day.date, // This is in 'M d' format, needs to be converted to 'Y-m-d' if possible
            backgroundColor: color,
            borderColor: color,
            display: 'block',
            classNames: ['attendance-event']
        };
    });
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth'
        },
        firstDay: 1, // Monday as first day
        height: 'auto',
        events: events,
        eventDidMount: function(info) {
            info.el.style.fontSize = '0.75rem';
            info.el.style.padding = '2px 4px';
            info.el.style.margin = '2px';
            info.el.style.borderRadius = '4px';
        }
    });
    calendar.render();
});
</script>
<style>
.fc-day-today {
    background-color: rgba(var(--color-primary-500), 0.1) !important;
}
.fc-day-today .fc-daygrid-day-number {
    font-weight: bold;
    color: var(--color-primary-600);
}
.attendance-event {
    text-align: center;
    font-weight: 500;
}
.fc .fc-daygrid-day-number {
    padding: 6px 8px;
}
.fc th {
    padding: 10px 0;
    font-weight: 600;
}
.fc-theme-standard td, .fc-theme-standard th {
    border-color: var(--color-gray-200);
}
.dark .fc-theme-standard td, .dark .fc-theme-standard th {
    border-color: var(--color-gray-700);
}
.fc-day-other {
    background-color: var(--color-gray-50);
}
.dark .fc-day-other {
    background-color: var(--color-gray-800/30);
}
</style>

</div>

