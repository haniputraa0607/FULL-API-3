<!DOCTYPE html>
<html>
<body>

<table>
    <tr>
        <td width="30">Total Gross Sales</td>
        <td>:</td>
    </tr>
    <tr>
        <td width="30">Total Sub Total</td>
        <td>: </td>
    </tr>
    <tr>
        <td width="30">Total Delivery</td>
        <td>: </td>
    </tr>
    <tr>
        <td width="30">Total Discount</td>
        <td>: </td>
    </tr>
</table>
<br>

<table style="border: 1px solid black">
    <thead>
    @if(!empty($config) && $config['is_active'] == 1)
        <tr><td style="background-color: #ff9933;" colspan="4">Sub Total = Gross Sales + Discount - Delivery</td></tr>
        <tr><td style="background-color: #ff9933;" colspan="4">Net Sale (Income Outlet) = Sub Total - Fee Item - Fee Payment - Fee Promo - Fee Subscription</td></tr>
        <tr></tr>
    @endif
    <tr>
        <th style="background-color: #dcdcdc;"> Name </th>
        <th style="background-color: #dcdcdc;" width="20"> Total Sold Out </th>
        <th style="background-color: #dcdcdc;" width="20"> Type </th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($summary))
        @foreach($summary as $val)
            <tr>
                <td style="text-align: left">{{$val['name']}}</td>
                <td style="text-align: left">{{$val['total_qty']}}</td>
                <td style="text-align: left">{{$val['type']}}</td>
            </tr>
        @endforeach
    @else
        <tr><td colspan="10" style="text-align: center">Data Not Available</td></tr>
    @endif
    </tbody>
</table>

</body>
</html>

