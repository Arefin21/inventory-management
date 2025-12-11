<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {
    }


    public function index(Request $request): View
    {
        $query = Product::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Paginate results (10 per page)
        $products = $query->latest()->paginate(10);

        return view('products.index', compact('products'));
    }



    public function create(): View
    {
        return view('products.create');
    }



    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle image upload using Service class
        $validated['image'] = $this->productService->handleImageUpload(
            $request->file('image')
        );

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }



    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        // Handle image upload using Service class
        $validated['image'] = $this->productService->handleImageUpload(
            $request->file('image'),
            $product->image
        );

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage (soft delete).
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}

