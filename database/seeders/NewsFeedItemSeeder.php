<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewsFeedItem;

class NewsFeedItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        NewsFeedItem::create([
            'title' => 'Test Item 1',
            'content' => 'Content for test item 1',
            'image' => 'path/to/image1.jpg',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'views' => 100,
            'likes' => 50,
            'comments' => 20,
            'shares' => 10
        ]);

        NewsFeedItem::create([
            'title' => 'Test Item 2',
            'content' => 'Content for test item 2',
            'image' => 'path/to/image2.jpg',
            'latitude' => 40.7138,
            'longitude' => -74.0070,
            'views' => 150,
            'likes' => 60,
            'comments' => 25,
            'shares' => 15
        ]);

        NewsFeedItem::create([
            'title' => 'Test Item 3',
            'content' => 'Content for test item 3',
            'image' => 'path/to/image3.jpg',
            'latitude' => 40.7148,
            'longitude' => -74.0080,
            'views' => 200,
            'likes' => 70,
            'comments' => 30,
            'shares' => 20
        ]);

        // Add more test items as needed
    }
}
