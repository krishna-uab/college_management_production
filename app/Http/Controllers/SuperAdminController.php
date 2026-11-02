<?php

namespace App\Http\Controllers;

use App\Models\SuperAdmin\CollegeClient;
use App\Models\SuperAdmin\FranchiseAgreement;
use App\Models\SuperAdmin\FranchiseBankDetail;
use App\Models\SuperAdmin\FranchiseDocument;
use App\Models\SuperAdmin\FranchiseMaster;
use App\Services\CommonService;
use App\Services\CrudService;
use App\Services\FileService;
use App\Traits\DatabaseConnectionWriter;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use InvalidArgumentException;
use LogicException;
use Throwable;

class SuperadminController extends Controller
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

        // Token middleware mein already set ho chuka hai.
        $data = JWTAuth::getPayload();

        switch ($action) {
            case 'get-franchise':
                return $this->getFrachise($request, $data);

            case 'add-update-franchise':
                return $this->addUpdateFranchise($request, $data);

            case 'upload-franchise-documents':
                return $this->addUpdateFranchiseDocuments($request, $data);

            case 'get-franchise-documents':
                return $this->getFranchiseDocuments($request, $data);
            case 'add-update-franchise-bank-details':
                return $this->addUpdateFranchiseBankDetails($request, $data);
            case 'get-franchise-bank-details':
                return $this->getFranchiseBankDetails($request, $data);

            case 'add-update-franchise-agreement':
                return $this->addUpdateFranchiseAgreement($request, $data);


            case 'trash-franchise':
                return $this->trashFrachise($request, $data);

            case 'restore-franchise':
                return $this->restoreFranchise($request, $data);

            case 'deleted-franchises':
                return $this->deletedFranchise($data);

            case 'delete-franchise':
                return $this->deleteFranchise($request, $data);

            case 'get-colleges':
                return $this->getCollege($request, $data);

            case 'create-college-db-connection':
                return $this->createCollegeDbConnection($request, $data);

            case 'add-update-client':
                return $this->addUpdateCollege($request, $data);


            default:
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid superadmin action',
                ], 404);
        }
    }

    public function getCollege(Request $request, $data)
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



            $colleges = $this->crudService->getRecordsWithCustomColumns(CollegeClient::class, $columns, [], [], 'id', 'desc');

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

    public function addUpdateCollege(Request $request, $data)
    {

        return $this->commonService->addUpdateCollege(
            $request,
            $data,
            true
        );
    }
    public function getCollegeById(Request $request, $data)
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

            $colleges = $this->crudService->getRecordWithCustomColumns(CollegeClient::class, $columns, [], ["id" => $request->id], 'id', 'desc');

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

    public function getFrachise(Request $request, $data)
    {
        try {

            $franchises = null;

            if ($request->filled('id')) {
                $franchises = FranchiseMaster::orderByDesc('created_at')->findOrFail($request->id);
            } else {
                $franchises = FranchiseMaster::orderByDesc('created_at')->get();
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

    public function addUpdateFranchise(Request $request, $data = null)
    {
        $id = $request->input('id');

        $validator = Validator::make($request->all(), [
            // Basic details
            'id'                       => 'nullable|integer|exists:franchise_master,id',
            'franchise_code'           => 'required|string|max:30|unique:franchise_master,franchise_code,' . ($id ?? 'NULL') . ',id',
            'franchise_name'           => 'required|string|max:150',
            'franchise_type'           => 'required|in:individual,proprietorship,partnership,llp,company',
            'status'                   => 'nullable|in:pending,under_review,active,suspended,terminated,rejected',
            'registered_on'            => 'required|date_format:Y-m-d',
            'source'                   => 'nullable|string|max:50',
            'referred_by'              => 'nullable|string|max:100',
            'approved_by'              => 'nullable|integer',
            'approved_at'              => 'nullable|date',
            'rejection_reason'         => 'nullable|string',

            // Company / Business details
            'business_name'            => 'nullable|string|max:200',
            'business_category'        => 'nullable|string|max:100',
            'cin_number'               => 'nullable|string|max:21|unique:franchise_master,cin_number,' . ($id ?? 'NULL') . ',id',
            'llpin_number'             => 'nullable|string|max:8|unique:franchise_master,llpin_number,' . ($id ?? 'NULL') . ',id',
            'business_registration_no' => 'nullable|string|max:50',
            'date_of_incorporation'    => 'nullable|date_format:Y-m-d',
            'company_pan'              => 'nullable|string|max:10',
            'gst_number'               => 'nullable|string|max:15',
            'tan_number'               => 'nullable|string|max:10',
            'msme_udyam_number'        => 'nullable|string|max:25',
            'company_email'            => 'nullable|email|max:150',
            'company_phone'            => 'nullable|string|max:15',
            'website'                  => 'nullable|string|max:200',
            'experience_years'         => 'nullable|integer|min:0|max:255',
            'staff_count'              => 'nullable|integer|min:0|max:4294967295',

            // Owner / Authorized person
            'owner_name'               => 'nullable|string|max:150',
            'father_or_spouse_name'    => 'nullable|string|max:150',
            'designation'              => 'nullable|string|max:100',
            'dob'                      => 'nullable|date_format:Y-m-d',
            'gender'                   => 'nullable|in:male,female,other',
            'owner_email'              => 'required|email|max:150|unique:franchise_master,owner_email,' . ($id ?? 'NULL') . ',id',
            'owner_phone'              => 'nullable|string|max:15',
            'alternate_phone'          => 'nullable|string|max:15',
            'whatsapp_number'          => 'nullable|string|max:15',
            'owner_pan'                => 'nullable|string|max:10',
            'aadhaar_number_encrypted' => 'nullable|string',
            'aadhaar_last4'            => 'nullable|digits:4',
            'photo_url'                => 'nullable|string|max:500',

            // Address
            'address_line1'            => 'nullable|string|max:255',
            'address_line2'            => 'nullable|string|max:255',
            'landmark'                 => 'nullable|string|max:150',
            'city'                     => 'nullable|string|max:100',
            'district'                 => 'nullable|string|max:100',
            'state'                    => 'nullable|string|max:100',
            'pincode'                  => 'nullable|string|size:6',
            'country'                  => 'nullable|string|max:50',

            // Territory
            'territory_state'          => 'nullable|string|max:255',
            'territory_district'       => 'nullable|string|max:100',

            // Other
            'remarks'                  => 'nullable|string',
            'created_by'               => 'nullable|integer',
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

            $franchise = DB::transaction(function () use ($validated, $id) {

                $franchiseData = collect($validated)
                    ->except(['id'])
                    ->all();

                // Defaults for new records
                if (!$id) {
                    $franchiseData['status'] = $franchiseData['status'] ?? 'pending';
                    $franchiseData['country'] = $franchiseData['country'] ?? 'India';
                }

                if ($id) {
                    $franchise = FranchiseMaster::findOrFail($id);

                    $franchise->fill($franchiseData);
                    $franchise->save();
                } else {
                    $franchise = FranchiseMaster::create($franchiseData);
                }

                return $franchise->fresh();
            });

            return response()->json([
                'status'  => true,
                'message' => $id
                    ? 'Franchise updated successfully.'
                    : 'Franchise created successfully.',
                'data'    => $franchise,
            ], $id ? 200 : 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to save franchise.',
            ], 500);
        }
    }

    public function addUpdateFranchiseBankDetails(Request $request, $data = null)
    {
        $id = $request->input('id');

        $validator = Validator::make($request->all(), [
            'id'                     => 'nullable|integer|exists:franchise_bank_details,id',

            'franchise_id'            => 'required|integer|exists:franchise_master,id',

            'account_holder_name'     => 'required|string|max:150',
            'bank_name'               => 'required|string|max:100',
            'branch_name'             => 'nullable|string|max:100',
            'branch_address'          => 'nullable|string|max:255',

            'account_number_encrypted' => 'required|string',
            'account_last4'            => 'required|digits:4',
            'ifsc_code'                => 'required|string|size:11|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i',
            'micr_code'                => 'nullable|string|size:9',
            'account_type'             => 'required|in:savings,current,overdraft',
            'upi_id'                   => 'nullable|string|max:100',

            'is_primary'               => 'nullable|boolean',
            'is_active'                => 'nullable|boolean',

            'cancelled_cheque_doc_id'  => 'nullable|integer|exists:franchise_documents,id',

            'verification_status'      => 'nullable|in:pending,verified,rejected',
            'verified_by'               => 'nullable|integer',
            'verified_at'               => 'nullable|date',
            'remarks'                  => 'nullable|string',
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

            $bankDetails = DB::transaction(function () use ($validated, $id) {

                $bankData = collect($validated)
                    ->except(['id'])
                    ->all();

                if (!$id) {
                    $bankData['is_primary'] = $bankData['is_primary'] ?? false;
                    $bankData['is_active'] = $bankData['is_active'] ?? true;
                    $bankData['verification_status'] =
                        $bankData['verification_status'] ?? 'pending';

                    // New bank details are pending verification by default.
                    $bankData['verified_by'] = null;
                    $bankData['verified_at'] = null;
                }

                if ($id) {
                    $bankDetails = FranchiseBankDetail::findOrFail($id);

                    $bankDetails->fill($bankData);
                    $bankDetails->save();
                } else {
                    $bankDetails = FranchiseBankDetail::create($bankData);
                }

                return $bankDetails->fresh();
            });

            return response()->json([
                'status'  => true,
                'message' => $id
                    ? 'Franchise bank details updated successfully.'
                    : 'Franchise bank details created successfully.',
                'data'    => $bankDetails,
            ], $id ? 200 : 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to save franchise bank details.',
            ], 500);
        }
    }

    public function getFranchiseBankDetails(Request $request, $data)
    {
        try {
            $validated = $request->validate([
                "franchise_id" => ['required', 'exists:franchise_master,id']
            ]);

            $franchise = FranchiseMaster::find($request->franchise_id);

            if (!$franchise) {
                return response()->json([
                    'status' => false,
                    'message' => 'Franchise not found.',
                ], 404);
            }

            // get bank details

            $bank_details = $franchise->bankDetails();

            return response()->json([
                'status' => true,
                'message' => 'Documents fetched successfully.',
                'data' => $bank_details,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch documents.',
            ], 500);
        }
    }

    public function addUpdateFranchiseDocuments(Request $request, $data = null)
    {
        $documentFields = [
            'pan_url',
            'company_pan_url',
            'aadhaar_url',
            'gst_certificate_url',
            'cin_certificate_url',
            'llp_incorporation_certificate_url',
            'moa_aoa_url',
            'partnership_deed_url',
            'board_resolution_url',
            'address_proof_url',
            'office_proof_url',
            'photo_url',
            'cancelled_cheque_url',
            'msme_certificate_url',
            'agreement_copy_url',
            'other_document_url',
        ];

        $rules = ['franchise_id' => ['required', 'integer', 'exists:franchise_master,id']];

        foreach ($documentFields as $field) {
            $rules[$field] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFiles = [];
        $oldFiles = [];

        try {
            $franchise = FranchiseMaster::findOrFail(
                $request->franchise_id
            );

            $franchise_name_slug = Str::slug($franchise->franchise_name);

            $path = franchise_asset_dir(
                $franchise_name_slug,
                'relative'
            );


            $documentData = [];

            foreach ($documentFields as $field) {
                if (!$request->hasFile($field)) {
                    continue;
                }

                $file = $request->file($field);

                // Remove _url suffix for the file name prefix.
                $prefix = preg_replace('/_url$/', '', $field);

                $fileName = FileService::upload(
                    $file,
                    $path,
                    $prefix
                );

                if (!$fileName) {
                    throw new \RuntimeException(
                        "Failed to upload {$field}."
                    );
                }

                $filePath = $path . '/' . $fileName;

                $uploadedFiles[] = $filePath;

                $documentData[$field] = $fileName;
            }

            if (empty($documentData)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please upload at least one document.',
                    'errors' => [
                        'documents' => [
                            'At least one document file is required.',
                        ],
                    ],
                ], 422);
            }

            $savedDocument = DB::transaction(function () use (
                $franchise,
                $documentData,
                &$oldFiles,
                $path
            ) {
                $document = FranchiseDocument::where(
                    'franchise_id',
                    $franchise->id
                )->first();

                // Collect old files before replacing their URLs.
                if ($document) {
                    foreach (array_keys($documentData) as $field) {
                        $oldFileName = $document->{$field};

                        if ($oldFileName) {
                            $oldFiles[] = $path . '/' . $oldFileName;
                        }
                    }
                }

                return FranchiseDocument::updateOrCreate(
                    ['franchise_id' => $franchise->id],
                    $documentData
                );
            });

            foreach (array_unique($oldFiles) as $oldFile) {
                try {
                    FileService::delete($oldFile);
                } catch (Throwable $deleteException) {
                    report($deleteException);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Franchise documents saved successfully.',
                'data' => $savedDocument->fresh(),
            ], 200);
        } catch (Throwable $e) {
            foreach (array_unique($uploadedFiles) as $uploadedFile) {
                try {
                    FileService::delete($uploadedFile);
                } catch (Throwable $deleteException) {
                    report($deleteException);
                }
            }

            report($e);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFranchiseDocuments(Request $request, $data)
    {
        try {

            $validated = $request->validate([
                "franchise_id" => ['required', 'exists:franchise_master,id']
            ]);

            $franchise = FranchiseMaster::find($request->franchise_id);

            if (!$franchise) {
                return response()->json([
                    'status' => false,
                    'message' => 'Franchise not found.',
                ], 404);
            }

            $baseUrl = franchise_asset_dir(Str::slug($franchise->franchise_name), 'external');

            $document = FranchiseDocument::where('franchise_id', $franchise->id)->first();

            $keys = [
                'pan_url',
                'company_pan_url',
                'aadhaar_url',
                'gst_certificate_url',
                'cin_certificate_url',
                'llp_incorporation_certificate_url',
                'moa_aoa_url',
                'partnership_deed_url',
                'board_resolution_url',
                'address_proof_url',
                'office_proof_url',
                'photo_url',
                'cancelled_cheque_url',
                'msme_certificate_url',
                'agreement_copy_url',
            ];

            $documents = [];

            foreach ($keys as $key) {
                $file = $document->$key ?? null;

                if (!$file) {
                    $documents[$key] = null;
                    continue;
                }

                if (preg_match('/^https?:\/\//i', $file)) {
                    $documents[$key] = $file;
                    continue;
                }

                $fileName = basename($file);

                $documents[$key] = $baseUrl . rawurlencode($fileName);
            }

            return response()->json([
                'status' => true,
                'message' => 'Documents fetched successfully.',
                'data' => $documents,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch documents.',
            ], 500);
        }
    }


    public function addUpdateFranchiseAgreement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'franchise_id' => [
                'required',
                'integer',
                'exists:franchise_master,id',
            ],

            'agreement_number' => 'required|string|max:50|unique:franchise_agreement,agreement_number',
            'agreement_type' => [
                'nullable',
                Rule::in(['new', 'renewal', 'amendment']),
            ],
            'version' => 'nullable|integer|min:1',
            'parent_agreement_id' => [
                'nullable',
                'integer',
                'exists:franchise_agreement,id',
            ],

            'agreement_date' => 'required|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_months' => 'nullable|integer|min:1',
            'auto_renew' => 'nullable|boolean',
            'renewal_notice_days' => 'nullable|integer|min:0',

            'franchise_fee' => 'required|numeric|min:0',
            'fee_gst_percent' => 'nullable|numeric|between:0,100',
            'security_deposit' => 'nullable|numeric|min:0',
            'deposit_status' => [
                'nullable',
                Rule::in(['pending', 'received', 'refunded', 'adjusted']),
            ],
            'royalty_percent' => 'nullable|numeric|between:0,100',
            'revenue_share_percent' => 'nullable|numeric|between:0,100',
            'minimum_sales_target' => 'nullable|numeric|min:0',
            'target_period' => [
                'nullable',
                Rule::in(['monthly', 'quarterly', 'yearly']),
            ],
            'payment_terms' => 'nullable|string',

            'territory_state' => 'nullable|string|max:255',
            'territory_district' => 'nullable|string|max:100',
            'is_exclusive' => 'nullable|boolean',
            'non_compete_months' => 'nullable|integer|min:0',
            'termination_notice_days' => 'nullable|integer|min:0',
            'jurisdiction' => 'nullable|string|max:150',
            'special_terms' => 'nullable|string',

            'stamp_paper_number' => 'nullable|string|max:50',
            'stamp_duty_amount' => 'nullable|numeric|min:0',
            'company_signatory_name' => 'nullable|string|max:150',
            'company_signatory_designation' => 'nullable|string|max:100',
            'franchisee_signatory_name' => 'nullable|string|max:150',
            'witness1_name' => 'nullable|string|max:150',
            'witness2_name' => 'nullable|string|max:150',

            'signed_by_franchisee' => 'nullable|boolean',
            'franchisee_signed_on' => 'nullable|date',
            'signed_by_company' => 'nullable|boolean',
            'company_signed_on' => 'nullable|date',

            'template_file' => 'nullable|file|mimes:pdf|max:10240',
            'signed_file' => 'nullable|file|mimes:pdf|max:10240',

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'sent',
                    'signed',
                    'active',
                    'expired',
                    'terminated',
                ]),
            ],

            'terminated_on' => 'nullable|date',
            'termination_reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'created_by' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFiles = [];

        try {
            $validated = $validator->validated();

            $franchise = FranchiseMaster::findOrFail(
                $validated['franchise_id']
            );

            $path = franchise_asset_dir($franchise->franchise_name, '', 'relative');

            $templateFileUrl = null;
            $signedFileUrl = null;

            $agreement = DB::transaction(function () use (
                $request,
                $validated,
                $franchise,
                $path,
                &$uploadedFiles,
                &$templateFileUrl,
                &$signedFileUrl
            ) {
                if ($request->hasFile('template_file')) {
                    $fileName = FileService::upload($request->file('template_file'), $path, 'agreement');

                    if (!$fileName) {
                        throw new \RuntimeException(
                            'Agreement template upload failed.'
                        );
                    }

                    $templateFileUrl = $path . '/' . $fileName;
                    $uploadedFiles[] = $templateFileUrl;
                }

                // Upload signed agreement PDF
                if ($request->hasFile('signed_file')) {
                    $fileName = FileService::upload($request->file('signed_file'), $path, 'signed_agreement');

                    if (!$fileName) {
                        throw new \RuntimeException(
                            'Signed agreement upload failed.'
                        );
                    }

                    $signedFileUrl = $path . '/' . $fileName;
                    $uploadedFiles[] = $signedFileUrl;
                }

                $data = $validated;

                // File inputs are not database columns
                unset($data['template_file'], $data['signed_file']);

                $data['template_file_url'] = $templateFileUrl;
                $data['signed_file_url'] = $signedFileUrl;

                // Defaults
                $data['agreement_type'] ??= 'new';
                $data['version'] ??= 1;
                $data['auto_renew'] ??= false;
                $data['is_exclusive'] ??= false;
                $data['signed_by_franchisee'] ??= false;
                $data['signed_by_company'] ??= false;
                $data['status'] ??= 'draft';

                return FranchiseAgreement::create($data);
            });

            return response()->json([
                'status' => true,
                'message' => 'Franchise agreement created successfully.',
                'data' => $agreement,
            ], 201);
        } catch (Throwable $e) {
            foreach ($uploadedFiles as $uploadedFile) {
                FileService::delete($uploadedFile);
            }

            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to create franchise agreement.',
            ], 500);
        }
    }

    public function trashFrachise(Request $request, $data)
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

    public function restoreFranchise(Request $request, $data)
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

    public function deletedFranchise($data)
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

    public function deleteFranchise(Request $request, $data)
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

    // create a new database connection
    public function createCollegeDbConnection(Request $request, $data): JsonResponse
    {
        $validatedData = $request->validate([
            'college_id' => 'required|exists:college_clients,id',
            'group' => 'required|string',
            'db_name'    => 'required|string',
            'db_user'    => 'required|string',
            'db_password'    => 'required|string',
        ]);

        // gete here college_client by college_id

        $college_name = CollegeClient::select(['name'])->findOrFail($validatedData['college_id']);

        if (!$college_name) {
            return response()->json([
                "status" => false,
                "message" => "College client not found with given college id : " . $validatedData['college_id']
            ]);
        }

        $college_name = $college_name ?: $validatedData['group'];


        $response = $this->addConnection(
            $validatedData['group'],
            $validatedData['db_name'],
            $validatedData['db_user'],
            $validatedData['db_password'],
            $college_name
        );

        $data = $response->getData(true);

        if ($data['success'] === true) {
            return response()->json([
                'status' => true,
                'message' => $data['message'],
                'connection' => $data['connection'] ?? null,
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => $data['message'] ?? 'Failed to create database connection.',
        ], 500);
    }
}
