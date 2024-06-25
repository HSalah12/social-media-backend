<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsFeedItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\RecommendationService;
use App\Models\SearchLog;
use Auth;
class RecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function getRecommendations(Request $request)
{
    $user = Auth::user();
    if (!$user) {
        Log::error('User not authenticated');
        return response()->json(['error' => 'User not authenticated'], 401);
    }

    $limit = $request->input('limit', 10);

    Log::info('Fetching recommendations for user', ['user_id' => $user->id]);

    try {
        $recommendations = $this->recommendationService->getHybridRecommendations($user, $limit);
    } catch (\Exception $e) {
        Log::error('Error fetching recommendations', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to fetch recommendations'], 500);
    }

    Log::info('Recommendations fetched', ['count' => count($recommendations)]);

    if (count($recommendations) == 0) {
        Log::warning('No recommendations found', ['user_id' => $user->id]);
    }

    return response()->json($recommendations);
}

    public function getTrendingContent(Request $request)
    {
        $trendingContent = Cache::remember('trending_content', 60, function () {
            return NewsFeedItem::where('created_at', '>=', Carbon::now()->subDay())
                ->orderByRaw('(views + likes + comments + shares) DESC')
                ->take(10)
                ->get();
        });

        // Log the trending content for debugging
        Log::info('Trending Content Retrieved', ['count' => $trendingContent->count()]);

        // Add image_url to each item
        $trendingContent->each(function ($item) {
            $item->image_url = $item->image ? url('storage/' . $item->image) : null;
        });

        return response()->json($trendingContent);
    }

    public function getPopularContent(Request $request)
    {
        $popularContent = Cache::remember('popular_content', 60, function () {
            return NewsFeedItem::orderByRaw('(views + likes + comments + shares) DESC')
                ->take(10)
                ->get();
        });

        // Log the popular content for debugging
        Log::info('Popular Content Retrieved', ['count' => $popularContent->count()]);

        // Add image_url to each item
        $popularContent->each(function ($item) {
            $item->image_url = $item->image ? url('storage/' . $item->image) : null;
        });

        return response()->json($popularContent);
    }

    public function getGeolocationRecommendations(Request $request)
    {
        // Validate the request
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'numeric|min:1',  // Optional radius, minimum value of 1 km
            'limit' => 'numeric|min:1'    // Optional limit, minimum value of 1
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 10); // Default radius in kilometers
        $limit = $request->input('limit', 10); // Default limit

        Log::info('Fetching geolocation-based recommendations', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius
        ]);

        $query = NewsFeedItem::selectRaw("*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance", [$latitude, $longitude, $latitude])
            ->havingRaw("distance < ?", [$radius])
            ->orderBy('distance')
            ->take($limit);

        Log::info('SQL Query', ['query' => $query->toSql(), 'bindings' => $query->getBindings()]);

        $recommendations = $query->get();

        Log::info('Geolocation recommendations fetched', ['count' => count($recommendations)]);

        // Add image_url to each item
        $recommendations->each(function ($item) {
            $item->image_url = $item->image ? url('storage/' . $item->image) : null;
        });

        // Log the search query
        SearchLog::create([
            'user_id' => Auth::id(),
            'query' => 'geolocation_recommendations',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
            'results_count' => count($recommendations)
        ]);

        return response()->json($recommendations);
    }
}
