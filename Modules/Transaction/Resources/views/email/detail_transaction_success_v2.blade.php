<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="{{ config('url.storage_url_view') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
        @font-face {
            font-family: "WorkSans-Black";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Black.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Bold";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Bold.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-ExtraBold";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-ExtraBold.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-ExtraLight";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-ExtraLight.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Light";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Light.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Medium";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Medium.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Regular";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Regular.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-SemiBold";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-SemiBold.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Thin";
            font-style: normal;
            font-weight: 400;
            src: url('{{ config('url.storage_url_view') }}{{ ('fonts/Work_Sans/WorkSans-Thin.ttf') }}');
        }
        .WorkSans-Black{
            font-family: "WorkSans-Black";
        }
        .WorkSans-Bold{
            font-family: "WorkSans-Bold";
        }
        .WorkSans-ExtraBold{
            font-family: "WorkSans-ExtraBold";
        }
        .WorkSans-ExtraLight{
            font-family: "WorkSans-ExtraLight";
        }
        .WorkSans-Medium{
            font-family: "WorkSans-Medium";
        }
        .WorkSans-Regular{
            font-family: "WorkSans-Regular";
        }
        .WorkSans{
            font-family: "WorkSans-Regular";
        }
        .WorkSans-SemiBold{
            font-family: "WorkSans-SemiBold";
        }
        .WorkSans-Thin{
            font-family: "WorkSans-Thin";
        }

        .kotak {
            margin : 10px;
            padding: 10px;
            /*margin-right: 15px;*/
            -webkit-box-shadow: 0px 1.7px 3.3px 0px #eeeeee;
            -moz-box-shadow: 0px 1.7px 3.3px 0px #eeeeee;
            box-shadow: 0px 1.7px 3.3px 0px #eeeeee;
            /* border-radius: 3px; */
            background: #fff;
            font-family: 'WorkSans';
        }

        .kotak-qr {
            -webkit-box-shadow: 0px 0px 5px 0px rgba(214,214,214,1);
            -moz-box-shadow: 0px 0px 5px 0px rgba(214,214,214,1);
            box-shadow: 0px 0px 5px 0px rgba(214,214,214,1);
            background: #fff;
            width: 130px;
            height: 130px;
            margin: 0 auto;
            border-radius: 20px;
            padding: 10px;
        }

        .kotak-full {
            margin-bottom : 15px;
            padding: 10px;
            background: #fff;
            font-family: 'Open Sans', sans-serif;
        }

        .kotak-inside {
            padding-left: 25px;
            padding-right: 25px
        }

        body {
            background: #fafafa;
        }

        .completed {
            color: green;
        }

        .bold {
            font-weight: bold;
        }

        .space-bottom {
            padding-bottom: 5px;
        }

        .space-top-all {
            padding-top: 15px;
        }

        .space-text {
            padding-bottom: 10px;
        }

        .space-nice {
            padding-bottom: 20px;
        }

        .space-bottom-big {
            padding-bottom: 25px;
        }

        .space-top {
            padding-top: 5px;
        }

        .line-bottom {
            border-bottom: 1px solid rgba(0,0,0,.1);
            margin-bottom: 15px;
        }

        .text-grey {
            color: #aaaaaa;
        }

        .text-much-grey {
            color: #bfbfbf;
        }

        .text-black {
            color: #000000;
        }

        .text-medium-grey {
            color: #806e6e6e;
        }

        .text-grey-white {
            color: #707070;
        }

        .text-grey-light {
            color: #b6b6b6;
        }

        .text-grey-medium-light{
            color: #a9a9a9;
        }

        .text-black-grey-light{
            color: #333333;
        }


        .text-medium-grey-black{
            color: #424242;
        }

        .text-grey-black {
            color: #4c4c4c;
        }

        .text-grey-red {
            color: #9a0404;
        }

        .text-grey-red-cancel {
            color: rgba(154,4,4,1);
        }

        .text-grey-blue {
            color: rgba(0,140,203,1);
        }

        .text-grey-yellow {
            color: rgba(227,159,0,1);
        }

        .text-grey-green {
            color: rgba(4,154,74,1);
        }

        .text-red{
            color: #990003;
        }

        .text-20px {
            font-size: 20px;
        }
        .text-21-7px {
            font-size: 21.7px;
        }

        .text-16-7px {
            font-size: 16.7px;
        }

        .text-15px {
            font-size: 15px;
        }

        .text-14-3px {
            font-size: 14.3px;
        }

        .text-14px {
            font-size: 14px;
        }

        .text-13-3px {
            font-size: 13.3px;
        }

        .text-12-7px {
            font-size: 12.7px;
        }

        .text-12px {
            font-size: 12px;
        }

        .text-11-7px {
            font-size: 11.7px;
        }

        .round-red{
            border: 1px solid #990003;
            border-radius: 50%;
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right:3px;
        }

        .round-grey{
            border: 1px solid #aaaaaa;
            border-radius: 50%;
            width: 7px;
            height: 7px;
            display: inline-block;
            margin-right:3px;
        }

        .bg-red{
            background: #990003;
        }

        .bg-grey{
            background: #aaaaaa;
        }

        .round-white{
            width: 10px;
            height: 10px;
            display: inline-block;
            margin-right:3px;
        }

        .line-vertical{
            font-size: 5px;
            width:10px;
            margin-right: 3px;
        }

        .inline{
            display: inline-block;
        }

        .vertical-top{
            vertical-align: top;
            padding-top: 5px;
        }

        .top-5px{
            top: -5px;
        }
        .top-10px{
            top: -10px;
        }
        .top-15px{
            top: -15px;
        }
        .top-20px{
            top: -20px;
        }
        .top-25px{
            top: -25px;
        }
        .top-30px{
            top: -30px;
        }
        .top-35px{
            top: -35px;
        }

        #map{
            border-radius: 10px;
            width: 100%;
            height: 150px;
        }

        .label-free{
            background: #6c5648;
            padding: 3px 15px;
            border-radius: 6.7px;
            float: right;
        }

        .text-strikethrough{
            text-decoration:line-through
        }

        #modal-usaha {
            position: fixed;
            top: 0;
            left: 0;
            background: rgba(0,0,0, 0.5);
            width: 100%;
            display: none;
            height: 100vh;
            z-index: 999;
        }

        .modal-usaha-content {
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -125px;
            margin-top: -125px;
        }

        .modal.fade .modal-dialog {
            transform: translate3d(0, 0, 0);
        }
        .modal.in .modal-dialog {
            transform: translate3d(0, 0, 0);
        }

        .body-admin{
            max-width: 480px;
            margin: auto;
            background-color: #fafafa;
            border: 1px solid #7070701c;
        }

    </style>
