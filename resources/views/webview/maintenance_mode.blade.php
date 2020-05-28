<?php
    use App\Lib\MyHelper;
    $title = "Deals Detail";
?>
@extends('webview.main')

@section('css')
    <link rel="stylesheet" href="{{ env('API_URL') }}css/referral.css">
    <link rel="stylesheet" href="{{ env('API_URL') }}css/fontawesome.css">
    <style>
        body {
            width: 100%;
        }
        .box {
            margin: 30px 22px;
            padding: 0px;
        }
        .list {
            padding-left: 15px;
        }
        .no-space-bottom {
            margin-bottom: 0px;
        }
        .text-black {
            color: #3d3935;
        }
        .text-apricot {
            color: #ff9d6e;
        }
        .text-13-3px {
            font-size: 13.3px;
        }
        .text-16-7px {
            font-size: 16.7px;
        }
        .text-21-7px {
            font-size: 21.7px;
        }
        .space-top-30 {
            margin-top: 30px;
        }
        .bg-img {
            width: 100%;
        }
        .code {
            top: 48%;
            font-size: 21.7px;
            width: 100%;
            text-align: center;
            position: absolute;
            left: 0;
        }
        .btn-custom {
            width: 100%;
            height: 43.3px;
            background-color: #8fd6bd;
            font-size: 16.7px;
            color: #10704E;
            box-shadow: 0px 0px 6.7px 0px #F5F5F5;
            border-radius: 6.7px;
        }

        .content{
            text-align: center;
            padding-right: 20%;
            padding-left: 20%;
            padding-top: 35%;
        }
    </style>
@stop

@section('content')
    <div class="content">
        <img src="{{ $image }}" width="100" height="100">
        <p>{{$message}}</p>
    </div>
@stop
