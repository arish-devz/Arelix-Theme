<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Helpers\SoftwareVersionService;

class BaseController extends Controller
{
    
    public function __construct(private SoftwareVersionService $version, private ViewFactory $view)
    {
    }

    
    public function index(): View
    {
        return $this->view->make('admin.index', ['version' => $this->version]);
    }
}
