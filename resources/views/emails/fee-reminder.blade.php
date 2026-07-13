<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #222; max-width: 600px;">
    <p>Dear Parent/Guardian,</p>
    <p><strong>{{ $eventLabel ?? 'Fee reminder' }}</strong></p>
    <p>{{ $message }}</p>
    <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">
    <p style="font-size: 13px; color: #555;">
        Student: <strong>{{ $student->name }}</strong><br>
        Class: {{ $student->class_name ?? 'N/A' }}<br>
        Outstanding balance: <strong>Tsh {{ $balance }}</strong><br>
        Due date: <strong>{{ $dueDate }}</strong>
    </p>
</body>
</html>
