<?php

namespace App\Http\Controllers\API\Hrms\Onboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Hrms\EmployeeJobProfile;
use App\Models\Hrms\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Settings\Department;
use App\Models\Settings\Designation;
use App\Models\Settings\EmploymentType;
use App\Models\Settings\Joblocation;
use App\Models\Hrms\EmployeePersonalDetail;
use App\Models\Hrms\EmployeeBankAccount;
use App\Models\Hrms\EmployeeContactDetail;
use App\Models\Hrms\EmployeeContact;
use App\Models\Hrms\EmployeeDoc;
use App\Models\Settings\DocumentType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\Models\Hrms\EmployeeRelation;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OnboardController extends Controller
{
    /**
     * GET /api/hrms/employees/{employee}/job-profile
     * Get job profile information for an employee
     */
    public function getEmployeeJobProfile(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Unauthenticated'
            ], 401);
        }

        $employee = $user->employee;
        if (!$employee) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Employee profile not found'
            ], 404);
        }

        // Fetch job profile with related data
        $jobProfile = EmployeeJobProfile::with([
            'department',
            'designation',
            'employment_type',
            'joblocation',
            'manager' => function($q) {
                $q->select('id', 'fname', 'mname', 'lname');
            }
        ])
        ->where('employee_id', $employee->id)
        ->first();

        if (!$jobProfile) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Job profile not found for this employee'
            ], 404);
        }

        // Format the response data
        $data = [
            'employee_code' => $jobProfile->employee_code,
            'department' => [
                'id' => $jobProfile->department->id,
                'name' => $jobProfile->department->title
            ],
            'designation' => [
                'id' => $jobProfile->designation->id,
                'name' => $jobProfile->designation->title
            ],
            'employment_type' => [
                'id' => $jobProfile->employment_type->id,
                'name' => $jobProfile->employment_type->title
            ],
            'job_location' => $jobProfile->joblocation ? [
                'id' => $jobProfile->joblocation->id,
                'name' => $jobProfile->joblocation->name
            ] : null,
            'reporting_manager' => $jobProfile->manager ? [
                'id' => $jobProfile->manager->id,
                'name' => trim($jobProfile->manager->fname . ' ' . $jobProfile->manager->mname . ' ' . $jobProfile->manager->lname)
            ] : null,
            'date_of_joining' => $jobProfile->doh ? $jobProfile->doh->toDateString() : null,
            'date_of_exit' => $jobProfile->doe ? $jobProfile->doe->toDateString() : null,
            'uan_number' => $jobProfile->uanno,
            'esic_number' => $jobProfile->esicno
        ];

        return response()->json([
            'message_type' => 'success',
            'message_display' => 'none',
            'message' => 'Employee job profile fetched successfully',
            'data' => $data
        ], 200);
    }

    /**
     * GET /api/hrms/departments
     * Get all departments for the firm
     */
    public function getDepartments(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            $departments = Department::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false)
                ->orderBy('title')
                ->get(['id', 'title', 'code']);

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Departments fetched successfully',
                'data' => $departments
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/designations
     * Get all designations for the firm
     */
    public function getDesignations(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch designations for the firm
            $designations = Designation::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false)
                ->orderBy('title')
                ->get(['id', 'title', 'code']);

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Designations fetched successfully',
                'data' => $designations
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employment-types
     * Get all employment types for the firm
     */
    public function getEmploymentTypes(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch employment types for the firm
            $employmentTypes = EmploymentType::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false)
                ->orderBy('title')
                ->get(['id', 'title', 'code']);

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Employment types fetched successfully',
                'data' => $employmentTypes
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/reporting-managers
     * Get a list of potential reporting managers for the user's firm
     */
    public function getReportingManagers(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch employees to be potential managers
            $managers = Employee::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false)
                ->where('id', '!=', $employee->id) // Exclude the current employee
                ->with('emp_job_profile') // Eager load the job profile relationship
                ->get(['id', 'fname', 'lname'])
                ->map(function($manager) {
                    return [
                        'id' => $manager->id,
                        'name' => trim($manager->fname . ' ' . $manager->lname),
                        'employee_code' => $manager->emp_job_profile ? $manager->emp_job_profile->employee_code : null,
                    ];
                });

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Reporting managers fetched successfully',
                'data' => $managers
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/job-locations
     * Get all job locations for the firm
     */
    public function getJobLocations(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch job locations for the firm
            // Note: Joblocation model uses 'name' and 'code' columns
            $jobLocations = Joblocation::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false)
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Job locations fetched successfully',
                'data' => $jobLocations
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/hrms/employees/job-profile
     * Save or update employee job profile
     */
    public function saveEmployeeJobProfile(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'employee_code'      => 'nullable|string|max:255',
                'department_id'      => 'required|integer|exists:departments,id',
                'designation_id'     => 'required|integer|exists:designations,id',
                'employment_type_id' => 'required|integer|exists:employment_types,id',
                'reporting_manager'  => 'nullable|integer|exists:employees,id',
                'joblocation_id'     => 'nullable|integer|exists:joblocations,id',
                'doh'                => 'nullable|date', // Date of Hiring
                'doe'                => 'nullable|date|after_or_equal:doh', // Date of Exit
                'uanno'              => 'nullable|string|max:255',
                'esicno'             => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            // Find or create the employee job profile
            $jobProfile = EmployeeJobProfile::where('employee_id', $employee->id)->first();

            if ($jobProfile) {
                // Update existing profile
                $jobProfile->update($validated);
            } else {
                // Create new profile
                $jobProfile = EmployeeJobProfile::create(array_merge($validated, [
                    'firm_id' => $employee->firm_id,
                    'employee_id' => $employee->id,
                ]));
            }

            DB::commit();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Employee job profile saved successfully',
                'data' => array_merge(['id' => $jobProfile->id], $validated) // Return ID and validated input data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/hrms/employees/personal-details
     * Save or update employee personal details
     */
    public function saveEmployeePersonalDetail(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'dob'            => 'nullable|date',
                'marital_status' => 'nullable|string|max:255',
                'doa'            => 'nullable|date|after_or_equal:dob',
                'nationality'    => 'nullable|string|max:255',
                'fathername'     => 'nullable|string|max:255',
                'mothername'     => 'nullable|string|max:255',
                'adharno'        => 'nullable|string|max:255',
                'panno'          => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            // Find or create the employee personal detail record
            $personalDetail = EmployeePersonalDetail::where('employee_id', $employee->id)->first();

            if ($personalDetail) {
                // Update existing record
                $personalDetail->update($validated);
            } else {
                // Create new record
                $personalDetail = EmployeePersonalDetail::create(array_merge($validated, [
                    'firm_id' => $employee->firm_id,
                    'employee_id' => $employee->id,
                ]));
            }

            DB::commit();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Employee personal details saved successfully',
                'data' => array_merge(['id' => $personalDetail->id], $validated) // Return ID and validated input data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/hrms/employees/post-bank-account
     * Save or update employee bank account details
     */
    public function saveEmployeeBankAccount(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'bank_name'     => 'required|string|max:255',
                'branch_name'   => 'required|string|max:255',
                'address'       => 'nullable|string|max:255',
                'ifsc'          => 'required|string|max:255', // You might want a more specific regex validation for IFSC
                'bankaccount'   => 'required|string|max:255', // You might want to validate format/uniqueness
                'is_primary'    => 'nullable|boolean',
                'is_inactive'   => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            // Find or create the employee bank account record
            // Assuming one primary bank account per employee based on model structure
            $bankAccount = EmployeeBankAccount::where('employee_id', $employee->id)->first();

            if ($bankAccount) {
                // Update existing record
                $bankAccount->update($validated);
            } else {
                // Create new record
                // Set defaults for boolean fields if not provided
                $dataToCreate = array_merge($validated, [
                    'firm_id' => $employee->firm_id,
                    'employee_id' => $employee->id,
                ]);
                if (!isset($dataToCreate['is_primary'])) {
                    $dataToCreate['is_primary'] = false;
                }
                if (!isset($dataToCreate['is_inactive'])) {
                    $dataToCreate['is_inactive'] = false;
                }

                $bankAccount = EmployeeBankAccount::create($dataToCreate);
            }

            DB::commit();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Employee bank account details saved successfully',
                'data' => array_merge(['id' => $bankAccount->id], $validated) // Return ID and validated input data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employees/contact-details
     * Get employee contact details
     */
    public function getEmployeeContactDetail(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch employee contact details
            $contactDetail = EmployeeContactDetail::where('employee_id', $employee->id)->first();

            if (!$contactDetail) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Contact details not found for this employee'
                ], 404);
            }

            // Assuming EmployeeContactDetail model has common contact fields
            $data = [
                'present_address' => $contactDetail->present_address ?? null,
                'permanent_address' => $contactDetail->permanent_address ?? null,
                'mobile_phone' => $contactDetail->mobile_phone ?? null,
                'emergency_contact_name' => $contactDetail->emergency_contact_name ?? null,
                'emergency_contact_relationship' => $contactDetail->emergency_contact_relationship ?? null,
                'emergency_contact_phone' => $contactDetail->emergency_contact_phone ?? null,
                // Add other contact fields as per your EmployeeContactDetail model
            ];

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Employee contact details fetched successfully',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employees/personal-details
     * Get employee personal details
     */
    public function getEmployeePersonalDetail(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch employee personal details
            $personalDetail = EmployeePersonalDetail::where('employee_id', $employee->id)->first();

            if (!$personalDetail) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Personal details not found for this employee'
                ], 404);
            }

            // Format the response data
            $data = [
                'id' => $personalDetail->id,
                'dob' => $personalDetail->dob ? $personalDetail->dob->toDateString() : null,
                'marital_status' => $personalDetail->marital_status,
                'doa' => $personalDetail->doa ? $personalDetail->doa->toDateString() : null,
                'nationality' => $personalDetail->nationality,
                'fathername' => $personalDetail->fathername,
                'mothername' => $personalDetail->mothername,
                'adharno' => $personalDetail->adharno,
                'panno' => $personalDetail->panno,
            ];

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Employee personal details fetched successfully',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employees/bank-account
     * Get employee bank account details
     */
    public function getEmployeeBankAccount(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch employee bank account details
            $bankAccounts = EmployeeBankAccount::where('employee_id', $employee->id)->get();

            if ($bankAccounts->isEmpty()) { // Check if the collection is empty
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Bank account details not found for this employee'
                ], 404);
            }

            // Format the response data as a list of bank accounts
            $data = $bankAccounts->map(function($account) {
                return [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'branch_name' => $account->branch_name,
                    'address' => $account->address,
                    'ifsc' => $account->ifsc,
                    'bankaccount' => $account->bankaccount,
                    'is_primary' => (bool) $account->is_primary,
                    'is_inactive' => (bool) $account->is_inactive,
                ];
            });

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Employee bank account details fetched successfully',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employees/contacts
     * Get all contact details for an employee
     */
    public function getEmployeeContacts(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch all contact details for the employee
            $contacts = EmployeeContact::where('employee_id', $employee->id)
                ->where('is_inactive', false) // Assuming you only want active contacts
                ->orderBy('contact_type') // Order by type or primary status as needed
                ->get();

            if ($contacts->isEmpty()) {
                return response()->json([
                    'message_type' => 'success', // Or error, depending on desired behavior for no contacts
                    'message_display' => 'none', // Or flash
                    'message' => 'No contact details found for this employee',
                    'data' => []
                ], 200); // Or 404
            }

            // Format the response data - returning the collection directly for now
            // You might want to map this to select specific fields or format booleans
            $formattedContacts = $contacts->map(function($contact) {
                 return [
                    'id' => $contact->id,
                    'contact_type' => $contact->contact_type,
                    'contact_type_label' => $contact->contact_type_label,
                    'contact_value' => $contact->contact_value,
                    'contact_person' => $contact->contact_person,
                    'relation' => $contact->relation,
                    'is_primary' => (bool) $contact->is_primary,
                    'is_for_emergency' => (bool) $contact->is_for_emergency,
                 ];
             });

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => count($contacts) . ' contact details fetched successfully',
                'data' => $formattedContacts
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/contact-types
     * Get the list of available contact types
     */
    public function getContactTypes()
    {
        try {
            // Return the static contact types array from the model
            $contactTypes = EmployeeContact::CONTACT_TYPE_SELECT;

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Contact types fetched successfully',
                'data' => $contactTypes
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/hrms/employees/post-contacts
     * Save or update employee contact details (single record)
     */
    public function saveEmployeeContacts(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Validate the incoming request data for a single contact
            $validatedData = $request->validate([
                'id' => 'nullable|integer|exists:employee_contacts,id', // Optional ID for updating existing contact
                'contact_type' => 'required|string|in:' . implode(',', array_keys(EmployeeContact::CONTACT_TYPE_SELECT)),
                'contact_value' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'relation' => 'nullable|string|max:255',
                'is_primary' => 'nullable|boolean',
                'is_for_emergency' => 'nullable|boolean',
                'is_inactive' => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            // Find existing contact if ID is provided and belongs to the employee
            $contact = null;
            if (isset($validatedData['id'])) {
                $contact = EmployeeContact::where('employee_id', $employee->id)
                                            ->find($validatedData['id']);
                // If contact exists and belongs to this employee, remove ID from data for update
                if ($contact) {
                    unset($validatedData['id']); // Don't try to update the ID
                }
            }

            if ($contact) {
                // Update existing contact
                $contact->update($validatedData);
            } else {
                 // If no ID or ID was invalid/didn't belong to employee, create a new record
                 // Check if a primary contact of this type already exists if is_primary is true
                 if (isset($validatedData['is_primary']) && $validatedData['is_primary']) {
                     // Optional: Add logic here to deactivate the old primary contact of this type
                     // or return an error if only one primary is allowed per type.
                     // For now, it will just create a new primary without changing the old one.
                 }
                // Ensure firm_id and employee_id are set for new records
                $contact = EmployeeContact::create(array_merge($validatedData, [
                    'firm_id' => $employee->firm_id,
                    'employee_id' => $employee->id,
                ]));
            }

            DB::commit();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Contact details saved successfully',
                'data' => ['id' => $contact->id] // Return ID of the saved contact
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
             DB::rollBack();
             return response()->json([
                 'message_type' => 'error',
                 'message_display' => 'popup',
                 'message' => 'Validation failed',
                 'errors' => $e->errors()
             ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employees/docs
     * Get all document details for an employee
     */
    public function getEmployeeDocs(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch all document details for the employee, with document type
            $employeeDocs = EmployeeDoc::with('document_type')
                ->where('employee_id', $employee->id)
                ->where('is_inactive', false) // Assuming you only want active documents
                ->orderBy('document_type_id')
                ->get();

            if ($employeeDocs->isEmpty()) {
                return response()->json([
                    'message_type' => 'success', // Or error, depending on desired behavior
                    'message_display' => 'none', // Or flash
                    'message' => 'No document details found for this employee',
                    'data' => []
                ], 200); // Or 404
            }

            // Format the response data as a list of documents
            $data = $employeeDocs->map(function($doc) {
                 return [
                    'id' => $doc->id,
                    'document_type_id' => $doc->document_type_id,
                    'document_type_name' => $doc->document_type->title ?? null, // Assuming DocumentType has a 'title' field
                    'document_number' => $doc->document_number,
                    'issued_date' => $doc->issued_date ? $doc->issued_date->toDateString() : null,
                    'expiry_date' => $doc->expiry_date ? $doc->expiry_date->toDateString() : null,
                    'doc_url' => $doc->doc_url,
                    'is_inactive' => (bool) $doc->is_inactive,
                 ];
             });

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => count($employeeDocs) . ' document details fetched successfully',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/hrms/employees/post-doc
     * Save or update employee document details (single record)
     * Accepts file upload via multipart/form-data
     */
    public function saveEmployeeDoc(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Validate the incoming request data for a single document and the file
            $validatedData = $request->validate([
                'id'               => 'nullable|integer|exists:employee_docs,id', // Optional ID for updating existing doc
                'document_type_id' => 'required|integer|exists:document_types,id', // Assuming document_types table exists
                'document_number'  => 'required|string|max:255',
                'issued_date'      => 'nullable|date',
                'expiry_date'      => 'nullable|date|after_or_equal:issued_date',
                'document_file'    => 'nullable|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:5120', // Max 5MB, specified types
                'is_inactive'      => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            $employeeDoc = null;
            if (isset($validatedData['id'])) {
                $employeeDoc = EmployeeDoc::where('employee_id', $employee->id)
                                           ->find($validatedData['id']);
                if ($employeeDoc) {
                    unset($validatedData['id']); // Don't try to update the ID
                }
            }

            if ($employeeDoc) {
                $employeeDoc->update($validatedData);
            } else {
                $employeeDoc = EmployeeDoc::create(array_merge($validatedData, [
                    'firm_id' => $employee->firm_id,
                    'employee_id' => $employee->id,
                ]));
            }

            // Handle file upload using Spatie Media Library
            if ($request->hasFile('document_file')) {
                $media = $employeeDoc->addMediaFromRequest('document_file')
                    ->toMediaCollection('documents');
                
                // Update the doc_url field with the media URL
                $employeeDoc->update([
                    'doc_url' => $media->getUrl()
                ]);
            }

            DB::commit();

            // Fetch the saved document with its type for the response
            $savedDoc = EmployeeDoc::with('document_type')->find($employeeDoc->id);

            // Get the document URL from media collection
            $media = $savedDoc->getMedia('documents')->first();
            $docUrl = $media ? $media->getUrl() : $savedDoc->doc_url;

            // Prepare response data
            $responseData = [
                'id' => $savedDoc->id,
                'document_type_id' => $savedDoc->document_type_id,
                'document_type_name' => $savedDoc->document_type->title ?? null,
                'document_number' => $savedDoc->document_number,
                'issued_date' => $savedDoc->issued_date ? $savedDoc->issued_date->toDateString() : null,
                'expiry_date' => $savedDoc->expiry_date ? $savedDoc->expiry_date->toDateString() : null,
                'doc_url' => $docUrl,
                'is_inactive' => (bool) $savedDoc->is_inactive,
            ];

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Document details saved successfully',
                'data' => $responseData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/document-types
     * Get all document types for the firm
     */
    public function getDocumentTypes(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch document types for the firm
            $documentTypes = DocumentType::where('firm_id', $employee->firm_id)
                ->where('is_inactive', false) // Assuming you only want active types
                ->orderBy('title') // Order by title
                ->get(['id', 'title', 'code']);

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Document types fetched successfully',
                'data' => $documentTypes
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/relations
     * Get the list of available relation types
     */
    public function getRelations()
    {
        try {
            // Return the static relation types array from the model
            $relations = EmployeeRelation::RELATION_SELECT;

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => 'Relation types fetched successfully',
                'data' => $relations
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/hrms/employees/relations
     * Get all relation details for an employee
     */
    public function getEmployeeRelations(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Fetch all relation details for the employee
            $employeeRelations = EmployeeRelation::where('employee_id', $employee->id)
                ->where('is_inactive', false) // Assuming you only want active relations
                ->orderBy('relation') // Or order by person_name, etc.
                ->get();

            if ($employeeRelations->isEmpty()) {
                return response()->json([
                    'message_type' => 'success', // Or error, depending on desired behavior
                    'message_display' => 'none', // Or flash
                    'message' => 'No relation details found for this employee',
                    'data' => []
                ], 200); // Or 404
            }

            // Format the response data as a list of relations
            $data = $employeeRelations->map(function($relation) {
                 return [
                    'id' => $relation->id,
                    'relation' => $relation->relation,
                    'relation_label' => $relation->relation_label ?? null,
                    'person_name' => $relation->person_name,
                    'occupation' => $relation->occupation,
                    'dob' => $relation->dob ? $relation->dob->toDateString() : null,
                    'qualification' => $relation->qualification,
                    'is_inactive' => (bool) $relation->is_inactive,
                 ];
             });

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'none',
                'message' => count($employeeRelations) . ' relation details fetched successfully',
                'data' => $data
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/hrms/employees/post-relation
     * Save or update employee relation details (single record)
     */
    public function saveEmployeeRelation(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $employee = $user->employee;
            if (!$employee) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Employee profile not found'
                ], 404);
            }

            // Validate the incoming request data for a single relation
            $validatedData = $request->validate([
                'id' => 'nullable|integer|exists:employee_relations,id', // Optional ID for updating existing relation
                'relation' => 'required|string|in:' . implode(',', array_keys(EmployeeRelation::RELATION_SELECT)),
                'person_name' => 'required|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'dob' => 'nullable|date',
                'qualification' => 'nullable|string|max:255',
                'is_inactive' => 'nullable|boolean',
            ]);

            DB::beginTransaction();

            // Find existing relation if ID is provided and belongs to the employee
            $employeeRelation = null;
            if (isset($validatedData['id'])) {
                $employeeRelation = EmployeeRelation::where('employee_id', $employee->id)
                                            ->find($validatedData['id']);
                // If relation exists and belongs to this employee, remove ID from data for update
                if ($employeeRelation) {
                    unset($validatedData['id']); // Don't try to update the ID
                }
            }

            if ($employeeRelation) {
                // Update existing relation
                $employeeRelation->update($validatedData);
            } else {
                 // If no ID or ID was invalid/didn't belong to employee, create a new record
                 // Ensure firm_id and employee_id are set for new records
                 // Set defaults for boolean fields if not provided
                 $dataToCreate = array_merge($validatedData, [
                     'firm_id' => $employee->firm_id,
                     'employee_id' => $employee->id,
                 ]);
                 if (!isset($dataToCreate['is_inactive'])) {
                     $dataToCreate['is_inactive'] = false;
                 }

                 $employeeRelation = EmployeeRelation::create($dataToCreate);
            }

            DB::commit();

            return response()->json([
                'message_type' => 'success',
                'message_display' => 'popup',
                'message' => 'Employee relation details saved successfully',
                'data' => ['id' => $employeeRelation->id] // Return ID of the saved relation
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
             DB::rollBack();
             return response()->json([
                 'message_type' => 'error',
                 'message_display' => 'popup',
                 'message' => 'Validation failed',
                 'errors' => $e->errors()
             ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'popup',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
