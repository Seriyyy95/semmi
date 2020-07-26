<div class="row">
<div class="col-md-12">
    @if(isset($message) and strlen($message) > 0)
        <div class="alert alert-danger">{{$message}}</div>
    @endif

    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <div class="alert alert-danger">{{$error}}</div>
        @endforeach
    @endif

    @if (session()->has('success'))
        <div class="alert alert-success">
            @if(is_array(session()->get('success')))
                <ul>
                    @foreach (session()->get('success') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @else
                {{ session()->get('success') }}
            @endif
        </div>
    @endif

    @if (session()->has('fail'))
        <div class="alert alert-danger">
            @if(is_array(session()->get('fail')))
                <ul>
                    @foreach (session()->get('fail') as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @else
                {{ session()->get('fail') }}
            @endif
        </div>
    @endif
</div>
</div>