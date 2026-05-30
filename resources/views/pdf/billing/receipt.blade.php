<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $receipt->receipt_number }}</title>
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

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
        }

        td:last-child {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .header,
        .grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            width: 44%;
        }

        .amount {
            font-size: 24px;
            font-weight: bold;
        }

        .badge {
            background: #ecfdf5;
            border-radius: 999px;
            color: #047857;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 10px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $payment = $receipt->payment;
        $invoice = $payment?->invoice;
        $booking = $invoice?->booking;
    @endphp

    <div class="header">
        <div>
            <p class="muted">Hotel Management</p>
            <h1>Receipt</h1>
            <p class="badge">Paid</p>
        </div>
        <div>
            <p><strong>Receipt Number:</strong> {{ $receipt->receipt_number }}</p>
            <p><strong>Issued At:</strong> {{ $receipt->issued_at?->toDateTimeString() }}</p>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Booking Details</h2>
            <p><strong>Booking Number:</strong> {{ $booking?->booking_number }}</p>
            <p><strong>Invoice Number:</strong> {{ $invoice?->invoice_number }}</p>
            <p><strong>Guest Name:</strong> {{ $booking?->guest_name }}</p>
            <p><strong>Room:</strong> {{ $booking?->room }}</p>
        </div>

        <div class="card">
            <h2>Payment Details</h2>
            <p class="amount">{{ $payment?->amount }}</p>
            <p><strong>Type:</strong> {{ $payment?->type }}</p>
            <p><strong>Method:</strong> {{ $payment?->method }}</p>
            <p><strong>Reference:</strong> {{ $payment?->reference ?? 'N/A' }}</p>
            <p><strong>Paid At:</strong> {{ $payment?->paid_at?->toDateTimeString() }}</p>
        </div>
    </div>

    <h2>Summary</h2>
    <table>
        <tr>
            <td>Total Invoice Amount</td>
            <td>{{ $invoice?->total_amount }}</td>
        </tr>
        <tr>
            <td>Amount Paid On This Receipt</td>
            <td>{{ $payment?->amount }}</td>
        </tr>
        <tr>
            <td>Remaining Due</td>
            <td>{{ $invoice?->amount_due }}</td>
        </tr>
    </table>
</body>
</html>
