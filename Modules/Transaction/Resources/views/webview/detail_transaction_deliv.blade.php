<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Questrial" rel="stylesheet">
    <link href="{{ env('STORAGE_URL_VIEW') }}{{('css/slide.css') }}" rel="stylesheet">
    <style type="text/css">
    	.kotak {
    		margin : 10px;
    		padding: 10px;
    		/*margin-right: 15px;*/
    		-webkit-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
			-moz-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
			box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
			border-radius: 3px;
			background: #fff;
			font-family: 'Open Sans', sans-serif;
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
    		padding-bottom: 15px;
    	}

        .space-top {
            padding-top: 15px;
        }

    	.space-text {
    		padding-bottom: 10px;
    	}

    	.line-bottom {
    		border-bottom: 1px solid #eee;
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
    		color: #666;
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

    	.open-sans-font {
    		font-family: 'Open Sans', sans-serif;
    	}

    	.questrial-font {
    		font-family: 'Questrial', sans-serif;
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
    </style>
  </head>
  <body>
    {{ csrf_field() }}
    @php
        // print_r($data);die();
    @endphp
  	<div class="kotak">
  		<div class="container line-bottom">
	   		<div class="row space-bottom">
	   			<div class="col-6 text-grey-black text-14-3px">{{ $data['outlet']['outlet_name'] }}</div>
	   			<div class="col-6 text-right text-medium-grey text-12-7px">{{ date('l', strtotime($data['transaction_date'])) }}, {{ date('d F Y H:i', strtotime($data['transaction_date'])) }}</div>
	   		</div>
	   		<div class="row space-text">
	   			<div class="col-4"></div>
	   			<div class="col-8 text-right bold text-12-7px">{{ $data['transaction_receipt_number'] }}</div>
	   		</div>
	   	</div>
	   	<div class="container">
	   		<div class="row">
	   			<div class="col-12 text-grey text-13-3px">YOUR TRANSACTION</div>
	   		</div><hr>
            @foreach ($data['product_transaction'] as $key => $pro)
    	   		<div class="row">
    	   			<div class="col-6 text-grey-white text-14px">{{ $pro['product']['product_name'] }}</div>
    	   			<div class="col-6 text-right text-grey-white">IDR {{ str_replace(',', '.', number_format($pro['transaction_product_subtotal'])) }}</div>
    	   			<div class="col-12 text-grey text-11-7px space-text">@if (isset($pro['transaction_product_note'])) ({{ $pro['transaction_product_note'] }}) @endif</div>
    	   		</div>
    	   		<div class="row space-bottom">
    	   			<div class="col-12 text-grey text-12-7px">{{ $pro['transaction_product_qty'] }} x IDR {{ str_replace(',', '.', number_format($pro['transaction_product_price'])) }}</div>
    	   		</div>
            @endforeach
            <hr>

            @foreach ($data['order_label_v2'] as $key => $label)
    	   		<div class="row space-text">
    	   			<div class="col-6 text-grey-white text-14px">{{ $label }}</div>
    	   			<div class="col-6 text-right @if($label == 'Discount') text-grey-red @else text-grey-white @endif text-14px">@if($label == 'Discount') - @endif IDR {{ str_replace(',', '.', number_format($data[$data['order_v2'][$key]])) }}</div>
    	   		</div>
            @endforeach
            <hr>
	   		<div class="row space-text">
	   			<div class="col-6 text-14px text-black">Total Price</div>
	   			<div class="col-6 text-right text-14px text-black">IDR {{ str_replace(',', '.', number_format($data['transaction_grandtotal'])) }}</div>
	   		</div>
            @if ($data['trasaction_payment_type'] == 'Balance')
    	   		<div class="row space-text">
    	   			<div class="col-6 text-14px text-black">Your Balance</div>
    	   			<div class="col-6 text-right text-14px text-grey-red">IDR {{ str_replace(',', '.', number_format($data['balance'])) }}</div>
    	   		</div>
    	   		<div class="row space-text">
    	   			<div class="col-6 bold text-14px text-black">Total Payment</div>
    	   			<div class="col-6 text-right bold text-14px text-black">IDR {{ str_replace(',', '.', number_format($data['transaction_grandtotal'] + $data['balance'])) }}</div>
    	   		</div>
            @endif
	   	</div>
  	</div>

    @if ($data['trasaction_type'] == 'Delivery')
      	<div class="kotak">
      		<div class="container">
    	   		<div class="row">
    	   			<div class="col-6 questrial-font text-14px text-black">Deliver to</div>
    	   		</div><hr>
    	   		<div class="row">
    	   			<div class="col-12 space-text text-13-3px text-black">{{ $data['detail']['destination_name'] }} <span class="text-grey text-11-7px">({{ $data['detail']['destination_phone'] }})</span></div>
    	   			<div class="col-12 space-text text-13-3px text-black">{!! $data['detail']['destination_address'] !!}</div>
                    @if (isset($data['detail']['destination_description']))
    	   			     <div class="col-12 text-13-3px text-black">Description : </div>
    	   			     <div class="col-12 text-13-3px text-black">{{ $data['detail']['destination_description'] }}</div>
                    @endif
    	   		</div><hr>
    	   		<div class="row space-bottom">
    	   			<div class="col-6 text-grey-white text-14px">Delivery Courier</div>
    	   			<div class="col-6 text-right text-black text-14px">{{ $data['detail']['shipment_courier'] }} ({{ $data['detail']['shipment_courier_service'] }})</div>
    	   		</div>
    	   	</div>
      	</div>
    @endif

    @if ($data['transaction_payment_status'] == 'Completed'|| $data['transaction_payment_status'] == 'Paid')
      	<div class="kotak">
      		<div class="container">
      			<div class="row">
    	   			<div class="col-6 questrial-font text-black text-14px">Payment Method</div>
    	   		</div><hr>
    	   		<div class="row space-bottom">
    	   			<div class="col-6 text-black text-12-7px">@if ($data['trasaction_payment_type'] == 'Balance') BALANCE @endif @if ($data['trasaction_payment_type'] == 'Midtrans') ONLINE PAYMENT @endif @if ($data['trasaction_payment_type'] == 'Manual') BANK TRANSFER @endif</div>
                    <div class="col-6 text-right text-black text-13-3px">IDR @if ($data['trasaction_payment_type'] == 'Balance') {{ str_replace(',', '.', number_format(abs($data['balance']))) }} @else {{ str_replace(',', '.', number_format(abs($data['transaction_grandtotal']))) }} @endif</div>
                    @if (isset($data['payment']['payment_type']))
    	   			     <div class="col-12 text-black text-11-7px">{{ $data['payment']['payment_type'] }}</div>
                    @endif

                    @if (isset($data['payment']['payment_method']))
                         <div class="col-12 text-black text-11-7px">{{ $data['payment']['payment_method'] }}</div>
                    @endif
    	   		</div>
    	   	</div>
      	</div>
    @endif

  	<div class="kotak">
  		<div class="container">
  			<div class="row">
	   			<div class="col-6 questrial-font text-black text-14px">Payment Status</div>
	   		</div><hr>
	   		<div class="row space-bottom">
	   			<div class="col-12 @if ($data['transaction_payment_status'] == 'Completed') text-grey-green @endif @if ($data['transaction_payment_status'] == 'Pending') text-grey-yellow @endif @if ($data['transaction_payment_status'] == 'Paid') text-grey-blue @endif @if ($data['transaction_payment_status'] == 'Cancelled') text-grey-red-cancel @endif text-12-7px">{{ $data['transaction_payment_status'] }}</div>
	   			<div class="col-12 text-much-grey text-11-7px">@if ($data['transaction_payment_status'] == 'Completed') Your payment completed @endif @if ($data['transaction_payment_status'] == 'Pending') Please pay your order to continue process order @endif @if ($data['transaction_payment_status'] == 'Paid') Please wait for admin confirmation @endif @if ($data['transaction_payment_status'] == 'Cancelled') Your payment cancelled @endif</div>
	   		</div>
	   	</div>
  	</div>



    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.js"></script>

  </body>
</html>