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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <style type="text/css">
        @font-face {
                font-family: "WorkSans-Black";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Black.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-ExtraBold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-ExtraBold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-ExtraLight";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-ExtraLight.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Light";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Light.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Medium.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Regular";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Regular.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-SemiBold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-SemiBold.ttf') }}');
        }
        @font-face {
                font-family: "WorkSans-Thin";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/Work_Sans/WorkSans-Thin.ttf') }}');
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
            -webkit-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
            -moz-box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
            box-shadow: 0px 0px 21px 0px rgba(168,168,168,1);
            border-radius: 3px;
            background: #fff;
            font-family: 'Open Sans', sans-serif;
        }

        .kelas-panah {
        	-webkit-text-stroke: 1px white;
            color: grey;
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

        div.div-panah {
		    position: relative;
		}

		div.panah {
			position: absolute;
		    top: 50%; left: 90%;
		    transform: translate(-50%,-50%);
		}


        .kotak-full {
            margin: 18.3px;
            margin-top: 16.7px;
            padding: 18.5px;
            background: #fff;
            font-family: 'Open Sans', sans-serif;
            box-shadow: 0 1pt 6.7px 0 rgba(0,0,0,0.08);
            font-size: 13px;
        }

        .kotak-full a:active, .kotak-full a:hover {
            text-decoration: none;
        }

        body {
            background: #f8f9fb;
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
            margin-top: 5px;
        }

        .space-left {
        	margin-left: 20px;
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

        .space-top {
        	padding-top: 5px;
        }

        .line-bottom {
            border-bottom: 0.3px solid #dbdbdb;
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

        .brownishGrey {
            color: rgb(102,102,102);
        }
        
        .sb3 {
            background: #ffffff;
            width: 90%;
            margin-bottom: 35px;
            padding: 25px;
            text-align: center;
            position: relative;
            box-shadow: #eeeeee 1.3px 2px 3.3px;
            border-radius: 5px;
        }

        .sb3:after {
            content: "";
            position: absolute;
            box-shadow: #eeeeee 1.3px 2px 3.3px;
            -moz-transform: rotate(45deg);
            -webkit-transform: rotate(45deg);
            bottom: -10px;
            left: 40px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent #FFF #FFF transparent;
        }

        .sb4 {
            background: #ffffff;
            width: 90%;
            margin-bottom: 35px;
            padding: 25px;
            text-align: center;
            position: relative;
            box-shadow: #eeeeee 1.3px 2px 3.3px;
            border-radius: 5px;
        }

        .sb4:after {
            content: "";
            position: absolute;
            box-shadow: #eeeeee 1.3px 2px 3.3px;
            -moz-transform: rotate(45deg);
            -webkit-transform: rotate(45deg);
            bottom: -10px;
            right: 40px;
            border-width: 10px;
            border-style: solid;
            border-color: transparent #FFF #FFF transparent;
        }
    </style>
  </head>
  <body>
    @foreach ($faq as $key => $value)
	    <div class="@if ($key % 2 == 0) sb3 @else sb4 @endif kotak-full text-left">
	        <div class="div-panah brownishGrey">
                <a href="#" data-toggle="collapse" data-target="#multi-collapse{{ $key }}" aria-expanded="false">
    	            <div class="row">
    	                <div class="col-12 WorkSans-Medium" style="color: #333333;">{{ $value['question'] }}</div>
    	            </div>
                </a>
            </div>
            <div class="collapse WorkSans-SemiBold" id="multi-collapse{{ $key }}" style="color: #383b67;padding-top: 16px">
            <hr style="margin-top: 0px;">
                <div class="row">
                    <div class="col-12">{{ $value['answer'] }}</div>
                </div>
            </div>
	    </div>

   @endforeach


    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.js"></script>
    <script type="text/javascript">
    	$(document).on('click', '.div-panah', function () {
    		var text = $(this).find('.kelas-panah').hasClass('add');
    		console.log(text);
    		if ($(this).find('.kelas-panah').hasClass('add')) {
    			$(this).find('.add').remove();
    			$(this).find('.panah').html('<i class="fa fa-angle-up kelas-panah remove"></i>');
    		} else {
    			$(this).find('.remove').remove();
    			$(this).find('.panah').html('<i class="fa fa-angle-down kelas-panah add"></i>');
    		}
    	});
    </script>

  </body>
</html>