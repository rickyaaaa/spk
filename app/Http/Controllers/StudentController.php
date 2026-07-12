<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        return view('students.index', [
            'students' => Student::query()->orderBy('name')->get(),
            'editingStudent' => null,
        ]);
    }

    public function edit(Student $student): View
    {
        return view('students.index', [
            'students' => Student::query()->orderBy('name')->get(),
            'editingStudent' => $student,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated) {
            $this->releaseSoftDeletedNis($validated['nis']);

            Student::query()->create($validated);
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $this->validated($request, $student);

        DB::transaction(function () use ($student, $validated) {
            $this->releaseSoftDeletedNis($validated['nis']);

            $student->update($validated);
        });

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    public function exportTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            // Add Excel separator declaration
            fwrite($handle, "sep=,\n");

            // Headers
            fputcsv($handle, ['nis', 'nama', 'kelas', 'status']);

            // Example rows to guide the user
            fputcsv($handle, ['2624007', 'Dayu', 'Paket C - XII', 'Aktif']);
            fputcsv($handle, ['2624008', 'Dani', 'Paket C - XII', 'Aktif']);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_siswa.csv"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return back()->with('error', 'Gagal membuka file CSV.');
        }

        // Read first line to detect delimiter and check for Excel sep= line
        $firstLine = fgets($handle);
        if (!$firstLine) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong.');
        }

        $delimiter = ',';
        if (str_starts_with(strtolower(trim($firstLine)), 'sep=')) {
            $parts = explode('=', trim($firstLine));
            if (isset($parts[1]) && strlen($parts[1]) === 1) {
                $delimiter = $parts[1];
            }
            $headerLine = fgets($handle);
        } else {
            $headerLine = $firstLine;
        }

        if (!$headerLine) {
            fclose($handle);
            return back()->with('error', 'File CSV kosong setelah deklarasi separator.');
        }

        // Auto-detect delimiter from header line if not set by sep=
        if (!str_contains($firstLine, 'sep=')) {
            $commas = substr_count($headerLine, ',');
            $semicolons = substr_count($headerLine, ';');
            $delimiter = $semicolons > $commas ? ';' : ',';
        }

        // Parse headers using detected delimiter
        $headers = str_getcsv($headerLine, $delimiter);
        if (! $headers) {
            fclose($handle);
            return back()->with('error', 'Header CSV tidak valid.');
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        $nisIndex = array_search('nis', $headers);
        $nameIndex = array_search('nama', $headers);
        $classIndex = array_search('kelas', $headers);
        $statusIndex = array_search('status', $headers);

        if ($nisIndex === false || $nameIndex === false || $classIndex === false || $statusIndex === false) {
            fclose($handle);
            return back()->with('error', 'Header CSV harus memiliki kolom: nis, nama, kelas, status.');
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $errors = [];
        $rowNumber = 1;

        $allowedClasses = ['Paket B - VIII', 'Paket B - IX', 'Paket C - XI', 'Paket C - XII'];
        $allowedStatuses = ['Aktif', 'Evaluasi'];

        DB::transaction(function () use ($handle, $nisIndex, $nameIndex, $classIndex, $statusIndex, $delimiter, $allowedClasses, $allowedStatuses, &$insertedCount, &$updatedCount, &$errors, &$rowNumber) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if (empty(array_filter($row))) {
                    continue;
                }

                $nis = trim((string) ($row[$nisIndex] ?? ''));
                $name = trim((string) ($row[$nameIndex] ?? ''));
                $className = trim((string) ($row[$classIndex] ?? ''));
                $status = trim((string) ($row[$statusIndex] ?? ''));

                if (empty($nis)) {
                    $errors[] = "Baris {$rowNumber}: NIS kosong, baris dilewati.";
                    continue;
                }

                if (empty($name)) {
                    $errors[] = "Baris {$rowNumber}: Nama kosong, baris dilewati.";
                    continue;
                }

                // Check class_name validity
                if (!in_array($className, $allowedClasses)) {
                    $classNameNormalized = null;
                    foreach ($allowedClasses as $ac) {
                        if (strcasecmp($ac, $className) === 0) {
                            $classNameNormalized = $ac;
                            break;
                        }
                    }
                    if ($classNameNormalized) {
                        $className = $classNameNormalized;
                    } else {
                        $errors[] = "Baris {$rowNumber}: Kelas '{$className}' tidak valid (pilih salah satu dari: " . implode(', ', $allowedClasses) . ").";
                        continue;
                    }
                }

                // Check status validity
                if (!in_array($status, $allowedStatuses)) {
                    $statusNormalized = null;
                    foreach ($allowedStatuses as $as) {
                        if (strcasecmp($as, $status) === 0) {
                            $statusNormalized = $as;
                            break;
                        }
                    }
                    if ($statusNormalized) {
                        $status = $statusNormalized;
                    } else {
                        $status = 'Aktif';
                    }
                }

                // Check if student exists to determine update vs insert
                $existing = Student::query()->where('nis', $nis)->first();
                $this->releaseSoftDeletedNis($nis);

                Student::query()->updateOrCreate(
                    ['nis' => $nis],
                    [
                        'name' => $name,
                        'class_name' => $className,
                        'status' => $status,
                    ]
                );

                if ($existing) {
                    $updatedCount++;
                } else {
                    $insertedCount++;
                }
            }
        });

        fclose($handle);

        if (! empty($errors)) {
            $errorSummary = implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $errorSummary .= ' dan beberapa baris lainnya bermasalah.';
            }

            if ($insertedCount > 0 || $updatedCount > 0) {
                return redirect()->route('students.index')->with('error', "Import berhasil sebagian (ditambah: {$insertedCount}, diupdate: {$updatedCount}), namun ada beberapa masalah: " . $errorSummary);
            }

            return redirect()->route('students.index')->with('error', 'Gagal mengimpor data siswa: ' . $errorSummary);
        }

        return redirect()->route('students.index')->with('success', "Berhasil mengimpor data siswa (ditambah: {$insertedCount}, diupdate: {$updatedCount}).");
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->whereNull('deleted_at')->ignore($student)],
            'name' => ['required', 'string', 'max:120'],
            'class_name' => ['required', 'string', Rule::in(['Paket B - VIII', 'Paket B - IX', 'Paket C - XI', 'Paket C - XII'])],
            'status' => ['required', 'string', Rule::in(['Aktif', 'Evaluasi'])],
        ]);
    }

    private function releaseSoftDeletedNis(string $nis): void
    {
        Student::query()
            ->onlyTrashed()
            ->where('nis', $nis)
            ->get()
            ->each(function (Student $student) use ($nis) {
                $suffix = '__deleted_'.$student->id;
                $baseLength = max(1, 30 - strlen($suffix));

                $student->forceFill([
                    'nis' => substr($nis, 0, $baseLength).$suffix,
                ])->save();
            });
    }
}
