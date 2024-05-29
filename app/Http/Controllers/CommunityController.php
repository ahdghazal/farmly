<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Like;
use App\Models\Reply;
use App\Models\SavedPost;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;


class CommunityController extends Controller
{

    /*public function createPost(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        $postData = [
            'user_id' => Auth::id(),
            'content' => $request->content,
        ];

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts_images', 'public');
            $postData['image_path'] = $imagePath;
        }

        $post = Post::create($postData);

        return response()->json($post, 201);
    }*/
    public function createPost(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'image' => 'nullable|string', 
        ]);

        $userId = Auth::id();

        $postData = [
            'user_id' => $userId,
            'content' => $request->content,
        ];

        if ($request->has('image')) {
            $imageData = $request->image;
            $imagePath = $this->saveBase64Image($imageData, $userId);
            $postData['image_path'] = $imagePath;
        }

        $post = Post::create($postData);

        return response()->json($post, 201);
    }

    private function saveBase64Image($imageData, $userId)
    {
        $decodedImage = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));

        $fileName = $userId . '_' . time() . '_' . uniqid() . '.png'; 

        $filePath = 'postPictures/' . $fileName;
        file_put_contents(public_path($filePath), $decodedImage);

        return $filePath;
    }



public function getPosts(Request $request)
{
    $sort = $request->query('sort', 'recent');
    $user = $request->query('user');
    
    $query = Post::with(['user', 'likes', 'replies.user']);
    
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

    return response()->json($reply, 201);
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
        $savedPosts = SavedPost::with('post.user', 'post.likes', 'post.replies.user')
            ->where('user_id', Auth::id())
            ->get()
            ->pluck('post');

        return response()->json($savedPosts, 200);
    }



}
