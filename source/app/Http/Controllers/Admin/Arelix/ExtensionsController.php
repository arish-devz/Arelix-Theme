<?php

namespace Pterodactyl\Http\Controllers\Admin\Arelix;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class ExtensionsController extends Controller
{
    /**
     * Server Splitter Manager
     */
    public function serverSplitter(): View
    {
        return view('admin.arelix.server-splitter', [
            // Load necessary data like whitelist
        ]);
    }

    /**
     * Staff Requests Manager
     */
    public function staffRequests(): View
    {
        return view('admin.arelix.staff-requests');
    }

    /**
     * Billing Manager
     */
    public function billing(): View
    {
        return view('admin.arelix.billing');
    }
}
