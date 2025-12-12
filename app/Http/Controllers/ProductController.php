<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {
    }


    public function index(Request $request): Response
    {
        $query = Product::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Paginate results (10 per page)
        $products = $query->latest()->paginate(10)->withQueryString();

        // Transform products to include image URLs
        $products->getCollection()->transform(function ($product) {
            $product->image_url = $product->image
                ? asset('storage/' . $product->image)
                : null;
            return $product;
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }



    public function create(): Response
    {
        return Inertia::render('Products/Create');
    }



    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Convert price and stock to proper types
        $validated['price'] = (float) $validated['price'];
        $validated['stock'] = (int) $validated['stock'];

        // Handle image upload using Service class
        if ($request->hasFile('image')) {
            $validated['image'] = $this->productService->handleImageUpload(
                $request->file('image')
            );
        } else {
            unset($validated['image']);
        }

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }



    public function edit(Product $product): Response
    {
        $product->image_url = $product->image
            ? asset('storage/' . $product->image)
            : null;

        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        // Convert price and stock to proper types
        $validated['price'] = (float) $validated['price'];
        $validated['stock'] = (int) $validated['stock'];

        // Handle image upload using Service class
        if ($request->hasFile('image')) {
            $validated['image'] = $this->productService->handleImageUpload(
                $request->file('image'),
                $product->image
            );
        } else {
            // Don't update image if no new image is uploaded
            unset($validated['image']);
        }

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

