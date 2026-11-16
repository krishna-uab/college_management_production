<?php

namespace App\Http\Controllers;

use App\Services\CommonService;
use App\Services\CrudService;
use App\Traits\DatabaseConnectionWriter;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CommonController extends Controller
{
    // trait
    use DatabaseConnectionWriter;

    protected CrudService $crudService;
    protected CommonService $commonService;

    public function __construct(
        CrudService $crudService,
        CommonService $commonService
    ) {
        $this->crudService = $crudService;
        $this->commonService = $commonService;
    }

    public function handle(Request $request, string $action)
    {

        $data = JWTAuth::parseToken()->getPayload();

        switch ($action) {

            case 'add-update-college':
                return $this->addUpdateCollege($request, $data);


            default:
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid superadmin action',
                ], 404);
        }
    }


    public function addUpdateCollege(Request $request, $data)
    {

        return $this->commonService->addUpdateCollege(
            $request,
            $data,
            true
        );
    }
}
