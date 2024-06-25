<?php

// app/Http/Controllers/NewsFeedController.php

namespace App\Http\Controllers;

use App\Models\NewsFeedItem;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Hootlex\Moderation\Moderation;
use Illuminate\Support\Facades\Storage;

use Auth;

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
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'category' => 'required|string',
            'image' => 'nullable|image|max:2048', // Validate image file
        ]);
    
        try {
            $imageUrl = null;
    
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('news_photos', 'public');
                $imageUrl = url(Storage::url($imagePath));
            }
    
            $newsFeedItem = NewsFeedItem::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'category' => $request->input('category'),
                'user_id' => $request->user()->id,
                'image' => $imageUrl,
            ]);
    
            // Invalidate the cache
            Cache::forget('news_feed_items');
    
            return response()->json([
                'message' => 'News feed item created successfully',
                'newsFeedItem' => $newsFeedItem->load('user'),
                'image_url' => $imageUrl,
            ], 200);
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

    
    public function share(Request $request, $id)
    {
        try {
            // Find the news feed item by ID
            $newsFeedItem = NewsFeedItem::findOrFail($id);
    
            // Check if the user has permission to share the content
            if (!$this->canShare($request->user(), $newsFeedItem)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
    
            // Increment the share count
            $newsFeedItem->increment('shares');
            $newsFeedItem->shared = '1';

            // Save the updated item
            $newsFeedItem->save();
    
            // Return the updated news feed item
            return response()->json(['message' => 'Content shared successfully', 'data' => $newsFeedItem], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'News feed item not found'], 404);
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Log::error('Error sharing news feed item: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to share content'], 500);
        }
    }
    

private function canShare($user, $newsFeedItem)
{
   
    // Example permission check: Only the owner or admin can share
    return $user->id === $newsFeedItem->user_id || $user->hasRole('admin');
}

public function getSharedContent(Request $request)
{
    // Retrieve shared content from the database
    $sharedContent = NewsFeedItem::where('shared', true)->with('user')->paginate(5);

    return response()->json($sharedContent);
}



public function like($newsFeedItemId)
    {
        $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

        // Check if the user has already liked the news feed item
        if ($newsFeedItem->likes()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'News feed item already liked'], 400);
        }

        // Increment the likes count in the news feed item
        $newsFeedItem->increment('likes');

        // Attach the like to the news feed item
        $newsFeedItem->likes()->attach(Auth::id());

        return response()->json(['message' => 'News feed item liked']);
    }

    public function unlike($newsFeedItemId)
    {
        $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

        // Check if the user has not liked the news feed item
        if (!$newsFeedItem->likes()->where('user_id', Auth::id())->exists()) {
            return response()->json(['message' => 'News feed item not liked'], 400);
        }

        // Decrement the likes count in the news feed item
        $newsFeedItem->decrement('likes');

        // Detach the like from the news feed item
        $newsFeedItem->likes()->detach(Auth::id());

        return response()->json(['message' => 'News feed item unliked']);
    }
    public function comment(Request $request, $newsFeedItemId)
    {
        // Create a new comment
    $comment = new Comment();
    $comment->content = $request->input('content');
    $comment->user_id = $request->user()->id; // Assuming you're using authentication
    $comment->news_feed_item_id = $newsFeedItemId;
    $comment->save();

    // Update the comments count in the news_feed_items table
    $newsFeedItem = NewsFeedItem::find($newsFeedItemId);
    if ($newsFeedItem) {
        $newsFeedItem->increment('comments');
    }

    return response()->json($comment, 200);
    }
    
    public function deleteComment(Request $request, $commentId)
    {
        // Find the comment
        $comment = Comment::find($commentId);
    
        // Ensure the comment exists
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
    
        // Get the associated news feed item
        $newsFeedItemId = $comment->news_feed_item_id;
        $newsFeedItem = NewsFeedItem::find($newsFeedItemId);
    
        // Delete the comment
        $comment->delete();
    
        // Decrement the comments count in the news_feed_items table
        if ($newsFeedItem) {
            $newsFeedItem->decrement('comments');
        }
    
        return response()->json(['message' => 'Comment deleted'], 200);
    }

    public function getCommentsForNewsFeedItem($newsFeedItemId)
    {
        $newsFeedItem = NewsFeedItem::find($newsFeedItemId);
    
        if (!$newsFeedItem) {
            return response()->json(['message' => 'News feed item not found'], 404);
        }
    
        $comments = Comment::where('news_feed_item_id', $newsFeedItemId)
                    ->leftJoin('users', 'comments.user_id', '=', 'users.id')
                    ->select('comments.*', 'users.name as user_name')
                    ->get();
    
        return response()->json($comments, 200);
    }
    
}
