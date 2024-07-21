<?php

// app/Http/Controllers/NewsFeedController.php

namespace App\Http\Controllers;
use App\Http\Resources\LikedUserResource;

use App\Models\NewsFeedItem;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Hootlex\Moderation\Moderation;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\ActivityFeed;
use App\Http\Resources\UserResource;
use DB ;
use Auth;
use Log;

class NewsFeedController extends Controller
{

    
    public function index(Request $request)
{
    $user = auth()->user();

    // Filter and paginate approved news feed items with user data
    $newsFeedItems = NewsFeedItem::where('status', 'approved')
        ->with('user:id,name,profile_picture')
        ->orderBy('created_at', 'desc')
        ->paginate(5);

    // Transform the data to include only necessary user fields
    $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($user) {
        $isLiked = $item->likes()->where('user_id', $user->id)->exists();
        $originalUser = null;
        $originalCreatedAt = null;

        if ($item->original_news_feed_item_id) {
            $originalItem = NewsFeedItem::find($item->original_news_feed_item_id);
            if ($originalItem) {
                $originalUser = $originalItem->user()->select('id', 'name', 'profile_picture', 'created_at')->first();
                $originalCreatedAt = $originalItem->created_at;
            }
        }

        return [
            'id' => $item->id,
            'media_url' => $item->media ?: null,
            'media_type' => $item->media_type,
            'category' => $item->category,
            'content' => $item->content,
            'views' => $item->views,
            'likes' => $item->likes,
            'comments' => $item->comments,
            'shares' => $item->shares,
            'created_at' => $item->created_at,
            'shared_at' => $item->shared_at, // Include the shared_at attribute
            'user' => [
                'id' => $item->user->id,
                'name' => $item->user->name,
                'profile_picture_url' => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
            ],
            'original_user' => $originalUser ? [
                'id' => $originalUser->id,
                'name' => $originalUser->name,
                'profile_picture_url' => $originalUser->profile_picture ? url('storage/' . $originalUser->profile_picture) : null,
                'created_at' => $originalCreatedAt,
            ] : null,
            'is_liked' => $isLiked,
            'is_saved' => $user ? $item->saves()->where('user_id', $user->id)->exists() : false,
        ];
    });

    return response()->json([
        'current_page' => $newsFeedItems->currentPage(),
        'last_page' => $newsFeedItems->lastPage(),
        'data' => $transformedItems,
        'total' => $newsFeedItems->total(),
    ]);
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
        $user = auth()->user();
        $category = $request->input('category');
        $perPage = $request->input('per_page', 5); // Default to 5 items per page if not specified
    
        $newsFeedItemsQuery = NewsFeedItem::query()->where('status', 'approved')->orderBy('created_at', 'desc');
    
        if ($category) {
            $newsFeedItemsQuery->where('category', $category);
        }
    
        $newsFeedItems = $newsFeedItemsQuery->with(['user' => function ($query) {
            $query->select('id', 'name', 'profile_picture');
        }])
        ->paginate($perPage);
    
