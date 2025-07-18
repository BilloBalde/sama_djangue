<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulletin</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
    </style>
</head>
<body>
    <h2>Bulletin scolaire - {{ $trimestre }}</h2>
    <p><strong>Nom :</strong> {{ $student->first_name }} {{ $student->last_name }}</p>
    <p><strong>Classe :</strong> {{ $student->classe->nom }}</p>

    <table>
        <thead>
            <tr>
                <th>Matière</th>
                <th>Notes</th>
                <th>Moyenne</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notes as $note)
                <tr>
                    <td>{{ $note['matiere'] }}</td>
                    <td>{{ implode(', ', $note['notes']) }}</td>
                    <td>{{ $note['moyenne'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
