<?php

namespace App\Http\Controllers;

use App\Models\TenantModels\WebsiteSlider;
use App\Services\CommonService;
use App\Traits\ResolveDatabaseConnection;
use Illuminate\Http\Request;
use Throwable;

class WebsiteController extends Controller
{
    // use Trait here

    use ResolveDatabaseConnection;

    protected CommonService $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function handle(Request $request, string $action)
    {

        $data = $this->tokenPayload();

        // Define model Connection [ Current Logged In College Ke liye ]
        $this->switchModels([
            WebsiteSlider::class,
        ]);

        switch ($action) {

            case 'get-slider':
                return $this->getSlider($request, $data);

            case 'add-update-slider':
                return $this->addUpdateSlider($request, $data);

            case 'remove-show-slide':
                return $this->hideShowSlide($request, $data);

            case 'delete-slide':
                return $this->deleteSlide($request, $data);

            default:
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid action.',
                ], 404);
        }
    }

    // 1. Get Slider
    public function getSlider(Request $request, $data = null)
    {
        try {

            return response()->json([
                'status'  => true,
                'message' => 'Academic session fetched successfully.',
                'data'    => $session,
            ], 200);

        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch academic sessions.',
            ], 500);
        }
    }
    // 2. Add & Update Slider
    public function addUpdateSlider(Request $request, $data = null)
    {
        try {

            return response()->json([
                'status'  => true,
                'message' => 'Academic session fetched successfully.',
                'data'    => $session,
            ], 200);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch academic sessions.',
            ], 500);
        }
    }
    // 1. Get Slider
    public function hideShowSlide(Request $request, $data = null)
    {
        try {

            return response()->json([
                'status'  => true,
                'message' => 'Academic session fetched successfully.',
                'data'    => $session,
            ], 200);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch academic sessions.',
            ], 500);
        }
    }
    // 1. Get Slider
    public function deleteSlide(Request $request, $data = null)
    {
        try {

            return response()->json([
                'status'  => true,
                'message' => 'Academic session fetched successfully.',
                'data'    => $session,
            ], 200);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch academic sessions.',
            ], 500);
        }
    }
}
