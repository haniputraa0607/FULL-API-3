<!DOCTYPE html>
<html>
<body>

<table>
    <tr>
        <td width="30"><b>Total Transaction</b></td>
        <td>: {{number_format($summary_fee['total_trx'])}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Gross Sales</b></td>
        <td>: {{number_format(((float)$summary_fee['total_sub_total']+abs((float)$summary_fee['total_discount_bundling'])),2,",","")}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Discount + Promo</b></td>
        <td>: {{number_format((abs($summary_fee['total_discount'])+$summary_fee['total_subscription']+abs($summary_fee['total_discount_delivery'])+abs((float)$summary_fee['total_discount_bundling'])),2,",","")}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Delivery</b></td>
        <td>: {{(float)$summary_fee['total_delivery']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Sub Total (Gross Sales + delivery - discount - promo)</b></td>
        <td>: {{number_format(((float)$summary_fee['total_gross_sales']-$summary_fee['total_subscription']),2,",","")}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Fee Item</b></td>
        <td>: {{(float)$summary_fee['total_fee_item']}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total MDR PG</b></td>
        <td>: {{number_format((float)$summary_fee['total_fee_pg'],2,",","")}}</td>
    </tr>
    @if(isset($show_another_income) && $show_another_income == 1)
    <tr>
        <td width="30"><b>Total Income Promo</b></td>
        <td>: {{number_format((float)$summary_fee['total_income_promo'],2,",","")}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Income Subscription</b></td>
        <td>: {{number_format((float)$summary_fee['total_income_subscription'],2,",","")}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Income Bundling Product</b></td>
        <td>: {{number_format((float)$summary_fee['total_income_bundling_product']??0,2,",","")}}</td>
    </tr>
    <tr>
        <td width="30"><b>Total Income Promo Cashback</b></td>
        <td>: {{number_format((float)$summary_fee['total_income_promo_payment_gateway']??0,2,",","")}}</td>
    </tr>
    @endif
    <tr>
        <td width="30"><b>Total Income Outlet</b></td>
        <td>: {{number_format((float)$summary_fee['total_income_outlet'],2,",","")}}</td>
    </tr>
</table>
<br>

@if(!empty($summary_product))
<table style="border: 1px solid black">
    <thead>
    <tr>
        <th style="background-color: #dcdcdc;"> Name </th>
        <th style="background-color: #dcdcdc;" width="20"> Variants </th>
        <th style="background-color: #dcdcdc;" width="20"> Type </th>
        <th style="background-color: #dcdcdc;" width="20"> Total Sold Out </th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($summary_product))
        @foreach($summary_product as $val)
            <tr>
                <td style="text-align: left">{{$val['name']}}</td>
                <td style="text-align: left">{{$val['variants']??''}}</td>
                <td style="text-align: left">{{ucfirst($val['type'])}}</td>
                <td style="text-align: left">{{$val['total_qty']}}</td>
            </tr>
        @endforeach
    @else
        <tr><td colspan="10" style="text-align: center">Data Not Available</td></tr>
    @endif
    </tbody>
</table>
@endif
</body>
</html>

