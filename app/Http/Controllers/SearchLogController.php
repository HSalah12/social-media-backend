<?php

namespace App\Http\Controllers;

use App\Models\SearchLog;
use Illuminate\Http\Request;

class SearchLogController extends Controller
{

    
    public function index()
    {
        // Get the number of searches per day
        $searchesPerDay = SearchLog::selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Get the top 5 most frequent search queries
        $topSearchQueries = SearchLog::selectRaw('query, count(*) as count')
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();

        // Get the average number of results returned per search
        $averageResults = SearchLog::avg('results_count');

        return response()->json([
            'searches_per_day' => $searchesPerDay,
            'top_search_queries' => $topSearchQueries,
            'average_results' => $averageResults,
        ]);
    }
}