        $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($user) {
            $isLiked = $item->likes()->where('user_id', $user->id)->exists();
            $originalUser = null;
            $originalCreatedAt = null;
    
            if ($item->original_news_feed_item_id) {
                $originalItem = NewsFeedItem::find($item->original_news_feed_item_id);
                if ($originalItem) {
                    $originalUser = $originalItem->user()->select('id', 'name', 'profile_picture', 'created_at')->first();
                    $originalCreatedAt = $originalItem->created_at;
                }
            }
    
            return [
                'id' => $item->id,
                'media_url' => $item->media ? : null,
                'media_type' => $item->media_type,
                'category' => $item->category,
                'content' => $item->content,
                'views' => $item->views,
                'likes' => $item->likes,
                'comments' => $item->comments,
                'shares' => $item->shares,
                'created_at' => $item->created_at,
                'shared_at' => $item->shared_at, // Include the shared_at attribute
                'user' => [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'profile_picture_url' => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
                ],
                'original_user' => $originalUser ? [
                    'id' => $originalUser->id,
                    'name' => $originalUser->name,
                    'profile_picture_url' => $originalUser->profile_picture ? url('storage/' . $originalUser->profile_picture) : null,
                    'created_at' => $originalCreatedAt,
                ] : null,
                'is_liked' => $isLiked,
                'is_saved' => $user ? $item->saves()->where('user_id', $user->id)->exists() : false,
            ];
        });
    
        return response()->json([
            'current_page' => $newsFeedItems->currentPage(),
            'last_page' => $newsFeedItems->lastPage(),
            'data' => $transformedItems,
            'total' => $newsFeedItems->total(),
        ]);
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

        // Increment the share count on the original item
        $newsFeedItem->increment('shares');

        // Clone the original news feed item to create a new shared item
        $sharedNewsFeedItem = $newsFeedItem->replicate();
        $sharedNewsFeedItem->user_id = Auth::id(); // Set the user ID of the sharer
        $sharedNewsFeedItem->original_news_feed_item_id = $newsFeedItem->id; // Reference to the original item
        $sharedNewsFeedItem->shared = true;
        $sharedNewsFeedItem->shared_at = now(); // Set the share time

        // Initialize views, likes, comments, and shares to 0
        $sharedNewsFeedItem->views = 0;
        $sharedNewsFeedItem->likes = 0;
        $sharedNewsFeedItem->comments = 0;
        $sharedNewsFeedItem->shares = 0;

        $sharedNewsFeedItem->save();

        // Load the original user data
        $originalUser = $newsFeedItem->user()->select('id', 'name', 'profile_picture', 'created_at')->first();

        // Create an activity feed entry
        ActivityFeed::create([
            'user_id' => Auth::id(),
            'activity_type' => 'share',
            'related_id' => $sharedNewsFeedItem->id,
            'description' => 'Shared a news feed item'
        ]);

        // Add the original user data to the response
        $sharedNewsFeedItem->original_user = [
            'id' => $originalUser->id,
            'name' => $originalUser->name,
            'profile_picture_url' => $originalUser->profile_picture ? url('storage/' . $originalUser->profile_picture) : null,
            'created_at' => $originalUser->created_at
        ];

        // Transform the shared news feed item to include necessary fields
        $transformedItem = [
            'id' => $sharedNewsFeedItem->id,
            'title' => $sharedNewsFeedItem->title,
            'content' => $sharedNewsFeedItem->content,
            'category' => $sharedNewsFeedItem->category,
            'user_id' => $sharedNewsFeedItem->user_id,
            'views' => $sharedNewsFeedItem->views,
            'likes' => $sharedNewsFeedItem->likes,
            'comments' => $sharedNewsFeedItem->comments,
            'shares' => $sharedNewsFeedItem->shares,
            'shared' => $sharedNewsFeedItem->shared,
            'media' => $sharedNewsFeedItem->media ? url('storage/' . $sharedNewsFeedItem->media) : null,
            'media_type' => $sharedNewsFeedItem->media_type,
            'recency_factor' => $sharedNewsFeedItem->recency_factor,
            'status' => $sharedNewsFeedItem->status,
            'latitude' => $sharedNewsFeedItem->latitude,
            'longitude' => $sharedNewsFeedItem->longitude,
            'original_news_feed_item_id' => $sharedNewsFeedItem->original_news_feed_item_id,
            'created_at' => $sharedNewsFeedItem->created_at,
            'updated_at' => $sharedNewsFeedItem->updated_at,
            'original_user' => $sharedNewsFeedItem->original_user,
            'shared_at' => $sharedNewsFeedItem->shared_at, // Include the share time
        ];

        return response()->json(['message' => 'Content shared successfully', 'data' => $transformedItem], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'News feed item not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error sharing news feed item: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to share content', 'error' => $e->getMessage()], 500);
    }
}




    // private function canShare($user, $newsFeedItem)
    // {
    //     // Example permission check: Only the owner or admin can share
    //     return $user->id === $newsFeedItem->user_id || $user->hasRole('admin');
    // }

    public function getSharedContent(Request $request)
    {
        // Retrieve shared content from the database
        $sharedContent = NewsFeedItem::where('shared', true)
            ->with(['user:id,name,profile_picture'])
            ->orderBy('created_at', 'desc')
            ->paginate(5);
    
        // Transform the data to match the required format
        $transformedItems = $sharedContent->getCollection()->map(function ($item) {
            return [
                "id" => $item->id,
                "title" => $item->title,
                "content" => $item->content,
                "category" => $item->category,
                "user_id" => $item->user_id,
                "views" => $item->views = 0,
                "likes" => $item->likes = 0,
                "comments" => $item->comments = 0,
                "shares" => $item->shares = 0,
                "shared" => $item->shared = 0,
                "media" => $item->media ? url('storage/' . $item->media) : null,
                "media_type" => $item->media_type,
                "recency_factor" => $item->recency_factor,
                "status" => $item->status,
                "latitude" => $item->latitude,
                "longitude" => $item->longitude,
                "original_news_feed_item_id" => $item->original_news_feed_item_id,
                "created_at" => $item->created_at,
                "updated_at" => $item->updated_at,
                "user" => [
                    "id" => $item->user->id,
                    "name" => $item->user->name,
                    "profile_picture" => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
                ],
            ];
        });
    
        return response()->json([
            'current_page' => $sharedContent->currentPage(),
            'data' => $transformedItems,
            'first_page_url' => $sharedContent->url(1),
            'from' => $sharedContent->firstItem(),
            'last_page' => $sharedContent->lastPage(),
            'last_page_url' => $sharedContent->url($sharedContent->lastPage()),
            'links' => $sharedContent->linkCollection(),
            'next_page_url' => $sharedContent->nextPageUrl(),
            'path' => $sharedContent->path(),
            'per_page' => $sharedContent->perPage(),
            'prev_page_url' => $sharedContent->previousPageUrl(),
            'to' => $sharedContent->lastItem(),
            'total' => $sharedContent->total(),
        ]);
    }
    



    public function like($newsFeedItemId)
{
    $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

    if ($newsFeedItem->likes()->where('user_id', Auth::id())->exists()) {
        return response()->json(['message' => 'News feed item already liked'], 400);
    }

    $newsFeedItem->likes()->attach(Auth::id());

    // Increment the likes count in the news_feed_items table
    $newsFeedItem->increment('likes');

    ActivityFeed::create([
        'user_id' => Auth::id(),
        'activity_type' => 'like',
        'related_id' => $newsFeedItem->id,
        'description' => 'Liked a news feed item'
    ]);

    // Return the updated number of likes
    return response()->json(['message' => 'News feed item liked', 'likes' => $newsFeedItem->likes()->count()]);
}

