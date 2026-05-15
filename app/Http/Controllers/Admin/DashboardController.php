<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosMedia;
use App\Models\TalosUser;
use App\Services\ContentTypeService;
use App\Services\ComponentService;

class DashboardController extends Controller
{
    public function __construct(
        private ContentTypeService $contentTypeService,
        private ComponentService $componentService,
    ) {}

    public function index()
    {
        $contentTypes = $this->contentTypeService->all();
        $components   = $this->componentService->all();
        $mediaCount   = TalosMedia::count();
        $userCount    = TalosUser::count();

        return view('talos.dashboard', compact('contentTypes', 'components', 'mediaCount', 'userCount'));
    }
}
