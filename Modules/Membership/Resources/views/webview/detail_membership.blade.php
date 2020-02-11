<!DOCTYPE html>
<html lang="en">
	<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <link rel="stylesheet" href="{{ env('S3_URL_VIEW') }}{{ ('assets/css/bootstrap.min.css') }}" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <title>Champ Membership</title>
        <style>
        @font-face {
            font-family: "WorkSans-Black";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Black.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Bold";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Bold.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-ExtraBold";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-ExtraBold.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-ExtraLight";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-ExtraLight.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Light";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Light.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Medium";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Medium.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Regular";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Regular.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-SemiBold";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-SemiBold.ttf') }}');
        }
        @font-face {
            font-family: "WorkSans-Thin";
            font-style: normal;
            font-weight: 400;
            src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Thin.ttf') }}');
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
        .font-header {
            font-family: 'WorkSans-Regular';
            font-size: 20px;
            color: #202020;
        }
        .font-title {
            font-family: 'WorkSans-Regular';
            font-size: 14px;
            color: #000000;
        }
        .font-nav {
            font-family: 'WorkSans-Regular';
            font-size: 14px;
            color: #545454;
        }
        .font-regular-gray{
            font-family: 'WorkSans-Regular';
            font-size: 12px;
            color: #545454;
        }
        .font-regular-black {
            font-family: 'WorkSans-Regular';
            font-size: 12px;
            color: #000000;
        }
        .font-regular-brown {
            font-family: 'WorkSans-Regular';
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
        .myprogress {
            position: relative;
        }
        .myprogress .pro-bar {
            width: 50%;
            height: 100%;
            background-color: #49a3df;
        }
        .myprogress:before {
            position: absolute;
            content: "{{number_format($result['user_membership']['user']['balance'] , 0, ',', '.')}}";
            text-align: center;
            padding: 10px;
            background: #ffffff;
            top: -45px;
            left: <?php echo (($result['user_membership']['user']['progress_now'] / $max_value) * 100) - 4 ?>%;
            border-radius: 10px;
            color: #333333;
            box-shadow: 0 1px 2px 0 #cccccc;
            font-size: 13.3px;
            font-family: WorkSans-SemiBold;
        }
        .myprogress:after {
            position: absolute;
            content: "";
            height: 10px;
            background: rgba(255, 0, 0, 0);
            top: -8px;
            left: <?php echo (($result['user_membership']['user']['progress_now'] / $max_value) * 100) - 2 ?>%;
            border-left: 6px solid rgba(255, 0, 0, 0);
            border-right: 6px solid rgba(255, 0, 0, 0);
            border-top: 6px solid #FFFFFF;
        }
        .text-black {
            color: #333333;
        }
        .text-grey {
            color: #707070;
        }
        </style>
	</head>
	<body style="background: #F8F9FB;">
        <div style="background-color: #f8f9fb;" id="carouselExampleFade" class="carousel slide carousel-fade" data-ride="carousel" data-interval="false">
            <div class="carousel-inner" style="text-shadow: none;">

                @foreach ($result['all_membership'] as $member)
                    @if($member['membership_name'] == $result['user_membership']['membership_name']) 
                    <div class="carousel-item active">
                        <div style="padding: 20px 0px 20px 0px;">
                            <div class="card" style="height: 149px;width: 80%;margin: auto;background: #F0F3F7;border: #aaaaaa;border-radius: 20px;box-shadow: 2px 6.7px 6.7px 0 #EEEEEE;">
                                <div class="card-body" style="display: flex;flex-wrap: wrap;padding: 10px;">
                                    <div class="col-9 text-left" style="margin-top: 7px;margin-bottom: 27px;">
                                        <p class="WorkSans-SemiBold text-black" style="margin-bottom: 4px;font-size: 15px;">{{$result['user_membership']['user']['name']}}</p>
                                        <p class="WorkSans text-grey" style="font-size: 10.7px;">@if ($result['user_membership']['user']['is_suspended'] == 0) Active @else Suspended @endif</p>
                                    </div>
                                    <div class="col-3">
                                        <img src="{{$member['membership_image']}}" style="width: 30px;float: right;"/>
                                    </div>
                                    <div class="col-6 text-left">
                                        <p class="WorkSans text-black" style="font-size: 10.7px;margin-bottom: 4px;">Poin saat ini</p>
                                        <p class="WorkSans-SemiBold" style="font-size: 13.3px;color: #383b67;">{{number_format($result['user_membership']['user']['progress_now'] , 0, ',', '.')}} poin</p>
                                    </div>
                                    <div class="col-6 text-right">
                                        <p class="WorkSans text-black" style="font-size: 10.7px;margin-bottom: 4px;">Status member</p>
                                        <p class="WorkSans-SemiBold" style="font-size: 13.3px;color: #383b67;">{{strtoupper($member['membership_name'])}}</p>
                                    </div>
                                </div>
                                @if (reset($result['all_membership']) != $member)
                                    <a style="left: -30px;" class="carousel-control-prev" href="#carouselExampleFade" role="button" data-slide="prev">
                                        <img src="{{env('S3_URL_VIEW').'img/membership/previous.png'}}" style="width: 33px;"/>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                @endif
                                @if (end($result['all_membership']) != $member)
                                    <a style="right: -30px;" class="carousel-control-next" href="#carouselExampleFade" role="button" data-slide="next">
                                        <img src="{{env('S3_URL_VIEW').'img/membership/next.png'}}" style="width: 33px;"/>
                                        <span class="sr-only">Next</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div style="position: relative;left: auto;right: auto;padding: 15px;top: 10px;margin-bottom: 10px;" class="carousel-caption">
                            <div style="margin-bottom: 25px;font-size: 14px;" class="WorkSans-SemiBold text-left text-black">Total transaksi Anda</div>
                            <div class="level-wrapper">
                                <div>
                                    <div class="current-level-info" style="margin: 0 15px;position:absolute; width:{{($result['user_membership']['user']['progress_now'] / $max_value) * 100}}%; z-index:10">
                                        <div style="width:{{($result['user_membership']['user']['progress_now'] / $max_value) * 100}}%;"></div> 
                                        <div style="color: #333333;font-size: 13.3px;"></div>
                                    </div>
                                    <div style="display:flex;margin: 5px 15px;flex-direction: row;justify-content: space-between;">
                                        @foreach ($result['all_membership'] as $item)
                                        <div class="current-level-info" style="width: 20px;">
                                            <img src="{{$item['membership_image']}}" style="width: 20px;float: right; @if($result['user_membership']['user']['progress_now'] >= $item['min_value']) display: none; @endif"/>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="level-progress-container" style="margin: 0 15px; height: 6px;">
                                    <div class="myprogress">
                                        <div class="level-progress" style="width:{{($result['user_membership']['user']['progress_now'] / $max_value) * 100}}%; height: 6px;background: linear-gradient(#63ba35, #2c7b25);"></div>
                                    </div>
                                </div>
                                <div class="level-info" style="margin: 0 15px;">
                                    @foreach ($result['all_membership'] as $item)
                                        <div class="WorkSans text-black" style="font-size: 13.3px; @if ($item != end($result['all_membership']) && $item != reset($result['all_membership'])) margin-left: 50px; @endif">@if($result['user_membership']['user']['progress_now'] <= $item['min_value']) {{number_format($item['min_value'] , 0, ',', '.')}} @endif</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div style="position: relative;left: auto;right: auto;padding: 15px;top: 10px;margin-bottom: 10px;" class="carousel-caption">
                            <div class="WorkSans-SemiBold text-black text-left" style="font-size: 14px;">Keuntungan {{$member['membership_name']}} member : </div>
                        </div>
                    </div>
                    @else
                    <div class="carousel-item">
                        <div style="padding: 20px 0px 20px 0px;">
                            <div class="card" style="height: 149px;width: 80%;margin: auto;background: #EEEEEE;border: #aaaaaa;border-radius: 20px;">
                                <div class="card-body" style="display: flex;flex-wrap: wrap;">
                                    <div class="col-12 text-center">
                                            <p style="margin-bottom: 10px;"></p>
                                        <img src="{{env('S3_URL_VIEW').'img/membership/lock.png'}}" style="width: 40px;"/>
                                        <p style="margin-bottom: 10px;"></p>
                                        <p style="font-size: 11.7px;">Naikan terus transaksi Anda untuk menuju <b>{{$member['membership_name']}}</b></p>
                                    </div>
                                </div>
                                @if (reset($result['all_membership']) != $member)
                                    <a style="left: -30px;" class="carousel-control-prev" href="#carouselExampleFade" role="button" data-slide="prev">
                                        <img src="{{env('S3_URL_VIEW').'img/membership/previous.png'}}" style="width: 33px;"/>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                @endif
                                @if (end($result['all_membership']) != $member)
                                    <a style="right: -30px;" class="carousel-control-next" href="#carouselExampleFade" role="button" data-slide="next">
                                        <img src="{{env('S3_URL_VIEW').'img/membership/next.png'}}" style="width: 33px;"/>
                                        <span class="sr-only">Next</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div style="position: relative;left: auto;right: auto;padding: 15px;top: 10px;margin-bottom: 10px;" class="carousel-caption">
                            <div style="margin-bottom: 25px;font-size: 14px;" class="WorkSans-SemiBold text-left text-black">Total transaksi Anda</div>
                                <div class="level-wrapper">
                                    <div>
                                        <div class="current-level-info" style="margin: 0 15px;position:absolute; width:{{($result['user_membership']['user']['progress_now'] / $max_value) * 100}}%; z-index:10">
                                            <div style="width:{{($result['user_membership']['user']['progress_now'] / $max_value) * 100}}%;"></div> 
                                            <div style="color: #333333;font-size: 13.3px;"></div>
                                        </div>
                                        <div style="display:flex;margin: 5px 15px;flex-direction: row;justify-content: space-between;">
                                            @foreach ($result['all_membership'] as $item)
                                            <div class="current-level-info" style="width: 20px;">
                                                <img src="{{$item['membership_image']}}" style="width: 20px;float: right; @if($result['user_membership']['user']['progress_now'] >= $item['min_value']) display: none; @endif"/>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="level-progress-container" style="margin: 0 15px; height: 6px;">
                                        <div class="myprogress">
                                            <div class="level-progress" style="width:{{($result['user_membership']['user']['progress_now'] / $max_value) * 100}}%; height: 6px;background: linear-gradient(#63ba35, #2c7b25);"></div>
                                        </div>
                                    </div>
                                    <div class="level-info" style="margin: 0 15px;">
                                        @foreach ($result['all_membership'] as $item)
                                            <div class="WorkSans text-black" style="font-size: 13.3px; @if ($item != end($result['all_membership']) && $item != reset($result['all_membership'])) margin-left: 50px; @endif">@if($result['user_membership']['user']['progress_now'] <= $item['min_value']) {{number_format($item['min_value'] , 0, ',', '.')}} @endif</div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div style="position: relative;left: auto;right: auto;padding: 15px;top: 10px;margin-bottom: 10px;" class="carousel-caption">
                                <div class="WorkSans-SemiBold text-black text-left" style="font-size: 14px;">Keuntungan {{$member['membership_name']}} member : </div>
                            </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        <script src="{{ env('API_URL') }}js/jquery.js"></script>
        <script src="{{ env('S3_URL_VIEW') }}{{ ('assets/js/bootstrap.min.js') }}" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script>
        $( document ).ready(function() {
            $(".ui-page").css("background-color", "#ffffff");
        });
        </script>
    </body>
</html>