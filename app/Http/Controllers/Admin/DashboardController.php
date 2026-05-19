<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosMedia;
use App\Models\TalosUser;
use App\Services\ComponentService;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private ContentTypeService $contentTypeService,
        private ComponentService $componentService,
        private DynamicModelService $modelService,
    ) {}

    public function index()
    {
        $contentTypes = $this->contentTypeService->all();
        $components   = $this->componentService->all();
        $mediaCount   = TalosMedia::count();
        $userCount    = TalosUser::count();

        $collectionCounts = [];
        foreach ($contentTypes as $type) {
            $uid = $type['__uid'];
            try {
                $collectionCounts[$uid] = $this->modelService->make($uid)->newQuery()->count();
            } catch (\Throwable) {
                $collectionCounts[$uid] = 0;
            }
        }

        $days      = 14;
        $dateRange = collect(range(0, $days - 1))
            ->map(fn($i) => Carbon::now()->subDays($days - 1 - $i)->toDateString())
            ->all();

        $activityDatasets = [];
        foreach ($contentTypes as $type) {
            $uid = $type['__uid'];
            try {
                $rows = $this->modelService->make($uid)->newQuery()
                    ->where('created_at', '>=', Carbon::now()->subDays($days - 1)->startOfDay())
                    ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                    ->groupBy('day')
                    ->pluck('cnt', 'day');

                $activityDatasets[] = [
                    'label' => $type['info']['displayName'],
                    'uid'   => $uid,
                    'data'  => array_map(fn($d) => (int) ($rows[$d] ?? 0), $dateRange),
                ];
            } catch (\Throwable) {}
        }

        return view('talos.dashboard', compact(
            'contentTypes', 'components', 'mediaCount', 'userCount',
            'collectionCounts', 'activityDatasets', 'dateRange'
        ));
    }
}
