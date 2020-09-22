<!DOCTYPE html>
<html>
<body>

<table style="border: 1px solid black">
    <thead>
    <tr>
        <th style="background-color: #dcdcdc;" width="20"> Recipient Number </th>
        <th style="background-color: #dcdcdc;" width="20"> Transaction Date </th>
        <th style="background-color: #dcdcdc;" width="20"> Outlet </th>
        <th style="background-color: #dcdcdc;" width="20"> Gross Sales </th>
        <th style="background-color: #dcdcdc;" width="20"> Nama Promo </th>
        <th style="background-color: #dcdcdc;" width="20"> Promo </th>
        <th style="background-color: #dcdcdc;" width="20"> Delivery </th>
        <th style="background-color: #dcdcdc;" width="20"> Sub Total </th>
        <th style="background-color: #dcdcdc;" width="20"> Biaya Jasa </th>
        <th style="background-color: #dcdcdc;" width="20"> Payment </th>
        <th style="background-color: #dcdcdc;" width="20"> MDR PG </th>
        <th style="background-color: #dcdcdc;" width="20"> Income Promo </th>
        <th style="background-color: #dcdcdc;" width="20"> Income Subscription </th>
        <th style="background-color: #dcdcdc;" width="20"> Income Outlet </th>
    </tr>
    </thead>
    <tbody>
    @if(!empty($data))
        @foreach($data as $val)
            <?php
            $discount = 0;
            $sub = 0;
            if(!empty($val['transaction_payment_subscription'])) {
                $sub = $val['transaction_payment_subscription']['subscription_nominal'];
                $discount = $sub;
            }else{
                $discount = abs($val['transaction_discount']);
            }
            ?>
            <tr>
                <td style="text-align: left">{{$val['transaction_receipt_number']}}</td>
                <td style="text-align: left">{{date('d M Y H:i', strtotime($val['transaction_date']))}}</td>
                <td style="text-align: left">{{$val['outlet_code']}}-{{$val['outlet_name']}}</td>
                <td style="text-align: left">{{$val['transaction_subtotal']}}</td>
                <td style="text-align: left">
                    <?php
                    $promoName = '';
                    if(count($val['vouchers']) > 0){
                        $promoName = $val['vouchers'][0]['deal']['deals_title'];
                    }elseif (!empty($val['promo_campaign'])){
                        $promoName = $val['promo_campaign']['promo_title'];
                    }elseif(!empty($val['transaction_payment_subscription'])) {
                        if(empty($val['transaction_payment_subscription']['subscription_title'])){
                            $promoName = 'Unknown Promo';
                        }else{
                            $promoName = $val['transaction_payment_subscription']['subscription_title'];
                        }
                    }elseif($val['discount_central'] > 0){
                        $promoName = 'Unknown Promo';
                    }

                    echo $promoName;
                    ?>
                </td>
                <td style="text-align: left">
                    <?php
                    echo (float)$discount;
                    ?>
                </td>
                <td style="text-align: left">{{$val['transaction_shipment_go_send']}}</td>
                <td style="text-align: left">{{$val['transaction_grandtotal']-$sub}}</td>
                <td style="text-align: left">{{(float)$val['fee_item']}}</td>
                <td style="text-align: left">
                    <?php
                    $payment = (!empty($val['payment_type']) ? $val['payment_type'] : '').(!empty($val['payment_method']) ? $val['payment_method'] : '');
                    echo $payment;
                    ?>
                </td>
                <td style="text-align: left">{{(float)$val['payment_charge']}}</td>
                <td style="text-align: left">{{(float)$val['discount_central']}}</td>
                <td style="text-align: left">{{(float)$val['subscription_central']}}</td>
                <td style="text-align: left">{{(float)$val['income_outlet']}}</td>
            </tr>
        @endforeach
    @else
        <tr><td colspan="10" style="text-align: center">Data Not Available</td></tr>
    @endif
    </tbody>
</table>

</body>
</html>

