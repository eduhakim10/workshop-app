<table>
    <thead>
        <tr>
            <th>Customer</th>
            <th>Vehicle</th>
            <th>Offer Number</th>
            <th>Amount Offer</th>
            <th>Amount Offer Revision</th>
            <th>Invoice Handover Date</th>
            <th>Work Order Number</th>
            <th>Work Order Date</th>
            <th>Assign To</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($services as $service)
            <tr>
                <td>{{ $service->customer->name }}</td>
                <td>{{ $service->vehicle->license_plate }}</td>
                <td>{{ $service->offer_number }}</td>
                <td>{{ $service->amount_offer }}</td>
                <td>{{ $service->amount_offer_revision }}</td>
                <td>{{ $service->invoice_handover_date }}</td>
                <td>{{ $service->work_order_number }}</td>
                <td>{{ $service->work_order_date }}</td>
                <td>{{ $service->assign_to }}</td>
                <td>{{ $service->status }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
