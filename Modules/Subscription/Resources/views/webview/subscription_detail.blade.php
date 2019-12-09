<?php
    use App\Lib\MyHelper;
    $title = "Subscription Detail";
?>
@extends('webview.main')

@section('css')
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
    <style type="text/css">
    	p{
    		margin-top: 0px !important;
    		margin-bottom: 0px !important;
    	}
    	.subscription-detail > div{
    		padding-left: 0px;
    		padding-right: 0px;
    	}
    	.subscription-img{
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
            .subscription-img{
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
			padding-bottom: 5px;
		}
		.nav-item .active:hover{
			border:none !important;
			border-bottom: 3px solid #383b67 !important;
		}
		.nav-tabs{
			border-bottom: none !important;
			overflow-x: auto;
			overflow-y: hidden;
			display: -webkit-box;
			display: -moz-box;
		}
		.nav-tabs>li {
			float:none;
		}
		::-webkit-scrollbar {
			width: 0px;
			background: transparent; /* make scrollbar transparent */
		}
    </style>
@stop

@section('content')
	<div class="subscription-detail" style="background-color: #f8f9fb;">
		@if(!empty($subscription))
			@php
				$subscription = $subscription[0];
                if ($subscription['subscription_price_cash'] != "") {
                    // $subscription_fee = MyHelper::thousand_number_format($subscription['subscription_price_cash']);
                }
                elseif ($subscription['subscription_price_point']) {
                    $subscription_fee = $subscription['subscription_price_point'] . " poin";
                }
                else {
                    $subscription_fee = "GRATIS";
                }
            @endphp
            <div class="container" style="padding: 10px;box-shadow: 0 0.7px 3.3px #0f000000;background-color: #ffffff;">
				<img class="subscription-img center-block" src="{{ $subscription['url_subscription_image'] }}" alt="">
            </div>
            <div class="col-md-4 col-md-offset-4" style="margin-top: 10px;box-shadow: 0 0.7px 3.3px #0f000000;">
                
                <div class="title-wrapper clearfix WorkSans-Bold">
                    <div class="title WorkSans-Medium" style="color: #aaaaaa;font-size: 10.7px;padding-bottom: 0px;">  
                        <div id="timer" style="color: #aaaaaa;">
                            <span id="timerchild">Berakhir dalam</span>
                        </div>
					</div>
				</div>
				<div class="title-wrapper clearfix WorkSans-Bold">
					<div class="title" style="color: #333333;font-size: 20px;">
						{{ $subscription['subscription_title'] }}
						@if($subscription['subscription_sub_title'] != null)
						<br>
						<p style="color: #333333;font-size: 15px;" class="WorkSans-Regular">{{ $subscription['subscription_sub_title'] }}</p>
						@endif
					</div>
				</div>

                @if($subscription['subscription_description'] != "")
				<div class="title-wrapper WorkSans-Regular">
					<div class="description" style="font-size: 11.7px;">{!! $subscription['subscription_description'] !!}</div>
				</div>
                @endif
			</div>
			
			<div class="container" style="margin-top: 10px;box-shadow: 0 0.7px 3.3px #0f000000;background-color: #ffffff;">
				<div class="col-12" style="padding: 10px 15px;">
					<ul class="nav nav-tabs WorkSans-Bold" id="myTab" role="tablist" style="font-size: 14px;">
						@foreach ($subscription['subscription_content'] as $item)
							@if ($item['order'] == 1)
								<li class="nav-item">
									<a class="nav-link" id="ketentuan-tab" data-toggle="tab" href="#ketentuan" role="tab" aria-controls="ketentuan" aria-selected="true">Ketentuan</a>
								</li>
							@endif
							@if ($item['order'] == 2)
								<li class="nav-item">
									<a class="nav-link" id="howuse-tab" data-toggle="tab" href="#howuse" role="tab" aria-controls="howuse" aria-selected="false">Cara Pakai</a>
								</li>
							@endif
						@endforeach
						<li class="nav-item">
							<a class="nav-link active" id="outlet-tab" data-toggle="tab" href="#outlet" role="tab" aria-controls="outlet" aria-selected="false"> Berlaku di</a>
						</li>
						@foreach ($subscription['subscription_content'] as $item)
							@if ($item['order'] != 1 && $item['order'] != 2)
								<li class="nav-item">
									<a class="nav-link" id="{{$item['order']}}-tab" data-toggle="tab" href="#{{$item['order']}}" role="tab" aria-controls="{{$item['order']}}" aria-selected="false">{{$item['title']}}</a>
								</li>
							@endif
						@endforeach
					</ul>
				</div>
				<div class="tab-content mt-4 WorkSans-Regular" id="myTabContent" style="padding: 0 15px;padding-bottom: 5px;font-size: 11.7px;color: #707070;">
					@foreach ($subscription['subscription_content'] as $items)
						@if ($items['order'] == 1)
							<div class="tab-pane fade" id="ketentuan" role="tabpanel" aria-labelledby="ketentuan-tab">
								<ol class="WorkSans-Regular" style="padding-left: 15px;">
									@foreach ($items['subscription_content_details'] as $item)
										<li>{{$item['content']}}</li>
									@endforeach
								</ol> 
							</div>
						@endif
						@if ($items['order'] == 2)
							<div class="tab-pane fade" id="howuse" role="tabpanel" aria-labelledby="howuse-tab">
								<ol class="WorkSans-Regular" style="padding-left: 15px;">
									@foreach ($items['subscription_content_details'] as $item)
										<li>{{$item['content']}}</li>
									@endforeach
								</ol> 
							</div>
						@endif
					@endforeach
					<div class="tab-pane fade show active" id="outlet" role="tabpanel" aria-labelledby="outlet-tab">
						@if ($subscription['is_all_outlet'] == true)
							<p class="WorkSans-Bold">Berlaku di semua Outlet</p>
						@else
							@foreach($subscription['outlet_by_city'] as $key => $outlet_city)
							<div class="outlet-city">{{ $outlet_city['city_name'] }}</div>
							<ul class="nav">
								@foreach($outlet_city['outlet'] as $key => $outlet)
								<li>- {{ $outlet['outlet_name'] }}</li>
								@endforeach
							</ul>
							@endforeach
						@endif
					</div>
					@foreach ($subscription['subscription_content'] as $item)
						@if ($item['order'] != 1 && $item['order'] != 2)
							<div class="tab-pane fade" id="{{$item['order']}}" role="tabpanel" aria-labelledby="{{$item['order']}}-tab">
								<ol class="WorkSans-Regular" style="padding-left: 15px;">
									@foreach ($items['subscription_content_details'] as $item)
										<li>{{$item['content']}}</li>
									@endforeach
								</ol> 
							</div>
						@endif
					@endforeach
				</div>
				<br>
			</div>
		@else
			<div class="col-md-4 col-md-offset-4">
				<h4 class="text-center" style="margin-top: 30px;">Subscription is not found</h4>
			</div>
		@endif
    </div>
    
@stop

@section('page-script')
	
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
    @if(!empty($subscription))
        <script type="text/javascript">
            @php $month = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', "Juli", 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp

            // timer
            var subscription_start = "{{ strtotime($subscription['subscription_start']) }}";
            var subscription_end   = "{{ strtotime($subscription['subscription_end']) }}";
            var server_time = "{{ strtotime($subscription['time_server']) }}";
            var timer_text;
            var difference;

            if (server_time >= subscription_start && server_time <= subscription_end) {
                // subscription date is valid and count the timer
                difference = subscription_end - server_time;
            }
            else {
                // subscription is not yet start
                difference = subscription_start - server_time;
            }

            var display_flag = 0;
            this.interval = setInterval(() => {
                if(difference >= 0) {
                    timer_text = timer(difference);
					@if($subscription['subscription_status'] == 'available')
					if(timer_text.includes('lagi')){
						document.getElementById("timer").innerHTML = "Berakhir dalam ";
					}else{
						document.getElementById("timer").innerHTML = "Berakhir pada ";
					}
                    document.getElementById('timer').innerHTML += timer_text;
                    @elseif($subscription['subscription_status'] == 'soon')
                        document.getElementById("timer").innerHTML = "Dimulai pada ";
                        document.getElementById('timer').innerHTML += "{{ date('d', strtotime($subscription['subscription_start'])) }} {{$month[date('m', strtotime($subscription['subscription_start']))-1]}} {{ date('Y', strtotime($subscription['subscription_start'])) }} jam {{ date('H:i', strtotime($subscription['subscription_start'])) }}";
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
					timer = "<span style='color: #383b67;'>{{ date('d', strtotime($subscription['subscription_end'])) }} {{$month[ date('m', strtotime($subscription['subscription_end']))-1]}} {{ date('Y', strtotime($subscription['subscription_end'])) }}</span>";
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
