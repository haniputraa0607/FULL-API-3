<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Questrial" rel="stylesheet">
    <link href="{{ env('S3_URL_VIEW') }}{{('css/slide.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
    <style type="text/css">
        @font-face {
                font-family: "ProductSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/ProductSans-Regular.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-BoldItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/ProductSans-BoldItalic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Italic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/ProductSans-Italic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/ProductSans-Medium.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-MediumItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/ProductSans-MediumItalic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/ProductSans-Regular.ttf') }}');
        }
        .ProductSans{
            font-family: "ProductSans";
        }
        .ProductSans-MediumItalic{
            font-family: "ProductSans-MediumItalic";
        }
        .ProductSans-Medium{
            font-family: "ProductSans-Medium";
        }
        .ProductSans-Italic{
            font-family: "ProductSans-Italic";
        }
        .ProductSans-BoldItalic{
            font-family: "ProductSans-BoldItalic";
        }
        .ProductSans-Bold{
            font-family: "ProductSans-Bold";
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
        }

        .kotak-full {
            padding: 10px;
            padding-top: calc(33px);
            background: #fff;
        }

        .kotak-biasa {
            margin-left: 5px;
        }

        .kotak-inside {
        	padding-left: 25px;
        	padding-right: 25px
        }

        .brownishGrey {
            color: rgb(102,102,102);
        }

        body {
            background: #fafafa;
        }
    </style>
  </head>
  <body>

    <div class="kotak-full">
        <div class="container">
            <div class="row">
                <div class="col-12 ProductSans brownishGrey" style="font-size: 16px; line-height: 26px"><?php echo $value; ?></div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.js"></script>

  </body>
</html>