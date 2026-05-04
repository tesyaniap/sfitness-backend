<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->available, fn($q) => $q->where('is_available', true))
            ->latest()
            ->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'barcode'      => 'nullable|string|max:50|unique:products,barcode',
            'description'  => 'nullable|string',
            'category'     => 'required|in:food,drink,supplement',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'image'        => 'nullable|string',
            'is_available' => 'boolean',
        ]);

        return response()->json(Product::create($data), 201);
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'barcode'      => 'nullable|string|max:50|unique:products,barcode,' . $product->id,
            'description'  => 'nullable|string',
            'category'     => 'sometimes|in:food,drink,supplement',
            'price'        => 'sometimes|numeric|min:0',
            'stock'        => 'sometimes|integer|min:0',
            'image'        => 'nullable|string',
            'is_available' => 'boolean',
        ]);

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(null, 204);
    }

    // Lookup produk by barcode — untuk scanner
    public function findByBarcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $product = Product::where('barcode', $request->barcode)
            ->where('is_available', true)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan.'], 404);
        }

        return response()->json($product);
    }
}
