<head>
	<title>{{ $title }}</title>
	<meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />

    <script src="{{ env('S3_URL_VIEW') }}{{ ('assets/global/plugins/pace/pace.min.js') }}" type="text/javascript"></script>
    <link href="{{ env('S3_URL_VIEW') }}{{ ('assets/webview/css/pace-flash.css') }}" rel="stylesheet" type="text/css" />
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="{{ env('S3_URL_VIEW') }}{{ ('assets/global/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.2/css/all.min.css" rel="stylesheet" type="text/css" />
    <!-- END GLOBAL MANDATORY STYLES -->

    <!-- another css plugin -->
	@yield('page-style-plugin')

    <style type="text/css">
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
        @font-face {
                font-family: "Seravek";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('/fonts/Seravek.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Bold";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Bold.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-BoldItalic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-BoldItalic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Italic";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Italic.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Regular";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Regular.ttf') }}');
        }
        @font-face {
                font-family: "ProductSans-Medium";
                font-style: normal;
                font-weight: 400;
                src: url('{{ env('S3_URL_VIEW') }}{{ ('fonts/ProductSans-Medium.ttf') }}');
        }
        .Seravek{
            font-family: "Seravek";
        }
        .ProductSans{
            font-family: "ProductSans-Regular";
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
        body{
            cursor: pointer;
            background-color: #fff;
            color: #858585;
            font-family: {{env('FONT_FAMILY', "Seravek")}}, sans-serif !important;
        }
        .pace .pace-progress{
            top: 0;
        }
        .pace .pace-activity{
            top: 15px;
            border-radius: 10px !important;
        }
    </style>

    <!-- css internal -->
	@yield('css')

</head>