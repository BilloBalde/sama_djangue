<?php

namespace App\Http\Controllers\Api;

use App\Models\Note;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class NoteController extends Controller
{


    public function index($student_id)
    {
        $notes = Note::where('student_id', $student_id)->with('subject')->get();
        return response()->json($notes, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'value' => 'required|numeric|between:0,20',
            'term' => 'required|string', // <-- Ajoute cette ligne
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $note = Note::create($request->all());
        return response()->json($note, 201);
    }


    public function show($id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        return response()->json($note);
    }
    
    // Mise à jour d'une note
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
            'term' => 'sometimes|string', // <-- Ajoute cette ligne
            'date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $note->update($request->all());
        return response()->json($note);
    }

    // Suppression d'une note
    public function destroy($id)
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json(['message' => 'Note non trouvée'], 404);
        }

        $note->delete();
        return response()->json(['message' => 'Note supprimée avec succès']);
    }

    // Calcul de la moyenne d’un élève
    public function calculateAverage($student_id)
    {
        $notes = Note::where('student_id', $student_id)->get();

        if ($notes->isEmpty()) {
            return response()->json(['message' => 'Aucune note trouvée pour cet élève.'], 404);
        }

        $average = $notes->avg('value');
        return response()->json([
            'student_id' => $student_id,
            'average' => round($average, 2)
        ]);
    }
    public function getNotesBySubjectAndTerm($student_id, $subject_id, $term)
    {
        $notes = Note::where('student_id', $student_id)
            ->where('subject_id', $subject_id)
            ->where('term', $term)
            ->get();

        return response()->json($notes);
    }

    public function averageByStudentSubjectTerm($student_id, $subject_id, $term)
    {
        $average = Note::where('student_id', $student_id)
            ->where('subject_id', $subject_id)
            ->where('term', $term)
            ->avg('value');

        return response()->json([
            'student_id' => $student_id,
            'subject_id' => $subject_id,
            'term' => $term,
            'average' => round($average, 2)
        ]);
    }

    public function generateBulletin($student_id, $term)
    {
        $subjects = Subject::all();
        $bulletin = [];

        foreach ($subjects as $subject) {
            $average = Note::where('student_id', $student_id)
                ->where('subject_id', $subject->id)
                ->where('term', $term)
                ->avg('value');

            $bulletin[] = [
                'subject' => $subject->name,
                'average' => round($average, 2)
            ];
        }

        return response()->json([
            'student_id' => $student_id,
            'term' => $term,
            'bulletin' => $bulletin
        ]);
    }


    public function getBulletin($student_id, $trimestre)
    {
        $student = Student::with('classe')->findOrFail($student_id);

        $notes = Note::with('subject')
            ->where('student_id', $student_id)
            ->where('term', $trimestre)
            ->get();

        if ($notes->isEmpty()) {
            return response()->json([
                'message' => 'Aucune note trouvée pour ce trimestre.'
            ], 404);
        }

        $formattedNotes = $notes->map(function ($note) {
            return [
                'matiere' => $note->subject->name,
                'note' => floatval($note->value)
            ];
        });

        $average = round($formattedNotes->avg('note'), 2);

        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'class' => $student->classe->name ?? 'Non attribuée'
            ],
            'term' => $trimestre,
            'notes' => $formattedNotes,
            'moyenne_generale' => $average
        ]);
    }

    //pour imprime le pdf et word

    
    public function exportBulletin($studentId, $trimestre, $format = 'pdf')
    {
        $student = Student::with('classe')->findOrFail($studentId);

        $notes = Note::with('subject')
            ->where('student_id', $studentId)
            ->where('term', $trimestre)
            ->get()
            ->groupBy('subject.name');

        // Exemple de moyenne par matière
        $formatted = [];
        foreach ($notes as $subject => $noteList) {
            $moyenne = round($noteList->avg('value'), 2);
            $formatted[] = [
                'matiere' => $subject,
                'notes' => $noteList->pluck('value')->toArray(),
                'moyenne' => $moyenne
            ];
        }

        if ($format === 'pdf') {
            $pdf = PDF::loadView('bulletin.pdf', [
                'student' => $student,
                'trimestre' => $trimestre,
                'notes' => $formatted
            ]);

            return $pdf->download("bulletin_{$student->id}_{$trimestre}.pdf");
        }

        if ($format === 'word') {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            $section->addText("Bulletin scolaire - $trimestre");
            $section->addText("Élève : {$student->first_name} {$student->last_name}");
            $section->addText("Classe : {$student->classe->nom}");

            foreach ($formatted as $item) {
                $section->addText("Matière : {$item['matiere']}");
                $section->addText("Notes : " . implode(', ', $item['notes']));
                $section->addText("Moyenne : {$item['moyenne']}");
                $section->addText('');
            }

            $fileName = "bulletin_{$student->id}_{$trimestre}.docx";
            $tempPath = storage_path("app/public/$fileName");
            IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

            return response()->download($tempPath)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'Format invalide'], 400);
    }

}
