<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Hrms\EmpPunch;
use Illuminate\Support\Facades\Http;

class GeocodePunchDetails extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'punches:geocode
                            {--batch=1000 : How many records to process this run}
                            {--force : Re-geocode even if punch_details is already set}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch lat/long from punch_geo_location, resolve to an address, and store it in punch_details';

    public function handle()
    {
        $batch = (int) $this->option('batch');

        // Base query: punches missing details (or all if --force)
        $query = EmpPunch::query();
        if (! $this->option('force')) {
            $query->whereNull('punch_details')
                ->orWhere('punch_details', '');
        }

        // grab only $batch records
        $punches = $query->limit($batch)->get();

        $count = $punches->count();
        if ($count === 0) {
            $this->info('No punches to geocode.');
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($punches as $punch) {
            $geo = json_decode($punch->punch_geo_location, true);
            if (isset($geo['latitude'], $geo['longitude'])) {
                $address = $this->reverseGeocode($geo['latitude'], $geo['longitude']);

                if ($address) {
                    $punch->update(['punch_details' => $address]);
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nProcessed {$count} punches.");
        return 0;
    }

    /**
     * Reverse-geocode lat/lng to a single address string.
     * Uses OpenStreetMap Nominatim; switch to Google if you prefer.
     */
    protected function reverseGeocode(string $lat, string $lng): ?string
    {
        // Include a valid User-Agent—required by Nominatim’s policy
        $response = Http::withHeaders([
            'User-Agent' => config('app.name') . ' (' . config('app.url') . ')'
        ])->retry(3, 500)
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'format'         => 'jsonv2',
                'lat'            => $lat,
                'lon'            => $lng,
                'addressdetails' => 1,
            ]);

        if (! $response->ok()) {
            \Log::warning("Geocode failed [{$lat},{$lng}]: HTTP {$response->status()}");
            return null;
        }

        $data = $response->json();

        if (isset($data['error'])) {
            \Log::warning("Nominatim error for [{$lat},{$lng}]: {$data['error']}");
            return null;
        }

        $addr = $data['address'] ?? [];

        // pick out the fields you want
        $parts = [
            'road'          => $addr['road']          ?? null,
            'neighbourhood' => $addr['neighbourhood'] ?? null,
            'suburb'        => $addr['suburb']        ?? null,
            'city'          => $addr['city']          ?? ($addr['town'] ?? ($addr['village'] ?? null)),
            'state'         => $addr['state']         ?? null,
            'postcode'      => $addr['postcode']      ?? null,
            'country'       => $addr['country']       ?? null,
            'country_code'  => $addr['country_code']  ?? null,
        ];

        // strip out nulls
        $filtered = array_filter($parts, fn($v) => ! is_null($v));

        if (empty($filtered)) {
            return null;
        }

        // return as a JSON string
        return json_encode($filtered, JSON_UNESCAPED_UNICODE);
    }


}
