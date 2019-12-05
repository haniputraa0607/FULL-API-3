<?php
    use App\Lib\MyHelper;
    $title = "Deals Detail";
?>
@extends('webview.main')

@section('css')
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
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
    		background-color: #ffffff;
    		position: relative;
    		display: flex;
    		align-items: center;
    	}
    	.col-left{
    		flex: 70%;
    	}
    	.col-right{
    		flex: 30%;
    	}
    	.title-wrapper > div{
    		padding: 10px 15px;
    	}
    	.title{
    		font-size: 18px;
    		color: #666666;
    	}
    	#timer{
    		position: absolute;
    		top: -25px;
    		right: 0px;
    		padding: 5px 30px;
    		/*border-bottom-left-radius: 7px !important;*/
    		color: #fff;
            display: none;
    	}
        .bg-yellow{
            background-color: #d1af28;
        }
		.bg-dark-blue {
			background-color: #383b67;
		}
        .bg-red{
            background-color: #c02f2fcc;
        }
        .bg-black{
            background-color: #000c;
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
			background-color: #ffffff;
    		padding: 15px;
    	}
		.outlet-wrapper{
		    padding: 0 15px 15px;
		}
    	.description{
    	    padding-top: 10px;
    	    font-size: 15px;
    	}
    	.subtitle{
    		margin-bottom: 10px;
    		color: #000;
    		font-size: 15px;
    	}
    	.outlet{
    	    font-size: 14.5px;
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
		.tab-head{
			padding-left: 0px !important;
			padding-right: 0px !important;
		}
		.nav-item a:focus{
			outline: unset;
		}
		.nav-item a:hover{
			border: 1px solid #fff !important;
		}
		.nav-item a{
			color: #707070 !important;
			font-weight: 600;
			padding-left: 28px;
			padding-right: 28px;
		}
		.nav-item .active{
			color: #383b67 !important;
			border:none !important;
			border-bottom: 3px solid #383b67 !important;
			font-weight: 600;
			padding: 10px;
			border-radius: 3px;
		}
		.nav-item .active:hover{
			border:none !important;
			border-bottom: 3px solid #383b67 !important;
		}
		.nav-tabs{
			border-bottom: 1px solid #f8f9fb;
			overflow-x: auto;
			overflow-y: hidden;
			display: -webkit-box;
			display: -moz-box;
		}
		.nav-tabs>li {
			float:none;
		}
		.nav>li>a:focus, .nav>li>a:hover {
			background-color: transparent;
		}
    </style>
@stop

@section('content')
	<div class="deals-detail" style="background-color: #f8f9fb;">
		@if(!empty($deals))
			@php
				$deals = $deals[0];
                if ($deals['deals_voucher_price_cash'] != "") {
					$deals_fee = number_format($deals['deals_voucher_price_cash'],0,",",".");
                }
                elseif ($deals['deals_voucher_price_point']) {
                    $deals_fee = number_format($deals['deals_voucher_price_point'],0,",",".") . " poin";
                }
                else {
                    $deals_fee = "GRATIS";
                }
			@endphp
			<div class="col-md-4 col-md-offset-4" style="box-shadow: 0 0.7px 3.3px #0f000000;">
				<img class="deals-img center-block" src="{{ $deals['url_deals_image'] }}" alt="">

				<div class="title-wrapper clearfix">
					<div class="col-6 voucher font-red WorkSans-Medium" style="color: #707070;">
					    @if($deals['deals_voucher_type'] != 'Unlimited')
						    {{ $deals['deals_total_voucher']-$deals['deals_total_claimed'] }}/{{ $deals['deals_total_voucher'] }}
						@endif
						kupon tersedia
					</div>
					<div class="col-6 text-right">
					    <div id="timer" style="color: #ffffff;" class="text-center WorkSans-Reguler">
					        <span id="timerchild">Akan berakhir dalam</span>
					    </div>
						<div class="fee text-right font-red WorkSans-Bold" style="color: #333333;">{{ $deals_fee }}</div>
					</div>
				</div>
				<div class="title-wrapper clearfix WorkSans-Bold">
					<div class="title" style="color: #333333;font-size: 20px;">
						{{ $deals['deals_title'] }}
						@if($deals['deals_second_title'] != null)
						<br>
						<p style="color: #333333;font-size: 15px;" class="WorkSans-Regular">{{ $deals['deals_second_title'] }}</p>
						@endif
					</div>
				</div>

                @if($deals['deals_description'] != "")
				<div class="title-wrapper WorkSans-Regular">
					<div class="description" style="font-size: 11.7px;">{!! $deals['deals_description'] !!}</div>
				</div>
                @endif
			</div>
			
			<div class="container" style="margin-top: 10px;box-shadow: 0 0.7px 3.3px #0f000000;background-color: #ffffff;">
				<div class="col-12" style="padding: 10px 15px;">
					<ul class="nav nav-tabs WorkSans-Bold" id="myTab" role="tablist" style="font-size: 14px;">
						<li class="nav-item">
							<a class="nav-link active" id="ketentuan-tab" data-toggle="tab" href="#ketentuan" role="tab" aria-controls="ketentuan" aria-selected="true">Ketentuan</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="howuse-tab" data-toggle="tab" href="#howuse" role="tab" aria-controls="howuse" aria-selected="false">Cara Pakai</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="outlet-tab" data-toggle="tab" href="#outlet" role="tab" aria-controls="outlet" aria-selected="false"> Berlaku di</a>
						</li>
					</ul>
				</div>
				<div class="tab-content mt-4 WorkSans-Regular" id="myTabContent" style="padding: 0 15px;padding-bottom: 5px;font-size: 11.7px;color: #707070;">
					<div class="tab-pane fade show active" id="ketentuan" role="tabpanel" aria-labelledby="ketentuan-tab">
						@if($deals['deals_tos'] != "")
						{!! $deals['deals_tos'] !!}
						@endif
					</div>
					<div class="tab-pane fade" id="howuse" role="tabpanel" aria-labelledby="howuse-tab">
						<p>Comming Soon</p>
					</div>
					<div class="tab-pane fade" id="outlet" role="tabpanel" aria-labelledby="outlet-tab">
						@foreach($deals['outlet_by_city'] as $key => $outlet_city)
						<div class="outlet-city">{{ $outlet_city['city_name'] }}</div>
						<ul class="nav">
							@foreach($outlet_city['outlet'] as $key => $outlet)
							<li>- {{ $outlet['outlet_name'] }}</li>
							@endforeach
						</ul>
						@endforeach
					</div>
				</div>
				<br>
			</div>
		@else
			<div class="col-md-4 col-md-offset-4">
				<h4 class="text-center" style="margin-top: 30px;">Deals is not found</h4>
			</div>
		@endif
	</div>
@stop

@section('page-script')
	
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
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
                document.getElementById('timer').classList.add("bg-dark-blue");
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
						document.getElementById("timer").innerHTML = "Akan berakhir dalam";
					}else{
						document.getElementById("timer").innerHTML = "Akan berakhir pada";
					}
                    document.getElementById("timer").innerHTML += "<br>";
                    document.getElementById('timer').innerHTML += timer_text;
                    @elseif($deals['deals_status'] == 'soon')
                    document.getElementById("timer").innerHTML = "Akan dimulai pada";
                    document.getElementById("timer").innerHTML += "<br>";
                    document.getElementById('timer').innerHTML += "<p class='WorkSans-Medium'>{{ date('d', strtotime($deals['deals_start'])) }} {{$month[date('m', strtotime($deals['deals_start']))-1]}} {{ date('Y', strtotime($deals['deals_start'])) }} jam {{ date('H:i', strtotime($deals['deals_start'])) }}</p>";
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
					timer = "<p class='WorkSans-Medium'>{{ date('d', strtotime($deals['deals_end'])) }} {{$month[ date('m', strtotime($deals['deals_end']))-1]}} {{ date('Y', strtotime($deals['deals_end'])) }}</p>";
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

                    timer = hoursDifference + ": jam " + minutesDifference + " menit lagi";
                    console.log('timer', timer);
                }

                return timer;
            }
        </script>
    @endif
@stop
