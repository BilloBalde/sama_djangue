<?php

namespace App\Http\Controllers\Api;

use App\Models\Note;
use App\Models\Student;

use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\BulletinService;
use App\Http\Controllers\Controller;

class BulletinController extends Controller
{
    protected $bulletinService;

    public function __construct(BulletinService $bulletinService)
    {
        $this->bulletinService = $bulletinService;
    }

    /**
     * Générer bulletin PDF pour un élève
     */
    public function generatePdf($studentId, Request $request)
    {

        
        try {
            $term = $request->get('term', 'trimestre_1');
            $year = $request->get('year', date('Y'));

            $student = Student::with(['class', 'tutor'])->findOrFail($studentId);
            
            $bulletinData = $this->bulletinService->prepareBulletinData($student, $term, $year);
            $pdf = $this->bulletinService->generatePdf($bulletinData);

            $fileName = "bulletin_{$student->first_name}_{$student->last_name}_{$term}_{$year}.pdf";
            
            //dd($bulletinData['subjects']);
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

    /**
     * Générer bulletin Word pour un élève
     */
    public function generateWord($studentId, Request $request)
    {
        try {
            $term = $request->get('term', 'trimestre_1');
            $year = $request->get('year', date('Y'));

            $student = Student::with(['class', 'tutor'])->findOrFail($studentId);
            
            $bulletinData = $this->bulletinService->prepareBulletinData($student, $term, $year);
            $wordFile = $this->bulletinService->generateWord($bulletinData);

            $fileName = "bulletin_{$student->first_name}_{$student->last_name}_{$term}_{$year}.docx";
            //dd($bulletinData['subjects']);
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

    /**
     * Aperçu des données du bulletin (pour vérification avant génération)
     */
    public function previewData($studentId, Request $request)
    {
        try {
            $term = $request->get('term', 'trimestre_1');
            $year = $request->get('year', date('Y'));

            $student = Student::with(['class', 'tutor'])->findOrFail($studentId);
            $bulletinData = $this->bulletinService->prepareBulletinData($student, $term, $year);

            return response()->json([
                'success' => true,
                'data' => $bulletinData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer bulletins pour toute une classe
     */
    public function generateClassBulletins($classId, Request $request)
    {
        try {
            $term = $request->get('term', 'trimestre_1');
            $year = $request->get('year', date('Y'));
            $format = $request->get('format', 'pdf'); // pdf ou word

            $class = \App\Models\ClassModel::with('students')->findOrFail($classId);
            
            if ($class->students->isEmpty()) {
                return response()->json([
                    'error' => 'Aucun élève trouvé dans cette classe'
                ], 404);
            }

            $files = [];
            foreach ($class->students as $student) {
                $bulletinData = $this->bulletinService->prepareBulletinData($student, $term, $year);
                
                if ($format === 'pdf') {
                    $pdf = $this->bulletinService->generatePdf($bulletinData);
                    $fileName = "bulletin_{$student->first_name}_{$student->last_name}_{$term}.pdf";
                    $files[] = [
                        'student_id' => $student->id,
                        'student_name' => $student->first_name . ' ' . $student->last_name,
                        'file_name' => $fileName,
                        'content' => base64_encode($pdf->output())
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulletins générés avec succès',
                'files' => $files,
                'total' => count($files)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération des bulletins de classe',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les termes disponibles pour un élève
     */
    public function getAvailableTerms($studentId)
    {
        try {
            $student = Student::findOrFail($studentId);
            
            $terms = Note::where('student_id', $studentId)
                ->select('term')
                ->distinct()
                ->orderBy('term')
                ->pluck('term');

            return response()->json([
                'success' => true,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'class' => $student->class->name ?? 'Non assigné'
                ],
                'available_terms' => $terms
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des termes',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}