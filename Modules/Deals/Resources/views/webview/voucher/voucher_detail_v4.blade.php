<?php
    use App\Lib\MyHelper;
    $title = "Deals Detail";
?>
@extends('webview.main')

@section('css')
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
	<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
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
        .bg-yellow{
            background-color: #d1af28;
        }
        .bg-red{
            background-color: #c02f2fcc;
        }
        .bg-black{
            background-color: #000c;
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
    	.subtitle2{
    		margin-bottom: 20px;
    		color: #aaaaaa;
    		font-size: 15px;
    	}
    	.kode-text{
    	    margin: 20px 0 8px;
    		color: #aaaaaa;
    		font-size: 18px;
    	}
    	.voucher-code{
    	    font-size: 22px;
    	}

    	#invalidate {
            color:#fff;
            background-color: #990003;
            border: none;
            border-radius: 5px;
            margin-bottom: 70px;
            margin-top: 30px;
            width: 90%;
            height: 48px;
            font-size: 18px;
        }
        #qr-code-modal{
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0, 0.5);
            /*width: 100%;*/
            /*height: 100vh;*/
            display: none;
            z-index: 999;
            overflow-y: auto;
        }
        #qr-code-modal-content{
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -155px;
            margin-top: -155px;
            padding: 30px;
            background: #fff;
            border-radius: 42.3px;
            border: 0;
        }

         .deals-qr {
            background: #fff;
            width: 135px;
            height: 135px;
            margin: 0 auto;
        }

		.card {
			background-color: rgb(248, 249, 251);
			transition: 0.3s;
			width: 100%;
			border-radius: 10px;
			background-repeat: no-repeat;
			background-size: 40% 100%;
			background-position: right;
		}

		#timer{
    		right: 0px;
			bottom:0px;
			width: 100%;
    	}
		#day{
    		right: 0px;
			bottom:0px;
			width: 100%;
    		padding: 5px;
    	}
		.card:hover {
			box-shadow: 0 0 5px 0 rgba(0,0,0,0.1);
		}

        @media only screen and (min-width: 768px) {
            .deals-img{
	    		width: auto;
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
		}
    </style>
@stop

@section('content')
	<div class="deals-detail">
		@if(!empty($voucher))
			@php
				$voucher = $voucher['data'][0];
			@endphp
			<div class="col-md-4 col-md-offset-4" style="background-color: #f8f9fb;">
				<!-- Modal QR Code -->
				@if(isset($voucher['redeemed_at']) && $voucher['redeemed_at'] != null || isset($voucher['used_at']) && $voucher['used_at'] == null)
				<div style="background-color: rgb(255, 255, 255);" class="col-md-12 title-wrapper clearfix ProductSans">
					@php $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', "Jul", 'Agu', 'Sep', 'Okt', 'Nov', 'Des']; @endphp
					<div style="font-size: 13px; color: rgb(128,0,0);padding-bottom: 0px;" class="text-right ProductSans"></i> Masa berlaku hingga {{date('d', strtotime($voucher['voucher_expired_at']))}} {{$bulan[date('m', strtotime($voucher['voucher_expired_at']))-1]}} {{ date('Y', strtotime($voucher['voucher_expired_at'])) }} &nbsp; {{ date('H:i', strtotime($voucher['voucher_expired_at'])) }}</div>
				</div>

				<div style="background-color: rgb(255, 255, 255);" class="title-wrapper col-md-12 clearfix ProductSans">
					<div class="title" style="padding-top: 5px; padding-bottom: 0px;">
						{{ $voucher['deal_voucher']['deal']['deals_title'] }}
						@if($voucher['deal_voucher']['deal']['deals_second_title'] != null)
						<br>
						{{ $voucher['deal_voucher']['deal']['deals_second_title'] }}
						@endif
					</div>
				</div>

				<div style="background-color: rgb(255, 255, 255);" class="title-wrapper col-md-12 clearfix ProductSans">
					<div class="title" style="padding-top: 0px; padding-bottom: 5px;">
						@if (isset($voucher['deal_voucher']['deal']['deals_voucher_price_point']))
							{{number_format($voucher['deal_voucher']['deal']['deals_voucher_price_point'])}} points
						@elseif (isset($voucher['deal_voucher']['deal']['deals_voucher_price_cash']))
							Rp {{number_format($voucher['deal_voucher']['deal']['deals_voucher_price_cash'])}}
						@else
							Gratis
						@endif
					</div>
				</div>

				<hr style="border-top: 1px dashed #aaaaaa; margin-top: 0px; margin-bottom: 15px;">

                <a id="qr-code-modal" href="#">
                    <div id="qr-code-modal-content">
                        <img class="img-responsive" src="{{ $voucher['voucher_hash'] }}">
                    </div>
                </a>

                <div class="description-wrapper ProductSans">
                    <div class="subtitle2 text-center ProductSans" style="font-size: 18px;">Pindai QR Code ini untuk validasi voucher</div>

                    <div class="deals-qr">
                        <img class="img-responsive" style="display: block; max-width: 100%;" src="{{ $voucher['voucher_hash'] }}">
                    </div>

                    <center class="kode-text">Kode Voucher</center>
                    <center class="voucher-code font-red ProductSans" style="color: rgba(32, 32, 32);">{{ $voucher['deal_voucher']['voucher_code'] }}</center>
                    <center class="kode-text" style="margin-top: 8px;">Atau input kode ini ke dalam POS</center>
                    <div class="line"></div>
				</div>

				<hr style="width:80%;border-top: 1px dashed #aaaaaa;margin-top: 0px;margin-bottom: 10px;">

				<div style="background-color: rgb(255, 255, 255);" class="title-wrapper col-md-12 clearfix ProductSans">
					<div class="text-center" style="padding-top: 0px; padding-bottom: 5px;">
						Pastikan langkah ini dilakukan oleh kasir. Jangan terima voucher apabila sudah dalam keadaan terbuka
					</div>
				</div>
				@else
				<div style="background-color: #ffffff;padding: 10px;box-shadow: 0 0.7px 3.3px #eeeeee;" class="col-md-12 clearfix WorkSans">
					<div style="position: relative;margin-top: 26.7px;">
						<hr style="position:absolute;z-index: 1;border: none;border-left: 1px dashed #eeeeee;height: 98px;left: 36%;top: 3%;">
						<div style="width: 56%;height: 100px;position: absolute;top: 10%;left: 40%;">
							<div class="cotainer">
								<div class="pull-left" style="margin-top: 10px;">
									@php $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', "Juli", 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp
									<p style="font-size: 15px;color: #333333;">{{$voucher['deal_voucher']['deal']['deals_title']}}</p>
									<p style="font-size: 13.3px;color: #333333;">{{$voucher['deal_voucher']['deal']['deals_second_title']}}</p>
									<div style="@if (isset($voucher['deal_voucher']['deal']['deals_second_title'])) margin-top: 20px; @else margin-top: 38px; @endif"></div>
									<p style="font-size: 10.7px;color: #707070;padding: 5px 10px;background-color: #f0f3f7;border-radius: 100px;">Kedaluwarsa {{date('d', strtotime($voucher['deal_voucher']['deal']['deals_end']))}} {{$bulan[date('m', strtotime($voucher['deal_voucher']['deal']['deals_end']))-1]}} {{ date('Y', strtotime($voucher['deal_voucher']['deal']['deals_end'])) }}</p>
								</div>
							</div>
						</div>
						<img src="{{ env('S3_URL_API').$voucher['deal_voucher']['deal']['deals_image'] }}" alt="" style="width: 85px;position: absolute;border-radius: 50%;top: 14.5%;left: 6.5%;">
						<img style="width:100%" height="130px" src="{{ env('S3_URL_API')}}img/asset/bg_item_kupon_saya.png" alt="">
					</div>
				</div>

				<div style="margin-top: 10px;box-shadow: 0 0.7px 3.3px #0f000000;background-color: #ffffff;">
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
							@if($voucher['deal_voucher']['deal']['deals_tos'] != "")
							{!! $voucher['deal_voucher']['deal']['deals_tos'] !!}
							@endif
						</div>
						<div class="tab-pane fade" id="howuse" role="tabpanel" aria-labelledby="howuse-tab">
							<p>Comming Soon</p>
						</div>
						<div class="tab-pane fade" id="outlet" role="tabpanel" aria-labelledby="outlet-tab">
							@foreach($voucher['deal_voucher']['deal']['outlet_by_city'] as $key => $outlet_city)
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

				@if(!isset($voucher['redeemed_at']) || $voucher['redeemed_at'] == null)
					<center style="position: fixed; bottom: 0; width: 100%; background-color: #ffffff;">
						<button style="outline:none; font-size:15px; margin-bottom: 15px; margin-top: 15px; background-color: #383b67; color: #ffffff" type="button" id="invalidate" class="btn WorkSans-Bold">{{$voucher['button_text']}}</button>
					</center>
				@endif
				@endif

				</div>
				<br>
				<br>
				<br>
		@else
			<div class="col-md-4 col-md-offset-4">
				<h4 class="text-center" style="margin-top: 30px;">Voucher not found</h4>
			</div>
		@endif
	</div>
@stop

@section('page-script')
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>
	<script type="text/javascript">
		@php $month = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', "Juli", 'Agustus', 'September', 'Oktober', 'November', 'Desember']; @endphp

		// timer
		var deals_end   = "{{ strtotime($voucher['voucher_expired_at']) }}";
		var server_time = "{{ strtotime($voucher['deal_voucher']['deal']['time_server']) }}";
		var timer_text;
		var difference;

		if (server_time <= deals_end) {
			difference = deals_end - server_time;
			document.getElementById('timer').classList.add("text-center");
		}

		var display_flag = 0;
		this.interval = setInterval(() => {
			daysDifference = Math.floor(difference/60/60/24);
			if (daysDifference >= 0) {
				document.getElementById('day').classList.add("text-center");
				document.getElementById("day").innerHTML = " ";
				document.getElementById("day").innerHTML += " ";
				document.getElementById('day').innerHTML += daysDifference;
				document.getElementById("day").innerHTML += " Hari";
			}

			if(difference >= 0) {
				timer_text = timer(difference);
				document.getElementById("timer").innerHTML = " ";
				document.getElementById("timer").innerHTML += " ";
				document.getElementById('timer').innerHTML += timer_text;

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
				document.getElementById('timer');
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

			timer = hoursDifference + ":" + minutesDifference + ":" + secondsDifference;

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
				if ($("#textTP").is(":visible")) {
				    $('html, body').animate({scrollTop: ($(document).height()-$("#textTP").height())-250}, 'slow');
				} else {
				    $('html, body').animate({scrollTop: $(document).height()-220}, 'slow');
				}
			});
			$(document).on('click', '#hideSK', function() {
				$('#textSK').slideUp( "slow" );
				$( "#hideSK" ).replaceWith( '<div id="showSK" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Syarat & Ketentuan</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-down"></i></i></div></div>' );
			});
			$(document).on('click', '#showTP', function(e) {
				$('#textTP').slideDown( "slow" );
				$( "#showTP" ).replaceWith( '<div id="hideTP" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Tempat Penukaran</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-up"></i></div></div>' );
				$('html, body').animate({scrollTop: $(document).height()-160}, 'slow');
			});
			$(document).on('click', '#hideTP', function() {
				$('#textTP').slideUp( "slow" );
				$( "#hideTP" ).replaceWith( '<div id="showTP" style="background-color: rgb(248, 249, 251);" class="title-wrapper col-md-12 clearfix ProductSans-Bold"><div class="title col-left" style="font-size: 15px; color: rgb(102, 102, 102);">Tempat Penukaran</div><div class="title" style="font-size: 15px; color: rgb(102, 102, 102);"><i class="fas fa-chevron-down"></i></div></div>' );
				$('#showTP').css('margin-bottom','20px');
			});

            $(document).on('click', '.deals-qr', function(e) {
                e.preventDefault();
                $('#qr-code-modal').fadeIn('fast');
                $('.deals-detail').css({'height': '100vh', 'overflow-y':'scroll'});

                // send flag to native
                var url = window.location.href;
                var result = url.replace("#true", "");
                result = result.replace("#false", "");
                result = result.replace("#", "");

                window.location.href = result + '#true';
            });

            $(document).on('click', '#qr-code-modal', function() {
                $('#qr-code-modal').fadeOut('fast');
                $('.deals-detail').attr('style', '');

                // send flag to native
                var url = window.location.href;
                var result = url.replace("#true", "");
                result = result.replace("#false", "");
                result = result.replace("#", "");

                window.location.href = result + '#false';
            });

            $(document).on('click', '#invalidate', function() {
                // send flag to native

                var url_first = window.location.href;
                var result_first = url_first.replace("#yes", "");
                result_first = result_first.replace("#no", "");
                result_first = result_first.replace("#", "");

                window.location.href = result_first + '#yes';

                // if (url == window.location.href+'#false') {
                //     console.log('kosong atau false');
                //     var result = url.replace("#true", "");
                //     result = result.replace("#false", "");
                //     result = result.replace("#", "");

                //     window.location.href = result + '#true';
                // } else {
                //     console.log('true');
                //     var result = url.replace("#true", "");
                //     result = result.replace("#false", "");
                //     result = result.replace("#", "");

                //     window.location.href = result + '#false';
                // }

            });

        });
        // window.stop()
    </script>
@stop
