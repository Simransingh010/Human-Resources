<?php

namespace App\Livewire\Hrms\AttendanceMeta;

use App\Models\Hrms\EmpPunch;
use Livewire\Component;
use App\Models\Hrms\EmpAttendance;

class ViewPunches extends Component
{
    public $attendanceId;
    public $attendance;
    public $punches = [];

    public function mount($attendanceId)
    {
        $this->attendanceId = $attendanceId;
        $this->loadAttendance();
    }

    protected function loadAttendance()
    {
        $this->attendance = EmpAttendance::with([
            'employee',
            'punches' => function ($query) {
                $query->orderBy('punch_datetime', 'desc')->with('location');
            }
        ])->findOrFail($this->attendanceId);

        $this->punches = $this->attendance->punches->map(function($punch) {
            $geoLocation = $this->formatGeoLocation($punch->punch_geo_location);
            $osmLocationName = null;

            if ($geoLocation && isset($geoLocation['latitude']) && isset($geoLocation['longitude'])) {
                $osmLocationName = $this->getLocationName(
                    $geoLocation['latitude'],
                    $geoLocation['longitude']
                );
            }

            $selfie = $punch->getMedia('selfie')->first();
            $selfieUrl = $selfie ? $selfie->getUrl() : null;

            return [
                'id' => $punch->id,
                'punch_datetime' => $punch->punch_datetime,
                'in_out' => $punch->in_out,
                'punch_geo_location' => $geoLocation,
                'is_final' => $punch->is_final,
                'location' => $punch->location ? [
                    'id' => $punch->location->id,
                    'title' => $punch->location->title
                ] : null,
                'osm_location_name' => $osmLocationName,
                'selfie_url' => $selfieUrl,
            ];
        });
    }

    protected function formatGeoLocation($geoLocationData)
    {
        if (empty($geoLocationData)) {
            return null;
        }

        if (is_string($geoLocationData)) {
            $geoLocationData = json_decode($geoLocationData, true);
        }

        return [
            'latitude' => $geoLocationData['latitude'] ?? null,
            'longitude' => $geoLocationData['longitude'] ?? null
        ];
    }

    protected function getLocationName($latitude, $longitude)
    {
        if (!$latitude || !$longitude) {
            return null;
        }

        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$latitude&lon=$longitude&zoom=18&addressdetails=1";

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'YourAppName/1.0' // Required by Nominatim usage policy
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // Return a readable address
            return $data['display_name'] ?? null;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.hrms.attendance-meta.view-punches');
    }
}
