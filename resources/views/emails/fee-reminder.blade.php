<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #222;">
    <p>Dear Parent/Guardian,</p>
    <p>This is a fee reminder for <strong>{{ $student->name }}</strong>.</p>
    <p><strong>Outstanding balance:</strong> Tsh {{ $balance }}</p>
    <p><strong>Due date:</strong> {{ $dueDate }}</p>
    <p>Please make payment on time to avoid delays in school services.</p>
    <p>— School Administration</p>
</body>
</html>