</head>
@php $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
<body style="background:#ffffff;max-width: 480px; margin: auto">
<div class="kotak-biasa" style="background-color: #FFFFFF;box-shadow: 0 0.7px 3.3px #eeeeee;">
    <div class="container" style="padding: 10px;margin-top: 10px;">
        <div class="text-center">
            <div class="col-12 text-14px"><b>Pesanan {{(strtolower($data['transaction_type']) == 'pickup order' ? 'Pickup Order':'Delivery')}}</b></div>
            <div class="col-12 text-13px space-text text-medium-grey-black WorkSans-Regular">{{ date('d', strtotime($data['transaction_date'])) }} {{ $bulan[date('n', strtotime($data['transaction_date']))] }} {{ date('Y', strtotime($data['transaction_date'])) }}</div>
            <div class="col-12 text-14px"><b>Order ID</b></div>
            <div class="col-12 text-13px space-text text-medium-grey-black WorkSans-Regular">#{{$data['order_id']}}</div>
        </div>
    </div>
</div>

@foreach ($data['transactions'] as $trx)
<div class="kotak-biasa" style="background-color: #FFFFFF;box-shadow: 0 0.7px 3.3px #eeeeee;">
    <div class="container" style="padding: 10px;margin-top: 10px;">
        <div class="row" style="margin-bottom: 5%;">
            <div class="col-12 text-13-3px WorkSans-Medium text-black">
                <div class="round-grey bg-grey" style="border: 1px solid #aaaaaa;border-radius: 50%;width: 5px;height: 5px;display: inline-block;margin-right:3px;background-color: #aaaaaa"></div>
                <b>{{$trx['outlet_name']}}</b>
            </div>
        </div>
        @foreach ($trx['product_transaction'] as $prod)
            <div class="row">
                <div class="col-2 text-13-3px WorkSans-SemiBold text-black">{{$prod['transaction_product_qty']}}x</div>
                <div class="col-6 text-14px WorkSans-SemiBold text-black">{{$prod['product_name']}}</div>
                <div class="col-4 text-13-3px text-right WorkSans-SemiBold text-black">{{ $prod['transaction_product_subtotal'] }}</div>
            </div>
            <?php
            $mod = [];
            $modifier = '';
            foreach ($prod['modifiers'] as $m){
                $mod[] = $m['text'].'('.$m['qty'].')';
            }
            $modifier = implode($mod,', ');
            ?>
            <div class="row">
                <div class="col-2 text-13-3px WorkSans-SemiBold text-black"></div>
                <div class="col-6 text-14px WorkSans-SemiBold text-black" style="color: darkgrey;font-size: 11px;">{{$modifier}}</div>
                <div class="col-4 text-13-3px text-right WorkSans-SemiBold text-black"></div>
            </div>
            <div class="row">
                <div class="col-2 text-13-3px WorkSans-SemiBold text-black"></div>
                <div class="col-6 text-14px WorkSans-SemiBold text-black" style="color: darkgrey;font-size: 11px;">{{implode(array_column($prod['variants'],'product_variant_name'), ', ')}}</div>
                <div class="col-4 text-13-3px text-right WorkSans-SemiBold text-black"></div>
            </div>
            <br>
        @endforeach
        @if(!empty($trx['shipping']))
            <div class="row" style="margin-bottom: 1%;">
                <div class="col-12 text-13-3px WorkSans-Medium text-black">
                    <b>Delivery (Grab Express)</b>
                </div>
            </div>
        @endif
    </div>
