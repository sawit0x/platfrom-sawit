<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{$gs->title}}</title>
</head>

<body>

<form action="{{$data['url']}}" method="{{$data['method']}}" id="auto_submit">
    @foreach($data['info'] as $key=> $value)
        <input type="hidden" name="{{$key}}" value="{{$value}}"/>
    @endforeach
</form>
<script>
    document.getElementById("auto_submit").submit();
</script>
</body>

</html>