public function unlike($newsFeedItemId)
{
    $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

    if (!$newsFeedItem->likes()->where('user_id', Auth::id())->exists()) {
        return response()->json(['message' => 'News feed item not liked'], 400);
    }

    $newsFeedItem->likes()->detach(Auth::id());

    // Decrement the likes count in the news_feed_items table
    $newsFeedItem->decrement('likes');

    ActivityFeed::create([
        'user_id' => Auth::id(),
        'activity_type' => 'unlike',
        'related_id' => $newsFeedItem->id,
        'description' => 'Unliked a news feed item'
    ]);

    // Return the updated number of likes
    return response()->json(['message' => 'News feed item unliked', 'likes' => $newsFeedItem->likes()->count()]);
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
          'message'=>'Comment added',
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
    public function updateComment(Request $request, $commentId)
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

    // Find the comment
    $comment = Comment::find($commentId);
    if (!$comment) {
        return response()->json(['message' => 'Comment not found'], 404);
    }

    // Check if the user is the author of the comment
    if ($comment->user_id !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Update the comment content
    $comment->content = $request->input('content');
    $comment->save();

    // Retrieve the updated comment with user data
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
            // 'comments' =>$comment->id->count()
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
    $newsFeedItems = NewsFeedItem::where('status', 'approved')
        ->where('user_id', $userId) // Only fetch posts by the authenticated user
        ->with('user:id,name,profile_picture')
        ->orderBy('created_at', 'desc')
        ->paginate(5);

    // Transform the data to include only necessary user fields and media URL
    $transformedItems = $newsFeedItems->getCollection()->map(function ($item) use ($userId) {
        $isLiked = $item->likes()->where('user_id', $userId)->exists();
        $isSaved = $item->saves()->where('user_id', $userId)->exists(); // Check if the current user has saved the item

        $originalUser = null;
        $originalCreatedAt = null;

        if ($item->original_news_feed_item_id) {
            $originalItem = NewsFeedItem::find($item->original_news_feed_item_id);
            if ($originalItem) {
                $originalUser = $originalItem->user()->select('id', 'name', 'profile_picture', 'created_at')->first();
                $originalCreatedAt = $originalItem->created_at;
            }
        }

        return [
            'id' => $item->id,
            'media_url' => $item->media ?: null,
            'media_type' => $item->media_type,
            'category' => $item->category,
            'content' => $item->content,
            'views' => $item->views,
            'likes' => $item->likes,
            'comments' => $item->comments,
            'shares' => $item->shares,
            'created_at' => $item->created_at,
            'shared_at' => $item->shared_at, // Include the shared_at attribute
            'user' => [
                'id' => $item->user->id,
                'name' => $item->user->name,
                'profile_picture_url' => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
            ],
            'original_user' => $originalUser ? [
                'id' => $originalUser->id,
                'name' => $originalUser->name,
                'profile_picture_url' => $originalUser->profile_picture ? url('storage/' . $originalUser->profile_picture) : null,
                'created_at' => $originalCreatedAt,
            ] : null,
            'is_liked' => $isLiked,
            'is_saved' => $isSaved, // Use the result of the check
        ];
    });

    return response()->json([
        'current_page' => $newsFeedItems->currentPage(),
        'last_page' => $newsFeedItems->lastPage(),
        'data' => $transformedItems,
        'total' => $newsFeedItems->total(),
    ]);
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
        $isSaved = $item->saves()->where('user_id', $authenticatedUserId)->exists(); // Check if the current user has saved the item

        $originalUser = null;
        $originalCreatedAt = null;

        if ($item->original_news_feed_item_id) {
            $originalItem = NewsFeedItem::find($item->original_news_feed_item_id);
            if ($originalItem) {
                $originalUser = $originalItem->user()->select('id', 'name', 'profile_picture', 'created_at')->first();
                $originalCreatedAt = $originalItem->created_at;
            }
        }

        return [
            'id' => $item->id,
            'media_url' => $item->media ?: null,
            'media_type' => $item->media_type,
            'category' => $item->category,
            'content' => $item->content,
            'views' => $item->views,
            'likes' => $item->likes,
            'comments' => $item->comments,
            'shares' => $item->shares,
            'created_at' => $item->created_at,
            'shared_at' => $item->shared_at, // Include the shared_at attribute
            'user' => [
                'id' => $item->user->id,
                'name' => $item->user->name,
                'profile_picture_url' => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
            ],
            'original_user' => $originalUser ? [
                'id' => $originalUser->id,
                'name' => $originalUser->name,
                'profile_picture_url' => $originalUser->profile_picture ? url('storage/' . $originalUser->profile_picture) : null,
                'created_at' => $originalCreatedAt,
            ] : null,
            'is_liked' => $isLiked,
            'is_saved' => $isSaved, // Use the result of the check
        ];
    });

    return response()->json([
        'current_page' => $newsFeedItems->currentPage(),
        'last_page' => $newsFeedItems->lastPage(),
        'data' => $transformedItems,
        'total' => $newsFeedItems->total(),
    ]);
}

