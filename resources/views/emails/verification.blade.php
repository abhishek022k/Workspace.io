<html>
<p>
    Welcome {{$name}}, <br>
    Account creation for your account with email {{$email}} is almost complete. <br>
    Please click the link below to verify your account.
</p>
<a href="http://localhost:8000/auth/verify/{{$code}}">verification link</a>
<p>Thanks, <br>
    Workspace.io.</p>

</html>