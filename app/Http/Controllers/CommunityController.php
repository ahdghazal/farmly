<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Like;
use App\Models\Reply;
use App\Models\Report;
use App\Models\SavedPost;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\PostImage;
use Illuminate\Support\Facades\Storage;


class CommunityController extends Controller
{
   
    public function createPost(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'images' => 'nullable|array|max:4', // Limit to 4 images
            'images.*' => 'nullable|string', // Validate base64-encoded image strings
        ]);
    
        // Ensure at least one of content or images is provided
        if (!$request->filled('content') && !$request->has('images')) {
            return response()->json(['error' => 'Post cannot be empty.'], 422);
        }
    
        $userId = Auth::id();
    
        $postData = [
            'user_id' => $userId,
            'content' => $request->content,
        ];
    
        $post = Post::create($postData);
    
        if ($request->has('images')) {
            foreach ($request->images as $imageData) {
                $imagePath = $this->saveBase64Image($imageData, $userId);
                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $imagePath,
                ]);
            }
        }
    
        return response()->json($post->load('images'), 201);
    }
    

    private function saveBase64Image($imageData, $userId)
    {
        $decodedImage = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
        $fileName = $userId . '_' . time() . '_' . uniqid() . '.png';
        $filePath = 'postPictures/' . $fileName;
        file_put_contents(public_path($filePath), $decodedImage);
        return $filePath;
    }

    public function searchPosts(Request $request)
    {
        $request->validate(['query' => 'required|string']);

        $query = $request->query('query');
        $posts = Post::with(['user', 'likes', 'replies.user', 'images'])
            ->where('content', 'LIKE', '%' . $query . '%')
            ->orderBy('created_at', 'desc')
            ->get();

        $posts->each(function ($post) {
            $post->is_liked_by_user = $post->likes->contains('user_id', Auth::id());
            $post->is_saved_by_user = $post->savedPosts->contains('user_id', Auth::id());
        });

        return response()->json($posts, 200);
    }

    public function getPosts(Request $request)
    {
        $sort = $request->query('sort', 'recent');
        $user = $request->query('user');
        
        $query = Post::with(['user', 'likes', 'replies.user', 'images']);
        
        if ($user) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('name', $user);
            });
        }
        
        if ($sort == 'most_liked') {
            $query->withCount('likes')->orderBy('likes_count', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        $posts = $query->get();
        
        $posts->each(function ($post) {
            $post->is_liked_by_user = $post->likes->contains('user_id', Auth::id());
            $post->is_saved_by_user = $post->savedPosts->contains('user_id', Auth::id());
        });
        
        return response()->json($posts, 200);
    }

    public function likePost($postId)
    {
        $like = Like::firstOrCreate([
            'user_id' => Auth::id(),
            'post_id' => $postId,
        ]);

        $post = Post::find($postId);
        if ($post->user_id !== Auth::id()) {
            Notification::create([
                'user_id' => $post->user_id,
                'type' => 'like',
                'data' => 'Your post was liked by ' . Auth::user()->name,
            ]);
        }

        return response()->json($like, 200);
    }

    public function unlikePost($postId)
    {
        $like = Like::where([
            'user_id' => Auth::id(),
            'post_id' => $postId,
        ])->first();

        if ($like) {
            $like->delete();
        }

        return response()->json(null, 204);
    }

    public function savePost($postId)
    {
        $savedPost = SavedPost::firstOrCreate([
            'user_id' => Auth::id(),
            'post_id' => $postId,
        ]);

        return response()->json($savedPost, 200);
    }

    public function unsavePost($postId)
    {
        $savedPost = SavedPost::where([
            'user_id' => Auth::id(),
            'post_id' => $postId,
        ])->first();

        if ($savedPost) {
            $savedPost->delete();
        }

        return response()->json(null, 204);
    }

    public function replyToPost(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $reply = Reply::create([
            'user_id' => Auth::id(),
            'post_id' => $postId,
            'content' => $request->content,
        ]);

        $post = Post::find($postId);
        if ($post->user_id !== Auth::id()) {
            Notification::create([
                'user_id' => $post->user_id,
                'type' => 'reply',
                'data' => 'Your post received a reply from ' . Auth::user()->name,
            ]);
        }

        return response()->json($reply->load('user'), 201);
    }

    public function deleteReply($replyId)
    {
        $reply = Reply::where('id', $replyId)->where('user_id', Auth::id())->first();
        if ($reply) {
            $reply->delete();
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'Reply not found or you are not authorized to delete this reply'], 404);    
    }

    public function getSavedPosts()
    {
        $savedPosts = SavedPost::with('post.user', 'post.likes', 'post.replies.user', 'post.images')
            ->where('user_id', Auth::id())
            ->get()
            ->pluck('post');

        return response()->json($savedPosts, 200);
    }


    public function deletePost($postId)
{
    $post = Post::find($postId);

    if (!$post) {
        return response()->json(['error' => 'Post not found'], 404);
    }

    if ($post->user_id !== Auth::id()) {
        return response()->json(['error' => 'You are not authorized to delete this post'], 403);
    }

    foreach ($post->images as $image) {
        $image->delete();
        Storage::disk('public')->delete($image->image_path);
    }

    $post->delete();

    return response()->json(null, 204);
}


public function getMyPosts(Request $request)
{
    $userId = Auth::id();
    $sort = $request->query('sort', 'latest');

    $query = Post::with(['user', 'likes', 'replies.user', 'images'])
                ->where('user_id', $userId);

    if ($sort === 'earliest') {
        $query->orderBy('created_at', 'asc');
    } else {
        $query->orderBy('created_at', 'desc');
    }

    $posts = $query->get();

    $posts->each(function ($post) {
        $post->is_liked_by_user = $post->likes->contains('user_id', Auth::id());
        $post->is_saved_by_user = $post->savedPosts->contains('user_id', Auth::id());
    });

    return response()->json($posts, 200);
}

public function reportPost(Request $request, $postId)
{
    $post = Post::findOrFail($postId);

    // Check if the user has already reported this post
    $alreadyReported = $post->reports()->where('user_id', Auth::id())->exists();
    if ($alreadyReported) {
        return response()->json(['error' => 'Post already reported by the user.'], 422);
    }

    // Create a new report for the post
    $report = new Report();
    $report->post_id = $postId;
    $report->user_id = Auth::id();
    $report->save();

    // Check if the post has received more than 20 reports
    $reportsCount = $post->reports()->count();
    if ($reportsCount >= 20) {
        // Notify the admin
        $adminId = 1; // Assuming the admin user ID is 1
        $notification = new Notification();
        $notification->user_id = $adminId;
        $notification->type = 'post_reported';
        $notification->data = 'Post ID ' . $postId . ' has received more than 20 reports. Please review.';
        $notification->save();
    }

    return response()->json(['message' => 'Post reported successfully.'], 200);
}
}
