<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RebootPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\Register;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->phone,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
        ];
        return response($response, 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('Name', $request->phone)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Неверный пароль'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    public function rebootpassword(RebootPasswordRequest $request)
    {
        $user = User::where('name', '=', $request->phone)->first();
        if (!$user || !Hash::check($request->oldpassword, $user->password)) {
            return response([
                'message' => "Неверный старый пароль"
            ], 401);
        }
        $user->password = bcrypt($request->newspassword);
        $user->save();
        return response([
            'message' => 'Пароль был успешно заменён!'
        ], 201);
    }

    public function change(ChangeRequest $request)
    {

        $user = User::where('id', '=', $request->UserID)->first();
        if (!$user) {
            return response([
                'message' => 'Пользователь не был найден'
            ], 401);
        }
        $user->fio = $request->fio;
        $user->email = $request->email;
        $user->telephone = $request->telephone;
        $user->address = $request->address;
        $user->save();
        return response([
            'message' => 'Данные успешно были изменены',
            'data' => $request
        ]);
    }

    /* доделать */
    public function forgot()
    {
        Mail::to("saxah23332@gmail.com")->send(new Register('saxah23332@gmail.com')); // mail: saxah232@mail.ru
        return response('privet', 201);
    }
}

?>
