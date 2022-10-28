<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>
<?php
use App\Lib\MyHelper;
?>
<body style="background:#ffffff;max-width: 480px; margin: auto">
<br>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <b style="font-size: 14px">Info Transaction</b>
                <hr>
            </div>
        </div>
        <table style="font-size: 12px">
            <tr>
                <td>No Order</td>
                <td><b>: {{$detail['transaction_receipt_number']}}</b></td>
            </tr>
            <tr>
                <td>Tanggal & Waktu</td>
                <td><b>: {{$detail['transaction_date']}}</b></td>
            </tr>
        </table>
    </div>
</div>
<br>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <b style="font-size: 14px">Delivery Info</b>
                <hr>
            </div>
        </div>
        <table style="font-size: 12px">
            <tr>
                <td>Shipment ID</td>
                <td><b>: {{$detail['transaction_receipt_number']}}</b></td>
            </tr>
            <tr>
                <td>Name</td>
                <td><b>: {{$detail['address']['destination_name']}}</b></td>
            </tr>
            <tr>
                <td>Phone</td>
                <td><b>: {{$detail['address']['destination_phone']}}</b></td>
            </tr>
            <tr>
                <td>Address</td>
                <td><b>: {{$detail['address']['destination_address']}} ({{$detail['address']['destination_city']}} - {{$detail['address']['destination_province']}}</b></td>
            </tr>
            <tr>
                <td>Notes</td>
                <td><b>: {{(empty($detail['address']['destination_description']) ? '-' : $detail['address']['destination_description'])}}</b></td>
            </tr>
        </table>
    </div>
</div>
<br>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <b style="font-size: 14px">Detail Item</b>
                <hr>
            </div>
        </div>
        <table style="width: 100%;font-size: 12px">
            @foreach($detail['transaction_products'] as $product)
                <tr>
                    <td width="40%">
                        {{$product['product_name']}}<br>
                        @if(!empty($product['variants']))
                            {{($product['variants']??"")}}<br>
                        @endif
                        {{($product['note']??"")}}
                    </td>
                    <td width="25%" style="text-align: center">{{$product['product_qty']}} x {{$product['product_base_price']}}</td>
                    <td width="25%" style="text-align: right">
                        <b>{{$product['product_total_price']}}</b>
                        @if(!empty($product['discount_all']))
                            <b style="color: red"><br>- {{number_format($product['discount_all'],0,",",".")}}</b>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <b style="font-size: 14px">PAYMENT</b>
                <hr>
            </div>
        </div>
        <table style="width: 100%;font-size: 12px">
            @foreach($detail['payment_detail'] as $pd)
                <tr>
                    @if(strpos($pd['text'],"Discount") === false && strpos(strtolower($pd['text']),"point") === false)
                        <td width="40%">{{$pd['text']}}</td>
                        <td width="25%">{{$pd['value']}}</td>
                    @else
                        <td width="40%" style="color: red">{{$pd['text']}}</td>
                        <td width="25%" style="color: red">{{$pd['value']}}</td>
                    @endif
                </tr>
            @endforeach
                <tr>
                    <td width="40%"><h5><b>GRAND TOTAL</b></h5></td>
                    <td width="25%"><h5><b>{{$detail['transaction_grandtotal']}}</b></h5></td>
                </tr>
                <tr>
                    <td width="40%"><h5><b>PAYMENT USE</b></h5></td>
                    <td width="25%"><h5><b>@if(!empty($detail['payment'])) {{$detail['payment']}} @else - @endif</b></h5></td>
                </tr>
        </table>
    </div>
</div>
</body>
</html>