<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans|Questrial" rel="stylesheet">
  </head>
  <body>
  <br>
    <div class="offset-3 offset-md-4">
    <form action="{{url('user-delete/'.$phone)}}" method="POST">
        {{ csrf_field() }}
        <input type="hidden" name="phone" value="{{$phone}}">
        <button type="submit" class="btn btn-danger col-4 col-md-2">Yes</button>
        <button type="button" class="btn btn-default col-4 col-md-2">No</button>
    </form>
    </div>
  </body>
</html>