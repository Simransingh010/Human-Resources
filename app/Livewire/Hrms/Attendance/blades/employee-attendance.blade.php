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
                
                <!-- Calendar CSS -->
                @push('styles')
                <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.11.3/main.min.css' rel='stylesheet' />
                @endpush

                <!-- Calendar Container -->
                <div id="calendar" class="w-full"></div>

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
                                {{dd($punch)}}
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

<!-- Calendar Scripts -->
@push('scripts')
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
@endpush

</div>

