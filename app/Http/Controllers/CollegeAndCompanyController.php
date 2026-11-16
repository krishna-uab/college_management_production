<?php

namespace App\Http\Controllers;

use App\Models\TenantModels\AcademicSession;
use App\Models\TenantModels\Institute;
use App\Services\CommonService;
use App\Services\FileService;
use App\Traits\ResolveDatabaseConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CollegeAndCompanyController extends Controller
{
    use ResolveDatabaseConnection;

    protected CommonService $commonService;

    public function __construct(CommonService $commonService) {
        $this->commonService = $commonService;
    }

    public function handle(Request $request, string $action)
    {
        $this->switchModels([
            AcademicSession::class,
        ]);

        $data = $this->tokenPayload();

        switch ($action) {

            case 'get-session':
                return $this->getSession($request, $data);

            case 'get-active-session':
                return $this->getActiveSession($request, $data);

            case 'change-session':
                return $this->changeSession($request, $data);

            case 'get-institute-details':
                return $this->getInstituteDetails($request, $data);

            case 'update-institute-details':
                return $this->updateInstituteDetails($request, $data);

            default:
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid action.',
                ], 404);
        }
    }

    // 1. Get Sessions
    public function getSession(Request $request, $data = null)
    {
        try {

            $validator = Validator::make($request->all(), [
                'school_id' => 'required|integer|min:1',
                'id'        => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $schoolId = $request->school_id;

            $query = AcademicSession::where('school_id', $schoolId);

            if ($request->filled('id')) {

                $session = $query->where('id', $request->id)->first();

                if (!$session) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Academic session not found.',
                    ], 404);
                }

                return response()->json([
                    'status'  => true,
                    'message' => 'Academic session fetched successfully.',
                    'data'    => $session,
                ], 200);
            }

            $sessions = $query
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Academic sessions fetched successfully.',
                'data'    => $sessions,
            ], 200);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch academic sessions.',
            ], 500);
        }
    }

    // 2. Get Active Session
    public function getActiveSession(Request $request, $data = null)
    {
        try {

            $activeSession = AcademicSession::getCurrentSession();
            if (!$activeSession) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No active academic session found.',
                ], 404);
            }
            return response()->json([
                'status'  => true,
                'data'    => $activeSession,

            ], 200);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 3. Change academic session
    public function changeSession(Request $request, $data = null)
    {
        $validator = Validator::make($request->all(), [

            'id'        => 'required|integer|min:1',
            'school_id' => 'required|integer|min:1',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {

            $schoolId = (int) $request->school_id;
            $id = (int) $request->id;

            $session = DB::transaction(function () use (
                $schoolId,
                $id
            ) {

                $selectedSession = AcademicSession::where('id', $id)->first();

                if (!$selectedSession) {
                    return null;
                }

                AcademicSession::where('school_id', $schoolId)
                    ->update(['status' => 0]);

                $selectedSession->status = 1;

                $selectedSession->save();

                return $selectedSession->fresh();
            }, 3);

            if (!$session) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Academic session not found for this school.',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Academic session changed successfully.',
                'data'    => $session,
            ], 200);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to change academic session.',
            ], 500);
        }
    }

    // 4. Get Company, College & School Details
    public function getInstituteDetails(Request $request, $data = null)
    {
        try {

            Institute::useConnection($data['group']);

            $institute = Institute::first();

            if (!$institute) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Institute not found',
                ], 404);
            }

            $result = $institute->toArray();

            $documentFields = [
                'college_logo',
                'college_photo',
                'college_stamp',
                'principal_signature',
                'director_signature',
                'header_image',
                'report_card_header_image',
            ];

            $documentPath = institute_documents_dir('institute', 'external');

            foreach ($documentFields as $field) {

                if (!empty($result[$field])) {

                    $result[$field] = rtrim($documentPath, '/')
                        . '/' . ltrim($result[$field], '/');
                } else {

                    $result[$field] = null;
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Institute details fetched successfully',
                'data'    => $result,
            ]);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // 5. Update Institute Details

    public function updateInstituteDetails(Request $request, $data = null)
    {

        Institute::useConnection($data['group']);

        try {

            $request->validate([
                'name' => ['required', 'string', 'max:255'],

                'college_code' => [
                    'nullable',
                    'string',
                    'max:30',
                    'unique:institutes,college_code,' . ($id ?? 'NULL') . ',id,deleted_at,NULL',
                ],

                'college_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'college_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
                'college_stamp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'principal_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'director_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'header_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
                'report_card_header_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            ]);

            $documentFields = [
                'college_logo',
                'college_photo',
                'college_stamp',
                'principal_signature',
                'director_signature',
                'header_image',
                'report_card_header_image',
            ];

            $id = $request->id;

            $institute = $id
                ? Institute::findOrFail($id)
                : null;



            $data = $request->except(
                array_merge($documentFields, ['_method'])
            );

            $path = institute_documents_dir($data['group'], 'relative');

            foreach ($documentFields as $field) {

                if ($request->hasFile($field)) {

                    $data[$field] = FileService::update(
                        $request->file($field),
                        $path,
                        $field,
                        $institute?->{$field}
                    );
                }
            }

            $data['ip_address'] = $request->ip();
            $data['updated_by'] = Auth::id();


            $institute = Institute::updateOrCreate(
                [
                    'id' => $id,
                ],
                $data
            );

            return response()->json([
                'status' => true,
                'message' => $id
                    ? 'Institute updated successfully'
                    : 'Institute created successfully',
                'data' => $institute,
            ]);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage() ?? "Failed to update institute details",
            ], 500);
        }
    }

}
