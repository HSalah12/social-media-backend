<?php

// app/Services/RecommendationService.php

namespace App\Services;

use App\Models\User;
use App\Models\NewsFeedItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    public function getCollaborativeRecommendations(User $user, $limit = 10)
    {
        $interactedItemIds = $user->interactions()->pluck('news_feed_item_id')->toArray();
        Log::info('Interacted item IDs', ['item_ids' => $interactedItemIds]);

        $similarUserIds = DB::table('user_interactions')
            ->whereIn('news_feed_item_id', $interactedItemIds)
            ->where('user_id', '!=', $user->id)
            ->pluck('user_id')
            ->unique()
            ->toArray();
        Log::info('Similar user IDs', ['user_ids' => $similarUserIds]);

        $recommendedItemIds = DB::table('user_interactions')
            ->whereIn('user_id', $similarUserIds)
            ->whereNotIn('news_feed_item_id', $interactedItemIds)
            ->select('news_feed_item_id', DB::raw('count(*) as interactions_count'))
            ->groupBy('news_feed_item_id')
            ->orderByDesc('interactions_count')
            ->limit($limit)
            ->pluck('news_feed_item_id')
            ->toArray();
        Log::info('Recommended item IDs', ['item_ids' => $recommendedItemIds]);

        return NewsFeedItem::whereIn('id', $recommendedItemIds)->get();
    }

    public function getContentBasedRecommendations(User $user, $limit = 10)
    {
        $categories = $user->interactions()
            ->join('news_feed_items', 'user_interactions.news_feed_item_id', '=', 'news_feed_items.id')
            ->pluck('news_feed_items.category')
            ->unique()
            ->toArray();
        Log::info('Interacted categories', ['categories' => $categories]);

        return NewsFeedItem::whereIn('category', $categories)
            ->whereNotIn('id', $user->interactions()->pluck('news_feed_item_id')->toArray())
            ->limit($limit)
            ->get();
    }

    public function getHybridRecommendations($user, $limit)
    {
        // Example implementation, adjust accordingly
        $recommendations = NewsFeedItem::where('user_id', '!=', $user->id)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        return $recommendations;
    }
}
