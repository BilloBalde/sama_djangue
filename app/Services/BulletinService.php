<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Note;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SchoolYear;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\JcTable;

class BulletinService
{
    public function prepareBulletinData(Student $student, string $term, string $school_year_id)
    {
        $schoolYear = SchoolYear::find($school_year_id);
        if (!$schoolYear) {
            throw new \Exception('Année scolaire non trouvée.');
        }

        $notes = Note::with('subject')
            ->where('student_id', $student->id)
            ->where('term', $term)
            ->where('school_year_id', $school_year_id)
            ->get();

        if ($notes->isEmpty()) {
            throw new \Exception('Aucune note trouvée pour ce trimestre.');
        }

        $subjectAverages = [];
        $totalPoints = 0;
        $totalCoefficient = 0;

        foreach ($notes->groupBy('subject_id') as $subjectId => $subjectNotes) {
            $subject = $subjectNotes->first()->subject;
            $coefficient = $subject->coefficient ?? 1;
            $average = round($subjectNotes->avg('value'), 2);
            
            $subjectAverages[] = [
                'subject_name' => $subject->name,
                'coefficient' => $coefficient,
                'notes' => $subjectNotes->pluck('value')->toArray(),
                'average' => $average,
                'points' => round($average * $coefficient, 2)
            ];

            $totalPoints += $average * $coefficient;
            $totalCoefficient += $coefficient;
        }

        $generalAverage = $totalCoefficient > 0 ? round($totalPoints / $totalCoefficient, 2) : 0;
        $classRank = $this->calculateClassRank($student->class_id, $term, $school_year_id, $generalAverage);
        $appreciation = $this->getAppreciation($generalAverage);

        $schoolInfo = [
            'name' => config('app.school_name', 'École SAMA DJANGUE'),
            'address' => config('app.school_address', 'Dakar, Sénégal'),
            'phone' => config('app.school_phone', '+221 XX XXX XX XX'),
            'email' => config('app.school_email', 'contact@samadjangue.sn'),
            'logo_path' => file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null
        ];

        return [
            'school' => $schoolInfo,
            'student' => [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'full_name' => $student->first_name . ' ' . $student->last_name,
                'birth_date' => $student->birth_date ? $student->birth_date->format('d/m/Y') : 'Non renseigné',
                'class_name' => $student->class->name ?? 'Non assigné'
            ],
            'tutor' => [
                'name' => $student->tutor ? $student->tutor->name : 'Non renseigné',
                'email' => $student->tutor ? $student->tutor->email : ''
            ],
            'period' => [
                'term' => $term,
                'year' => $schoolYear->name,
                'term_label' => $this->getTermLabel($term)
            ],
            'subjects' => $subjectAverages,
            'statistics' => [
                'general_average' => $generalAverage,
                'total_subjects' => count($subjectAverages),
                'class_rank' => $classRank,
                'total_students' => $this->getTotalStudentsInClass($student->class_id),
                'appreciation' => $appreciation
            ],
            'generated_at' => now()->format('d/m/Y à H:i')
        ];
    }

