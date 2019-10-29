<?php
    use App\Lib\MyHelper;
    $title = "Deals Detail";
?>
@extends('webview.main')

@section('css')
    <style type="text/css">
    	p{
    		margin-top: 0px !important;
    		margin-bottom: 0px !important;
    	}
    	.deals-detail > div{
    		padding-left: 0px;
    		padding-right: 0px;
    	}
    	.deals-img{
    		width: 100%;
    		height: auto;
    	}
    	.title-wrapper{
    		background-color: #f8f8f8;
    		position: relative;
    		display: flex;
    	}
    	.col-left{
    		flex: 70%;
    	}
    	.col-right{
    		flex: 30%;
    	}
    	.title-wrapper > div{
    		padding: 10px 5px;
    	}
    	.title{
    		font-size: 18px;
    		color: rgba(32, 32, 32);
    	}
    	#timer{
    		position: absolute;
    		right: 0px;
			bottom:0px;
			width: 100%;
    		padding: 10px;
    		/*border-bottom-left-radius: 7px !important;*/
    		color: #fff;
            display: none;
    	}
        .bg-yellow{
            background-color: #d1af28;
        }
        .bg-red{
            background-color: #c02f2fcc;
        }
        .bg-black{
            background-color: rgba(0, 0, 0, 0.5);
        }
        .bg-grey{
            background-color: #cccccc;
        }
    	.fee{
			margin-top: 30px;
			font-size: 18px;
			color: #000;
    	}
    	.description-wrapper{
    		padding: 20px;
    	}
		.outlet-wrapper{
		    padding: 0 20px;
		}
    	.description{
    	    padding-top: 10px;
    	    font-size: 14px;
    	}
    	.subtitle{
    		margin-bottom: 10px;
    		color: #000;
    		font-size: 15px;
    	}
    	.outlet{
    	    font-size: 13.5px;
    	}
    	.outlet-city:not(:first-child){
    		margin-top: 10px;
    	}

    	.voucher{
    	    margin-top: 30px;
    	}
    	.font-red{
    	    color: #990003;
    	}

        @media only screen and (min-width: 768px) {
            /* For mobile phones: */
            .deals-img{
	    		width: auto;
	    		height: auto;
	    	}
        }
    </style>
@stop