</div>
@endforeach

<div class="kotak-biasa" style="background-color: #FFFFFF;box-shadow: 0 0.7px 3.3px #eeeeee;">
    <div class="container" style="padding: 10px;margin-top: 10px;">
        <div class="kotak" style="margin: 0px;margin-top: 10px;border-radius: 10px;">
            @foreach($data['payment_detail'] as $dt)
                <div class="row">
                    <div class="col-6 text-13-3px WorkSans-SemiBold text-black ">{{$dt['name']}}</div>
                    @if(is_numeric(strpos(strtolower($dt['name']), 'discount')))
                        <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black" style="color:#a6ba35;">{{ $dt['amount'] }}</div>
                    @else
                        <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ $dt['amount'] }}</div>
                    @endif
                </div>
            @endforeach
            <div style="background: #FFE7CA;padding-top: 1.5%;padding-bottom: 1.5%;">
                <div class="row">
                    <div class="col-6 text-13-3px WorkSans-SemiBold text-black " style="font-size: 16px;"><b>Grand Total</b></div>
                    <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black" style="font-size: 16px"><b>{{$data['transaction_grandtotal']}}</b></div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($data['payment']))
    <div class="kotak-biasa" style="background-color: #FFFFFF;box-shadow: 0 0.7px 3.3px #eeeeee;">
        <div class="container" style="padding: 10px;margin-top: 10px;">
            <div class="row space-bottom">
                <div class="col-12 text-14px WorkSans-SemiBold text-black" style="margin-left: 1%;"><b>Detail Pembayaran</b></div>
            </div>
            <div class="kotak" style="margin: 0px;margin-top: 10px;border-radius: 10px;">
                <div class="row">
                    @foreach($data['payment'] as $dt)
                        @if($dt['name'] == 'Balance')
                            <div class="col-6 text-13-3px WorkSans-SemiBold text-black ">Poin</div>
                            <div class="col-6 text-13-3px text-right WorkSans-SemiBold" style="color: #ff0000">{{ $dt['amount'] }}</div>
                        @else
                            <div class="col-6 text-13-3px WorkSans-SemiBold text-black ">{{$dt['name']}}</div>
                            <div class="col-6 text-13-3px text-right WorkSans-SemiBold text-black">{{ $dt['amount'] }}</div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
</body>
</html>