    public function generatePdf($bulletinData)
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $html = $this->generateHtmlTemplate($bulletinData);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf;
    }

    public function generateWord($bulletinData)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(2),
            'marginRight' => Converter::cmToTwip(2)
        ]);

        $phpWord->addFontStyle('titleStyle', ['size' => 16, 'bold' => true, 'color' => '2E74B5']);
        $phpWord->addFontStyle('headerStyle', ['size' => 12, 'bold' => true]);
        $phpWord->addFontStyle('normalStyle', ['size' => 10]);
        $phpWord->addTableStyle('tableStyle', [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 50,
            'alignment' => JcTable::CENTER
        ]);

        $headerTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $headerRow = $headerTable->addRow();
        $logoCell = $headerRow->addCell(3000);
        if ($bulletinData['school']['logo_path']) {
            $logoCell->addImage($bulletinData['school']['logo_path'], [
                'width' => 80,
                'height' => 80,
                'alignment' => Jc::CENTER
            ]);
        }
        
        $schoolCell = $headerRow->addCell(6000);
        $schoolCell->addText($bulletinData['school']['name'], 'titleStyle', ['alignment' => 'center']);
        $schoolCell->addText($bulletinData['school']['address'], 'normalStyle', ['alignment' => 'center']);
        $schoolCell->addText($bulletinData['school']['phone'], 'normalStyle', ['alignment' => 'center']);
        
        $section->addTextBreak(2);
        $section->addText(
            'BULLETIN DE NOTES - ' . strtoupper($bulletinData['period']['term_label']),
            'titleStyle',
            ['alignment' => 'center']
        );
        $section->addTextBreak(1);

        $studentTable = $section->addTable('tableStyle');
        $studentTable->addRow();
        $studentTable->addCell(2000)->addText('Élève :', 'headerStyle');
        $studentTable->addCell(4000)->addText($bulletinData['student']['full_name'], 'normalStyle');
        
        $studentTable->addRow();
        $studentTable->addCell(2000)->addText('Classe :', 'headerStyle');
        $studentTable->addCell(4000)->addText($bulletinData['student']['class_name'], 'normalStyle');
        
        $studentTable->addRow();
        $studentTable->addCell(2000)->addText('Année :', 'headerStyle');
        $studentTable->addCell(4000)->addText($bulletinData['period']['year'], 'normalStyle');

        $section->addTextBreak(1);

        $notesTable = $section->addTable('tableStyle');
        $headerRow = $notesTable->addRow(400);
        $headerRow->addCell(3000)->addText('MATIÈRES', 'headerStyle');
        $headerRow->addCell(2000)->addText('COEFFICIENT', 'headerStyle');
        $headerRow->addCell(2000)->addText('MOYENNE', 'headerStyle');
        $headerRow->addCell(2000)->addText('POINTS', 'headerStyle');

        foreach ($bulletinData['subjects'] as $subject) {
            $row = $notesTable->addRow();
            $row->addCell(3000)->addText($subject['subject_name'], 'normalStyle');
            $row->addCell(2000)->addText($subject['coefficient'], 'normalStyle');
            $row->addCell(2000)->addText($subject['average'] . '/20', 'normalStyle');
            $row->addCell(2000)->addText($subject['points'], 'normalStyle');
        }

        $totalRow = $notesTable->addRow();
        $totalRow->addCell(3000)->addText('MOYENNE GÉNÉRALE', 'headerStyle');
        $totalRow->addCell(2000)->addText('', 'normalStyle');
        $totalRow->addCell(2000)->addText($bulletinData['statistics']['general_average'] . '/20', 'headerStyle');
        $totalRow->addCell(2000)->addText('', 'normalStyle');

        $section->addTextBreak(1);

        $statsTable = $section->addTable('tableStyle');
        $statsTable->addRow();
        $statsTable->addCell(3000)->addText('Rang dans la classe :', 'headerStyle');
        $statsTable->addCell(3000)->addText($bulletinData['statistics']['class_rank'] . '/' . $bulletinData['statistics']['total_students'], 'normalStyle');

        $statsTable->addRow();
        $statsTable->addCell(3000)->addText('Appréciation :', 'headerStyle');
        $statsTable->addCell(3000)->addText($bulletinData['statistics']['appreciation'], 'normalStyle');

        $section->addTextBreak(1);
        $section->addText('Généré le ' . $bulletinData['generated_at'], 'normalStyle', ['alignment' => 'right']);

        $tempFile = tempnam(sys_get_temp_dir(), 'bulletin_') . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return $tempFile;
    }

    private function generateHtmlTemplate($data)
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Bulletin de Notes</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #2E74B5; padding-bottom: 20px; margin-bottom: 30px; }
                .school-name { font-size: 24px; font-weight: bold; color: #2E74B5; }
                .school-info { font-size: 12px; color: #666; margin-top: 10px; }
                .title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; background-color: #f0f0f0; padding: 10px; }
                .student-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .info-row { display: flex; justify-content: space-between; margin: 5px 0; }
                .label { font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
                th { background-color: #2E74B5; color: white; font-weight: bold; }
                .total-row { background-color: #f0f0f0; font-weight: bold; }
                .stats { margin-top: 20px; }
                .appreciation { background-color: #e8f4fd; padding: 15px; border-left: 4px solid #2E74B5; margin: 20px 0; }
                .footer { text-align: right; margin-top: 30px; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='school-name'>{$data['school']['name']}</div>
                <div class='school-info'>
                    {$data['school']['address']}<br>
                    Tél: {$data['school']['phone']} | Email: {$data['school']['email']}
                </div>
            </div>
            
            <div class='title'>BULLETIN DE NOTES - " . strtoupper($data['period']['term_label']) . "</div>
            
            <div class='student-info'>
                <div class='info-row'>
                    <span><span class='label'>Élève:</span> {$data['student']['full_name']}</span>
                    <span><span class='label'>Classe:</span> {$data['student']['class_name']}</span>
                </div>
                <div class='info-row'>
                    <span><span class='label'>Année scolaire:</span> {$data['period']['year']}</span>
                    <span><span class='label'>Date de naissance:</span> {$data['student']['birth_date']}</span>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>MATIÈRES</th>
                        <th>COEFFICIENT</th>
                        <th>MOYENNE</th>
                        <th>POINTS</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($data['subjects'] as $subject) {
            $html .= "
                <tr>
                    <td style='text-align: left;'>{$subject['subject_name']}</td>
                    <td>{$subject['coefficient']}</td>
                    <td>{$subject['average']}/20</td>
                    <td>{$subject['points']}</td>
                </tr>";
        }

        $html .= "
                <tr class='total-row'>
                    <td style='text-align: left;'>MOYENNE GÉNÉRALE</td>
                    <td></td>
                    <td>{$data['statistics']['general_average']}/20</td>
                    <td></td>
                </tr>
                </tbody>
            </table>
            
            <div class='stats'>
                <div class='info-row'>
                    <span><span class='label'>Rang dans la classe:</span> {$data['statistics']['class_rank']}/{$data['statistics']['total_students']}</span>
                    <span><span class='label'>Total matières:</span> {$data['statistics']['total_subjects']}</span>
                </div>
            </div>
            
            <div class='appreciation'>
                <strong>Appréciation:</strong> {$data['statistics']['appreciation']}
            </div>
            
            <div class='footer'>
                Généré le {$data['generated_at']}
            </div>
        </body>
        </html>";

        return $html;
    }

    private function calculateClassRank($classId, $term, $school_year_id, $studentAverage)
    {
        $students = Student::where('class_id', $classId)->get();
        $averages = [];

        foreach ($students as $student) {
            $notes = Note::where('student_id', $student->id)
                ->where('term', $term)
                ->where('school_year_id', $school_year_id)
                ->with('subject')
                ->get();
            
            if ($notes->isNotEmpty()) {
                $totalPoints = $notes->sum(function ($note) {
                    return $note->value * ($note->subject->coefficient ?? 1);
                });
                $totalCoefficient = $notes->sum('subject.coefficient');
                $average = $totalCoefficient > 0 ? round($totalPoints / $totalCoefficient, 2) : 0;
                $averages[] = $average;
            }
        }

        if (empty($averages)) {
            return 1;
        }

        rsort($averages);
        $rank = array_search($studentAverage, $averages);
        return $rank !== false ? $rank + 1 : count($averages);
    }

    private function getTotalStudentsInClass($classId)
    {
        return Student::where('class_id', $classId)->count();
    }

    private function getAppreciation($average)
    {
        if ($average >= 16) {
            return 'Très Bien - Félicitations !';
        } elseif ($average >= 14) {
            return 'Bien - Continuez vos efforts';
        } elseif ($average >= 12) {
            return 'Assez Bien - Peut mieux faire';
        } elseif ($average >= 10) {
            return 'Passable - Travaillez davantage';
        } else {
            return 'Insuffisant - Redoublez d\'efforts';
        }
    }

    private function getTermLabel($term)
    {
        return ucfirst(str_replace('_', ' ', $term));
    }
}