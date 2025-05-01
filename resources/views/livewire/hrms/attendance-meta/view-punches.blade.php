<flux:card class=" border-0">
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-1">
        <flux:heading size="lg">Attendance Details</flux:heading>
        <flux:badge size="lg" color="lime">
            {{ $attendance->work_date->format('d M, Y') }}
        </flux:badge>
    </div>

{{--    --}}

    <!-- Employee Info Section -->
    <div class="bg-teal-400/20 rounded-lg mb-1 p-2">
        <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
                <flux:icon name="user-circle" class="w-12 h-12 text-gray-400"/>
            </div>
            <div>
                <div class="text-xl font-semibold">
                    {{ $attendance->employee->fname }} {{ $attendance->employee->lname }}
                </div>
                <div class="text-sm text-gray-600">
                    Employee ID: {{ $attendance->employee->id }}
                </div>
            </div>
        </div>
    </div>

    <!-- Punches Timeline Section -->
    <div class="">
{{--        <flux:label size="md">Punch Records</flux:label>--}}

        <div class="relative space-y-4">
            <!-- Timeline Line -->
            <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            @forelse($punches as $punch)
                <div class="relative flex items-start space-x-4 pl-6">
                    <!-- Timeline Dot -->

                    <!-- Punch Card -->
                    <div class="flex-1">
                        <flux:card class="hover:shadow-md transition-shadow">
                            <!-- Punch Header -->
                            <div class="p-0 {{ $punch['in_out'] === 'in' ? 'bg-green-50' : 'bg-red-50' }} rounded-t-lg">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-3">
                                        <flux:badge color="{{ $punch['in_out'] === 'in' ? 'green' : 'red' }}" size="lg">
                                            {{ strtoupper($punch['in_out']) }}
                                        </flux:badge>
                                        <div>
                                            <div class="text-lg font-semibold">
                                                {{ \Carbon\Carbon::parse($punch['punch_datetime'])->format('h:i A') }}
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                {{ \Carbon\Carbon::parse($punch['punch_datetime'])->format('d M, Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ $punch['selfie_url'] }}" target="_blank">
                                            <flux:profile circle :chevron="false" avatar="{{ $punch['selfie_url'] }}" />
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Punch Details -->
                            <div class="p-0 space-y-4">
                                <!-- Location Details -->
                                @if(isset($punch['punch_geo_location']) && !empty($punch['punch_geo_location']))
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div class="space-y-1">
                                                <div class="flex items-center space-x-2">
                                                    <flux:icon name="map-pin" class="w-4 h-4 text-gray-400"/>
                                                    <div class="text-sm font-medium text-gray-700">
                                                        Location Details<br>
                                                        <div class="text-sm font-medium text-gray-700">
                                                            @if(!empty($punch['location']['title']))
                                                                {{ $punch['location']['title'] }}
                                                            @elseif(!empty($punch['osm_location_name']))
                                                                {{ Str::limit($punch['osm_location_name'], 50) }}
                                                            @else
                                                                Location not specified
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        @if(isset($punch['punch_geo_location']['latitude']) && isset($punch['punch_geo_location']['longitude']))
                                            <div class="relative w-full h-48 rounded-lg overflow-hidden border border-gray-200">
                                                <iframe
                                                        width="100%"
                                                        height="100%"
                                                        frameborder="0"
                                                        style="border:0"
                                                        src="https://maps.google.com/maps?q={{ $punch['punch_geo_location']['latitude'] }},{{ $punch['punch_geo_location']['longitude'] }}&z=15&output=embed"
                                                        allowfullscreen
                                                ></iframe>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </flux:card>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <flux:icon name="clock" class="w-12 h-12 mx-auto text-gray-400 mb-4"/>
                    <div class="text-gray-500">No punch records found for this attendance.</div>
                </div>
            @endforelse
        </div>
    </div>
</flux:card>
