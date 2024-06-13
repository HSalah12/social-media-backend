<?php

// app/Http/Controllers/NewsFeedController.php

namespace App\Http\Controllers;

use App\Models\NewsFeedItem;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Hootlex\Moderation\Moderation;

class NewsFeedController extends Controller
{

    
    public function index(Request $request)
    {
        $viewWeight = 1;
        $likeWeight = 2;
        $commentWeight = 3;
        $shareWeight = 4;
        $recencyWeight = 0.5; // Adjust weights as needed

        $currentTime = now()->timestamp;

        $newsFeedItems = NewsFeedItem::orderBy('created_at', 'desc')->paginate(5);

        $sortedItems = $newsFeedItems->sortByDesc(function ($item) use ($viewWeight, $likeWeight, $commentWeight, $shareWeight, $recencyWeight, $currentTime) {
            $recencyFactor = $item->recency_factor ? $item->recency_factor->timestamp() : $currentTime;
            return $item->views * $viewWeight
                   + $item->likes * $likeWeight
                   + $item->comments * $commentWeight
                   + $item->shares * $shareWeight
                   + ($currentTime - $recencyFactor) * $recencyWeight;
        });

        return response()->json($sortedItems);
    }
    public function store(Request $request)
{
    // dd($request->all());

    $request->validate([
        'title' => 'required|string',
        'content' => 'required|string',
        'category' => 'required|string',
    ]);

    try {
        $newsFeedItem = NewsFeedItem::create([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'category' => $request->input('category'),
            'user_id' => $request->user()->id,
        ]);

          // Invalidate the cache
        Cache::forget('news_feed_items');


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
    public function filter(Request $request)
    {
        $category = $request->input('category');

    if ($category) {
        $newsFeedItems = NewsFeedItem::where('category', $category)->with('user')->get();
    } else {
        $newsFeedItems = NewsFeedItem::with('user')->get();
    }

    return response()->json($newsFeedItems);
    }
    
    public function approve($id)
    {
        try {
            $newsFeedItem = NewsFeedItem::findOrFail($id);
            $newsFeedItem->status = 'approved';
            $newsFeedItem->save();
    
            return response()->json(['message' => 'News feed item approved successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'News feed item not found'], 404);
        }
    }

    public function reject($id)
{
    try {
        $newsFeedItem = NewsFeedItem::findOrFail($id);
        $newsFeedItem->status = 'rejected';
        $newsFeedItem->save();

        return response()->json(['message' => 'News feed item rejected successfully']);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'News feed item not found'], 404);
    }
}

    public function pending()
    {
        $pendingItems = NewsFeedItem::where('status', 'pending')->get();

        return response()->json(['data' => $pendingItems]);
    }

    
}