@section('content')
	<div class="deals-detail">
		@if(!empty($deals))
			@php
				$deals = $deals[0];
                if ($deals['deals_voucher_price_cash'] != "") {
                    $deals_fee = MyHelper::thousand_number_format($deals['deals_voucher_price_cash']);
                }
                elseif ($deals['deals_voucher_price_point']) {
                    $deals_fee = $deals['deals_voucher_price_point'] . " poin";
                }
                else {
                    $deals_fee = "GRATIS";
                }
			@endphp
			<div class="col-md-4 col-md-offset-4">
				<div style="background-color: #f8f8f8; position: relative;" class="clearfix">
					<img class="deals-img center-block" src="{{ $deals['url_deals_image'] }}" alt="">
					<div style="font-size: 12px;" class="text-right" id="timer">
						<span id="timerchild"><i class="fas fa-clock"></i>Berakhir Dalam</span>
					</div>
				</div>
				<div style="background-color: rgb(255, 255, 255);" class="title-wrapper col-md-12 clearfix ProductSans">
					<div class="title ProductSans">
						{{ $deals['deals_title'] }}
						@if($deals['deals_second_title'] != null)
						<br>
						{{ $deals['deals_second_title'] }}
						@endif
					</div>
				</div>
				@if($deals['deals_voucher_type'] != 'Unlimited')
				<div style="background-color: rgb(255, 255, 255);" class="col-md-12">
					<div style="padding: 10px 5px;">
						<div class="progress" style="background-color: rgb(235, 235, 235); height:6px; width: 190px; margin-top: -10px; margin-bottom: 0px;">
							<div style="background-color: rgb(128,0,0); width: {{ ((($deals['deals_total_voucher'] - $deals['deals_total_claimed']) / $deals['deals_total_voucher']) * 100) }}%;" class="progress-bar" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
					</div>
				</div>
				<div style="background-color: rgb(255, 255, 255);" class="col-md-12 title-wrapper clearfix ProductSans">
					<div style="margin-top: -15px; font-size: 13px;" class="fee text-right ProductSans">{{ $deals['deals_total_voucher'] - $deals['deals_total_claimed'] }} kupon tersedia</div>
				</div>
				@endif

				<hr style="margin-top: 0px; margin-bottom: 15px;">

				<div style="background-color: rgb(255, 255, 255);" class="col-md-12 title-wrapper clearfix">
					@php $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', "Jul", 'Agu', 'Sep', 'Okt', 'Nov', 'Des']; @endphp
					<div style="margin-top: -15px; font-size: 13px;" class="fee text-right ProductSans"><i class="far fa-calendar"></i> &nbsp Masa berlaku : <b>{{date('d', strtotime($deals['deals_start']))}} {{$bulan[date('m', strtotime($deals['deals_start']))-1]}} {{ date('Y', strtotime($deals['deals_start'])) }} - {{date('d', strtotime($deals['deals_end']))}} {{$bulan[date('m', strtotime($deals['deals_end']))-1]}} {{ date('Y', strtotime($deals['deals_end'])) }}</b></div>
				</div>

				<hr style="margin-top: 0px; margin-bottom: 0px;">

                @if($deals['deals_description'] != "")
				<div style="padding-top: 0px;" class="description-wrapper ProductSans">
					<div class="description">{!! $deals['deals_description'] !!}</div>
				</div>
				@endif

				<div id="showSK" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold">
					<div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Syarat & Ketentuan</div>
					<div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-down"></i></i></div>
				</div>

                @if($deals['deals_tos'] != "")
				<div id="textSK" style="padding-top: 0px;" class="description-wrapper ProductSans">
					<div class="description">{!! $deals['deals_tos'] !!}</div>
				</div>
				@endif

				<div id="showTP" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold">
					<div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Tempat Penukaran</div>
					<div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-down"></i></div>
				</div>

				<div id="textTP" class="outlet-wrapper ProductSans" style="padding-top: 10px; margin-bottom: 20px;">
					<div class="outlet">
						@foreach($deals['outlet_by_city'] as $key => $outlet_city)
						<div class="outlet-city" style="color: rgb(0, 0, 0);">{{ $outlet_city['city_name'] }}</div>
						<ul class="nav" style="color: rgb(84, 84, 84);">
							@foreach($outlet_city['outlet'] as $key => $outlet)
							<li>- {{ strtoupper($outlet['outlet_name']) }}</li>
							@endforeach
						</ul>
						@endforeach
					</div>
				</div>
			</div>
		@else
			<div class="col-md-4 col-md-offset-4">
				<h4 class="text-center" style="margin-top: 30px;">Deals is not found</h4>
			</div>
		@endif
	</div>
@stop

