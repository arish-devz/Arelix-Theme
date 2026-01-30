<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Theme;

use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Illuminate\Http\Request;

class ArelixAddonController extends ClientApiController
{
    public function index()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function store(Request $request)
    {
        return response()->json(['success' => true]);
    }
}
