<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin\AllCollege;
use App\Models\SuperAdmin\CollegeAppUser;
use App\Models\SuperAdmin\FranchiseMaster;
use App\Services\CommonService;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use LogicException;
use Throwable;

class SuperAdminController extends Controller
{
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
        switch ($action) {

            // Super Admin -> Franchise

            case 'get-franchise':
                return $this->getFrachise($request);

            case 'add-update-franchise':
                return $this->addUpdateFranchise($request);

            case 'trash-franchise':
                return $this->trashFrachise($request);

            case 'restore-franchise':
                return $this->restoreFranchise($request);

            case 'deleted-franchises':
                return $this->deletedFranchise();

            case 'delete-franchise':
                return $this->deleteFranchise($request);

                // Super Admin -> College

            case 'get-colleges':
                return $this->getCollege($request);

            case 'add-update-college':
                return $this->addUpdateCollege($request);


            default:
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid superadmin action',
                ], 404);
        }
    }


    public function getCollege(Request $request)
    {
        try {

            $columns =  [
                'id',
                'name',
                'common_branch_id',
                'school_app_code',
                'status',
                'reg_date',
                'renewal_date',
                'user_type',
                'plan_type',
                'log_time',
                'active_day',
                'grace_period',
                'franchise_id',
            ];



            $colleges = $this->crudService->getRecordsWithCustomColumns(AllCollege::class, $columns, [], [], 'id', 'desc');

            return response()->json([
                'status'  => true,
                'message' => 'College list fetched successfully.',
                'data'    => $colleges,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch colleges.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function getCollegeById(Request $request)
    {
        try {

            $columns =  [
                'id',
                'name',
                'common_branch_id',
                'school_app_code',
                'status',
                'reg_date',
                'renewal_date',
                'user_type',
                'plan_type',
                'log_time',
                'active_day',
                'grace_period',
                'franchise_id',
            ];



            $colleges = $this->crudService->getRecordWithCustomColumns(AllCollege::class, $columns, [], ["id" => $request->id], 'id', 'desc');

            return response()->json([
                'status'  => true,
                'message' => 'College list fetched successfully.',
                'data'    => $colleges,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch colleges.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function addUpdateCollege(Request $request)
    {
        $id = $request->input('id');

        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer|exists:all_colleges,id',

            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('all_colleges', 'name')->ignore($id),
            ],

            'db_name' => 'nullable|string',
            'db_user' => 'nullable|string',
            'db_password' => 'nullable|string',

            'common_branch_id' => 'sometimes|integer|min:0',

            'school_app_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('all_colleges', 'school_app_code')->ignore($id),
            ],

            'status' => 'sometimes|boolean',
            'reg_date' => 'required|date',
            'renewal_date' => 'nullable|date|after_or_equal:reg_date',
            'user_type' => 'required|integer|between:0,255',
            'plan_type' => 'required|integer|between:0,65535',
            'log_time' => 'sometimes|integer|min:0',
            'active_day' => 'nullable|integer|min:0',
            'grace_period' => 'sometimes|integer|min:0',
            'franchise_id' => 'sometimes|integer|min:1',

            'app_user' => 'required|array',
            'app_user.registration_id' => 'nullable|integer',
            'app_user.mobile_no' => 'nullable|string|max:10',
            'app_user.email' => 'nullable|email|max:255',
            'app_user.password' => 'nullable|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();

            $id = $validated['id'] ?? null;

            // Authenticated user ID
            $userId = auth()->id();

            // Request karne wale user ka IP
            $ipAddress = $request->ip();

            $college = DB::transaction(function () use (
                $validated,
                $id,
                $userId,
                $ipAddress
            ) {
                $collegeData = collect($validated)
                    ->except(['id', 'app_user'])
                    ->all();

                unset(
                    $collegeData['created_by'],
                    $collegeData['updated_by'],
                    $collegeData['deleted_by'],
                    $collegeData['ip_address'],
                    $collegeData['created_at'],
                    $collegeData['updated_at'],
                    $collegeData['deleted_at']
                );

                if ($id) {
                    $college = AllCollege::findOrFail($id);

                    $college->fill($collegeData);

                    $college->updated_by = $userId;
                    $college->ip_address = $ipAddress;

                    $college->save();
                } else {
                    $collegeData['created_by'] = $userId;
                    $collegeData['updated_by'] = $userId;
                    $collegeData['ip_address'] = $ipAddress;

                    $college = AllCollege::create($collegeData);
                }

                $existingAppUser = CollegeAppUser::where(
                    'college_id',
                    $college->id
                )
                    ->where('user_privilege_id', 2)
                    ->first();

                $userData = $validated['app_user'];
                $userData['college_id'] = $college->id;
                $userData['user_privilege_id'] = 2;

                $this->commonService->createAppUser(
                    $userData,
                    $existingAppUser?->id
                );

                return $college->fresh();
            });

            return response()->json([
                'status' => true,
                'message' => $id
                    ? 'College and app user updated successfully.'
                    : 'College and app user created successfully.',
                'data' => $college,
            ], $id ? 200 : 201);
        } catch (Throwable $e) {
            // report($e);

            // return response()->json([
            //     'status' => false,
            //     'message' => 'Unable to save college and app user.',
            // ], 500);
            report($e);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function getFrachise(Request $request)
    {
        try {

            $franchises = null;

            if ($request->filled('id')) {
                $franchises = $this->crudService->getResult(FranchiseMaster::class, [], ["id" => $request->id], 'id', 'desc');
            } else {
                $franchises = $this->crudService->getResults(FranchiseMaster::class, [], [], 'id', 'desc');
            }
            return response()->json([
                'status'  => true,
                'message' => 'Franchise list fetched successfully.',
                'data'    => $franchises,
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch franchises.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function addUpdateFranchise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                  => 'nullable|integer|exists:franchise_masters,id',
            'company_name'        => 'required|string|max:250',
            'person_name'         => 'required|string|max:250',
            'contact_number'      => 'required|string|max:15',
            'support_contact_no_1' => 'required|string|max:15',
            'support_contact_no_2' => 'required|string|max:15',
            'support_time'        => 'required|string|max:250',
            'ip_address'          => 'nullable|ip|max:45',
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
            unset($validated['id']);


            $franchise = FranchiseMaster::updateOrCreate([
                'id' =>  $id
            ], $validated);

            if ($id) {
                $message = 'Franchise updated successfully.';
                $code = 200;
            } else {
                $message = 'Franchise created successfully.';
                $code = 201;
            }

            return response()->json([
                'status'  => true,
                'message' => $message,
                'data'    => $franchise->fresh(),
            ], $code);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Franchise not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to save franchise.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function trashFrachise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->crudService->softDelete(
                FranchiseMaster::class,
                (int) $request->id
            );

            return response()->json([
                'status'  => true,
                'message' => 'Franchise moved to trash successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Franchise not found.',
            ], 404);
        } catch (LogicException | InvalidArgumentException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to move franchise to trash.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function restoreFranchise(Request $request)
    {
        try {
            $franchise = $this->crudService->restore(
                FranchiseMaster::class,
                $request->id
            );

            return response()->json([
                'status'  => true,
                'message' => 'Trashed franchise restored successfully.',
                'data'    => $franchise,
            ], 200);
        } catch (LogicException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to restore franchise or already restored.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function deletedFranchise()
    {
        try {
            $franchises = $this->crudService->getTrashed(
                FranchiseMaster::class
            );

            return response()->json([
                'status'  => true,
                'message' => 'Trashed franchises fetched successfully.',
                'data'    => $franchises,
            ], 200);
        } catch (LogicException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch trashed franchises.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteFranchise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'franchise_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->crudService->forceDelete(
                FranchiseMaster::class,
                (int) $request->id
            );

            return response()->json([
                'status'  => true,
                'message' => 'Franchise permanently deleted successfully.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Trashed franchise not found.',
            ], 404);
        } catch (LogicException | InvalidArgumentException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Unable to permanently delete franchise.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
