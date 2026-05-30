<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            margin: 40px;
        }

        h1 {
            font-size: 28px;
            margin: 0;
        }

        h2 {
            font-size: 16px;
            margin: 0 0 10px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
            text-align: left;
        }

        th:last-child,
        td:last-child {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .header,
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
        }

        .summary table {
            width: 280px;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
        }

        .badge {
            background: #eef2ff;
            border-radius: 999px;
            color: #3730a3;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 10px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <p class="muted">Hotel Management</p>
            <h1>Invoice</h1>
            <p class="badge">{{ $invoice->status }}</p>
        </div>
        <div>
            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Issued At:</strong> {{ $invoice->issued_at?->toDateTimeString() }}</p>
            <p><strong>Due At:</strong> {{ $invoice->due_at?->toDateTimeString() ?? 'N/A' }}</p>
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <h2>Booking Details</h2>
            <p><strong>Booking Number:</strong> {{ $invoice->booking?->booking_number }}</p>
            <p><strong>Guest Name:</strong> {{ $invoice->booking?->guest_name }}</p>
            <p><strong>Guest Email:</strong> {{ $invoice->booking?->guest_email }}</p>
            <p><strong>Room:</strong> {{ $invoice->booking?->room }}</p>
            <p>
                <strong>Stay:</strong>
                {{ $invoice->booking?->check_in?->toDateString() }}
                to
                {{ $invoice->booking?->check_out?->toDateString() }}
            </p>
        </div>

        <div class="card">
            <h2>Amount Summary</h2>
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td>{{ $invoice->subtotal }}</td>
                </tr>
                <tr>
                    <td>Discount</td>
                    <td>{{ $invoice->discount }}</td>
                </tr>
                <tr>
                    <td>Total Amount</td>
                    <td>{{ $invoice->total_amount }}</td>
                </tr>
                <tr>
                    <td>Amount Paid</td>
                    <td>{{ $invoice->amount_paid }}</td>
                </tr>
                <tr class="total">
                    <td>Amount Due</td>
                    <td>{{ $invoice->amount_due }}</td>
                </tr>
            </table>
        </div>
    </div>

    <h2>Payments</h2>
    <table>
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Type</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Paid At</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->receipt?->receipt_number ?? 'N/A' }}</td>
                    <td>{{ $payment->type }}</td>
                    <td>{{ $payment->method }}</td>
                    <td>{{ $payment->reference ?? 'N/A' }}</td>
                    <td>{{ $payment->paid_at?->toDateTimeString() }}</td>
                    <td>{{ $payment->amount }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No payments recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
