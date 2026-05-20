<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $dashboardAnalyticsService,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'analytics' => $this->dashboardAnalyticsService->build(),
        ]);
    }
}
