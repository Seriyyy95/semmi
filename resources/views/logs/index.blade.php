<table class="table table-striped">
    <thead class="thead-dark">
        <tr>
            <th>Дата</th>
            <th>Сообщение</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($logs as $record)
        <tr>
            <td>{{$record->created_at}}</td>
            <td>{{$record->message}}</td>
        </tr>
        @endforeach
    </tbody>
</table>
