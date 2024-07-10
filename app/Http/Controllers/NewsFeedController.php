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
use Carbon\Carbon;
use Auth;
use Log;
use App\Models\ActivityFeed;
use App\Http\Resources\UserResource;


class NewsFeedController extends Controller
{

    
    public function index(Request $request)
    {
        $userId = Auth::id(); // Get the authenticated user's ID

        // Filter and paginate approved news feed items with user data
        $newsFeedItems = NewsFeedItem::where('status', 'approved')
            ->with('user:id,name,profile_picture')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Transform the data to include only necessary user fields
        $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($userId) {
            $isLiked = $item->likes()->where('user_id', $userId)->exists();
            return [
                'id' => $item->id,
                'media_url' => $item->media ?  : null,
                'media_type' => $item->media_type,
                'content' => $item->content,
                'views' => $item->views,
                'likes' => $item->likes,
                'comments' => $item->comments,
                'shares' => $item->shares,
                'created_at' => $item->created_at,
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'profile_picture_url' => $item->user->profile_picture_url,
                ] : null,
                'is_liked' => $isLiked,
            ];
        });

        return response()->json($transformedItems);
    }

    public function indexpending(Request $request)
    {
        $userId = Auth::id(); // Get the authenticated user's ID

        // Filter and paginate pending news feed items with user data
        $newsFeedItems = NewsFeedItem::where('status', 'pending')
            ->with('user:id,name,profile_picture')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Transform the data to include only necessary user fields
        $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($userId) {
            $isLiked = $item->likes()->where('user_id', $userId)->exists();
            return [
                'id' => $item->id,
                'media_url' => $item->media ?  : null,
                'media_type' => $item->media_type,
                'content' => $item->content,
                'views' => $item->views,
                'likes' => $item->likes,
                'comments' => $item->comments,
                'shares' => $item->shares,
                'created_at' => $item->created_at,
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'profile_picture_url' => $item->user->profile_picture_url,
                ] : null,
                'is_liked' => $isLiked,
            ];
        });

        return response()->json($transformedItems);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'category' => 'required|string',
            'media' => 'nullable|mimes:jpeg,png,jpg,gif,mp4,mov,ogg,qt|max:50048', // Validate media file
        ]);

        try {
            $mediaUrl = null;
            $mediaType = null;

            if ($request->hasFile('media')) {
                $mediaPath = $request->file('media')->store('news_media', 'public');
                $mediaUrl = url(Storage::url($mediaPath));
                $mediaType = strpos($request->file('media')->getMimeType(), 'image') !== false;
            }

            $newsFeedItem = NewsFeedItem::create([
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'category' => $request->input('category'),
                'user_id' => $request->user()->id,
                'media' => $mediaUrl,
                'media_type' => $mediaType,
            ]);

            // Invalidate the cache
            Cache::forget('news_feed_items');

            return response()->json([
                'message' => 'News feed item created successfully',
                'newsFeedItem' => [
                    'id' => $newsFeedItem->id,
                    'title' => $newsFeedItem->title,
                    'content' => $newsFeedItem->content,
                    'category' => $newsFeedItem->category,
                    'media' => $mediaUrl,
                    'media_type' => $mediaType,
                    'created_at' => $newsFeedItem->created_at,
                    'updated_at' => $newsFeedItem->updated_at,
                    'user' => new UserResource($newsFeedItem->user), // Use UserResource
                ]
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
            'media' => 'nullable|mimes:jpeg,png,jpg,gif,mp4,mov,ogg,qt|max:50048', // Validate media file
        ]);

        try {
            $newsFeedItem = NewsFeedItem::findOrFail($id);

            if ($request->user()->id !== $newsFeedItem->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $mediaUrl = $newsFeedItem->media; // Keep the existing media URL by default
            $mediaType = $newsFeedItem->media_type; // Keep the existing media type by default

            if ($request->hasFile('media')) {
                $mediaPath = $request->file('media')->store('news_media', 'public');
                $mediaUrl = url(Storage::url($mediaPath));
                $mediaType = strpos($request->file('media')->getMimeType(), 'image') !== false;

                $newsFeedItem->media = $mediaUrl;
                $newsFeedItem->media_type = $mediaType;
            }

            $newsFeedItem->content = $request->input('content');
            $newsFeedItem->save();

            return response()->json([
                'message' => 'News feed item updated successfully',
                'newsFeedItem' => [
                    'id' => $newsFeedItem->id,
                    'title' => $newsFeedItem->title,
                    'content' => $newsFeedItem->content,
                    'category' => $newsFeedItem->category,
                    'media' => $mediaUrl,
                    'media_type' => $mediaType,
                    'created_at' => $newsFeedItem->created_at,
                    'updated_at' => $newsFeedItem->updated_at,
                    'user' => new UserResource($newsFeedItem->user), // Use UserResource
                ]
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'News feed item not found'], 404);
        } catch (\Exception $e) {
            \Log::error('Error updating news feed item: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update news feed item.'], 500);
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

            // Create an activity feed entry
            ActivityFeed::create([
                'user_id' => Auth::id(),
                'activity_type' => 'share',
                'related_id' => $newsFeedItem->id,
                'description' => 'Shared a news feed item'
            ]);

            return response()->json(['message' => 'Content shared successfully', 'data' => $newsFeedItem], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'News feed item not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error sharing news feed item: ' . $e->getMessage());
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

        // Create an activity feed entry
        ActivityFeed::create([
            'user_id' => Auth::id(),
            'activity_type' => 'like',
            'related_id' => $newsFeedItem->id,
            'description' => 'Liked a news feed item'
        ]);

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

        // Create an activity feed entry
        ActivityFeed::create([
            'user_id' => Auth::id(),
            'activity_type' => 'unlike',
            'related_id' => $newsFeedItem->id,
            'description' => 'Unliked a news feed item'
        ]);

        return response()->json(['message' => 'News feed item unliked']);
    }

    public function comment(Request $request, $newsFeedItemId)
    {
        // Validate the request input
        $request->validate([
            'content' => 'required|string',
        ]);
    
        // Retrieve the authenticated user
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        // Find the news feed item
        $newsFeedItem = NewsFeedItem::find($newsFeedItemId);
        if (!$newsFeedItem) {
            return response()->json(['message' => 'News feed item not found'], 404);
        }
    
        // Create a new comment
        $comment = new Comment();
        $comment->content = $request->input('content');
        $comment->user_id = $user->id; // Use the authenticated user's ID
        $comment->news_feed_item_id = $newsFeedItemId;
        $comment->save();
    
        // Update the comments count in the news_feed_items table
        $newsFeedItem->increment('comments');
    
        // Retrieve the comment with user data
        $commentWithUser = Comment::where('id', $comment->id)
            ->with('user:id,name,profile_picture')
            ->first();
    
        return response()->json([
            'id' => $commentWithUser->id,
            'content' => $commentWithUser->content,
            'user' => [
                'id' => $commentWithUser->user->id,
                'name' => $commentWithUser->user->name,
                'profile_picture_url' => $commentWithUser->user->profile_picture_url,
            ],
            'created_at' => $commentWithUser->created_at,
            'updated_at' => $commentWithUser->updated_at,
        ], 200);
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
        ->select('comments.*', 'users.name as user_name', 'users.profile_picture as user_profile_picture')
        ->get();

    $commentsWithUserDetails = $comments->map(function ($comment) {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'user_id' => $comment->user_id,
            'news_feed_item_id' => $comment->news_feed_item_id,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'user_name' => $comment->user_name,
            'user_image' => url('storage/' . $comment->user_profile_picture),
        ];
    });

    return response()->json($commentsWithUserDetails, 200);
}


    public function getTrendingContent(Request $request)
    {
        $trendingContent = Cache::remember('trending_content', 60, function () {
            return NewsFeedItem::where('created_at', '>=', Carbon::now()->subDay())
                ->orderByRaw('(views + likes + comments + shares) DESC')
                ->take(10)
                ->get();
        });

        Log::info('Trending Content Retrieved', ['count' => $trendingContent->count()]);

        return response()->json($trendingContent);
    }

    public function getPopularContent(Request $request)
    {
        $popularContent = Cache::remember('popular_content', 60, function () {
            return NewsFeedItem::orderByRaw('(views + likes + comments + shares) DESC')
                ->take(10)
                ->get();
        });

        Log::info('Popular Content Retrieved', ['count' => $popularContent->count()]);

        return response()->json($popularContent);
    }

    public function getCategories(Request $request)
    {
        try {
            // Fetch distinct categories from the NewsFeedItem table
            $categories = NewsFeedItem::distinct()->pluck('category');

            return response()->json(['categories' => $categories], 200);
        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('Error retrieving categories: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to retrieve categories.'], 500);
        }
    }

    public function getUserNewsFeed(Request $request)
    {
        $userId = Auth::id(); // Get the authenticated user's ID

        // Filter and paginate approved news feed items for the authenticated user
        $newsFeedItems = NewsFeedItem::where('user_id', $userId)
            ->where('status', 'approved')
            ->with('user:id,name,profile_picture')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Transform the data to include only necessary user fields and media URL
        $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($userId) {
            $isLiked = $item->likes()->where('user_id', $userId)->exists();
            return [
                'id' => $item->id,
                'media_url' => $item->media ?  : null,
                'media_type' => $item->media_type,
                'content' => $item->content,
                'views' => $item->views,
                'likes' => $item->likes,
                'comments' => $item->comments,
                'shares' => $item->shares,
                'created_at' => $item->created_at,
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'profile_picture_url' => $item->user->profile_picture ? : null,
                ] : null,
                'is_liked' => $isLiked,
            ];
        });

        return response()->json($transformedItems);
    }

    public function gettUserNewsFeed(Request $request, $userId)
    {
        $authenticatedUserId = Auth::id(); // Get the authenticated user's ID

        // Filter and paginate approved news feed items for the specified user
        $newsFeedItems = NewsFeedItem::where('user_id', $userId)
            ->where('status', 'approved')
            ->with('user:id,name,profile_picture')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Transform the data to include only necessary user fields and media URL
        $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($authenticatedUserId) {
            $isLiked = $item->likes()->where('user_id', $authenticatedUserId)->exists();
            return [
                'id' => $item->id,
                'media_url' => $item->media ?  : null,
                'media_type' => $item->media_type,
                'content' => $item->content,
                'views' => $item->views,
                'likes' => $item->likes,
                'comments' => $item->comments,
                'shares' => $item->shares,
                'created_at' => $item->created_at,
                'user' => $item->user ? [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'profile_picture_url' => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
                ] : null,
                'is_liked' => $isLiked,
            ];
        });

        return response()->json($transformedItems);
    }
}
