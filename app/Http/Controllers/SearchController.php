<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsFeedItem;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        $category = $request->input('category');
        $dateRange = $request->input('date_range');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // Validate the input
        $request->validate([
            'query' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'date_range' => 'nullable|string|regex:/^\d{4}-\d{2}-\d{2}:\d{4}-\d{2}-\d{2}$/',
            'sort_by' => 'nullable|string|in:title,created_at,updated_at',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        // Build the query
        $results = NewsFeedItem::whereRaw(
            "MATCH(title, content) AGAINST(? IN BOOLEAN MODE)",
            [$query]
        );

        // Apply category filter
        if ($category) {
            $results->where('category', $category);
        }

        // Apply date range filter
        if ($dateRange) {
            [$startDate, $endDate] = explode(':', $dateRange);
            $results->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Apply sorting
        $results->orderBy($sortBy, $sortOrder);

        // Log the final query for debugging
        Log::info("Search query: ", ['query' => $results->toSql(), 'bindings' => $results->getBindings()]);

        // Get the results
        $results = $results->get();

        // Return the search results
        return response()->json($results);
    }

    public function suggestions(Request $request)
    {
        $query = $request->input('query');

        // Validate the input
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        // Fetch titles and categories matching the query
        $titles = NewsFeedItem::where('title', 'LIKE', "%{$query}%")
            ->select('title')
            ->distinct()
            ->limit(10)
            ->get();

        $categories = NewsFeedItem::where('category', 'LIKE', "%{$query}%")
            ->select('category')
            ->distinct()
            ->limit(10)
            ->get();
            
        $contents = NewsFeedItem::where('content', 'LIKE', "%{$query}%")
            ->select('content as suggestion', 'title', 'category')
            ->distinct()
            ->limit(10)
            ->get();

        // Merge titles, categories, and contents
        $suggestions = $titles->merge($categories)->merge($contents);
       

        // Return the suggestions
        return response()->json($suggestions);
    }
}
