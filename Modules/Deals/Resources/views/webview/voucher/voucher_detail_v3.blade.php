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
    		align-items: center;
    	}
    	.col-left{
    		flex: 70%;
    	}
    	.col-right{
    		flex: 30%;
    	}
    	.title-wrapper > div{
    		padding: 15px;
    	}
    	.title{
    		font-size: 18px;
    		color: #666666;
    		padding-top: 10px !important;
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
        .bg-grey{
            background-color: #cccccc;
        }
    	.fee{
			margin-top: 30px;
			font-size: 18px;
			color: #000;
    	}
    	.description-wrapper{
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
    	.outlet{
    	    font-size: 14.5px;
    	}
    	.outlet-city:not(:first-child){
    		margin-top: 10px;
    	}

    	.voucher{
    	    font-size: 16px;
    	    padding-bottom: 0 !important;
    	}
    	.font-red{
    	    color: #990003;
    	}

    	#invalidate {
            color:#fff;
            background-color: #990003;
            border: none;
            border-radius: 5px;
            margin-bottom: 70px;
            margin-top: 30px;
            width: 100%;
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
		@if(!empty($voucher))
			@php
				$voucher = $voucher[0];
			@endphp
			<div class="col-md-4 col-md-offset-4">
				<img class="deals-img center-block" src="{{ $voucher['deal_voucher']['deal']['url_deals_image'] }}" alt="">

				<div class="title-wrapper clearfix">
					<div class="col-left voucher font-red">
					   Kedaluwarsa {{ date('d M Y H:i') }} Kedaluwarsa {{ date('d M Y H:i', strtotime($voucher['voucher_expired_at'])) }}
					</div>
				</div>
				<div class="title-wrapper clearfix GoogleSans-Medium">
					<div class="title">
						{{ $voucher['deal_voucher']['deal']['deals_title'] }}
						@if($voucher['deal_voucher']['deal']['deals_second_title'] != null)
						<br>
						{{ $voucher['deal_voucher']['deal']['deals_second_title'] }}
						@endif
					</div>
				</div>

				<!-- Modal QR Code -->
                @if($voucher['redeemed_at'] != null && $voucher['used_at'] == null)
                <a id="qr-code-modal" href="#">
                    <div id="qr-code-modal-content">
                        <img class="img-responsive" src="{{ $voucher['voucher_hash'] }}">
                    </div>
                </a>

                <div class="description-wrapper">
                    <div class="subtitle2 text-center GoogleSans">Tunjukkan QR Code di bawah ke kasir</div>

                    <div class="deals-qr">
                        <img class="img-responsive" style="display: block; max-width: 100%;" src="{{ $voucher['voucher_hash'] }}">
                    </div>

                    <center class="kode-text">Kode Kupon</center>
                    <center class="voucher-code font-red GoogleSans-Medium">{{ $voucher['deal_voucher']['voucher_code'] }}</center>
                    <div class="line"></div>
                </div>
                @endif

                @if($voucher['deal_voucher']['deal']['deals_description'] != "")
				<div class="description-wrapper">
					<div class="description">{!!$voucher['deal_voucher']['deal']['deals_description'] !!}</div>
				</div>
                @endif

				<div class="outlet-wrapper">
					@if ($voucher['deal_voucher']['deal']['label_outlet'] == 'Some' || $voucher['redeemed_at'] != null)
						<div class="subtitle">Dapat digunakan di outlet:</div>
						<div class="outlet">
							@if($voucher['redeemed_at'] != null)
								@if(isset($voucher['outlet_name']))
								{{$voucher['outlet_name']}}
								@endif
							@else
								@foreach($voucher['deal_voucher']['deal']['outlet_by_city'] as $key => $outlet_city)
								@if(isset($outlet_city['city_name']))
								<div class="outlet-city">{{ $outlet_city['city_name'] }}</div>
								<ul class="nav">
									@foreach($outlet_city['outlet'] as $key => $outlet)
									<li>- {{ $outlet['outlet_name'] }}</li>
									@endforeach
								</ul>
								@endif
								@endforeach
							@endif
						</div>
					@else
						<div class="subtitle">TERSEDIA DI SEMUA OUTLET</div>
					@endif
				</div>

			</div>
		@else
			<div class="col-md-4 col-md-offset-4">
				<h4 class="text-center" style="margin-top: 30px;">Voucher not found</h4>
			</div>
		@endif
	</div>
@stop

@section('page-script')
    <script type="text/javascript" src="{{ env('STORAGE_URL_VIEW') }}{{('assets/global/plugins/jquery.min.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(document).ready(function() {

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

        });
        // window.stop()
    </script>
@stop
