<?php

// app/Http/Controllers/NewsFeedController.php

namespace App\Http\Controllers;

use App\Models\NewsFeedItem;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\NewsAggregatorService;

class NewsFeedController extends Controller
{

    
    public function index()
    {
        $newsFeedItems = NewsFeedItem::with('user')->get();
        return response()->json($newsFeedItems);
    }

    public function store(Request $request)
{
    // dd($request->all());

    $request->validate([
        'title' => 'required|string',
        'content' => 'required|string',
    ]);

    try {
        $newsFeedItem = NewsFeedItem::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'user_id' => $request->user()->id,
        ]);

        return response()->json($newsFeedItem->load('user'), 200);
    } catch (\Exception $e) {
        // Log the exception for debugging
        \Log::error('Error saving news feed item: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to save news feed item.'], 500);
    }
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        try {
            $newsFeedItem = NewsFeedItem::findOrFail($id);

            if ($request->user()->id !== $newsFeedItem->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $newsFeedItem->update($request->all());
            return response()->json($newsFeedItem->load('user'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'News feed item not found'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $newsFeedItem = NewsFeedItem::findOrFail($id);

            if ($request->user()->id !== $newsFeedItem->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $newsFeedItem->delete();
            return response()->json(['message' => 'News feed item Deleted'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'News feed item not found'], 404);
        }
    }
    public function aggregate()
    {
        $newsAggregator = new NewsAggregatorService();
        $newsAggregator->storeNews();
        return response()->json(['message' => 'News aggregated successfully']);
    }
    
}
