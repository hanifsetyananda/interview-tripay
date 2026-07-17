<?php

namespace App\Http\Controllers;

use App\Models\Products;
use App\Services\TriPayService;

class DashboardController extends Controller
{
    public function index()
    {
        $products = Products::limit(10)->get();

        return inertia('Dashboard', compact('products'));
    }

    public function test()
    {
        $tripay = new TriPayService;
        $result = $tripay->paymentChannels();
        dd($result);

        return inertia('Dashboard');
    }
}
