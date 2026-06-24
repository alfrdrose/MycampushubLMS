<?php

namespace App\Http\Controllers;


use App\Enums\Role;
use App\Models\SectionModel;
use App\Services\StudentEnrollmentSyncService;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\User;
use App\Models\StudentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    private const STUDENT_CSV_COLUMNS = [
        'username',
        'account_email',
        'account_password',
        'role',
        'first_name',
        'last_name',
        'student_email',
        'guardian_email',
        'grade',
        'section_id',
    ];

    public function __construct(
        private readonly StudentEnrollmentSyncService $studentEnrollmentSyncService
    ) {
    }

    public function index(Request $request)
{
    $search = $request->input('search');
    $status = $request->input('status');
    $role   = $request->input('role');

    $users = User::with('roles')   // ⬅️ important
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($status, function ($query, $status) {
            $query->where('status', $status);
        })
        ->when($role, function ($query, $role) {
            // Only apply if not "All"
            if ($role !== 'all' && $role !== null) {
                $query->role($role);   // ⬅️ Spatie role filter
            }
        })
        ->orderBy('id', 'asc')
        ->paginate(10)
        ->withQueryString();

    $columns = Schema::getColumnListing('users');

    $exclude = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at'
    ];

    $columns = array_diff($columns, $exclude);

    // ⬅️ Add role column to table columns
    $columns[] = 'role';

    $columns[] = 'actions';

    return view('pages.admin.dashboard.accounts', [
        'title' => 'My Campus LMS | Accounts',
        'preloader' => false,
        'darkmode'  => true,
        'scrollup'  => true,
        'sidebar'   => true,
        'banner'    => false,
        'users'     => $users,
        'columns'   => $columns,
        'status'    => $status,
    ]);
}

    public function downloadStudentCsvTemplate()
    {
        $columns = self::STUDENT_CSV_COLUMNS;

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            fclose($handle);
        }, 'student_accounts_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function uploadStudentCsv(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'students_csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            ],
            [
                'students_csv.required' => 'Select a CSV file to import.',
                'students_csv.file' => 'The selected upload must be a file.',
                'students_csv.mimes' => 'Upload a valid CSV file in `.csv` format.',
                'students_csv.max' => 'The CSV file may not be larger than 5 MB.',
            ]
        );

        if ($validator->fails()) {
            return $this->failStudentCsvImport(
                $validator->errors()->get('students_csv')
            );
        }

        $file = $request->file('students_csv');
        $csv = new \SplFileObject($file->getRealPath());
        $csv->setFlags(\SplFileObject::READ_CSV);

        $rows = [];
        foreach ($csv as $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            $isEmpty = true;
            foreach ($row as $value) {
                if ($value !== null && trim((string) $value) !== '') {
                    $isEmpty = false;
                    break;
                }
            }

            if ($isEmpty) {
                continue;
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            return $this->failStudentCsvImport([
                'The uploaded CSV is empty. Add at least one student row before importing.',
            ]);
        }

        $columns = self::STUDENT_CSV_COLUMNS;
        $headerIssues = $this->inspectStudentCsvHeader(array_shift($rows), $columns);

        if ($headerIssues !== []) {
            return $this->failStudentCsvImport($headerIssues);
        }

        if ($rows === []) {
            return $this->failStudentCsvImport([
                'The CSV only contains the header row. Add at least one student record before importing.',
            ]);
        }

        $existingUsernames = User::query()
            ->pluck('name')
            ->map(fn ($name) => strtolower($name))
            ->flip()
            ->all();

        $existingAccountEmails = User::query()
            ->pluck('email')
            ->map(fn ($email) => strtolower($email))
            ->flip()
            ->all();

        $existingStudentEmails = StudentModel::query()
            ->pluck('email')
            ->map(fn ($email) => strtolower($email))
            ->flip()
            ->all();

        $sectionGradeLevels = SectionModel::query()
            ->pluck('grade_level', 'id')
            ->map(fn ($gradeLevel) => $gradeLevel === null ? null : (string) $gradeLevel)
            ->all();

        $parsedRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $row = is_array($row) ? $row : [];
            $rawColumnCount = count($row);
            $normalizedRow = array_slice(array_pad($row, count($columns), null), 0, count($columns));
            $data = array_combine($columns, array_map(function ($value) {
                $value = trim((string) $value);
                return $value === '' ? null : $value;
            }, $normalizedRow));

            if ($data['role'] !== null) {
                $data['role'] = strtolower($data['role']);
            }

            $parsedRows[] = [
                'row' => $rowNumber,
                'raw_column_count' => $rawColumnCount,
                'raw_row' => $row,
                'data' => $data,
            ];
        }

        $usernameCounts = $this->buildStudentCsvDuplicateMap($parsedRows, 'username');
        $accountEmailCounts = $this->buildStudentCsvDuplicateMap($parsedRows, 'account_email');
        $studentEmailCounts = $this->buildStudentCsvDuplicateMap($parsedRows, 'student_email');

        $preparedRows = [];
        $rowIssues = [];
        $issueItems = [];

        foreach ($parsedRows as $parsedRow) {
            $rowNumber = $parsedRow['row'];
            $data = $parsedRow['data'];
            $rowIssueItems = [];

            if ($parsedRow['raw_column_count'] > count($columns)) {
                $extraValues = array_slice($parsedRow['raw_row'] ?? [], count($columns));
                $extraValues = array_values(array_filter(array_map(function ($value) {
                    $value = trim((string) $value);
                    return $value === '' ? null : $value;
                }, $extraValues)));

                $message = 'Unexpected extra data was found after [section_id]. Remove any cells beyond [section_id].';
                if ($extraValues !== []) {
                    $message = 'Unexpected extra data [' . implode(', ', $extraValues) . '] was found after [section_id]. Remove any cells beyond [section_id].';
                }

                $rowIssueItems[] = $this->buildStudentCsvIssueItem(
                    $rowNumber,
                    'after section_id',
                    null,
                    $message
                );
            }

            $rowValidator = Validator::make(
                $data,
                $this->studentCsvRules(),
                $this->studentCsvMessages(),
                $this->studentCsvAttributes()
            );

            $rowValidator->after(function ($validator) use (
                $data,
                $existingUsernames,
                $existingAccountEmails,
                $existingStudentEmails,
                $usernameCounts,
                $accountEmailCounts,
                $studentEmailCounts,
                $sectionGradeLevels
            ) {
                $username = strtolower((string) ($data['username'] ?? ''));
                $accountEmail = strtolower((string) ($data['account_email'] ?? ''));
                $studentEmail = strtolower((string) ($data['student_email'] ?? ''));

                if ($username !== '') {
                    if (isset($existingUsernames[$username])) {
                        $validator->errors()->add('username', 'This username [' . ($data['username'] ?? '') . '] already exists in the system.');
                    }

                    if (($usernameCounts[$username] ?? 0) > 1) {
                        $validator->errors()->add('username', 'This username [' . ($data['username'] ?? '') . '] is duplicated within the CSV file.');
                    }
                }

                if ($accountEmail !== '') {
                    if (isset($existingAccountEmails[$accountEmail])) {
                        $validator->errors()->add('account_email', 'This account email [' . ($data['account_email'] ?? '') . '] already exists in the system.');
                    }

                    if (($accountEmailCounts[$accountEmail] ?? 0) > 1) {
                        $validator->errors()->add('account_email', 'This account email [' . ($data['account_email'] ?? '') . '] is duplicated within the CSV file.');
                    }
                }

                if ($studentEmail !== '') {
                    if (isset($existingStudentEmails[$studentEmail])) {
                        $validator->errors()->add('student_email', 'This student email [' . ($data['student_email'] ?? '') . '] already exists in the system.');
                    }

                    if (($studentEmailCounts[$studentEmail] ?? 0) > 1) {
                        $validator->errors()->add('student_email', 'This student email [' . ($data['student_email'] ?? '') . '] is duplicated within the CSV file.');
                    }
                }

                $sectionId = $data['section_id'] ?? null;
                $grade = $data['grade'] ?? null;

                if ($sectionId === null || filter_var($sectionId, FILTER_VALIDATE_INT) === false) {
                    return;
                }

                $sectionId = (int) $sectionId;
                $sectionGrade = $sectionGradeLevels[$sectionId] ?? null;

                if ($sectionGrade === null) {
                    $validator->errors()->add('section_id', 'This section id [' . $sectionId . '] does not exist.');
                    return;
                }

                if ($grade !== null && (string) $grade !== (string) $sectionGrade) {
                    $validator->errors()->add(
                        'section_id',
                        'This section id [' . $sectionId . '] belongs to Grade [' . $sectionGrade . '], but the grade column contains [' . $grade . '].'
                    );
                }
            });

            if ($rowValidator->fails()) {
                foreach ($rowValidator->errors()->getMessages() as $field => $messages) {
                    foreach (array_values(array_unique($messages)) as $message) {
                        $rowIssueItems[] = $this->buildStudentCsvIssueItem(
                            $rowNumber,
                            $field,
                            $data[$field] ?? null,
                            $message
                        );
                    }
                }

                $formattedIssueTexts = array_values(array_unique(array_map(
                    fn ($item) => $item['text'],
                    $rowIssueItems
                )));

                $rowIssues[] = [
                    'row' => $rowNumber,
                    'issues' => $formattedIssueTexts,
                ];
                $issueItems = [...$issueItems, ...$rowIssueItems];
                continue;
            }

            $preparedRows[] = $data;
        }

        if ($rowIssues !== []) {
            return $this->failStudentCsvImport(
                ['The import was stopped because one or more rows failed validation. Review the report below, fix the CSV, and try again.'],
                $rowIssues,
                [
                    'rows_scanned' => count($parsedRows),
                    'rows_ready' => count($preparedRows),
                    'rows_invalid' => count($rowIssues),
                ],
                $issueItems
            );
        }

        if ($preparedRows === []) {
            return $this->failStudentCsvImport([
                'No valid student rows were found to import.',
            ]);
        }

        DB::transaction(function () use ($preparedRows) {
            foreach ($preparedRows as $data) {
                $user = User::create([
                    'name' => $data['username'],
                    'email' => $data['account_email'],
                    'password' => Hash::make($data['account_password']),
                ]);

                $user->assignRole('student');

                $studentData = [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['student_email'],
                    'grade' => $data['grade'],
                    'user_id' => $user->id,
                ];

                if (Schema::hasColumn('student_models', 'guardian_email')) {
                    $studentData['guardian_email'] = $data['guardian_email'];
                }

                if (!empty($data['section_id'])) {
                    $studentData['section_id'] = $data['section_id'];
                }

                $student = $user->student()->create($studentData);

                $this->studentEnrollmentSyncService->syncCurrentEnrollment($student);
            }
        });

        $count = count($preparedRows);

        return redirect()
            ->route('admin.accounts')
            ->with('success', "Imported {$count} student account" . ($count === 1 ? '' : 's') . ' successfully!');
    }

    private function studentCsvRules(): array
    {
        $guardianRule = Schema::hasColumn('student_models', 'guardian_email')
            ? ['required', 'email', 'max:255']
            : ['nullable', 'email', 'max:255'];

        return [
            'username' => ['bail', 'required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/'],
            'account_email' => ['bail', 'required', 'email', 'max:255'],
            'account_password' => ['bail', 'required', 'string', 'min:6', 'max:255'],
            'role' => ['required', Rule::in(['student'])],
            'first_name' => ['bail', 'required', 'string', 'max:255', "regex:/^[\\pL\\s.'-]+$/u"],
            'last_name' => ['bail', 'required', 'string', 'max:255', "regex:/^[\\pL\\s.'-]+$/u"],
            'student_email' => ['bail', 'required', 'email', 'max:255'],
            'guardian_email' => $guardianRule,
            'grade' => ['required', Rule::in(['1','2','3','4','5','6','7','8','9','10','11','12'])],
            'section_id' => ['nullable', 'integer'],
        ];
    }

    private function studentCsvMessages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.regex' => 'Username may only contain letters, numbers, periods, underscores, and hyphens.',
            'account_email.required' => 'Account email is required.',
            'account_email.email' => 'Account email must be a valid email address.',
            'account_password.required' => 'Account password is required.',
            'account_password.min' => 'Account password must be at least 6 characters.',
            'role.required' => 'Role is required.',
            'role.in' => 'Role must be set to `student` for this import.',
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'student_email.required' => 'Student email is required.',
            'student_email.email' => 'Student email must be a valid email address.',
            'guardian_email.required' => 'Guardian email is required.',
            'guardian_email.email' => 'Guardian email must be a valid email address.',
            'grade.required' => 'Grade is required.',
            'grade.in' => 'Grade must be a number from 1 to 12.',
            'section_id.integer' => 'Section ID must be a whole number.',
        ];
    }

    private function studentCsvAttributes(): array
    {
        return [
            'username' => 'username',
            'account_email' => 'account email',
            'account_password' => 'account password',
            'student_email' => 'student email',
            'guardian_email' => 'guardian email',
            'section_id' => 'section',
        ];
    }

    private function inspectStudentCsvHeader(array $header, array $expectedColumns): array
    {
        $normalizedHeader = array_map(function ($value) {
            $value = trim((string) $value);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return strtolower($value);
        }, $header);
        $expectedHeader = array_map('strtolower', $expectedColumns);
        $issues = [];

        $blankColumns = [];
        foreach ($normalizedHeader as $index => $value) {
            if ($value === '') {
                $blankColumns[] = $index + 1;
            }
        }

        if ($blankColumns !== []) {
            $issues[] = 'The header row contains empty column name' . (count($blankColumns) === 1 ? '' : 's') . ' in column ' . implode(', ', $blankColumns) . '.';
        }

        $headerCounts = array_count_values(array_filter($normalizedHeader));
        $duplicateColumns = array_keys(array_filter($headerCounts, fn ($count) => $count > 1));
        if ($duplicateColumns !== []) {
            $issues[] = 'The header row contains duplicate column name' . (count($duplicateColumns) === 1 ? '' : 's') . ': ' . implode(', ', $duplicateColumns) . '.';
        }

        $missingColumns = array_values(array_diff($expectedHeader, array_unique(array_filter($normalizedHeader))));
        if ($missingColumns !== []) {
            $issues[] = 'Missing required column' . (count($missingColumns) === 1 ? '' : 's') . ': ' . implode(', ', $missingColumns) . '.';
        }

        $extraColumns = array_values(array_diff(array_unique(array_filter($normalizedHeader)), $expectedHeader));
        if ($extraColumns !== []) {
            $issues[] = 'Unexpected column' . (count($extraColumns) === 1 ? '' : 's') . ': ' . implode(', ', $extraColumns) . '.';
        }

        if ($normalizedHeader !== $expectedHeader) {
            $issues[] = 'The header row must match the template exactly and stay in this order: ' . implode(', ', $expectedColumns) . '.';
        }

        return array_values(array_unique($issues));
    }

    private function buildStudentCsvDuplicateMap(array $parsedRows, string $field): array
    {
        $counts = [];

        foreach ($parsedRows as $parsedRow) {
            $value = strtolower((string) ($parsedRow['data'][$field] ?? ''));

            if ($value === '') {
                continue;
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        return $counts;
    }

    private function buildStudentCsvIssueItem(
        ?int $line,
        string $column,
        mixed $value,
        string $message
    ): array {
        return [
            'line' => $line,
            'column' => $column,
            'text' => $this->formatStudentCsvIssueText($column, $value, $message),
        ];
    }

    private function formatStudentCsvIssueText(string $column, mixed $value, string $message): string
    {
        $safeColumnsWithoutValueEcho = ['account_password', 'after section_id'];
        $value = is_scalar($value) ? trim((string) $value) : '';

        if (
            $value === ''
            || in_array($column, $safeColumnsWithoutValueEcho, true)
            || str_contains($message, '[')
        ) {
            return $message;
        }

        $columnPrefix = ucfirst(str_replace('_', ' ', $column)) . ' ';
        if (!str_starts_with(strtolower($message), strtolower($columnPrefix))) {
            return $message;
        }

        $trimmedMessage = preg_replace(
            '/^' . preg_quote($columnPrefix, '/') . '/i',
            '',
            $message
        );

        if (!is_string($trimmedMessage) || trim($trimmedMessage) === '') {
            return $message;
        }

        return 'This ' . str_replace('_', ' ', $column) . ' [' . $value . '] ' . lcfirst(trim($trimmedMessage));
    }

    private function failStudentCsvImport(
        array $fileIssues,
        array $rowIssues = [],
        array $summary = [],
        array $issueItems = []
    ) {
        $fileIssues = array_values(array_unique(array_filter($fileIssues)));
        $rowIssues = array_values(array_map(function ($rowIssue) {
            return [
                'row' => $rowIssue['row'],
                'issues' => array_values(array_unique(array_filter($rowIssue['issues'] ?? []))),
            ];
        }, $rowIssues));
        $issueItems = array_values(array_filter($issueItems, function ($issueItem) {
            return is_array($issueItem) && !empty($issueItem['text']);
        }));

        if ($issueItems === []) {
            $issueItems = array_map(function ($issue) {
                return [
                    'line' => null,
                    'column' => 'csv header',
                    'text' => $issue,
                ];
            }, $fileIssues);
        } else {
            $issueItems = [
                ...array_map(function ($issue) {
                    return [
                        'line' => null,
                        'column' => 'csv header',
                        'text' => $issue,
                    ];
                }, $fileIssues),
                ...$issueItems,
            ];
        }

        return back()
            ->withErrors([
                'students_csv' => $fileIssues[0] ?? 'CSV import validation failed. Review the validation report and try again.',
            ], 'studentCsv')
            ->with('studentCsvImportFeedback', [
                'file_issues' => $fileIssues,
                'row_issues' => $rowIssues,
                'issue_items' => $issueItems,
                'summary' => array_merge([
                    'rows_scanned' => 0,
                    'rows_ready' => 0,
                    'rows_invalid' => count($rowIssues),
                ], $summary),
            ]);
    }







    public function create()
    {
        return view('components.accounts.addaccountContent', [
            'title' => 'My Campus LMS | Add Account',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => 'required|string',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($validated['role']);

        if ($validated['role'] === 'teacher') {
            $user->teacher()->create([
                'first_name' => '',
                'last_name'  => '',
                'email'      => $user->email,
            ]);
        }

        if ($validated['role'] === 'student') {
            $student = $user->student()->create([
                'first_name' => '',
                'last_name'  => '',
                'email'      => $user->email,
            ]);

            $this->studentEnrollmentSyncService->syncCurrentEnrollment($student);
        }

        return redirect()
            ->route('admin.accounts')
            ->with('success', 'Account added successfully!');
    }

    public function storeTeacherAccount(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => ['required', Rule::in(['teacher'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function storeStudentAccount(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role'     => ['required', Rule::in(['student'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    public function storeTeacherWithAccount(Request $request)
    {
        $validated = $request->validate([
            'account_name'     => 'required|string|max:255',
            'account_email'    => 'required|email|unique:users,email',
            'account_password' => 'required|min:6|confirmed',
            'account_role'     => ['required', Rule::in(['teacher'])],
            'first_name'       => ['required', 'string', 'max:255'],
            'last_name'        => ['required', 'string', 'max:255'],
            'faculty'          => [
                'required',
                Rule::in([
                    'english',
                    'mathematics',
                    'science',
                    'filipino',
                    'ap',
                    'mapeh',
                    'esp',
                    'tle',
                    'shs_core',
                    'shs_specialized',
                ]),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['account_name'],
                'email' => $validated['account_email'],
                'password' => Hash::make($validated['account_password']),
            ]);

            $user->assignRole($validated['account_role']);

            $user->teacher()->create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['account_email'],
                'faculty' => $validated['faculty'],
            ]);
        });

        return redirect()
            ->route('admin.accounts')
            ->with('success', 'Account created successfully!');
    }

    public function storeStudentWithAccount(Request $request)
    {
        $validated = $request->validate([
            'account_name'     => 'required|string|max:255',
            'account_email'    => 'required|email|unique:users,email',
            'account_password' => 'required|min:6|confirmed',
            'account_role'     => ['required', Rule::in(['student'])],
            'first_name'       => ['required', 'string', 'max:255'],
            'last_name'        => ['required', 'string', 'max:255'],
            'guardian_email'   => ['nullable', 'email', 'max:255'],
            'section_id'       => ['nullable', 'integer', 'exists:section_models,id'],
            'grade'            => [
                'required',
                Rule::in([
                    '1',
                    '2',
                    '3',
                    '4',
                    '5',
                    '6',
                    '7',
                    '8',
                    '9',
                    '10',
                    '11',
                    '12',
                ]),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['account_name'],
                'email' => $validated['account_email'],
                'password' => Hash::make($validated['account_password']),
            ]);

            $user->assignRole($validated['account_role']);

            $student = $user->student()->create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'guardian_email' => $validated['guardian_email'] ?? null,
                'email' => $validated['account_email'],
                'grade' => $validated['grade'],
                'section_id' => $validated['section_id'] ?? null,
            ]);

            $this->studentEnrollmentSyncService->syncCurrentEnrollment($student);
        });

        return redirect()
            ->route('admin.accounts')
            ->with('success', 'Account created successfully!');
    }

    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    // Show the update form
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('pages.admin.dashboard.updateaccount', [
            'title' => 'My Campus LMS | Update Account',
            'preloader' => false,
            'darkmode'  => true,
            'scrollup'  => true,
            'sidebar'   => true,
            'banner'    => false,
            'user'      => $user,
        ]);
    }



    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Validate inputs
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $id,
            'password'    => 'nullable|min:6|confirmed',
            'status'      => 'required|string',
        ]);

        // Update fields
        $user->name   = $validated['name'];
        $user->email  = $validated['email'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.accounts')
            ->with('success', 'Account updated successfully!');
    }



    /**
     * Remove the specified resource from storage.
     */
   public function destroy(string $id)
{
    $user = User::findOrFail($id);
    $user->delete();

    return response()->json([
        'success' => true,
        'message' => 'Account deleted successfully!',
        'reload' => true
    ]);
}

}
