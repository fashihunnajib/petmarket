<?php
namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\View\View;

class PublicCatalogController extends Controller
{
    public function index(string $shop): View
    {
        $tenant = Tenant::query()
            ->where('slug', $shop)
            ->where('status', 'active')
            ->firstOrFail();

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_available', true)
            ->where('stock_qty', '>', 0)
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('customer.home', [
            'shop' => $tenant->name,
            'products' => $products,
        ]);
    }
}
