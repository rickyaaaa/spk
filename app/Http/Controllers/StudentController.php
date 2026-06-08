<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        Student::query()->create($this->validated($request));

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $student->update($this->validated($request, $student));

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    private function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($student)],
            'name' => ['required', 'string', 'max:120'],
            'class_name' => ['required', 'string', 'max:80'],
            'status' => ['required', 'string', 'max:30'],
        ]);
    }
}
