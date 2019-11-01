<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css" />
        <link rel="stylesheet" href="asset/css/App.css" />
        <title>Champ Membership</title>
        <style>

        @font-face {
                font-family: "ProductSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-BoldItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-BoldItalic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Italic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Italic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Regular";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Regular.ttf') }}');
        }
        body {
            background-color: rgba(0, 0, 0, 0.1);
            font-family: 'ProductSans-Regular';
            font-size: 14px;
        }
        a:hover{
            text-decoration: none
        }
        .font-header {
            font-family: 'ProductSans-Regular';
            font-size: 20px;
            color: #202020;
        }
        .font-title {
            font-family: 'ProductSans-Regular';
            font-size: 14px;
            color: #000000;
        }
        .font-nav {
            font-family: 'ProductSans-Regular';
            font-size: 14px;
            color: #545454;
        }
        .font-regular-gray{
            font-family: 'ProductSans-Regular';
            font-size: 12px;
            color: #545454;
        }
        .font-regular-black {
            font-family: 'ProductSans-Regular';
            font-size: 12px;
            color: #000000;
        }
        .font-regular-brown {
            font-family: 'ProductSans-Regular';
            font-size: 12px;
            color: #837046;
        }

        .container {
            display: flex;
            flex: 1;
            flex-direction: column;
            min-height: 100vh;
            min-height: calc(var(--vh, 1vh) * 100);
            margin: auto;
            padding-bottom: 70px;
            background-color: #ffffff;
            position: relative;
        }
        .content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        /* header */
        .header {
            display: flex;
            flex-direction: row;
            height: 70px;
            padding: 0px 5px;
            align-items: center;
            justify-content: center;
        }
        .header-icon {
            position: absolute;
            left: 0;
            margin: 0px 16px;
        }
        .header-title {
            display: flex;
            flex: 1;
            justify-content: center
        }

        /* navtop */
        .navtop-container {
            display: flex;
            justify-content: space-between;
            flex-direction: row;
            background-image: linear-gradient(to bottom, #ffffff, #fafafa 40%, #ededed 82%, #e6e6e6);
        }
        .navtop-item {
            display: flex;
            flex: 1;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 5px 10px
        }
        .navtop-item img{
            height: 40px;
            width: 40px;
            margin-bottom: 5px
        }
        .navtop-item.active{
            background-color: #ffffff;
            border-bottom-style: solid;
            border-bottom-width: 2px;
            border-bottom-color: #800000
        }

        /* content */
        .tab-content {
            margin: 10px 0px;
        }
        .content-list {
            display: flex;
            flex-direction: column;
            padding: 8px 0px;
            margin-bottom: 16px;
        }
        .content-list-item {
            display: flex;
            flex: 1;
            flex-direction: row;
        }
        .content-list .content-list-item img{
            margin-right: 8px;
            height: 15px;
            width: 15px;
        }

        /* member level */
        .level-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            margin: 10px 0px;
        }
        .level-container img{
            margin-left: 0px 8px 0px;
            height: 24px;
            width: 24px;
        }
        .level-wrapper {
            flex: 1;
        }
        .level-wrapper img{
            margin-right: 8px;
            height: 18px;
            width: 18px;
        }

        .current-level-info{
            position: relative;
            display: flex;
            flex-direction: row
        }
        .level-info{
            display: flex;
            flex-direction: row;
            justify-content: space-between;

        }
        .level-progress-container {
            position: relative;
            height: 8px;
            border-radius: 8px;
            margin: 8px 0px;
            background-color: #ebebeb;
        }
        .level-progress {
            position: absolute;
            left:0;
            top:0;
            z-index: 9;
            height: 8px;
            background-color: #800000;
            border-radius: 8px
        }
        .level-progress-blank {
            width: 50%;
        }
        </style>
	</head>
	<body>
		<div class="container">
			<div class="content">
                <div class="nav nav-tabs navtop-container" id="nav-tab" role="tablist">
                    @foreach ($result['all_membership'] as $item)
                    <a class="navtop-item @if($item['membership_name'] == $result['user_membership']['membership_name']) active @endif" id="nav-{{strtolower($item['membership_name'])}}-tab" data-toggle="tab" href="#nav-{{strtolower($item['membership_name'])}}" role="tab" aria-controls="nav-home" aria-selected="true">
                        <img src="{{$item['membership_image']}}"/>
                        <div class="font-nav">{{$item['membership_name']}}</div>
                    </a>
                    @endforeach
                </div>

                <div class="tab-content" id="nav-tabContent">
                    <div class="font-title">{{$result['user_membership']['membership_name']}} Level</div>
                    <div class="font-regular-gray">Tingkatkan transaksimu!</div>

                    @foreach ($result['all_membership'] as $key => $item)
                    <div class="tab-pane fade show @if($item['membership_name'] == $result['user_membership']['membership_name']) active @endif" id="nav-{{strtolower($item['membership_name'])}}" role="tabpanel" aria-labelledby="nav-home-tab">
                        @if (isset($result['all_membership'][$key+1]))
                            @php
                                $trx_total = $result['all_membership'][$key+1]['min_total_balance'] - $result['user_membership']['user']['progress_now']
                            @endphp
                            @if ($trx_total <= 0)
                                <div class="font-regular-gray">Anda telah melewati tingkatan ini</div>
                            @else
                                <div class="font-regular-gray">Rp {{number_format($trx_total)}} menuju {{$result['all_membership'][$key+1]['membership_name']}} Member</div>
                            @endif
                            <div class="level-container">
                                <div class="level-wrapper">
                                    @if ($trx_total <= 0)
                                       <div class="current-level-info">
                                           <div style="width:100%"></div>
                                           <img src="{{env('APP_URL')}}images/coin.png"/>
                                           <div class="font-regular-brown">{{number_format($result['user_membership']['user']['progress_now'])}}</div>
                                       </div>
                                       <div class="level-progress-container" style="margin-right: 10px; height: 6px;">
                                           <div class="level-progress" style="width:100%; height: 6px;"></div>
                                       </div>
                                   @else
                                       <div class="current-level-info">
                                           <div style="width:{{ ($result['user_membership']['user']['progress_now'] / $result['all_membership'][$key+1]['min_total_balance']) * 100 }}%;"></div>
                                           <img src="{{env('APP_URL')}}images/coin.png"/>
                                           <div class="font-regular-brown">{{number_format($result['user_membership']['user']['progress_now'])}}</div>
                                       </div>
                                       <div class="level-progress-container" style="margin-right: 10px; height: 6px;">
                                           <div class="level-progress" style="width:{{ ($result['user_membership']['user']['progress_now'] / $result['all_membership'][$key+1]['min_total_balance']) * 100 }}%; height: 6px;"></div>
                                       </div>
                                   @endif
                                    <div class="level-info">
                                        @if ($result['all_membership'][$key+1]['min_total_balance'] == $result['all_membership'][$key+1]['min_total_balance'] - $result['all_membership'][$key]['min_total_balance'])
                                            <div class="font-regular-black">{{number_format(0)}}</div>
                                        @else
                                            <div class="font-regular-black">{{number_format($result['all_membership'][$key]['min_total_balance'])}}</div>
                                        @endif
                                        <div class="font-regular-black">{{number_format($result['all_membership'][$key+1]['min_total_balance'])}}</div>
                                    </div>
                                </div>
                                <img src="{{$result['all_membership'][$key+1]['membership_image']}}"/>
                            </div>
                        @else
                            @php
                                $trx_total = 15000000 - $result['user_membership']['user']['progress_now']
                            @endphp
                            @if ($trx_total <= 0)
                                <div class="font-regular-gray">Anda telah melewati tingkatan ini</div>
                            @else
                                <div class="font-regular-gray">Rp {{number_format($trx_total)}} menyelesaikan semua Member</div>
                            @endif
                            <div class="level-container">
                                <div class="level-wrapper">
                                   <div class="current-level-info">
                                       <div style="width:{{ ($result['user_membership']['user']['progress_now'] / 15000000) * 100 }}%;"></div>
                                       <img src="{{env('APP_URL')}}images/coin.png"/>
                                       <div class="font-regular-brown">{{number_format($result['user_membership']['user']['progress_now'])}}</div>
                                   </div>
                                   <div class="level-progress-container" style="margin-right: 10px; height: 6px;">
                                       <div class="level-progress" style="width:{{ ($result['user_membership']['user']['progress_now'] / 15000000) * 100 }}%; height: 6px;"></div>
                                   </div>
                                    <div class="level-info">
                                        <div class="font-regular-black">{{number_format($result['all_membership'][$key]['min_total_balance'])}}</div>
                                        <div class="font-regular-black">{{number_format(15000000)}}</div>
                                    </div>
                                </div>
                                <img src="{{$item['membership_image']}}"/>
                            </div>
                        @endif

                        <div class="font-title">{{$item['membership_name']}} Benefit : </div>
                        <div class="content-list">
                            @if($item['benefit_point_multiplier'] != null && $item['benefit_point_multiplier'] > 0)
                            <div class="content-list-item">
                                <img src="{{$item['membership_image']}}"/>
                                <div class=font-regular-gray>
                                    Benefit Point {{number_format($item['benefit_point_multiplier'])}}
                                </div>
                            </div>
                            @endif
                            @if($item['benefit_cashback_multiplier'] != null && $item['benefit_cashback_multiplier'] > 0)
                            <div class="content-list-item">
                                <div class="font-regular-gray">
                                    <li style="list-style-type:none;">
                                        <img src="{{$item['membership_image']}}"/> Benefit Cashback {{number_format($item['benefit_cashback_multiplier'])}}%
                                    </li>
                                    @if($item['cashback_maximum'] != null)
                                    <li style="list-style-type:none;">
                                        <img src="{{$item['membership_image']}}"/> Cashback Maksimum {{number_format($item['cashback_maximum'])}}
                                    </li>
                                    @endif
                                </div>
                            </div>
                            @endif
                            @if($item['benefit_discount'] != null && $item['benefit_discount'] > 0)
                            <div class="content-list-item">
                                <div class=font-regular-gray>
                                    <img src="{{$item['membership_image']}}"/> Benefit Discount {{number_format($item['benefit_discount'])}}
                                </div>
                            </div>
                            @endif
                            @if($item['membership_promo_id'] != null && count($item['membership_promo_id']) > 0)
                            <div class="content-list-item">
                                <div class=font-regular-gray>
                                    @foreach ($item['membership_promo_id'] as $data)
                                        <li style="list-style-type:none;">
                                            <img src="{{$item['membership_image']}}"/> {{$data['promo_name']}}
                                        </li>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
        <script src="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>

        <script>
            $(function(){
                $( ".tab-content" ).on( "swiperight", swiperightHandler );
                function swiperightHandler(){
                    var active = $(".navtop-item.active")
                    var length = active.prev(".navtop-item").length
                    if(length > 0) {
                        var id = active.attr("href")
                        var prev_id = active.prev(".navtop-item").attr("href")
                        active.prev('.navtop-item').addClass("active");
                        active.removeClass("active")
                        $(id).removeClass("show")
                        $(id).removeClass("active")
                        $(prev_id).addClass("show")
                        $(prev_id).addClass("active")
                    }
                }
            });

            $(function(){
                $( ".tab-content" ).on( "swipeleft", swipeleftHandler );
                function swipeleftHandler(){
                    var active = $(".navtop-item.active")
                    var length = active.next(".navtop-item").length
                    if (length > 0) {
                        var id = active.attr("href")
                        var next_id = active.next(".navtop-item").attr("href")
                        active.next('.navtop-item').addClass("active");
                        active.removeClass("active")
                        $(id).removeClass("show")
                        $(id).removeClass("active")
                        $(next_id).addClass("show")
                        $(next_id).addClass("active")
                    }
                }
            });
        </script>
    </body>
</html>
