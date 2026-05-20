<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class CustomerManagementController extends Controller
{
    public function index(): Response
    {
        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
            ->withCount('orders')
            ->latest()
            ->paginate(12);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
        ]);
    }
}
