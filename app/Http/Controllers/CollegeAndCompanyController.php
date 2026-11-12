<?php

namespace App\Http\Controllers;

use App\Models\TenantModels\AcademicSession;
use App\Traits\ResolveDatabaseConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CollegeAndCompanyController extends Controller
{

    use ResolveDatabaseConnection;


    public function handle(Request $request, string $action)
    {
        $this->switchModels([
            AcademicSession::class,
        ]);

        $data = $this->tokenPayload();

        switch ($action) {

            case 'get-session':
                return $this->getSession($request, $data);

            case 'generate-session':
                return $this->generateSession($request, $data);

            case 'get-active-session':
                return $this->getActiveSession($request, $data);

            case 'change-session':
                return $this->changeSession($request, $data);

            case 'remove-session':
                return $this->removeSession($request, $data);

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

    // 3. Generate Academic Session
    public function generateSession(Request $request, $data = null)
    {
        $validator = Validator::make($request->all(), [

            'id'            => 'nullable|integer|min:1',
            'school_id'     => 'required|integer|min:1',
            'session_start' => 'required|date_format:Y-m-d',
            'session_end'   => 'required|date_format:Y-m-d|after_or_equal:session_start',
            'session_name'  => 'nullable|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {

            $validated = $validator->validated();

            $id = $validated['id'] ?? null;
            $schoolId = (int) $validated['school_id'];

            $sessionData = [
                'session_start' => $validated['session_start'],
                'session_end'   => $validated['session_end'],
                'session_name'  => $validated['session_name'] ?? null,
                'status'        => 1,
            ];

            $session = DB::transaction(function () use (
                $id,
                $schoolId,
                $sessionData
            ) {

                if ($id) {

                    $session = AcademicSession::where('school_id', $schoolId)
                        ->where('id', $id)
                        ->first();

                    if (!$session) {
                        return null;
                    }

                    AcademicSession::where('school_id', $schoolId)
                        ->where('id', '!=', $id)
                        ->update(['status' => 0]);

                    $session->update($sessionData);
                } else {

                    AcademicSession::where('school_id', $schoolId)
                        ->update(['status' => 0]);

                    $session = AcademicSession::create(array_merge(
                        $sessionData,
                        ['school_id' => $schoolId]
                    ));
                }

                return $session->fresh();
            }, 3);

            if (!$session) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Academic session not found for this school.',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => $id
                    ? 'Academic session updated successfully.'
                    : 'Academic session created successfully.',
                'data'    => $session,
            ], $id ? 200 : 201);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to save academic session.',
            ], 500);
        }
    }

    // 4. Change academic session
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

    // 5. Remove Academic Session
    public function removeSession(Request $request, $data = null)
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

            $session = AcademicSession::where('school_id', $schoolId)
                ->where('id', $id)
                ->first();

            if (!$session) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Academic session not found for this school.',
                ], 404);
            }

            if ((int) $session->status === 1) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Active academic session cannot be deleted.',
                ], 422);
            }

            $session->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Academic session deleted successfully.',
            ], 200);
        } catch (Throwable $e) {

            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to delete academic session.',
            ], 500);
        }
    }
}
