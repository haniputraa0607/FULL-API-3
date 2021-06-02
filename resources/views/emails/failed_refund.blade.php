<table style="border:1px solid #C0C0C0;border-collapse:collapse;padding:5px; width: 100%">
	<thead>
		<tr>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Transaction Date</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Receipt Number</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Payment Method</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Payment Reference Number</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Grandtotal</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Manual Refund</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Failed Void Reason</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Customer Name</th>
			<th style="border:1px solid #C0C0C0;padding:5px;background:#F0F0F0;">Customer Phone</th>
		</tr>
	</thead>
	<tbody>
		@if($transaction ?? false)
		<tr>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ date('d F Y H:i', strtotime($transaction['transaction_date'])) }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction['transaction_receipt_number'] }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction->payment_method }}{{ $transaction->payment_detail ? "($transaction->payment_detail)" : '' }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction->payment_reference_number }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ \App\Lib\MyHelper::requestNumber($transaction['transaction_grandtotal'], '_CURRENCY') }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ \App\Lib\MyHelper::requestNumber($transaction->manual_refund, '_CURRENCY') }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction->failed_void_reason }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction['name'] }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction['phone'] }}
			</td>
		</tr>
		@endif
		@foreach($transactions ?? [] as $transaction)
		<tr>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ date('d F Y H:i', strtotime($transaction['transaction_date'])) }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction['transaction_receipt_number'] }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction->payment_method }}{{ $transaction->payment_detail ? "($transaction->payment_detail)" : '' }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction->payment_reference_number }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ \App\Lib\MyHelper::requestNumber($transaction['transaction_grandtotal'], '_CURRENCY') }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ \App\Lib\MyHelper::requestNumber($transaction->manual_refund, '_CURRENCY') }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction->failed_void_reason }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction['name'] }}
			</td>
			<td style="border:1px solid #C0C0C0;padding:5px;">
				{{ $transaction['phone'] }}
			</td>
		</tr>
		@endforeach
	</tbody>
</table>