public function getLikedUsers($newsFeedItemId)
{
    try {
        // Find the news feed item by ID
        $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

        // Retrieve liked users with their details and pivot data
        $likedUsers = $newsFeedItem->likes()->get();

        // Format liked users data using resource collection
        $formattedLikedUsers = LikedUserResource::collection($likedUsers);

        // Return JSON response with formatted liked users data
        return response()->json(['liked_users' => $formattedLikedUsers], 200);
    } catch (ModelNotFoundException $e) {
        // Handle case where news feed item is not found
        return response()->json(['message' => 'News feed item not found'], 404);
    } catch (\Exception $e) {
        // Log any unexpected exceptions
        Log::error('Error retrieving liked users: ' . $e->getMessage());

        // Return a generic error message
        return response()->json(['message' => 'Failed to retrieve liked users. Please try again later.'], 500);
    }
}



    public function savePost($newsFeedItemId)
{
    $user = Auth::user();
    $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

    if ($newsFeedItem->saves()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Post already saved'], 400);
    }

    $newsFeedItem->saves()->attach($user->id);

    return response()->json(['message' => 'Post saved successfully']);
}

public function unsavePost($newsFeedItemId)
{
    $user = Auth::user();
    $newsFeedItem = NewsFeedItem::findOrFail($newsFeedItemId);

    if (!$newsFeedItem->saves()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'Post not saved'], 400);
    }

    $newsFeedItem->saves()->detach($user->id);

    return response()->json(['message' => 'Post unsaved successfully']);
}