@section('page-script')
	<script type="text/javascript" src="{{ env('S3_URL_VIEW') }}{{('assets/global/plugins/jquery.min.js') }}" type="text/javascript"></script>
    @if(!empty($deals))
		<script type="text/javascript">
            @php $month = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', "Juli", 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp

            // timer
            var deals_start = "{{ strtotime($deals['deals_start']) }}";
            var deals_end   = "{{ strtotime($deals['deals_end']) }}";
            var server_time = "{{ strtotime($deals['time_server']) }}";
            var timer_text;
            var difference;

            if (server_time >= deals_start && server_time <= deals_end) {
                // deals date is valid and count the timer
                difference = deals_end - server_time;
                document.getElementById('timer').classList.add("bg-black");
            }
            else {
                // deals is not yet start
                difference = deals_start - server_time;
                document.getElementById('timer').classList.add("bg-grey");
            }

            var display_flag = 0;
            this.interval = setInterval(() => {
                if(difference >= 0) {
                    timer_text = timer(difference);
					@if($deals['deals_status'] == 'available')
					if(timer_text.includes('lagi')){
						document.getElementById("timer").innerHTML = "<i class='fas fa-clock'></i> &nbsp; Berakhir dalam";
					}else{
						document.getElementById("timer").innerHTML = "<i class='fas fa-clock'></i> &nbsp; Berakhir pada";
					}
                    document.getElementById("timer").innerHTML += " ";
                    document.getElementById('timer').innerHTML += timer_text;
                    @elseif($deals['deals_status'] == 'soon')
                    document.getElementById("timer").innerHTML = "<i class='fas fa-clock'></i> &nbsp; Akan dimulai pada";
                    document.getElementById("timer").innerHTML += " ";
                    document.getElementById('timer').innerHTML += "{{ date('d', strtotime($deals['deals_start'])) }} {{$month[date('m', strtotime($deals['deals_start']))-1]}} {{ date('Y', strtotime($deals['deals_start'])) }} : {{ date('H:i', strtotime($deals['deals_start'])) }}";
                    @endif

                    difference--;
                }
                else {
                    clearInterval(this.interval);
                }

                // if days then stop the timer
                if (timer_text!=null && timer_text.includes("day")) {
                    clearInterval(this.interval);
                }

                // show timer
                if (display_flag == 0) {
                    document.getElementById('timer').style.display = 'block';
                    display_flag = 1;
                }
            }, 1000); // 1 second

            function timer(difference) {
                if(difference === 0) {
                    return null;    // stop the function
                }

                var daysDifference, hoursDifference, minutesDifference, secondsDifference, timer;

                // countdown
                daysDifference = Math.floor(difference/60/60/24);
                if (daysDifference > 0) {
					timer = "{{ date('d', strtotime($deals['deals_end'])) }} {{$month[ date('m', strtotime($deals['deals_end']))-1]}} {{ date('Y', strtotime($deals['deals_end'])) }}";
                  //  timer = daysDifference + " hari";
                    console.log('timer d', timer);
                }
                else {
                    difference -= daysDifference*60*60*24;

                    hoursDifference = Math.floor(difference/60/60);
                    difference -= hoursDifference*60*60;
                    hoursDifference = ("0" + hoursDifference).slice(-2);

                    minutesDifference = Math.floor(difference/60);
                    difference -= minutesDifference*60;
                    minutesDifference = ("0" + minutesDifference).slice(-2);

                    secondsDifference = Math.floor(difference);

                    if (secondsDifference-1 < 0) {
                        secondsDifference = "00";
                    }
                    else {
                        secondsDifference = secondsDifference-1;
                        secondsDifference = ("0" + secondsDifference).slice(-2);
                    }
                    console.log('timer h', hoursDifference);
                    console.log('timer m', minutesDifference);
                    console.log('timer s', secondsDifference);

                    timer = hoursDifference + " : " + minutesDifference + " : " + secondsDifference;
                    console.log('timer', timer);
                }

                return timer;
            }
		</script>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#textSK').hide();
				$('#textTP').css('display','none');
				$('#showTP').css('margin-bottom','20px');
				$(document).on('click', '#showSK', function() {
					$('#textSK').slideDown( "slow" );
					$( "#showSK" ).replaceWith( '<div id="hideSK" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Syarat & Ketentuan</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-up"></i></i></div></div>' );
					$('html, body').animate({scrollTop: $(document).height()-100}, 'slow');
				});
				$(document).on('click', '#hideSK', function() {
					$('#textSK').slideUp( "slow" );
					$( "#hideSK" ).replaceWith( '<div id="showSK" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Syarat & Ketentuan</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-down"></i></i></div></div>' );
				});
				$(document).on('click', '#showTP', function(e) {
					$('#textTP').slideDown( "slow" );
					$( "#showTP" ).replaceWith( '<div id="hideTP" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Tempat Penukaran</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-up"></i></div></div>' );
                    $('html, body').animate({scrollTop: $(document).height()-100}, 'slow');
				});
				$(document).on('click', '#hideTP', function() {
					$('#textTP').slideUp( "slow" );
					$( "#hideTP" ).replaceWith( '<div id="showTP" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Tempat Penukaran</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-down"></i></div></div>' );
					$('#showTP').css('margin-bottom','20px');
				});
			});
		</script>
    @endif
@stop