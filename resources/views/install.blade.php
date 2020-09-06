@extends("adminlte::auth.auth-page")

@section("auth_body")
@include("notifications")
<form action="{{route('installer.install')}}" method="POST">
    <div class="form-group">
        <label for="user_login">Имя пользователя</label>
        <input type="text" name="user_login"  class="form-control" />
    </div>
    <div class="form-group">
        <label for="user_email">Email пользователя</label>
        <input type="text" name="user_email"  class="form-control" />
    </div>
    <div class="form-group">
        <label for="user_password">Пароль</label>
        <input type="password" name="user_password"  class="form-control" />
    </div>
    <div class="form-group">
        <label for="user_password_confirm">Подтверждение пароля</label>
        <input type="password" name="user_password_confirm" class="form-control" />
    </div>
    @csrf
    <input type="submit" class="btn btn-primary" value="Сохранить" />
</form>
@endsection

