<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="-1" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <style type="text/css">
         @font-face {
                font-family: "GoogleSans";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('STORAGE_URL_VIEW') }}{{ ('fonts/GoogleSans-Regular.ttf') }}');
        }
 		.GoogleSans{
            font-family: "GoogleSans";
        }

    	.kotak1 {
    		padding-top: 0px;
    		padding-bottom: 10px;
    		padding-left: 26.3px;
    		padding-right: 26.3px;
			background: #fff;
			font-family: 'Seravek', sans-serif;
    	}

    	.kotak2 {
    		padding-top: 10px;
    		padding-bottom: 10px;
    		padding-left: 26.3px;
    		padding-right: 26.3px;
			background: #fff;
			font-family: 'Seravek', sans-serif;
			height: 100%
    	}

    	.brown div {
    		color: #6c5648;
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

    	.space-sch {
    		padding-bottom: 5px;
    	}

    	.min-left {
    		margin-left: -15px;
    		margin-right: 10px;
    	}

    	.line-bottom {
    		border-bottom: 0.3px solid #dbdbdb;
    		margin-bottom: 5px;
		    padding-bottom: 10px;
    	}

    	.text-red {
    		color: #990003;
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

    	.text-grey-light {
    		color: #c4c4c4;
    	}

		.text-grey-2{
			color: #a9a9a9;
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

    	.text-16-7px {
    		font-size: 16.7px;
    	}
    	.text-23-3px {
    		font-size: 23.3px;
    	}
    	.text-14-3px {
    		font-size: 14.3px;
    	}

    	.text-14px {
    		font-size: 14px;
    	}

		.text-15px {
			font-size: 15px;
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

    	.logo-img {
    		width: 16.7px;
    		height: 16.7px;
			margin-top: -7px;
			margin-right: 5px;
    	}

      .text-bot {
        margin-left: -15px;
      }

    </style>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
	<script>
        $(document).ready(function(){
            var height = $('#row').height();
            $('#row').css('margin-top', 'calc((100vh - '+height+'px)/2)')
			console.log($('#row').css('margin-top'))
		})
	</script>

  </head>
  <body>
    {{ csrf_field() }}
  	<div class="kotak1" style="height:100vh">
   		<div class="row" id="row">
   			<div class="space-bottom" style="margin: auto;">
			   <img class="img-responsive" style="width: 80px;" src="{{ env('STORAGE_URL_VIEW') }}{{ ('img/webview/empty.png') }}">
			</div>
			<div class="col-12 text-23-3px text-red GoogleSans space-sch" style="margin: auto; text-align:center">
			Oops!
			</div>
			<div class="col-12 text-16-7px text-grey-light GoogleSans" style="margin: auto; text-align:center">
			Please refresh this page and try again
			</div>

   		</div>
  	</div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->

  </body>

</html>