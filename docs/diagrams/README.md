# FTRS System Diagrams (Clean Academic Style)

Simple UML diagrams matching standard project documentation format — aligned with the Mbonea FTRS system flow.

## Download for Word

| Diagram | PNG file |
|---------|----------|
| **Use Case** | `png/01-use-case-diagram.png` |
| **Activity — School Admin payment** | `png/02-activity-diagram-fee-lifecycle.png` |
| **Activity — Parent bank upload** | `png/02b-activity-diagram-parent-bank-payment.png` |
| **Class** | `png/03-class-diagram.png` |
| **Sequence — Record payment** | `png/04-sequence-diagram-record-receipt.png` |
| **Sequence — Bank payment** | `png/05-sequence-diagram-bank-payment.png` |
| **Sequence — Fee reminder (1–5)** | `png/06-sequence-diagram-manual-reminder.png` |
| **ER Diagram** | `png/07-er-diagram.png` |

**Zip bundle:** `FTRS-Diagrams-Word-Import.zip`

**Folder:** `/Applications/XAMPP/xamppfiles/htdocs/1/school_receipt_system/docs/diagrams/png/`

## Insert into Word

1. **Insert → Pictures → Picture from File**
2. Choose the `.png` from the `png` folder
3. Resize from corners; use **Wrap Text → Top and Bottom**

## Demo login credentials

| Role | Login | Password |
|------|-------|----------|
| Super Admin | `sarahgeorge7224@gmail.com` | `Super@FTRS2025` |
| School Admin | `admin@mbonea.sc.tz` | `Mbonea@Admin2025` |
| Parent (Mkumbo) | `+255655139724` | `Mkumbo@2025` |
| Parent (Gordon) | `+255755666899` | `Gordon@2025` |
| Parent (Chaula) | `+255718216434` | `Chaula@2025` |
| Other parents | Seeder phones | `Parent@2025` |

Login: `http://127.0.0.1:8088/login`

## What each diagram shows

- **Use Case:** School Admin, Parent, Super Admin and 8 core functions (register, fees, payment, balance, notifications, reports, settings, SMS/email).
- **Activity (Admin):** Login → enter payment → validate → save → update balance → notify parent.
- **Activity (Parent):** Bank payment upload and verification flow.
- **Class:** Parent, Student, FeeStructure, Payment, User, Notification.
- **Sequence (Payment):** Admin → System → Database → Notification Service → Parent.
- **Sequence (Reminder):** Bursar selects 1–5 parents, sends SMS/email, logs messages.
- **ER:** Super Admin, School Admin, Parent, Student, Payment, Notification with PK/FK relationships.
