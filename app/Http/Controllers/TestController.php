<?php

namespace App\Http\Controllers;

use App\Models\TenantTest;
use App\Traits\ResolveDatabaseConnection;
use Illuminate\Http\Request;


class TestController extends Controller
{

    use ResolveDatabaseConnection;


    public function handle(Request $request, string $action)
    {

        $this->switchModels([TenantTest::class]);

        $data = $this->tokenPayload();

        switch ($action) {
            case 'list':
                return $this->getStudents($data, $request);

            case 'detail':
                return $this->getStudentDetail($data, $request);

            case 'get-jwt-payload':
                return $this->tokenPayload();

            case 'testing-college-db-connection':
                return $this->getTestingCollegeData($data);

            default:
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid student action'
                ], 404);
        }
    }

    private function getStudents($data, Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Student list'
        ]);
    }

    private function getStudentDetail($data, Request $request)
    {
        return response()->json([
            'status' => true,
            'message' => 'Student detail'
        ]);
    }

    // testing tetant database connection
    public function getTestingCollegeData($tokenData)
    {
        $data = null;
        $data = TenantTest::first();
        return response()->json([
            "status" => true,
            "message" => "Data fetched successfully",
            "data" => $data,
            "token_details" => $tokenData
        ]);
    }
}
