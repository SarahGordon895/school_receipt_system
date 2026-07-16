<!DOCTYPE html>
<html>
<body style="margin: 0; background: #f4f6f8; font-family: Arial, sans-serif; color: #222;">
    <div style="max-width: 620px; margin: 24px auto; background: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
        <div style="background: #173b64; color: #fff; padding: 22px 28px;">
            <h1 style="margin: 0; font-size: 22px;">Welcome to {{ $schoolName }}</h1>
        </div>

        <div style="padding: 28px;">
            <p>Dear {{ $parent->name }},</p>

            <p>
                Your parent/guardian account has been registered in the school system.
                You have also been linked to the following student:
            </p>

            <table style="width: 100%; border-collapse: collapse; margin: 18px 0;">
                <tr>
                    <td style="padding: 9px; border: 1px solid #ddd; background: #f7f7f7;"><strong>Student</strong></td>
                    <td style="padding: 9px; border: 1px solid #ddd;">{{ $student->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 9px; border: 1px solid #ddd; background: #f7f7f7;"><strong>Admission number</strong></td>
                    <td style="padding: 9px; border: 1px solid #ddd;">{{ $student->admission_no ?: 'Not assigned' }}</td>
                </tr>
                <tr>
                    <td style="padding: 9px; border: 1px solid #ddd; background: #f7f7f7;"><strong>Class</strong></td>
                    <td style="padding: 9px; border: 1px solid #ddd;">{{ $student->class_name ?: 'Not assigned' }}</td>
                </tr>
            </table>

            <h2 style="font-size: 18px; margin-top: 24px;">Parent portal login details</h2>

            <div style="padding: 16px; background: #f4f7fb; border-left: 4px solid #173b64;">
                <p style="margin: 0 0 8px;"><strong>Login phone number:</strong> {{ $parent->phone }}</p>
                <p style="margin: 0;"><strong>Temporary password:</strong> {{ $temporaryPassword }}</p>
            </div>

            <p style="margin: 24px 0;">
                <a href="{{ $loginUrl }}" style="display: inline-block; padding: 11px 20px; color: #fff; background: #173b64; text-decoration: none; border-radius: 5px;">
                    Log in to the parent portal
                </a>
            </p>

            <p>
                After logging in, you can view the student's fee balance, receipts, payment history,
                notifications, and submit bank payment receipts.
            </p>

            <p style="padding: 12px; background: #fff3cd; border: 1px solid #ffe69c; border-radius: 5px;">
                <strong>Security notice:</strong> This password was created by the school administrator.
                Please log in and change it immediately from
                <a href="{{ $profileUrl }}">your profile</a>. Do not share your password with anyone.
            </p>

            <p>
                If the phone number or student details above are incorrect, please contact the school administration.
            </p>

            <p>Regards,<br><strong>{{ $schoolName }} Administration</strong></p>
        </div>
    </div>
</body>
</html>
