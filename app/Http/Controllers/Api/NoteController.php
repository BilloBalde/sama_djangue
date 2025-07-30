<?php
namespace App\Http\Controllers\Api;

use App\Models\Note;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel as Classe;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
        $this->middleware('role:teacher')->only(['store', 'update', 'destroy']);
        $this->middleware('role:admin|teacher|parent|student')->only(['index', 'show', 'calculateAverage', 'getNotesBySubjectAndTerm', 'averageByStudentSubjectTerm', 'getBulletin', 'getAvailableTerms']);
        $this->middleware('role:admin|teacher')->only(['generatePdf', 'generateWord', 'previewData', 'generateClassBulletins']);
    }

    /**
     * Vérifie si l'utilisateur a accès aux données d'un étudiant
     */
    private function checkStudentAccess(Student $student)
    {
        if (!auth()->user()->hasRole('admin|teacher') && 
            auth()->user()->id !== $student->user_id && 
            auth()->user()->id !== $student->tutor_id) {
            abort(403, 'Accès non autorisé');
        }
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer|exists:students,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($request->student_id);
        $this->checkStudentAccess($student);

        $notes = Note::where('student_id', $request->student_id)
            ->with('subject:id,name,coefficient', 'teacher:id,name')
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note trouvée pour cet étudiant'], 404);
        }

        return response()->json([
            'notes' => $notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'student_id' => $note->student_id,
                    'subject_id' => $note->subject_id,
                    'subject_name' => $note->subject->name,
                    'value' => floatval($note->value),
                    'coefficient' => $note->subject->coefficient,
                    'term' => $note->term,
                    'date' => $note->date,
                    'comment' => $note->comment,
                    'teacher' => $note->teacher->name,
                    'school_year_id' => $note->school_year_id
                ];
            })
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'value' => 'required|numeric|between:0,20',
            'term' => 'required|string|min:1',
            'date' => 'required|date',
            'comment' => 'nullable|string',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $note = Note::create(array_merge($request->all(), ['teacher_id' => auth()->id()]));
        return response()->json($note->load('subject', 'teacher'), 201);
    }

    public function show($id)
    {
        $note = Note::with('subject:id,name,coefficient', 'teacher:id,name')->find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        $this->checkStudentAccess($note->student);

        return response()->json([
            'id' => $note->id,
            'student_id' => $note->student_id,
            'subject_id' => $note->subject_id,
            'subject_name' => $note->subject->name,
            'value' => floatval($note->value),
            'coefficient' => $note->subject->coefficient,
            'term' => $note->term,
            'date' => $note->date,
            'comment' => $note->comment,
            'teacher' => $note->teacher->name,
            'school_year_id' => $note->school_year_id
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'sometimes|exists:students,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'value' => 'sometimes|numeric|between:0,20',
            'term' => 'sometimes|string|min:1',
            'date' => 'sometimes|date',
            'comment' => 'sometimes|nullable|string',
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $note->update($request->all());
        return response()->json($note->load('subject', 'teacher'), 200);
    }

    public function destroy($id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        $note->delete();
        return response()->json(['message' => 'Note supprimée avec succès'], 200);
    }

    public function calculateAverage($student_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($student_id);
        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        $this->checkStudentAccess($student);

        $notes = Note::where('student_id', $student_id)
            ->where('school_year_id', $request->school_year_id)
            ->with('subject:id,coefficient')
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note trouvée pour cet étudiant'], 404);
        }

        $totalWeighted = $notes->sum(function ($note) {
            return $note->value * ($note->subject->coefficient ?? 1);
        });
        $totalCoefficient = $notes->sum('subject.coefficient');
        $average = $totalCoefficient > 0 ? round($totalWeighted / $totalCoefficient, 2) : 0;

        return response()->json([
            'student_id' => $student_id,
            'school_year_id' => $request->school_year_id,
            'average' => $average
        ], 200);
    }

    public function getNotesBySubjectAndTerm($student_id, $subject_id, $term, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($student_id);
        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        $this->checkStudentAccess($student);

        $notes = Note::where('student_id', $student_id)
            ->where('subject_id', $subject_id)
            ->where('term', $term)
            ->where('school_year_id', $request->school_year_id)
            ->with('subject:id,name,coefficient', 'teacher:id,name')
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note trouvée pour cette matière et ce trimestre'], 404);
        }

        return response()->json($notes->map(function ($note) {
            return [
                'id' => $note->id,
                'student_id' => $note->student_id,
                'subject_id' => $note->subject_id,
                'subject_name' => $note->subject->name,
                'value' => floatval($note->value),
                'coefficient' => $note->subject->coefficient,
                'term' => $note->term,
                'date' => $note->date,
                'comment' => $note->comment,
                'teacher' => $note->teacher->name,
                'school_year_id' => $note->school_year_id
            ];
        }), 200);
    }

    public function averageByStudentSubjectTerm($student_id, $subject_id, $term, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::find($student_id);
        if (!$student) {
            return response()->json(['message' => 'Étudiant non trouvé'], 404);
        }

        $this->checkStudentAccess($student);

        $notes = Note::where('student_id', $student_id)
            ->where('subject_id', $subject_id)
            ->where('term', $term)
            ->where('school_year_id', $request->school_year_id)
            ->with('subject:id,name,coefficient')
            ->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note trouvée pour cette matière et ce trimestre'], 404);
        }

        $average = $notes->avg('value');

        return response()->json([
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'term' => $term,
            'school_year_id' => $request->school_year_id,
            'average' => round($average, 2)
        ], 200);
    }

    public function getBulletin($student_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|min:1',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $student = Student::with(['class', 'tutor'])->findOrFail($student_id);
            $this->checkStudentAccess($student);

            $bulletinData = $this->bulletinService->prepareBulletinData($student, $request->term, $request->school_year_id);
            return response()->json($bulletinData, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération du bulletin',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function generatePdf($studentId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|min:1',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $student = Student::with(['class', 'tutor'])->findOrFail($studentId);
            $this->checkStudentAccess($student);

            $bulletinData = $this->bulletinService->prepareBulletinData($student, $request->term, $request->school_year_id);
            $pdf = $this->bulletinService->generatePdf($bulletinData);

            $fileName = "bulletin_{$student->first_name}_{$student->last_name}_{$request->term}_{$request->school_year_id}.pdf";
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du bulletin PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateWord($studentId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|min:1',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $student = Student::with(['class', 'tutor'])->findOrFail($studentId);
            $this->checkStudentAccess($student);

            $bulletinData = $this->bulletinService->prepareBulletinData($student, $request->term, $request->school_year_id);
            $wordFile = $this->bulletinService->generateWord($bulletinData);

            $fileName = "bulletin_{$student->first_name}_{$student->last_name}_{$request->term}_{$request->school_year_id}.docx";
            return response()->download($wordFile, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du bulletin Word',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function previewData($studentId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|min:1',
            'school_year_id' => 'required|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $student = Student::with(['class', 'tutor'])->findOrFail($studentId);
            $this->checkStudentAccess($student);

            $bulletinData = $this->bulletinService->prepareBulletinData($student, $request->term, $request->school_year_id);
            return response()->json($bulletinData, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateClassBulletins($classId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term' => 'required|string|min:1',
            'school_year_id' => 'required|exists:school_years,id',
            'format' => 'required|in:pdf,word'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $class = Classe::with('students')->findOrFail($classId);
            if ($class->students->isEmpty()) {
                return response()->json(['message' => 'Aucun élève trouvé dans cette classe'], 404);
            }

            $files = [];
            foreach ($class->students as $student) {
                try {
                    $bulletinData = $this->bulletinService->prepareBulletinData($student, $request->term, $request->school_year_id);
                    $fileName = "bulletin_{$student->first_name}_{$student->last_name}_{$request->term}_{$request->school_year_id}";

                    if ($request->format === 'pdf') {
                        $pdf = $this->bulletinService->generatePdf($bulletinData);
                        $fileName .= '.pdf';
                        $content = $pdf->output();
                        $contentType = 'application/pdf';
                    } else {
                        $wordFile = $this->bulletinService->generateWord($bulletinData);
                        $fileName .= '.docx';
                        $content = file_get_contents($wordFile);
                        $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                        @unlink($wordFile); // Supprimer le fichier temporaire
                    }

                    $files[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'file_name' => $fileName,
                        'content' => base64_encode($content)
                    ];
                } catch (\Exception $e) {
                    continue; // Ignorer les étudiants sans notes
                }
            }

            if (empty($files)) {
                return response()->json(['message' => 'Aucun bulletin généré pour cette classe'], 404);
            }

            return response()->json([
                'message' => 'Bulletins générés avec succès',
                'files' => $files,
                'total' => count($files)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération des bulletins de classe',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAvailableTerms($studentId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_year_id' => 'sometimes|exists:school_years,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $student = Student::with('class')->findOrFail($studentId);
            $this->checkStudentAccess($student);

            $query = Note::where('student_id', $studentId);
            if ($request->has('school_year_id')) {
                $query->where('school_year_id', $request->school_year_id);
            }

            $terms = $query->select('term')
                ->distinct()
                ->orderBy('term')
                ->pluck('term');

            if ($terms->isEmpty()) {
                return response()->json(['message' => 'Aucun trimestre trouvé pour cet étudiant'], 404);
            }

            return response()->json([
                'student' => [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'class' => $student->class->name ?? 'Non assigné'
                ],
                'available_terms' => $terms
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des termes',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}