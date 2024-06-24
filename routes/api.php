<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\FollowRequestController;
use App\Http\Controllers\FriendSuggestionController;
use App\Http\Controllers\FriendRequestController;
use App\Http\Controllers\NewsFeedController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SearchController;


Auth::routes();   
// register
Route::post('register', 'App\Http\Controllers\Auth\RegisterController@register');

// login
Route::post('login', 'App\Http\Controllers\Auth\LoginController@login');

// logout
Route::post('/logout', 'App\Http\Controllers\Auth\LogoutController@logout')->middleware('auth:api');

// verify
Route::post('/verify', 'App\Http\Controllers\Auth\VerificationController@verify')->Middleware('verify_token');

// reset password
Route::post('/reset-password', 'App\Http\Controllers\Auth\ResetPasswordController@reset')->middleware('auth:api');

// forget password
Route::post('/forget-password', 'App\Http\Controllers\Auth\ForgotPasswordController@forgot');

// resend otp 
Route::post('/forget-password/resendotp', 'App\Http\Controllers\Auth\ForgotPasswordController@resendOtp');

// update profile
Route::post('/profile', [UserProfileController::class, 'store']);

// update profile
Route::put('/users', 'App\Http\Controllers\UserController@update')->middleware('auth:api');

// Return profile Data
Route::get('/profile/{id}', 'App\Http\Controllers\UserProfileController@show')->middleware('auth:api');;

// Delete Account
Route::delete('/profile/{id}', 'App\Http\Controllers\UserProfileController@destroy')->middleware('auth:api');

// Show account data
Route::get('/profile', 'App\Http\Controllers\UserProfileController@showdata')->middleware('auth:api');

//Accept and Rejectfollow and unfollow
Route::post('follow-requests/send', 'App\Http\Controllers\FollowRequestController@send')->middleware('auth:api');
Route::put('follow-requests/{id}/accept', 'App\Http\Controllers\FollowRequestController@accept')->middleware('auth:api');
Route::put('follow-requests/{id}/reject', 'App\Http\Controllers\FollowRequestController@reject')->middleware('auth:api');
Route::delete('follow-requests/{id}', 'App\Http\Controllers\FollowRequestController@unfollow')->middleware('auth:api');

// GET activities
Route::get('profile/{id}/activities', 'App\Http\Controllers\UserProfileController@activities')->middleware('auth:api');

// Deactivate & Delete profile
Route::post('/profile/deactivate', 'App\Http\Controllers\UserProfileController@deactivate')->middleware('auth:api');

// Profile interactions
Route::get('/profile-interactions', 'App\Http\Controllers\ProfileInteractionController@index')->middleware('auth:api');
Route::post('/profile-interactions', 'App\Http\Controllers\ProfileInteractionController@store')->middleware('auth:api');

// follow-status & friend-status
Route::get('users/follow-status/{followerId}','App\Http\Controllers\FollowRequestController@checkFollowStatus')->middleware('auth:api');
Route::get('users/{user}/friend-status/{friend}', 'App\Http\Controllers\UserController@friendStatus')->middleware('auth:api');

//friend suggestions
Route::get('/suggest-friends', 'App\Http\Controllers\FriendSuggestionController@suggest')->middleware('auth:api');

//friend requests
Route::post('friend-requests/send/{receiverId}', 'App\Http\Controllers\FriendRequestController@sendFriendRequest')->middleware('auth:api');
Route::put('friend-requests/accept/{requestId}','App\Http\Controllers\FriendRequestController@acceptFriendRequest')->middleware('auth:api');
Route::delete('friend-requests/reject/{requestId}', 'App\Http\Controllers\FriendRequestController@rejectFriendRequest')->middleware('auth:api');

//activity-feeds
Route::get('activity-feed', 'App\Http\Controllers\ActivityFeedController@index')->middleware('auth:api');

//privacy-settings
Route::post('/privacy-settings/update', 'App\Http\Controllers\PrivacySettingsController@update')->middleware('auth:api');

//news-feed
Route::post('/news-feed', 'App\Http\Controllers\NewsFeedController@store')->middleware('auth:api');
Route::put('/news-feed/{id}', 'App\Http\Controllers\NewsFeedController@update')->middleware('auth:api');
Route::delete('/news-feed/{id}', 'App\Http\Controllers\NewsFeedController@destroy')->middleware('auth:api');

// Aggregate
Route::get('/news-feed', 'App\Http\Controllers\NewsFeedController@index')->middleware('auth:api');

//news-feed filter
Route::get('/news-feed/filter', 'App\Http\Controllers\NewsFeedController@filter')->middleware('auth:api');

//news-feed approve-reject-pending
Route::put('news-feed/approve/{id}', 'App\Http\Controllers\NewsFeedController@approve');
Route::put('news-feed/reject/{id}', 'App\Http\Controllers\NewsFeedController@reject');
Route::get('news-feed/pending', 'App\Http\Controllers\NewsFeedController@pending');

//news-feed share
Route::post('/newsfeed/{id}/share', 'App\Http\Controllers\NewsFeedController@share')->middleware('auth:api');

//news-feed shared
Route::get('/news-feed/shared', 'App\Http\Controllers\NewsFeedController@getSharedContent')->middleware('auth:api');

//news-feed like
Route::post('/content/{contentId}/like', 'App\Http\Controllers\NewsFeedController@like')->name('content.like');

//news-feed unlike
Route::delete('/content/{contentId}/like', 'App\Http\Controllers\NewsFeedController@unlike')->name('content.unlike');

//news-feed comments
Route::post('/news-feed/{id}/comment', 'App\Http\Controllers\NewsFeedController@comment');
Route::get('/news-feed-items/{news_feed_item_id}/comments', 'App\Http\Controllers\NewsFeedController@getCommentsForNewsFeedItem');
Route::delete('comments/{id}', 'App\Http\Controllers\NewsFeedController@deleteComment')->middleware('auth:api');



//messages
Route::post('conversations', 'App\Http\Controllers\ConversationController@createConversation')->middleware('auth:api');
Route::post('conversations/send-messag', 'App\Http\Controllers\ConversationController@sendMessage')->middleware('auth:api');
Route::get('conversations/{conversationId}/messages', 'App\Http\Controllers\ConversationController@getMessages')->middleware('auth:api');

//group-chats
Route::post('/group-chats', 'App\Http\Controllers\GroupChatController@create')->middleware('auth:api');
Route::post('/group-chats/{groupChat}/messages', 'App\Http\Controllers\GroupChatController@sendMessage')->middleware('auth:api');
Route::get('/group-chats/{groupChat}/messages', 'App\Http\Controllers\GroupChatController@getMessages')->middleware('auth:api');


//chats search
Route::get('/messages/search', 'App\Http\Controllers\ConversationController@search')->middleware('auth:api');

//online-users
Route::get('online-users', 'App\Http\Controllers\UserController@getOnlineUsers');

// search indexing
Route::get('/search', 'App\Http\Controllers\SearchController@search')->name('api.search');