public function getSavedPosts(Request $request)
{
    $user = Auth::user();

    // Retrieve saved posts for the authenticated user
    $savedPosts = $user->savedNewsFeedItems()
        ->with(['user:id,name,profile_picture', 'originalUser:id,name,profile_picture'])
        ->orderBy('created_at', 'desc')
        ->paginate(5);

    // Transform the data to include necessary fields
    $transformedItems = $savedPosts->getCollection()->map(function ($item) use ($user) {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'content' => $item->content,
            'category' => $item->category,
            'user_id' => $item->user_id,
            'views' => $item->shared ? 0 : $item->views, // Reset views if shared
            'likes' => $item->shared ? 0 : $item->likes, // Reset likes if shared
            'comments' => $item->shared ? 0 : $item->comments, // Reset comments if shared
            'shares' => $item->shared ? 0 : $item->shares, // Reset shares if shared
            'shared' => $item->shared,
            'media' => $item->media ? url('storage/' . $item->media) : null,
            'media_type' => $item->media_type,
            'recency_factor' => $item->recency_factor,
            'status' => $item->status,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'original_news_feed_item_id' => $item->original_news_feed_item_id,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'user' => $item->user ? [
                'id' => $item->user->id,
                'name' => $item->user->name,
                'profile_picture_url' => $item->user->profile_picture ? url('storage/' . $item->user->profile_picture) : null,
            ] : null,
            'original_user' => $item->originalUser ? [
                'id' => $item->originalUser->id,
                'name' => $item->originalUser->name,
                'profile_picture_url' => $item->originalUser->profile_picture ? url('storage/' . $item->originalUser->profile_picture) : null,
            ] : null,
            'is_liked' => $item->likes()->where('user_id', $user->id)->exists(),
            'is_saved' => true, // since these are saved posts
        ];
    });

    return response()->json([
        'current_page' => $savedPosts->currentPage(),
        'last_page' => $savedPosts->lastPage(),
        'data' => $transformedItems,
    ]);
}



}
