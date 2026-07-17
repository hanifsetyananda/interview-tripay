<?php

namespace App\Http\Controllers;

use App\Models\Products;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ProductsController extends Controller
{
    public function index(): Response
    {
        return inertia('Products/Index', [
            'products' => Products::query()->latest('id')->get(),
            'success' => session('success'),
        ]);
    }

    public function create(): Response
    {
        return inertia('Products/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Products::query()->create($this->validated($request));

        return to_route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Products $product): Response
    {
        return inertia('Products/Edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Products $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return to_route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Products $product): RedirectResponse
    {
        $product->delete($product->id);

        return to_route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function validated(Request $request, ?Products $product = null): array
    {
        return $request->validate([
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'reference' => ['required', 'string', 'max:255'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);
    }
}
