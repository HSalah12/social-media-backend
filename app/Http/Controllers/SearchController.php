<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsFeedItem;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        // Validate the input
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        // Perform the full-text search
        $results = NewsFeedItem::whereRaw(
            "MATCH(title, content) AGAINST(? IN BOOLEAN MODE)",
            [$query]
        )->get();

        // Return the search results
        return response()->json($results);
    }
}
