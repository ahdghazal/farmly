<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketPlaceController extends Controller
{
    // Add a new product
    public function addProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'required|string',
            'picture' => 'nullable|picture|max:2048',
        ]);

        $product = new Product([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'email' => Auth::user()->email,
            'picture' => $request->picture
        ]);

        $product->save();

        return response()->json(['message' => 'Product added successfully!', 'product' => $product]);
    }

    // Update a product
    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'required|string',
            'picture' => 'nullable|picture|max:2048',
        ]);

        $product = Product::findOrFail($id);
        
        if ($product->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->name = $request->name;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->picture = $request->picture;
        

        $product->save();

        return response()->json(['message' => 'Product updated successfully!', 'product' => $product]);
    }

    // Delete a product
    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully!']);
    }

    // Show authenticated user's products
    public function showMyProducts()
    {
        $products = Auth::user()->products;
        return response()->json($products);
    }

    // Show a single product
    public function showProduct($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    // Show all products
    public function showAllProducts()
    {
        $products = Product::all();
        return response()->json($products);
    }

    // Search products by name
    public function searchProducts(Request $request)
    {
        $query = $request->input('query');
        $products = Product::where('name', 'LIKE', "%{$query}%")->get();
        return response()->json($products);
    }

    // Confirm that a product is sold
    public function confirmProductSold($id)
    {
        $product = Product::findOrFail($id);

        if ($product->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->is_sold = !$product->is_sold;
        $product->save();

        return response()->json(['message' => 'Product sale status updated successfully!', 'is_sold' => $product->is_sold]);
    }


 public function uploadProductPicture(Request $request)
 {
     $validator = Validator::make($request->all(), [
         'picture' => 'required|string',
         'picture_name' => 'required|string',
     ]);

     if ($validator->fails()) {
         return response()->json(['errors' => $validator->errors()], 422);
     }

     $encodedPicture = $request->picture;
     $category = $request->category;
     $userId = auth()->id();

     $filePath = $this->saveBase64ImageProduct($encodedPicture, $userId);

     return response()->json(['picture_path' => $filePath], 201);
 }

 private function saveBase64ImageProduct($imageData, $userId)
 {
     try {
         $decodedImage = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageData));
 
         $fileName = $userId . '_' . time() . '_' . uniqid() . '.png';
 
         $directory = 'productPictures/';
         $filePath = $directory . $fileName;
 
         if (!file_exists($directory)) {
             mkdir($directory, 0775, true);
         }
 
         if (file_put_contents($filePath, $decodedImage) === false) {
             throw new Exception("Failed to save the file.");
         }
 
         return $filePath;
     } catch (Exception $e) {
         Log::error('Failed to save image: ' . $e->getMessage());
         return response()->json(['error' => 'Failed to save image.'], 500);
     }
 }
 
}
