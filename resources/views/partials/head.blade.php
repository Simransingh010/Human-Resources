<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

@php
    use Illuminate\Support\Facades\Session;
    use App\Models\Saas\Firm;
    
    $firmTitle = null;
    $firmFavicon = null;
    $firmId = Session::get('firm_id');
    
    if (auth()->check() && $firmId) {
        $user = auth()->user();
        $firm = $user->firms()->where('firms.id', $firmId)->first();
        if ($firm) {
            $firmTitle = $firm->name ?? $firm->short_name;
            // Try favicon first, then squareLogo, then wideLogo as fallback
            $firmFavicon = $firm->getMedia('favicon')->first()?->getUrl() 
                        ?? $firm->getMedia('squareLogo')->first()?->getUrl()
                        ?? $firm->getMedia('wideLogo')->first()?->getUrl();
        }
    }
    $defaultFavicon = 'https://iqwing.live/assets/images/logo-iqwing.webp';
    $faviconUrl = $firmFavicon ?? $defaultFavicon;
    
    // Force HTTPS to avoid mixed content errors
    $faviconUrl = str_replace('http://', 'https://', $faviconUrl);
    
    // Add cache buster
    $faviconWithCache = $faviconUrl . (str_contains($faviconUrl, '?') ? '&' : '?') . 'v=' . time();
@endphp

<title>{{ $title ?? $firmTitle ?? 'IQwing' }}</title>
<!-- Favicon -->
<link rel="icon" type="image/png" href="{{ $faviconWithCache }}" />
<link rel="icon" type="image/x-icon" href="{{ $faviconWithCache }}" />
<link rel="shortcut icon" type="image/png" href="{{ $faviconWithCache }}" />
<link rel="apple-touch-icon" href="{{ $faviconWithCache }}" />
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    body {
        font-family: 'Inter', sans-serif;
    }

    .card-hover {
        transition: all 0.2s ease-in-out;
    }

    .card-hover:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #e5e7eb #f3f4f6;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f3f4f6;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 3px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #d1d5db;
    }

    .status-indicator {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
